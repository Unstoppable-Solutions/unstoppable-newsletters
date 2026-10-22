# Unstoppable Newsletters

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/unstoppable-newsletters.svg)](https://wordpress.org/plugins/unstoppable-newsletters/)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/unstoppable-newsletters.svg)](https://wordpress.org/plugins/unstoppable-newsletters/)
[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2+-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)

A proper newsletter archive for your site — PDF uploads, year tabs, featured sections, downloads. Built for organizations that want to publish a monthly PDF without hand-editing an HTML page every time.

> **End users:** install from the WordPress Plugin Directory — [wordpress.org/plugins/unstoppable-newsletters](https://wordpress.org/plugins/unstoppable-newsletters/)

## Features

- **PDF upload + archive** — upload the PDF, the plugin builds the archive page
- **Year tabs** — automatic grouping by publication year
- **Featured sections** — pin the latest newsletter or a highlight at the top
- **Download tracking** — see which issues people are opening
- **Zero HTML editing** — no monthly "copy the layout from last month" ritual
- **Works with any theme** — the display is scoped and style-aware

## Who it's for

Churches, nonprofits, schools, HOAs, clubs, trade associations — anyone who publishes a monthly or quarterly PDF newsletter and currently has their communications volunteer wrestling with a WordPress page editor every issue.

## Development

### Release workflow

Releases are deployed to WordPress.org automatically by GitHub Actions:

1. Finish the feature work on `main`
2. Bump the version in `unstoppable-newsletters.php` (header + any constant), `readme.txt` (Stable tag), and add a Changelog entry
3. Commit and push `main`
4. On GitHub, create a new **Release** tagged `vX.Y.Z` — the deploy workflow fires and pushes to WordPress.org SVN

### CI

Every push and pull request runs the official WordPress Plugin Check against the codebase.

## License

GPL-2.0-or-later. See the `License` header in `unstoppable-newsletters.php`.

## Author

Developed and maintained by [Unstoppable Solutions](https://unstoppablesolutions.app).
