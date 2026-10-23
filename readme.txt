=== Unstoppable Newsletters ===
Contributors: paramedic192
Tags: newsletter, pdf, email newsletter, monthly newsletter, document library
Requires at least: 5.8
Tested up to: 7.1.3
Stable tag: 1.6.15
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A beautiful newsletter archive with year tabs, featured sections, and PDF downloads for organizations.

== Description ==

**Stop editing HTML every month.** Unstoppable Newsletters gives your organization a professional, beautiful newsletter display — without touching a single line of code.

Whether you're a church, school, nonprofit, or business that publishes newsletters monthly or quarterly, this plugin eliminates the hassle of manually updating your website. Simply upload your PDF through the WordPress admin, and your page updates automatically with filterable year tabs, gradient-styled cards, and a featured newsletter section to highlight your latest issue.

= Features =

* **Custom Post Type** — Manage newsletters directly in WordPress admin
* **Featured Newsletter** — Highlight your current issue with quotes, events, and taglines
* **Monthly or Quarterly** — Choose your publishing frequency (months or Q1/Q2/Q3/Q4)
* **Year Filtering** — Visitors can browse by year with smooth tab navigation
* **Beautiful Design** — Gradient headers, hover effects, and responsive layout
* **PDF Integration** — Upload PDFs directly or link to external URLs
* **Customizable Colors** — Match your organization's branding
* **Flexible Display** — Show all periods or only published newsletters (hide Coming Soon)
* **Shortcode** — Display anywhere with `[unstoppable_newsletters]`
* **Elementor Compatible** — Works with all major page builders
* **Accessibility Ready** — Keyboard navigation and ARIA labels
* **Analytics Tracking** — Automatic Google Analytics events for PDF downloads

= Pro Features (Coming Soon) =

* Email notifications when you publish a new issue
* Subscriber management with signup forms
* Email service integrations (Mailchimp, Constant Contact, etc.)
* Custom email templates
* Priority support

= Perfect For =

* Churches & Religious Organizations
* Schools & Universities
* Nonprofits & Associations
* HOAs & Community Groups
* Clubs & Membership Organizations
* Any business with a monthly newsletter

= How It Works =

1. Add newsletters via **Newsletters → Add New**
2. Select the month and year
3. Upload your PDF or paste a URL
4. Mark the current issue as "Featured" for prominent display
5. Place `[unstoppable_newsletters]` on any page

= Shortcodes =

* `[unstoppable_newsletters]` — Display your newsletters
* `[unstne_archive]` — Alternate shortcode (same functionality)

= Shortcode Parameters =

* `show_featured="yes/no"` — Show or hide the featured newsletter section
* `show_tabs="yes/no"` — Show or hide the year tabs
* `show_title="yes/no"` — Show or hide the title and subtitle
* `show_card_year="yes/no"` — Show year badge on each card (useful when hiding tabs)
* `years="5"` — Only show the last X years
* `year="2024"` — Show specific year(s): single year, range, or list

**Year parameter examples:**
* `year="2024"` — Single year
* `year="2022-2024"` — Range of years (inclusive)
* `year="2022,2024,2026"` — Specific years (comma-separated)

Example: `[unstoppable_newsletters show_title="no" show_tabs="no" show_card_year="yes" year="2024"]`

== Installation ==

1. Upload the `unstoppable-newsletters` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Newsletters → Settings** to configure your organization name and colors
4. Add newsletters via **Newsletters → Add New**
5. Place the shortcode `[unstoppable_newsletters]` on any page

== Frequently Asked Questions ==

= How do I make a newsletter "Featured"? =

Edit the newsletter and check the "Make this the featured newsletter" checkbox. Only one newsletter can be featured at a time — setting a new one as featured automatically removes the previous featured status.

= Can I add events to the featured section? =

Yes! When editing a newsletter marked as featured, you can add multiple events with titles and date/time details that display in the featured section.

= What if I don't have a newsletter for a specific month? =

The plugin automatically shows "Coming Soon" for months that don't have a newsletter entry or have an entry without a PDF URL.

= Can I customize the colors to match my branding? =

Yes! Go to **Newsletters → Settings** to set your primary color, secondary color, and accent color.

= Does this work with Elementor? =

Yes! Simply use the Shortcode widget in Elementor and add `[unstoppable_newsletters]`. Works with all major page builders.

= Where can I get support? =

