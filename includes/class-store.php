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
}
