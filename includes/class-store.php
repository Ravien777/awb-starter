<?php

/**
 * Pattern Store configuration.
 *
 * The Store is powered by a remote manifest.json describing installable
 * pattern packages. Both the manifest URL and the hosts patterns may be
 * downloaded from are configurable — defaults derive from the manifest URL
 * itself, and can be extended via the awb_store_allowed_hosts filter.
 *
 * @package AWBStarter
 */
if (! defined('ABSPATH')) {
	exit;
}

class AWB_Store
{
	public const OPTION_MANIFEST_URL = 'awb_store_manifest_url';

	/**
	 * Configured manifest URL (empty string when the Store is not set up).
	 */
	public static function get_manifest_url(): string
	{
		$url = (string) get_option(self::OPTION_MANIFEST_URL, '');
		return '' === $url ? '' : esc_url_raw($url);
	}

	/**
	 * Hosts remote pattern zips may be downloaded from.
	 *
	 * Always includes the manifest URL's own host so a self-hosted store
	 * works out of the box; extend via the awb_store_allowed_hosts filter
	 * when downloads are served from a CDN or separate bucket domain.
	 *
	 * @return array<int, string> Lowercase hostnames.
	 */
	public static function get_allowed_hosts(): array
	{
		$hosts = [];

		$manifest_host = parse_url(self::get_manifest_url(), PHP_URL_HOST);
		if (is_string($manifest_host) && '' !== $manifest_host) {
			$hosts[] = strtolower($manifest_host);
		}

		/**
		 * Filter the allowlist of hosts pattern zips may be installed from.
		 *
		 * @param array<int, string> $hosts Lowercase hostnames derived from the manifest URL.
		 */
		$hosts = apply_filters('awb_store_allowed_hosts', $hosts);

		return array_values(array_unique(array_map('strtolower', array_filter($hosts, 'is_string'))));
	}

	/**
	 * Whether a URL's host may be used as an install source.
	 */
	public static function is_allowed_download(string $url): bool
	{
		$host = strtolower((string) parse_url($url, PHP_URL_HOST));
		return '' !== $host && in_array($host, self::get_allowed_hosts(), true);
	}

	/**
	 * Fetch the store manifest and return sanitized pattern data.
	 *
	 * @return array|WP_Error Array of patterns on success, WP_Error on failure.
	 */
	public static function fetch_manifest(): array|\WP_Error
	{
		$url = self::get_manifest_url();
		if ('' === $url) {
			return new \WP_Error('not_configured', __('No Store manifest URL is configured yet.', 'awb-starter'));
		}
		if (! self::is_allowed_download($url)) {
			return new \WP_Error('host_not_allowed', __('The configured manifest URL points to a host that is not allowed.', 'awb-starter'));
		}
		$response = wp_remote_get($url, ['timeout' => 15]);
		if (is_wp_error($response)) {
			return new \WP_Error('store_unreachable', __('Could not reach the pattern store: ', 'awb-starter') . $response->get_error_message());
		}
		$code = (int) wp_remote_retrieve_response_code($response);
		if ($code < 200 || $code >= 300) {
			return new \WP_Error('store_http_error', sprintf(__('Pattern store returned HTTP %d.', 'awb-starter'), $code));
		}
		$body  = json_decode(wp_remote_retrieve_body($response), true);
		$items = is_array($body['patterns'] ?? null) ? $body['patterns'] : [];
		$patterns = [];
		foreach ($items as $item) {
			if (! is_array($item)) {
				continue;
			}
			$download = esc_url_raw((string) ($item['download_url'] ?? ''));
			if ('' === $download) {
				continue;
			}
			$patterns[] = [
				'title'        => sanitize_text_field((string) ($item['title'] ?? '')),
				'description'  => sanitize_text_field((string) ($item['description'] ?? '')),
				'version'      => sanitize_text_field((string) ($item['version'] ?? '')),
				'author'       => sanitize_text_field((string) ($item['author'] ?? '')),
				'thumbnail'    => esc_url_raw((string) ($item['thumbnail'] ?? '')),
				'download_url' => $download,
			];
		}
		return $patterns;
	}

	/**
	 * Install a pattern from a remote URL.
	 *
	 * @param string $url The download URL.
	 * @return array|WP_Error Pattern data on success, WP_Error on failure.
	 */
	public static function install(string $url): array|\WP_Error
	{
		$url = esc_url_raw($url);
		if ('' === $url) {
			return new \WP_Error('no_url', __('No URL provided.', 'awb-starter'));
		}
		if (! self::is_allowed_download($url)) {
			return new \WP_Error('host_not_allowed', __('Patterns can only be installed from hosts configured in the Store settings.', 'awb-starter'));
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$tmp = download_url($url, 30);
		if (is_wp_error($tmp)) {
			return new \WP_Error('download_failed', $tmp->get_error_message());
		}
		$result = AWB_Pattern_Importer::install_from_zip($tmp, false);
		@unlink($tmp);
		if (! empty($result['success'])) {
			return $result['data'];
		}
		$error_data = ['message' => $result['error'] ?? __('Installation failed.', 'awb-starter')];
		if (isset($result['collision'])) {
			$error_data['code']  = 'collision';
			$error_data['title'] = $result['title'];
			$error_data['slug']  = $result['slug'];
			$error_data['files'] = $result['files'];
		}
		return new \WP_Error('install_failed', $result['error'] ?? __('Installation failed.', 'awb-starter'), $error_data);
	}
}
