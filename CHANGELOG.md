# Changelog

All notable changes to Unstoppable Newsletters are documented here.
This changelog follows [Keep a Changelog](https://keepachangelog.com) format.

## [Unreleased]

_No unreleased changes._

## [1.6.15] - 2026-09-07

### Added
- Newsletter search functionality with live search
- Email subscriber system with WordPress database storage
- Newsletter Signup widget for sidebars and footers
- Subscribe form option for newsletter archive
- Admin page to view and manage subscribers
- CSV export for subscriber list
- `show_search` shortcode parameter
- `show_subscribe` shortcode parameter

## [1.6.14] - 2026-09-01

### Fixed
- Contributors field in readme.txt

## [1.6.13] - 2026-08-28

### Fixed
- Transient name in flash notices now uses correct `unstne` prefix

## [1.6.12] - 2026-08-25

### Changed
- Updated "Tested up to" to WordPress 7.0

## [1.6.11] - 2026-08-20

### Fixed
- `wp_unslash()` called directly on `$_POST['unstne_events']`

## [1.6.10] - 2026-08-15

### Fixed
- All remaining HTML class names from `una-` to `unstne-` prefix
- PDF upload button, events repeater, and color picker functionality
- Featured section and newsletter card class names

## [1.6.9] - 2026-08-10

### Fixed
- Inline style to use `wp_add_inline_style()` per WordPress standards
- Added `wp_kses_post()` escaping to shortcode output after filter
- Admin notices class prefix from `una` to `unstne`
- Renamed settings page slug from `una-settings` to `unstne-settings`
- Renamed shortcode from `newsletter_archive` to `unstne_archive`
- All style/script handles to use `unstne-` prefix
- All CSS variables to use `--unstne-` prefix

## [1.6.8] - 2026-08-05

### Fixed
- `UNSTNE_OPTION_PREFIX` value (was still `una`, now `unstne`)
- Renamed main class to `UNSTNE_Plugin`
- Renamed `una()` function to `unstne()`
- Added phpcs ignore comments for validated input

## [1.6.7] - 2026-08-01

### Changed
- Changed prefix from `una_` to `unstne_` (4+ characters) per WordPress.org guidelines
- Updated contributors list
- Full compliance with WordPress.org Plugin Directory Guidelines

## [1.6.6] - 2026-07-28

### Changed
- Improved sanitization for WordPress.org Plugin Check compliance
- Shortened plugin short description
- Updated "Tested up to" to WordPress 6.9

## [1.6.5] - 2026-07-20

### Added
- `show_card_year="yes"` shortcode parameter
- Shows year badge on each newsletter card header
- Useful when hiding year tabs but still need to show what year newsletters are from

## [1.6.4] - 2026-07-15

### Fixed
- Pro plugin hook timing — Pro features now load correctly
- Moved `una_loaded` action to `plugins_loaded` for proper Pro integration

## [1.6.3] - 2026-07-10

### Added
- Enhanced `year` parameter to support:
  - Single year: `year="2024"`
  - Year range: `year="2022-2024"` (inclusive)
  - Multiple years: `year="2022,2024,2026"` (comma-separated)

## [1.6.2] - 2026-07-05

### Added
- `show_title="yes/no"` shortcode parameter to hide title and subtitle
- Useful when using your own headings via Elementor, theme, or page builder

## [1.6.1] - 2026-07-01

### Added
- Hooks for Pro plugin extensibility:
  - `una_settings_after_colors` - For Pro color preview
  - `una_settings_after_shortcode` - For Pro shortcode options
  - `una_shortcode_output` - Filter for Pro layout modifications

## [1.6.0] - 2026-06-25

### Added
- Shortcode parameters for display control:
  - `show_featured="yes/no"` — Show or hide featured section
  - `show_tabs="yes/no"` — Show or hide year tabs
  - `years="5"` — Limit to last X years
  - `year="2024"` — Show specific year only
- Settings page now displays shortcode options with examples
- When tabs are hidden, all years display expanded

## [1.5.4] - 2026-06-20

### Fixed
- Year tab hover text color now visible
- Added hover effect for active year tab (darkens on hover)
- Improved year tab hover state with subtle background change

## [1.5.3] - 2026-06-15

### Fixed
- Admin list column now shows "Quarter/Year" when in quarterly mode

## [1.5.2] - 2026-06-10

### Changed
- Expanded year dropdown to go back 50 years (previously only 10 years)
- Added next year option for scheduling future newsletters

## [1.5.1] - 2026-06-05

### Fixed
- Newsletter title auto-generation now respects quarterly mode
- Switching from monthly to quarterly no longer reverts title to month-based name

## [1.5.0] - 2026-06-01

### Added
- Optional "Web Version URL" field for linking to online newsletters
- Support for Mailchimp, Constant Contact, or any web-based newsletter
- Smart button display: "Read Online" + "Download PDF" when both available

## [1.4.0] - 2026-05-25

### Added
- "Display Order" setting to control newsletter arrangement
- Monthly mode: Choose between Jan → Dec or Dec → Jan
- Quarterly mode: Choose between Q1 → Q4 or Q4 → Q1

## [1.3.1] - 2026-05-20

### Fixed
- Featured newsletter section not displaying quarter in quarterly mode

## [1.3.0] - 2026-05-15

### Added
- Quarterly frequency option (Q1, Q2, Q3, Q4) for organizations that don't publish monthly
- "Hide Coming Soon" setting to only display published newsletters
- New Display Options section in settings

## [1.2.0] - 2026-05-10

### Changed
- Renamed plugin from "Unstoppable Newsletter Archive" to "Unstoppable Newsletters"
- Updated primary shortcode to `[unstoppable_newsletters]`
- Updated all branding and text references

## [1.1.0] - 2026-05-05

### Changed
- Converted to free WordPress.org version
- Removed license system (not needed for free plugins)

### Added
- Pro version upsell sidebar on settings page
- Action hooks for Pro version extensibility
- Admin menu gold branding

## [1.0.0] - 2026-05-01

### Added
- Initial release
- Custom post type for newsletters
- Featured newsletter section with mockup
- Year-based filtering with tabs
- Settings page for branding customization
- PDF upload integration via WordPress media library
- Events repeater for featured newsletter
- Responsive design for all devices
- Accessibility improvements (keyboard navigation, ARIA labels)
- Google Analytics event tracking for PDF downloads
