# AWB Starter

A WordPress plugin starter template for building custom WordPress solutions.

## Description

AWB Starter is a foundational WordPress plugin that provides a structured starting point for WordPress development projects. It includes best practices for plugin organization, configuration, and extensibility.

## Features

- Clean plugin structure and organization
- WordPress coding standards compliance
- Easy configuration and customization
- Modular architecture for scalability

## Installation

1. Download or clone this plugin into `/wp-content/plugins/awb-starter/`
2. Activate the plugin via WordPress admin dashboard
3. Configure plugin settings as needed

## Automatic Updates

The plugin checks GitHub releases for updates via the bundled
[Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker)
library (`lib/plugin-update-checker/`). No extra plugins or update servers are
required on live sites.

For a site to receive an update, the matching GitHub release must:

- Use a `vX.Y.Z` tag (e.g. `v2.2.4`)
- Include a zip asset built by `bin/build-release.sh`, whose top-level folder
  is `awb-starter/` (GitHub's auto-generated source archive will NOT work)

For private repositories, define `AWB_GITHUB_TOKEN` in `wp-config.php`.

### Releasing a new version

1. Bump the version in **both** places in `awb-starter.php`: the
   `Version:` header and the `AWB_VERSION` constant.
2. Commit the change.
3. Build the package: `bash bin/build-release.sh`
   (or pass an explicit version: `bash bin/build-release.sh 2.2.4`)
4. Tag and push:
   ```
   git tag vX.Y.Z && git push origin vX.Y.Z
   ```
5. Create a GitHub release for tag `vX.Y.Z` and attach
   `dist/awb-starter-X.Y.Z.zip` as a release asset.

Live sites will pick up the new version on their next update check (default
every 12 hours, or immediately from Dashboard → Updates → Check again).

## Usage

1. Customize the plugin functions in the main plugin files
2. Add your custom functionality following WordPress standards
3. Use hooks and filters for extensibility

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher

## Contributing

Contributions are welcome. Please ensure code follows WordPress coding standards.

## License

This project is licensed under the GPL v2 or later.

## Support

For issues or questions, please open an issue in the project repository.