Visit [https://unstoppablesolutions.app](https://unstoppablesolutions.app) for documentation and support.

== Screenshots ==

1. Newsletter display with year tabs
2. Featured newsletter section with mockup
3. Newsletter management in WordPress admin
4. Settings page for customization
5. Individual newsletter edit screen

== Changelog ==

= 1.6.15 =
* Added: Newsletter search functionality with live search
* Added: Email subscriber system with WordPress database storage
* Added: Newsletter Signup widget for sidebars and footers
* Added: Subscribe form option for newsletter archive
* Added: Admin page to view and manage subscribers
* Added: CSV export for subscriber list
* Added: show_search shortcode parameter
* Added: show_subscribe shortcode parameter

= 1.6.14 =
* Fixed contributors field

= 1.6.13 =
* Fixed: Transient name in flash notices now uses correct unstne prefix

= 1.6.12 =
* Updated "Tested up to" to WordPress 7.0

= 1.6.11 =
* Fixed wp_unslash() to be called directly on $_POST['unstne_events']

= 1.6.10 =
* Fixed all remaining HTML class names from 'una-' to 'unstne-' prefix
* Fixed PDF upload button, events repeater, and color picker functionality
* Fixed featured section and newsletter card class names

= 1.6.9 =
* Fixed inline style to use wp_add_inline_style() per WordPress standards
* Added wp_kses_post() escaping to shortcode output after filter
* Fixed admin notices class prefix from 'una' to 'unstne'
* Renamed settings page slug from 'una-settings' to 'unstne-settings'
* Renamed shortcode from 'newsletter_archive' to 'unstne_archive'
* Fixed all style/script handles to use 'unstne-' prefix
* Fixed all CSS variables to use '--unstne-' prefix

= 1.6.8 =
* Fixed UNSTNE_OPTION_PREFIX value (was still 'una', now 'unstne')
* Renamed main class to UNSTNE_Plugin
* Renamed una() function to unstne()
* Added phpcs ignore comments for validated input

= 1.6.7 =
* Changed prefix from `una_` to `unstne_` (4+ characters) per WordPress.org guidelines
* Updated contributors list
* Full compliance with WordPress.org Plugin Directory Guidelines

= 1.6.6 =
* Improved sanitization for WordPress.org Plugin Check compliance
* Shortened plugin short description
* Updated "Tested up to" to WordPress 6.9

= 1.6.5 =
* NEW: `show_card_year="yes"` shortcode parameter
* Shows year badge on each newsletter card header
* Useful when hiding year tabs but still need to show what year newsletters are from

= 1.6.4 =
* Fixed: Pro plugin hook timing — Pro features now load correctly
* Moved `una_loaded` action to `plugins_loaded` for proper Pro integration

= 1.6.3 =
* Enhanced `year` parameter to support:
  * Single year: `year="2024"`
  * Year range: `year="2022-2024"` (inclusive)
  * Multiple years: `year="2022,2024,2026"` (comma-separated)

= 1.6.2 =
* Added `show_title="yes/no"` shortcode parameter to hide title and subtitle
* Useful when using your own headings via Elementor, theme, or page builder

= 1.6.1 =
* Added hooks for Pro plugin extensibility:
  * `una_settings_after_colors` - For Pro color preview
  * `una_settings_after_shortcode` - For Pro shortcode options
  * `una_shortcode_output` - Filter for Pro layout modifications

= 1.6.0 =
* Added shortcode parameters for display control:
  * `show_featured="yes/no"` — Show or hide featured section
  * `show_tabs="yes/no"` — Show or hide year tabs
  * `years="5"` — Limit to last X years
  * `year="2024"` — Show specific year only
* Settings page now displays shortcode options with examples
* When tabs are hidden, all years display expanded

= 1.5.4 =
* Fixed: Year tab hover text color now visible
* Added hover effect for active year tab (darkens on hover)
* Improved year tab hover state with subtle background change

= 1.5.3 =
* Admin list column now shows "Quarter/Year" when in quarterly mode (was always "Month/Year")
* Column content displays quarter (Q1, Q2, etc.) instead of month when in quarterly mode

= 1.5.2 =
* Expanded year dropdown to go back 50 years (previously only 10 years)
* Added next year option for scheduling future newsletters

= 1.5.1 =
* Fixed: Newsletter title auto-generation now respects quarterly mode
* Fixed: Switching from monthly to quarterly no longer reverts title to month-based name
* Titles now auto-generate as "Q1 2025 Newsletter" when in quarterly mode

= 1.5.0 =
* Added optional "Web Version URL" field for linking to online newsletters
* Support for Mailchimp, Constant Contact, or any web-based newsletter
* Smart button display: "Read Online" + "Download PDF" when both available
* Newsletters with only web URL show "Read Online" button
* Newsletters with only PDF show "Read Now" + "Download PDF" buttons
* Coming Soon status now checks for either PDF or Web URL

= 1.4.0 =
* Added "Display Order" setting to control newsletter arrangement
* Monthly mode: Choose between Jan → Dec or Dec → Jan
* Quarterly mode: Choose between Q1 → Q4 or Q4 → Q1
* Settings page now shows context-aware options based on frequency selection

= 1.3.1 =
* Fixed featured newsletter section not displaying quarter (Q1, Q2, etc.) in quarterly mode
* Featured section now correctly shows "Q1 2025" instead of "January 2025" when using quarterly frequency

= 1.3.0 =
* Added quarterly frequency option (Q1, Q2, Q3, Q4) for organizations that don't publish monthly
* Added "Hide Coming Soon" setting to only display published newsletters
* New Display Options section in settings
* Quarterly newsletters display in a clean 4-column grid

= 1.2.0 =
* Renamed plugin from "Unstoppable Newsletter Archive" to "Unstoppable Newsletters"
* Updated primary shortcode to `[unstoppable_newsletters]` (alternate `[unstne_archive]` also available)
* Updated all branding and text references
* Improved settings page layout

= 1.1.0 =
* Converted to free WordPress.org version
* Removed license system (not needed for free plugins)
* Added Pro version upsell sidebar on settings page
* Added action hooks for Pro version extensibility
* Added admin menu gold branding

= 1.0.0 =
* Initial release
* Custom post type for newsletters
* Featured newsletter section with mockup
* Year-based filtering with tabs
* Settings page for branding customization
* PDF upload integration via WordPress media library
* Events repeater for featured newsletter
* Responsive design for all devices
* Accessibility improvements (keyboard navigation, ARIA labels)
* Google Analytics event tracking for PDF downloads

== Upgrade Notice ==

= 1.3.0 =
New quarterly frequency option and ability to hide "Coming Soon" placeholders. Perfect for organizations that don't publish monthly.

= 1.2.0 =
Plugin renamed to "Unstoppable Newsletters" — all existing settings and newsletters are preserved.

= 1.1.0 =
Now available as a free plugin! Pro features coming soon.

= 1.0.0 =
Initial release.
