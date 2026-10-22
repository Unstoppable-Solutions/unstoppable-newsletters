<?php
/**
 * Plugin Name: Unstoppable Newsletters
 * Plugin URI: https://unstoppablesolutions.app/plugins/unstoppable-newsletters/
 * Description: A beautiful, filterable newsletter library with year tabs, featured sections, and PDF downloads. Perfect for any organization that publishes monthly newsletters.
 * Version: 1.6.15
 * Author: Unstoppable Solutions
 * Author URI: https://unstoppablesolutions.app
 * Text Domain: unstoppable-newsletters
 * Requires at least: 5.8
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Unstoppable_Newsletters
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants.
define( 'UNSTNE_VERSION', '1.6.15' );
define( 'UNSTNE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'UNSTNE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'UNSTNE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'UNSTNE_PLUGIN_SLUG', 'unstoppable-newsletters' );
define( 'UNSTNE_OPTION_PREFIX', 'unstne' );

/**
 * Main Unstoppable Newsletters Class.
 */
final class UNSTNE_Plugin {

    /**
     * Single instance of the class.
     *
     * @var UNSTNE_Plugin
     */
    private static $instance = null;

    /**
     * Get single instance of the class.
     *
     * @return UNSTNE_Plugin
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files.
     */
    private function includes() {
        require_once UNSTNE_PLUGIN_DIR . 'includes/class-unstne-admin-notices.php';
        require_once UNSTNE_PLUGIN_DIR . 'includes/class-unstne-subscribers.php';
        require_once UNSTNE_PLUGIN_DIR . 'includes/class-unstne-widget.php';
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        // Core hooks.
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_unstne_newsletter', array( $this, 'save_meta_box' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_menu_branding' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
        add_action( 'manage_unstne_newsletter_posts_columns', array( $this, 'custom_columns' ) );
        add_action( 'manage_unstne_newsletter_posts_custom_column', array( $this, 'custom_column_content' ), 10, 2 );

        // Shortcode.
        add_shortcode( 'unstne_archive', array( $this, 'render_shortcode' ) );
        add_shortcode( 'unstoppable_newsletters', array( $this, 'render_shortcode' ) );

        // Activation/Deactivation.
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        // Initialize admin notices.
        new UNSTNE_Admin_Notices();

        // Initialize subscribers.
        new UNSTNE_Subscribers();

        // AJAX handler for newsletter search.
        add_action( 'wp_ajax_unstne_search_newsletters', array( $this, 'ajax_search_newsletters' ) );
        add_action( 'wp_ajax_nopriv_unstne_search_newsletters', array( $this, 'ajax_search_newsletters' ) );

        // Fire unstne_loaded on plugins_loaded so Pro can hook in.
        add_action( 'plugins_loaded', array( $this, 'fire_loaded_action' ), 20 );
    }

    /**
     * Fire the unstne_loaded action.
     * Called on plugins_loaded to allow Pro plugin to hook in.
     */
    public function fire_loaded_action() {
        /**
         * Action hook for Pro version to add additional functionality.
         *
         * @since 1.1.0
         */
        do_action( 'unstne_loaded', $this );
    }

    /**
     * Plugin activation.
     */
    public function activate() {
        $this->register_post_type();
        flush_rewrite_rules();

        // Create subscribers table.
        UNSTNE_Subscribers::create_table();

        // Set install date for review notice.
        if ( ! get_option( UNSTNE_OPTION_PREFIX . '_install_date' ) ) {
            update_option( UNSTNE_OPTION_PREFIX . '_install_date', time() );
        }

        // Set default settings.
        $defaults = array(
            'organization_name'  => get_bloginfo( 'name' ),
            'newsletter_title'   => __( 'Newsletters', 'unstoppable-newsletters' ),
            'subtitle'           => __( 'Stay connected with our updates and news', 'unstoppable-newsletters' ),
            'primary_color'      => '#2271b1',
            'secondary_color'    => '#135e96',
            'accent_color'       => '#f0b849',
            'frequency'          => 'monthly',
            'display_order'      => 'asc',
            'hide_coming_soon'   => 'no',
        );

        if ( ! get_option( UNSTNE_OPTION_PREFIX . '_settings' ) ) {
            update_option( UNSTNE_OPTION_PREFIX . '_settings', $defaults );
        }
    }

    /**
     * Plugin deactivation.
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Register custom post type.
     */
    public function register_post_type() {
        $labels = array(
            'name'               => __( 'Newsletters', 'unstoppable-newsletters' ),
            'singular_name'      => __( 'Newsletter', 'unstoppable-newsletters' ),
            'add_new'            => __( 'Add New', 'unstoppable-newsletters' ),
            'add_new_item'       => __( 'Add New Newsletter', 'unstoppable-newsletters' ),
            'edit_item'          => __( 'Edit Newsletter', 'unstoppable-newsletters' ),
            'new_item'           => __( 'New Newsletter', 'unstoppable-newsletters' ),
            'view_item'          => __( 'View Newsletter', 'unstoppable-newsletters' ),
            'search_items'       => __( 'Search Newsletters', 'unstoppable-newsletters' ),
            'not_found'          => __( 'No newsletters found', 'unstoppable-newsletters' ),
            'not_found_in_trash' => __( 'No newsletters found in trash', 'unstoppable-newsletters' ),
            'menu_name'          => __( 'Newsletters', 'unstoppable-newsletters' ),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 25,
            'menu_icon'           => 'dashicons-email-alt',
            'supports'            => array( 'title' ),
            'has_archive'         => false,
            'exclude_from_search' => true,
            'capability_type'     => 'post',
        );

        register_post_type( 'unstne_newsletter', $args );
    }

    /**
     * Add meta boxes.
     */
    public function add_meta_boxes() {
        add_meta_box(
            'unstne_newsletter_details',
            __( 'Newsletter Details', 'unstoppable-newsletters' ),
            array( $this, 'render_meta_box' ),
            'unstne_newsletter',
            'normal',
            'high'
        );

        add_meta_box(
            'unstne_featured_details',
            __( 'Featured Newsletter Settings', 'unstoppable-newsletters' ),
            array( $this, 'render_featured_meta_box' ),
            'unstne_newsletter',
            'normal',
            'default'
        );
    }

    /**
     * Render main meta box.
     *
     * @param WP_Post $post Current post object.
     */
    public function render_meta_box( $post ) {
        wp_nonce_field( 'unstne_save_newsletter', 'unstne_newsletter_nonce' );

        $settings    = get_option( UNSTNE_OPTION_PREFIX . '_settings', array() );
        $frequency   = $settings['frequency'] ?? 'monthly';
        $month       = get_post_meta( $post->ID, '_unstne_month', true );
        $quarter     = get_post_meta( $post->ID, '_unstne_quarter', true );
        $year        = get_post_meta( $post->ID, '_unstne_year', true );
        $pdf_url     = get_post_meta( $post->ID, '_unstne_pdf_url', true );
        $web_url     = get_post_meta( $post->ID, '_unstne_web_url', true );
        $description = get_post_meta( $post->ID, '_unstne_description', true );
        $featured    = get_post_meta( $post->ID, '_unstne_featured', true );

        $months = array(
            '01' => __( 'January', 'unstoppable-newsletters' ),
            '02' => __( 'February', 'unstoppable-newsletters' ),
            '03' => __( 'March', 'unstoppable-newsletters' ),
            '04' => __( 'April', 'unstoppable-newsletters' ),
            '05' => __( 'May', 'unstoppable-newsletters' ),
            '06' => __( 'June', 'unstoppable-newsletters' ),
            '07' => __( 'July', 'unstoppable-newsletters' ),
            '08' => __( 'August', 'unstoppable-newsletters' ),
            '09' => __( 'September', 'unstoppable-newsletters' ),
            '10' => __( 'October', 'unstoppable-newsletters' ),
            '11' => __( 'November', 'unstoppable-newsletters' ),
            '12' => __( 'December', 'unstoppable-newsletters' ),
        );

        $quarters = array(
            'Q1' => __( 'Q1 (Jan - Mar)', 'unstoppable-newsletters' ),
            'Q2' => __( 'Q2 (Apr - Jun)', 'unstoppable-newsletters' ),
            'Q3' => __( 'Q3 (Jul - Sep)', 'unstoppable-newsletters' ),
            'Q4' => __( 'Q4 (Oct - Dec)', 'unstoppable-newsletters' ),
        );

        $current_year = (int) gmdate( 'Y' );
        $years        = range( $current_year + 1, $current_year - 50 );
        ?>
        <table class="form-table">
            <?php if ( 'monthly' === $frequency ) : ?>
            <tr>
                <th scope="row">
                    <label for="unstne_month"><?php esc_html_e( 'Month', 'unstoppable-newsletters' ); ?></label>
                </th>
                <td>
                    <select name="unstne_month" id="unstne_month" class="regular-text">
                        <option value=""><?php esc_html_e( 'Select Month', 'unstoppable-newsletters' ); ?></option>
                        <?php foreach ( $months as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $month, $value ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php else : ?>
            <tr>
                <th scope="row">
                    <label for="unstne_quarter"><?php esc_html_e( 'Quarter', 'unstoppable-newsletters' ); ?></label>
                </th>
                <td>
                    <select name="unstne_quarter" id="unstne_quarter" class="regular-text">
                        <option value=""><?php esc_html_e( 'Select Quarter', 'unstoppable-newsletters' ); ?></option>
                        <?php foreach ( $quarters as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $quarter, $value ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <th scope="row">
                    <label for="unstne_year"><?php esc_html_e( 'Year', 'unstoppable-newsletters' ); ?></label>
                </th>
                <td>
                    <select name="unstne_year" id="unstne_year" class="regular-text">
                        <option value=""><?php esc_html_e( 'Select Year', 'unstoppable-newsletters' ); ?></option>
                        <?php foreach ( $years as $y ) : ?>
                            <option value="<?php echo esc_attr( $y ); ?>" <?php selected( $year, $y ); ?>>
                                <?php echo esc_html( $y ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="unstne_pdf_url"><?php esc_html_e( 'PDF File', 'unstoppable-newsletters' ); ?></label>
                </th>
                <td>
                    <input type="text" name="unstne_pdf_url" id="unstne_pdf_url" class="large-text" value="<?php echo esc_url( $pdf_url ); ?>">
                    <button type="button" class="button unstne-upload-pdf"><?php esc_html_e( 'Upload PDF', 'unstoppable-newsletters' ); ?></button>
                    <p class="description"><?php esc_html_e( 'Upload a PDF or enter a URL to an existing PDF.', 'unstoppable-newsletters' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="unstne_web_url"><?php esc_html_e( 'Web Version URL', 'unstoppable-newsletters' ); ?></label>
                </th>
                <td>
                    <input type="url" name="unstne_web_url" id="unstne_web_url" class="large-text" value="<?php echo esc_url( $web_url ); ?>" placeholder="https://">
                    <p class="description"><?php esc_html_e( 'Optional: Link to a web page version of this newsletter (internal page or external link like Mailchimp).', 'unstoppable-newsletters' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="unstne_description"><?php esc_html_e( 'Description', 'unstoppable-newsletters' ); ?></label>
                </th>
                <td>
                    <textarea name="unstne_description" id="unstne_description" rows="3" class="large-text"><?php echo esc_textarea( $description ); ?></textarea>
                    <p class="description"><?php esc_html_e( 'Brief description shown on the newsletter card.', 'unstoppable-newsletters' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="unstne_featured"><?php esc_html_e( 'Featured', 'unstoppable-newsletters' ); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="unstne_featured" id="unstne_featured" value="1" <?php checked( $featured, '1' ); ?>>
                        <?php esc_html_e( 'Make this the featured newsletter (displayed prominently at top)', 'unstoppable-newsletters' ); ?>
                    </label>
                    <p class="description"><?php esc_html_e( 'Only one newsletter can be featured at a time.', 'unstoppable-newsletters' ); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render featured newsletter meta box.
     *
     * @param WP_Post $post Current post object.
     */
    public function render_featured_meta_box( $post ) {
        $quote        = get_post_meta( $post->ID, '_unstne_quote', true );
        $quote_author = get_post_meta( $post->ID, '_unstne_quote_author', true );
        $tagline      = get_post_meta( $post->ID, '_unstne_tagline', true );
        $events       = get_post_meta( $post->ID, '_unstne_events', true );

        if ( ! is_array( $events ) ) {
            $events = array();
        }
        ?>
        <p class="description" style="margin-bottom: 15px;">
            <?php esc_html_e( 'These settings only apply when this newsletter is marked as "Featured".', 'unstoppable-newsletters' ); ?>
        </p>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="unstne_quote"><?php esc_html_e( 'Featured Quote', 'unstoppable-newsletters' ); ?></label>
                </th>
                <td>
                    <textarea name="unstne_quote" id="unstne_quote" rows="3" class="large-text"><?php echo esc_textarea( $quote ); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="unstne_quote_author"><?php esc_html_e( 'Quote Author', 'unstoppable-newsletters' ); ?></label>
                </th>
                <td>
                    <input type="text" name="unstne_quote_author" id="unstne_quote_author" class="regular-text" value="<?php echo esc_attr( $quote_author ); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="unstne_tagline"><?php esc_html_e( 'Tagline / Verse', 'unstoppable-newsletters' ); ?></label>
                </th>
                <td>
                    <input type="text" name="unstne_tagline" id="unstne_tagline" class="large-text" value="<?php echo esc_attr( $tagline ); ?>">
                    <p class="description"><?php esc_html_e( 'A short tagline or inspirational verse shown on the newsletter mockup.', 'unstoppable-newsletters' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e( 'Upcoming Events', 'unstoppable-newsletters' ); ?></label>
                </th>
                <td>
                    <div id="unstne-events-container">
                        <?php
                        if ( ! empty( $events ) ) {
                            foreach ( $events as $index => $event ) {
                                $this->render_event_row( $index, $event );
                            }
                        }
                        ?>
                    </div>
                    <button type="button" class="button unstne-add-event" style="margin-top: 10px;">
                        <?php esc_html_e( '+ Add Event', 'unstoppable-newsletters' ); ?>
                    </button>
                </td>
            </tr>
        </table>

        <script type="text/template" id="unstne-event-template">
            <?php $this->render_event_row( '{{INDEX}}', array( 'title' => '', 'details' => '' ) ); ?>
        </script>
        <?php
    }

    /**
     * Render a single event row.
     *
     * @param int|string $index Event index.
     * @param array      $event Event data.
     */
    private function render_event_row( $index, $event ) {
        ?>
        <div class="unstne-event-row" style="background: #f9f9f9; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
            <input type="text" name="unstne_events[<?php echo esc_attr( $index ); ?>][title]" placeholder="<?php esc_attr_e( 'Event Title', 'unstoppable-newsletters' ); ?>" class="regular-text" value="<?php echo esc_attr( $event['title'] ?? '' ); ?>" style="margin-bottom: 5px; width: 100%;">
            <input type="text" name="unstne_events[<?php echo esc_attr( $index ); ?>][details]" placeholder="<?php esc_attr_e( 'Date/Time Details', 'unstoppable-newsletters' ); ?>" class="regular-text" value="<?php echo esc_attr( $event['details'] ?? '' ); ?>" style="width: calc(100% - 80px);">
            <button type="button" class="button unstne-remove-event" style="vertical-align: top;"><?php esc_html_e( 'Remove', 'unstoppable-newsletters' ); ?></button>
        </div>
        <?php
    }

    /**
     * Save meta box data.
     *
     * @param int $post_id Post ID.
     */
    public function save_meta_box( $post_id ) {
        // Verify nonce.
        if ( ! isset( $_POST['unstne_newsletter_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['unstne_newsletter_nonce'] ) ), 'unstne_save_newsletter' ) ) {
            return;
        }

        // Check autosave.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Sanitize and save month.
        if ( isset( $_POST['unstne_month'] ) ) {
            $month = sanitize_text_field( wp_unslash( $_POST['unstne_month'] ) );
            if ( preg_match( '/^(0[1-9]|1[0-2])$/', $month ) ) {
                update_post_meta( $post_id, '_unstne_month', $month );
            }
        }

        // Sanitize and save quarter.
        if ( isset( $_POST['unstne_quarter'] ) ) {
            $quarter = sanitize_text_field( wp_unslash( $_POST['unstne_quarter'] ) );
            if ( in_array( $quarter, array( 'Q1', 'Q2', 'Q3', 'Q4' ), true ) ) {
                update_post_meta( $post_id, '_unstne_quarter', $quarter );
            }
        }

        // Sanitize and save year.
        if ( isset( $_POST['unstne_year'] ) ) {
            $year = absint( $_POST['unstne_year'] );
            if ( $year >= 2000 && $year <= 2100 ) {
                update_post_meta( $post_id, '_unstne_year', $year );
            }
        }

        // Sanitize and save PDF URL.
        if ( isset( $_POST['unstne_pdf_url'] ) ) {
            $pdf_url = esc_url_raw( wp_unslash( $_POST['unstne_pdf_url'] ) );
            update_post_meta( $post_id, '_unstne_pdf_url', $pdf_url );
        }

        // Sanitize and save Web URL.
        if ( isset( $_POST['unstne_web_url'] ) ) {
            $web_url = esc_url_raw( wp_unslash( $_POST['unstne_web_url'] ) );
            update_post_meta( $post_id, '_unstne_web_url', $web_url );
        }

        // Sanitize and save description.
        if ( isset( $_POST['unstne_description'] ) ) {
            $description = sanitize_textarea_field( wp_unslash( $_POST['unstne_description'] ) );
            update_post_meta( $post_id, '_unstne_description', $description );
        }

        // Handle featured status.
        $featured = isset( $_POST['unstne_featured'] ) ? '1' : '0';

        // If setting as featured, remove featured from all other newsletters.
        if ( '1' === $featured ) {
            $existing_featured = get_posts(
                array(
                    'post_type'      => 'unstne_newsletter',
                    'posts_per_page' => -1,
                    'meta_key'       => '_unstne_featured',
                    'meta_value'     => '1',
                    'post__not_in'   => array( $post_id ),
                    'fields'         => 'ids',
                )
            );

            foreach ( $existing_featured as $existing_id ) {
                update_post_meta( $existing_id, '_unstne_featured', '0' );
            }
        }

        update_post_meta( $post_id, '_unstne_featured', $featured );

        // Sanitize and save featured fields.
        if ( isset( $_POST['unstne_quote'] ) ) {
            $quote = sanitize_textarea_field( wp_unslash( $_POST['unstne_quote'] ) );
            update_post_meta( $post_id, '_unstne_quote', $quote );
        }

        if ( isset( $_POST['unstne_quote_author'] ) ) {
            $quote_author = sanitize_text_field( wp_unslash( $_POST['unstne_quote_author'] ) );
            update_post_meta( $post_id, '_unstne_quote_author', $quote_author );
        }

        if ( isset( $_POST['unstne_tagline'] ) ) {
            $tagline = sanitize_text_field( wp_unslash( $_POST['unstne_tagline'] ) );
            update_post_meta( $post_id, '_unstne_tagline', $tagline );
        }

        // Sanitize and save events.
        $events = array();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
        if ( isset( $_POST['unstne_events'] ) && is_array( $_POST['unstne_events'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below
            $posted_events = wp_unslash( $_POST['unstne_events'] );
            foreach ( $posted_events as $event ) {
                if ( is_array( $event ) && ! empty( $event['title'] ) ) {
                    $events[] = array(
                        'title'   => sanitize_text_field( $event['title'] ),
                        'details' => sanitize_text_field( $event['details'] ?? '' ),
                    );
                }
            }
        }
        update_post_meta( $post_id, '_unstne_events', $events );

        // Auto-generate title based on frequency setting.
        $settings  = get_option( UNSTNE_OPTION_PREFIX . '_settings', array() );
        $frequency = $settings['frequency'] ?? 'monthly';
        $year      = get_post_meta( $post_id, '_unstne_year', true );

        if ( $year ) {
            $new_title = '';

            if ( 'quarterly' === $frequency ) {
                // Quarterly mode - use quarter for title.
                $quarter = get_post_meta( $post_id, '_unstne_quarter', true );
                if ( $quarter ) {
                    $new_title = $quarter . ' ' . $year . ' Newsletter';
                }
            } else {
                // Monthly mode - use month for title.
                $month = get_post_meta( $post_id, '_unstne_month', true );
                if ( $month ) {
                    $month_names = array(
                        '01' => 'January',
                        '02' => 'February',
                        '03' => 'March',
                        '04' => 'April',
                        '05' => 'May',
                        '06' => 'June',
                        '07' => 'July',
                        '08' => 'August',
                        '09' => 'September',
                        '10' => 'October',
                        '11' => 'November',
                        '12' => 'December',
                    );
                    $month_name = $month_names[ $month ] ?? '';
                    if ( $month_name ) {
                        $new_title = $month_name . ' ' . $year . ' Newsletter';
                    }
                }
            }

            // Only update title if we have a valid new title.
            if ( $new_title ) {
                remove_action( 'save_post_unstne_newsletter', array( $this, 'save_meta_box' ) );
                wp_update_post(
                    array(
                        'ID'         => $post_id,
                        'post_title' => $new_title,
                    )
                );
                add_action( 'save_post_unstne_newsletter', array( $this, 'save_meta_box' ) );
            }
        }
    }

    /**
     * Add admin menu pages.
     */
    public function admin_menu() {
        add_submenu_page(
            'edit.php?post_type=unstne_newsletter',
            __( 'Subscribers', 'unstoppable-newsletters' ),
            __( 'Subscribers', 'unstoppable-newsletters' ),
            'manage_options',
            'unstne-subscribers',
            array( $this, 'render_subscribers_page' )
        );

        add_submenu_page(
            'edit.php?post_type=unstne_newsletter',
            __( 'Settings', 'unstoppable-newsletters' ),
            __( 'Settings', 'unstoppable-newsletters' ),
            'manage_options',
            'unstne-settings',
            array( $this, 'render_settings_page' )
        );

        /**
         * Action hook for Pro version to add additional menu items.
         *
         * @since 1.1.0
         */
        do_action( 'unstne_admin_menu' );
    }

    /**
     * Render subscribers admin page.
     */
    public function render_subscribers_page() {
        $subscribers = new UNSTNE_Subscribers();
        $subscribers->render_admin_page();
    }

    /**
     * Register settings.
     */
    public function register_settings() {
        register_setting(
            'unstne_settings_group',
            UNSTNE_OPTION_PREFIX . '_settings',
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
            )
        );
    }

    /**
     * Sanitize settings.
     *
     * @param array $input Input settings.
     * @return array Sanitized settings.
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();

        if ( isset( $input['organization_name'] ) ) {
            $sanitized['organization_name'] = sanitize_text_field( $input['organization_name'] );
        }

        if ( isset( $input['newsletter_title'] ) ) {
            $sanitized['newsletter_title'] = sanitize_text_field( $input['newsletter_title'] );
        }

        if ( isset( $input['subtitle'] ) ) {
            $sanitized['subtitle'] = sanitize_text_field( $input['subtitle'] );
        }

        if ( isset( $input['primary_color'] ) ) {
            $sanitized['primary_color'] = sanitize_hex_color( $input['primary_color'] );
        }

        if ( isset( $input['secondary_color'] ) ) {
            $sanitized['secondary_color'] = sanitize_hex_color( $input['secondary_color'] );
        }

        if ( isset( $input['accent_color'] ) ) {
            $sanitized['accent_color'] = sanitize_hex_color( $input['accent_color'] );
        }

        // Frequency setting.
        if ( isset( $input['frequency'] ) ) {
            $sanitized['frequency'] = in_array( $input['frequency'], array( 'monthly', 'quarterly' ), true ) ? $input['frequency'] : 'monthly';
        }

        // Display order setting.
        if ( isset( $input['display_order'] ) ) {
            $sanitized['display_order'] = in_array( $input['display_order'], array( 'asc', 'desc' ), true ) ? $input['display_order'] : 'asc';
        }

        // Hide coming soon setting.
        $sanitized['hide_coming_soon'] = isset( $input['hide_coming_soon'] ) && 'yes' === $input['hide_coming_soon'] ? 'yes' : 'no';

        return $sanitized;
    }

    /**
     * Render settings page.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'unstoppable-newsletters' ) );
        }

        $settings = get_option( UNSTNE_OPTION_PREFIX . '_settings', array() );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Newsletter Settings', 'unstoppable-newsletters' ); ?></h1>

            <div style="display: flex; gap: 30px; align-items: flex-start;">
                <div style="flex: 1; max-width: 800px;">
                    <form method="post" action="options.php">
                        <?php settings_fields( 'unstne_settings_group' ); ?>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="organization_name"><?php esc_html_e( 'Organization Name', 'unstoppable-newsletters' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="<?php echo esc_attr( UNSTNE_OPTION_PREFIX ); ?>_settings[organization_name]" id="organization_name" class="regular-text" value="<?php echo esc_attr( $settings['organization_name'] ?? '' ); ?>">
                                    <p class="description"><?php esc_html_e( 'Displayed in the newsletter mockup header.', 'unstoppable-newsletters' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="newsletter_title"><?php esc_html_e( 'Newsletter Title', 'unstoppable-newsletters' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="<?php echo esc_attr( UNSTNE_OPTION_PREFIX ); ?>_settings[newsletter_title]" id="newsletter_title" class="regular-text" value="<?php echo esc_attr( $settings['newsletter_title'] ?? '' ); ?>">
                                    <p class="description"><?php esc_html_e( 'Main title displayed above your newsletters.', 'unstoppable-newsletters' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="subtitle"><?php esc_html_e( 'Subtitle', 'unstoppable-newsletters' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="<?php echo esc_attr( UNSTNE_OPTION_PREFIX ); ?>_settings[subtitle]" id="subtitle" class="large-text" value="<?php echo esc_attr( $settings['subtitle'] ?? '' ); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="primary_color"><?php esc_html_e( 'Primary Color', 'unstoppable-newsletters' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="<?php echo esc_attr( UNSTNE_OPTION_PREFIX ); ?>_settings[primary_color]" id="primary_color" class="unstne-color-picker" value="<?php echo esc_attr( $settings['primary_color'] ?? '#2271b1' ); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="secondary_color"><?php esc_html_e( 'Secondary Color', 'unstoppable-newsletters' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="<?php echo esc_attr( UNSTNE_OPTION_PREFIX ); ?>_settings[secondary_color]" id="secondary_color" class="unstne-color-picker" value="<?php echo esc_attr( $settings['secondary_color'] ?? '#135e96' ); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="accent_color"><?php esc_html_e( 'Accent Color', 'unstoppable-newsletters' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="<?php echo esc_attr( UNSTNE_OPTION_PREFIX ); ?>_settings[accent_color]" id="accent_color" class="unstne-color-picker" value="<?php echo esc_attr( $settings['accent_color'] ?? '#f0b849' ); ?>">
                                </td>
                            </tr>
                            <?php
                            /**
                             * Hook for Pro color preview.
                             *
                             * @since 1.6.0
                             */
                            do_action( 'unstne_settings_after_colors' );
                            ?>
                        </table>

                        <h2><?php esc_html_e( 'Display Options', 'unstoppable-newsletters' ); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="frequency"><?php esc_html_e( 'Newsletter Frequency', 'unstoppable-newsletters' ); ?></label>
                                </th>
                                <td>
                                    <select name="<?php echo esc_attr( UNSTNE_OPTION_PREFIX ); ?>_settings[frequency]" id="frequency">
                                        <option value="monthly" <?php selected( $settings['frequency'] ?? 'monthly', 'monthly' ); ?>><?php esc_html_e( 'Monthly (Jan, Feb, Mar...)', 'unstoppable-newsletters' ); ?></option>
                                        <option value="quarterly" <?php selected( $settings['frequency'] ?? 'monthly', 'quarterly' ); ?>><?php esc_html_e( 'Quarterly (Q1, Q2, Q3, Q4)', 'unstoppable-newsletters' ); ?></option>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'How often your organization publishes newsletters.', 'unstoppable-newsletters' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="display_order"><?php esc_html_e( 'Display Order', 'unstoppable-newsletters' ); ?></label>
                                </th>
                                <td>
                                    <select name="<?php echo esc_attr( UNSTNE_OPTION_PREFIX ); ?>_settings[display_order]" id="display_order">
                                        <?php $current_frequency = $settings['frequency'] ?? 'monthly'; ?>
                                        <?php if ( 'quarterly' === $current_frequency ) : ?>
                                            <option value="asc" <?php selected( $settings['display_order'] ?? 'asc', 'asc' ); ?>><?php esc_html_e( 'Q1 → Q4 (Ascending)', 'unstoppable-newsletters' ); ?></option>
                                            <option value="desc" <?php selected( $settings['display_order'] ?? 'asc', 'desc' ); ?>><?php esc_html_e( 'Q4 → Q1 (Descending)', 'unstoppable-newsletters' ); ?></option>
                                        <?php else : ?>
                                            <option value="asc" <?php selected( $settings['display_order'] ?? 'asc', 'asc' ); ?>><?php esc_html_e( 'Jan → Dec (Ascending)', 'unstoppable-newsletters' ); ?></option>
                                            <option value="desc" <?php selected( $settings['display_order'] ?? 'asc', 'desc' ); ?>><?php esc_html_e( 'Dec → Jan (Descending)', 'unstoppable-newsletters' ); ?></option>
                                        <?php endif; ?>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'Order in which newsletters are displayed within each year.', 'unstoppable-newsletters' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="hide_coming_soon"><?php esc_html_e( 'Hide Coming Soon', 'unstoppable-newsletters' ); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="<?php echo esc_attr( UNSTNE_OPTION_PREFIX ); ?>_settings[hide_coming_soon]" id="hide_coming_soon" value="yes" <?php checked( $settings['hide_coming_soon'] ?? 'no', 'yes' ); ?>>
                                        <?php esc_html_e( 'Only show published newsletters (hide empty placeholders)', 'unstoppable-newsletters' ); ?>
                                    </label>
                                    <p class="description"><?php esc_html_e( 'Enable this if you don\'t publish on a regular schedule.', 'unstoppable-newsletters' ); ?></p>
                                </td>
                            </tr>
                        </table>

                        <h2><?php esc_html_e( 'Shortcode Usage', 'unstoppable-newsletters' ); ?></h2>
                        <p><?php esc_html_e( 'Use this shortcode on any page or post to display your newsletters:', 'unstoppable-newsletters' ); ?></p>
                        <code style="display: inline-block; padding: 10px 15px; background: #f0f0f1; font-size: 14px;">[unstoppable_newsletters]</code>
                        
                        <h3 style="margin-top: 20px;"><?php esc_html_e( 'Shortcode Options', 'unstoppable-newsletters' ); ?></h3>
                        <table class="widefat" style="max-width: 600px;">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Parameter', 'unstoppable-newsletters' ); ?></th>
                                    <th><?php esc_html_e( 'Values', 'unstoppable-newsletters' ); ?></th>
                                    <th><?php esc_html_e( 'Description', 'unstoppable-newsletters' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>show_featured</code></td>
                                    <td>yes / no</td>
                                    <td><?php esc_html_e( 'Show or hide the featured newsletter section', 'unstoppable-newsletters' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code>show_tabs</code></td>
                                    <td>yes / no</td>
                                    <td><?php esc_html_e( 'Show or hide the year tabs', 'unstoppable-newsletters' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code>show_title</code></td>
                                    <td>yes / no</td>
                                    <td><?php esc_html_e( 'Show or hide the title and subtitle', 'unstoppable-newsletters' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code>show_card_year</code></td>
                                    <td>yes / no</td>
                                    <td><?php esc_html_e( 'Show year badge on each card (useful when hiding tabs)', 'unstoppable-newsletters' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code>years</code></td>
                                    <td><?php esc_html_e( 'number', 'unstoppable-newsletters' ); ?></td>
                                    <td><?php esc_html_e( 'Only show the last X years', 'unstoppable-newsletters' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code>year</code></td>
                                    <td><?php esc_html_e( 'year(s)', 'unstoppable-newsletters' ); ?></td>
                                    <td><?php esc_html_e( 'Show specific year(s): "2024", "2022-2024", or "2022,2024,2026"', 'unstoppable-newsletters' ); ?></td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="description" style="margin-top: 10px;">
                            <?php esc_html_e( 'Example:', 'unstoppable-newsletters' ); ?> <code>[unstoppable_newsletters show_featured="no" years="5"]</code>
                        </p>
                        <p class="description" style="margin-top: 10px;">
                            <?php esc_html_e( 'Legacy shortcode:', 'unstoppable-newsletters' ); ?> <code>[newsletter_archive]</code>
                        </p>

                        <?php
                        /**
                         * Hook for Pro shortcode options.
                         *
                         * @since 1.6.0
                         */
                        do_action( 'unstne_settings_after_shortcode' );
                        ?>

                        <?php submit_button(); ?>
                    </form>
                </div>

                <?php if ( ! defined( 'UNSTNE_PRO_VERSION' ) ) : ?>
                <div style="width: 300px; flex-shrink: 0;">
                    <div style="background: linear-gradient(135deg, #1e3a5f 0%, #2271b1 100%); border-radius: 8px; padding: 25px; color: #fff;">
                        <h3 style="margin: 0 0 15px 0; color: #f0b849; font-size: 18px;">
                            <span class="dashicons dashicons-star-filled" style="margin-right: 5px;"></span>
                            <?php esc_html_e( 'Upgrade to Pro', 'unstoppable-newsletters' ); ?>
                        </h3>
                        <p style="margin: 0 0 15px 0; opacity: 0.9; font-size: 14px; line-height: 1.5;">
                            <?php esc_html_e( 'Unlock powerful features to engage your subscribers:', 'unstoppable-newsletters' ); ?>
                        </p>
                        <ul style="margin: 0 0 20px 0; padding-left: 20px; font-size: 13px; line-height: 1.8;">
                            <li><?php esc_html_e( 'Email notifications on publish', 'unstoppable-newsletters' ); ?></li>
                            <li><?php esc_html_e( 'Subscriber management', 'unstoppable-newsletters' ); ?></li>
                            <li><?php esc_html_e( 'Email service integrations', 'unstoppable-newsletters' ); ?></li>
                            <li><?php esc_html_e( 'Custom email templates', 'unstoppable-newsletters' ); ?></li>
                            <li><?php esc_html_e( 'Priority email support', 'unstoppable-newsletters' ); ?></li>
                        </ul>
                        <a href="https://unstoppablesolutions.app/plugins/unstoppable-newsletters-pro/" target="_blank" class="button" style="width: 100%; text-align: center; background: #f0b849; border-color: #f0b849; color: #1e3a5f; font-weight: 600;">
                            <?php esc_html_e( 'Get Pro Version', 'unstoppable-newsletters' ); ?>
                        </a>
                    </div>

                    <div style="margin-top: 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 15px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 14px;">
                            <?php esc_html_e( 'Need Help?', 'unstoppable-newsletters' ); ?>
                        </h4>
                        <p style="margin: 0 0 10px 0; font-size: 13px; color: #666;">
                            <?php esc_html_e( 'Check out our documentation or reach out for support.', 'unstoppable-newsletters' ); ?>
                        </p>
                        <a href="https://unstoppablesolutions.app/docs/unstoppable-newsletters/" target="_blank" style="font-size: 13px;">
                            <?php esc_html_e( 'View Documentation →', 'unstoppable-newsletters' ); ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue admin scripts.
     *
     * @param string $hook Current admin page hook.
     */
    public function admin_scripts( $hook ) {
        $screen = get_current_screen();

        // Only load on our plugin pages.
        if ( ! $screen || ( 'unstne_newsletter' !== $screen->post_type && strpos( $hook, 'unstne-' ) === false ) ) {
            return;
        }

        // Color picker.
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        // Media uploader.
        wp_enqueue_media();

        // Admin script.
        wp_enqueue_script(
            'unstne-admin',
            UNSTNE_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery', 'wp-color-picker' ),
            UNSTNE_VERSION,
            true
        );
    }

    /**
     * Add Unstoppable Solutions branding to admin menu.
     *
     * Applies gold (#f0b849) color to the Newsletters menu for brand consistency.
     */
    public function admin_menu_branding() {
        $custom_css = '
            /* Unstoppable Solutions gold branding for menu text */
            #adminmenu .menu-icon-unstne_newsletter .wp-menu-name {
                color: #f0b849 !important;
            }
            #adminmenu .menu-icon-unstne_newsletter:hover .wp-menu-name,
            #adminmenu .menu-icon-unstne_newsletter.current .wp-menu-name,
            #adminmenu .menu-icon-unstne_newsletter.wp-has-current-submenu .wp-menu-name {
                color: #fff !important;
            }
            /* Gold color for the dashicon */
            #adminmenu .menu-icon-unstne_newsletter .wp-menu-image:before {
                color: #f0b849;
            }
            #adminmenu .menu-icon-unstne_newsletter:hover .wp-menu-image:before,
            #adminmenu .menu-icon-unstne_newsletter.current .wp-menu-image:before,
            #adminmenu .menu-icon-unstne_newsletter.wp-has-current-submenu .wp-menu-image:before {
                color: #fff;
            }
        ';
        wp_add_inline_style( 'wp-admin', $custom_css );
    }

    /**
     * Enqueue frontend scripts.
     */
    public function frontend_scripts() {
        global $post;

        // Only load if shortcode is present.
        if ( ! is_a( $post, 'WP_Post' ) ) {
            return;
        }

        if ( ! has_shortcode( $post->post_content, 'unstne_archive' ) && ! has_shortcode( $post->post_content, 'unstoppable_newsletters' ) ) {
            return;
        }

        $settings = get_option( UNSTNE_OPTION_PREFIX . '_settings', array() );

        wp_enqueue_style(
            'unstne-frontend',
            UNSTNE_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            UNSTNE_VERSION
        );

        // Add custom CSS variables.
        $custom_css = ':root {
            --unstne-primary: ' . esc_attr( $settings['primary_color'] ?? '#2271b1' ) . ';
            --unstne-secondary: ' . esc_attr( $settings['secondary_color'] ?? '#135e96' ) . ';
            --unstne-accent: ' . esc_attr( $settings['accent_color'] ?? '#f0b849' ) . ';
        }';
        wp_add_inline_style( 'unstne-frontend', $custom_css );

        wp_enqueue_script(
            'unstne-frontend',
            UNSTNE_PLUGIN_URL . 'assets/js/frontend.js',
            array( 'jquery' ),
            UNSTNE_VERSION,
            true
        );

        // Localize script for AJAX.
        wp_localize_script( 'unstne-frontend', 'unstneAjax', array(
            'ajaxurl'         => admin_url( 'admin-ajax.php' ),
            'subscribe_nonce' => wp_create_nonce( 'unstne_subscribe_nonce' ),
            'search_nonce'    => wp_create_nonce( 'unstne_search_nonce' ),
            'strings'         => array(
                'subscribing'   => __( 'Subscribing...', 'unstoppable-newsletters' ),
                'searching'     => __( 'Searching...', 'unstoppable-newsletters' ),
                'error'         => __( 'An error occurred. Please try again.', 'unstoppable-newsletters' ),
                'no_results'    => __( 'No newsletters found.', 'unstoppable-newsletters' ),
            ),
        ) );
    }

    /**
     * Add custom columns to admin list.
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public function custom_columns( $columns ) {
        $new_columns = array();
        
        // Check frequency setting for column label.
        $settings  = get_option( UNSTNE_OPTION_PREFIX . '_settings', array() );
        $frequency = $settings['frequency'] ?? 'monthly';
        $period_label = 'quarterly' === $frequency 
            ? __( 'Quarter/Year', 'unstoppable-newsletters' ) 
            : __( 'Month/Year', 'unstoppable-newsletters' );

        foreach ( $columns as $key => $value ) {
            if ( 'title' === $key ) {
                $new_columns[ $key ] = $value;
                $new_columns['unstne_month_year'] = $period_label;
                $new_columns['unstne_pdf'] = __( 'PDF', 'unstoppable-newsletters' );
                $new_columns['unstne_featured'] = __( 'Featured', 'unstoppable-newsletters' );
            } else {
                $new_columns[ $key ] = $value;
            }
        }

        return $new_columns;
    }

    /**
     * Render custom column content.
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     */
    public function custom_column_content( $column, $post_id ) {
        switch ( $column ) {
            case 'unstne_month_year':
                $settings  = get_option( UNSTNE_OPTION_PREFIX . '_settings', array() );
                $frequency = $settings['frequency'] ?? 'monthly';
                $year      = get_post_meta( $post_id, '_unstne_year', true );
                
                if ( 'quarterly' === $frequency ) {
                    $quarter = get_post_meta( $post_id, '_unstne_quarter', true );
                    if ( $quarter && $year ) {
                        echo esc_html( $quarter . ' ' . $year );
                    } else {
                        echo '—';
                    }
                } else {
                    $month = get_post_meta( $post_id, '_unstne_month', true );
                    if ( $month && $year ) {
                        $month_names = array(
                            '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
                            '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug',
                            '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec',
                        );
                        echo esc_html( ( $month_names[ $month ] ?? $month ) . ' ' . $year );
                    } else {
                        echo '—';
                    }
                }
                break;

            case 'unstne_pdf':
                $pdf_url = get_post_meta( $post_id, '_unstne_pdf_url', true );
                if ( $pdf_url ) {
                    echo '<span class="dashicons dashicons-pdf" style="color: #00a32a;" title="' . esc_attr__( 'PDF uploaded', 'unstoppable-newsletters' ) . '"></span>';
                } else {
                    echo '<span class="dashicons dashicons-no" style="color: #d63638;" title="' . esc_attr__( 'No PDF', 'unstoppable-newsletters' ) . '"></span>';
                }
                break;

            case 'unstne_featured':
                $featured = get_post_meta( $post_id, '_unstne_featured', true );
                if ( '1' === $featured ) {
                    echo '<span class="dashicons dashicons-star-filled" style="color: #f0b849;"></span>';
                } else {
                    echo '—';
                }
                break;
        }
    }

    /**
     * Render the shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'show_featured'   => 'yes',    // yes/no - Show featured section
            'show_tabs'       => 'yes',    // yes/no - Show year tabs
            'years'           => '',       // Number - Only show last X years
            'show_title'      => 'yes',    // yes/no - Show title and subtitle
            'show_card_year'  => 'no',     // yes/no - Show year on each card
            'year'            => '',       // Specific year(s): "2024", "2022-2024", or "2022,2023,2024"
            'show_search'     => 'no',     // yes/no - Show search box
            'show_subscribe'  => 'no',     // yes/no - Show subscribe form
        ), $atts, 'newsletter_archive' );

        // Convert string yes/no to boolean.
        $show_featured   = 'yes' === strtolower( $atts['show_featured'] );
        $show_tabs       = 'yes' === strtolower( $atts['show_tabs'] );
        $show_card_year  = 'yes' === strtolower( $atts['show_card_year'] );
        $show_title      = 'yes' === strtolower( $atts['show_title'] );
        $show_search     = 'yes' === strtolower( $atts['show_search'] );
        $show_subscribe  = 'yes' === strtolower( $atts['show_subscribe'] );
        $limit_years     = ! empty( $atts['years'] ) ? absint( $atts['years'] ) : 0;
        
        // Parse year parameter - supports single year, range (2022-2024), or list (2022,2023,2024).
        $filter_years = array();
        if ( ! empty( $atts['year'] ) ) {
            $year_param = sanitize_text_field( $atts['year'] );
            
            // Check for range (e.g., "2022-2024").
            if ( strpos( $year_param, '-' ) !== false ) {
                $parts = explode( '-', $year_param );
                if ( count( $parts ) === 2 ) {
                    $start = absint( trim( $parts[0] ) );
                    $end   = absint( trim( $parts[1] ) );
                    if ( $start > 0 && $end > 0 ) {
                        $filter_years = range( min( $start, $end ), max( $start, $end ) );
                    }
                }
            }
            // Check for comma-separated list (e.g., "2022,2023,2024").
            elseif ( strpos( $year_param, ',' ) !== false ) {
                $parts = explode( ',', $year_param );
                foreach ( $parts as $y ) {
                    $y = absint( trim( $y ) );
                    if ( $y > 0 ) {
                        $filter_years[] = $y;
                    }
                }
            }
            // Single year.
            else {
                $single = absint( $year_param );
                if ( $single > 0 ) {
                    $filter_years[] = $single;
                }
            }
        }

        $settings         = get_option( UNSTNE_OPTION_PREFIX . '_settings', array() );
        $frequency        = $settings['frequency'] ?? 'monthly';
        $display_order    = $settings['display_order'] ?? 'asc';
        $hide_coming_soon = ( $settings['hide_coming_soon'] ?? 'no' ) === 'yes';

        // Get all newsletters grouped by year.
        $newsletters = get_posts(
            array(
                'post_type'      => 'unstne_newsletter',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'meta_key'       => '_unstne_year',
                'orderby'        => array(
                    'meta_value_num' => 'DESC',
                    '_unstne_month'     => 'DESC',
                ),
            )
        );

        // Organize by year and period (month or quarter).
        $by_year = array();
        foreach ( $newsletters as $newsletter ) {
            $year = get_post_meta( $newsletter->ID, '_unstne_year', true );
            
            if ( 'quarterly' === $frequency ) {
                $quarter = get_post_meta( $newsletter->ID, '_unstne_quarter', true );
                if ( $year && $quarter ) {
                    if ( ! isset( $by_year[ $year ] ) ) {
                        $by_year[ $year ] = array();
                    }
                    $by_year[ $year ][ $quarter ] = $newsletter;
                }
            } else {
                $month = get_post_meta( $newsletter->ID, '_unstne_month', true );
                if ( $year && $month ) {
                    if ( ! isset( $by_year[ $year ] ) ) {
                        $by_year[ $year ] = array();
                    }
                    $by_year[ $year ][ $month ] = $newsletter;
                }
            }
        }

        // Sort years descending.
        krsort( $by_year );

        // Filter by specific year(s) if set.
        if ( ! empty( $filter_years ) ) {
            $filtered = array();
            foreach ( $filter_years as $y ) {
                if ( isset( $by_year[ $y ] ) ) {
                    $filtered[ $y ] = $by_year[ $y ];
                }
            }
            // Sort the filtered results descending.
            krsort( $filtered );
            $by_year = $filtered;
        }
        // Or limit to last X years.
        elseif ( $limit_years > 0 ) {
            $by_year = array_slice( $by_year, 0, $limit_years, true );
        }

        // Get featured newsletter.
        $featured = get_posts(
            array(
                'post_type'      => 'unstne_newsletter',
                'posts_per_page' => 1,
                'post_status'    => 'publish',
                'meta_key'       => '_unstne_featured',
                'meta_value'     => '1',
            )
        );

        $featured_newsletter = ! empty( $featured ) ? $featured[0] : null;

        ob_start();
        ?>
        <div class="unstne-archive-wrapper">
            <?php if ( $show_title && ! empty( $settings['newsletter_title'] ) ) : ?>
                <div class="unstne-header">
                    <h2 class="unstne-title"><?php echo esc_html( $settings['newsletter_title'] ); ?></h2>
                    <?php if ( ! empty( $settings['subtitle'] ) ) : ?>
                        <p class="unstne-subtitle"><?php echo esc_html( $settings['subtitle'] ); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ( $show_search ) : ?>
                <div class="unstne-search-wrapper">
                    <form class="unstne-search-form" role="search">
                        <input type="search" class="unstne-search-input" placeholder="<?php esc_attr_e( 'Search newsletters...', 'unstoppable-newsletters' ); ?>" aria-label="<?php esc_attr_e( 'Search newsletters', 'unstoppable-newsletters' ); ?>">
                        <button type="submit" class="unstne-search-btn" style="background-color: <?php echo esc_attr( $settings['primary_color'] ?? '#2271b1' ); ?>">
                            <span class="dashicons dashicons-search"></span>
                            <span class="screen-reader-text"><?php esc_html_e( 'Search', 'unstoppable-newsletters' ); ?></span>
                        </button>
                    </form>
                    <div class="unstne-search-results" style="display: none;"></div>
                </div>
            <?php endif; ?>

            <?php if ( $show_featured && $featured_newsletter ) : ?>
                <?php $this->render_featured_section( $featured_newsletter, $settings ); ?>
            <?php endif; ?>

            <?php if ( $show_subscribe ) : ?>
                <div class="unstne-subscribe-wrapper">
                    <div class="unstne-subscribe-box" style="border-color: <?php echo esc_attr( $settings['primary_color'] ?? '#2271b1' ); ?>">
                        <h3 class="unstne-subscribe-title"><?php esc_html_e( 'Subscribe for Updates', 'unstoppable-newsletters' ); ?></h3>
                        <p class="unstne-subscribe-desc"><?php esc_html_e( 'Get notified when new newsletters are published.', 'unstoppable-newsletters' ); ?></p>
                        <form class="unstne-subscribe-form">
                            <?php wp_nonce_field( 'unstne_subscribe_nonce', 'unstne_subscribe_nonce' ); ?>
                            <div class="unstne-subscribe-fields">
                                <input type="text" name="unstne_name" placeholder="<?php esc_attr_e( 'Your Name', 'unstoppable-newsletters' ); ?>" class="unstne-input">
                                <input type="email" name="unstne_email" placeholder="<?php esc_attr_e( 'Your Email', 'unstoppable-newsletters' ); ?>" required class="unstne-input">
                                <button type="submit" class="unstne-subscribe-btn" style="background-color: <?php echo esc_attr( $settings['primary_color'] ?? '#2271b1' ); ?>">
                                    <?php esc_html_e( 'Subscribe', 'unstoppable-newsletters' ); ?>
                                </button>
                            </div>
                            <div class="unstne-form-message" style="display: none;"></div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $by_year ) ) : ?>
                <div class="unstne-newsletter-section">
                    <?php if ( $show_tabs && count( $by_year ) > 1 ) : ?>
                    <div class="unstne-year-tabs" role="tablist">
                        <?php
                        $first = true;
                        foreach ( array_keys( $by_year ) as $year ) :
                            ?>
                            <button class="unstne-year-tab <?php echo $first ? 'active' : ''; ?>" data-year="<?php echo esc_attr( $year ); ?>" role="tab" aria-selected="<?php echo $first ? 'true' : 'false'; ?>">
                                <?php echo esc_html( $year ); ?>
                            </button>
                            <?php
                            $first = false;
                        endforeach;
                        ?>
                    </div>
                    <?php endif; ?>

                    <?php
                    $first = true;
                    // If tabs are hidden, show all years' content.
                    $show_all_content = ! $show_tabs || count( $by_year ) <= 1;
                    foreach ( $by_year as $year => $periods ) :
                        ?>
                        <div id="unstne-year-<?php echo esc_attr( $year ); ?>" class="unstne-year-content <?php echo ( $first || $show_all_content ) ? 'active' : ''; ?>"<?php echo $show_all_content ? ' style="display: block;"' : ''; ?>>
                            <div class="unstne-newsletter-grid <?php echo 'quarterly' === $frequency ? 'quarterly-grid' : ''; ?>">
                                <?php
                                if ( 'quarterly' === $frequency ) {
                                    // Quarterly display.
                                    $all_quarters = 'desc' === $display_order
                                        ? array( 'Q4', 'Q3', 'Q2', 'Q1' )
                                        : array( 'Q1', 'Q2', 'Q3', 'Q4' );
                                    foreach ( $all_quarters as $q ) :
                                        $newsletter = $periods[ $q ] ?? null;
                                        $has_content = $newsletter && (
                                            ! empty( get_post_meta( $newsletter->ID, '_unstne_pdf_url', true ) ) ||
                                            ! empty( get_post_meta( $newsletter->ID, '_unstne_web_url', true ) )
                                        );
                                        $is_coming_soon = ! $has_content;
                                        
                                        // Skip if hide_coming_soon is enabled and no newsletter.
                                        if ( $hide_coming_soon && $is_coming_soon ) {
                                            continue;
                                        }
                                        
                                        $this->render_quarter_card( $q, $year, $newsletter, $show_card_year );
                                    endforeach;
                                } else {
                                    // Monthly display.
                                    $all_months = 'desc' === $display_order
                                        ? array( '12', '11', '10', '09', '08', '07', '06', '05', '04', '03', '02', '01' )
                                        : array( '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12' );
                                    foreach ( $all_months as $m ) :
                                        $newsletter = $periods[ $m ] ?? null;
                                        $has_content = $newsletter && (
                                            ! empty( get_post_meta( $newsletter->ID, '_unstne_pdf_url', true ) ) ||
                                            ! empty( get_post_meta( $newsletter->ID, '_unstne_web_url', true ) )
                                        );
                                        $is_coming_soon = ! $has_content;
                                        
                                        // Skip if hide_coming_soon is enabled and no newsletter.
                                        if ( $hide_coming_soon && $is_coming_soon ) {
                                            continue;
                                        }
                                        
                                        $this->render_newsletter_card( $m, $year, $newsletter, $show_card_year );
                                    endforeach;
                                }
                                ?>
                            </div>
                        </div>
                        <?php
                        $first = false;
                    endforeach;
                    ?>
                </div>
            <?php else : ?>
                <div class="unstne-no-newsletters">
                    <p><?php esc_html_e( 'No newsletters have been published yet. Check back soon!', 'unstoppable-newsletters' ); ?></p>
                </div>
            <?php endif; ?>
        </div><!-- .unstne-archive-wrapper -->
        <?php
        $output = ob_get_clean();

        /**
         * Filter the shortcode output.
         *
         * @since 1.6.0
         * @param string $output  HTML output.
         * @param array  $atts    Shortcode attributes.
         * @param array  $by_year Newsletters organized by year.
         */
        $filtered_output = apply_filters( 'unstne_shortcode_output', $output, $atts, $by_year );
        
        // Escape output after filter to ensure safety.
        return wp_kses_post( $filtered_output );
    }

    /**
     * Render featured section.
     *
     * @param WP_Post $newsletter Newsletter post.
     * @param array   $settings   Plugin settings.
     */
    private function render_featured_section( $newsletter, $settings ) {
        $month        = get_post_meta( $newsletter->ID, '_unstne_month', true );
        $quarter      = get_post_meta( $newsletter->ID, '_unstne_quarter', true );
        $year         = get_post_meta( $newsletter->ID, '_unstne_year', true );
        $pdf_url      = get_post_meta( $newsletter->ID, '_unstne_pdf_url', true );
        $web_url      = get_post_meta( $newsletter->ID, '_unstne_web_url', true );
        $quote        = get_post_meta( $newsletter->ID, '_unstne_quote', true );
        $quote_author = get_post_meta( $newsletter->ID, '_unstne_quote_author', true );
        $tagline      = get_post_meta( $newsletter->ID, '_unstne_tagline', true );
        $events       = get_post_meta( $newsletter->ID, '_unstne_events', true );

        $has_pdf = ! empty( $pdf_url );
        $has_web = ! empty( $web_url );

        // Check frequency setting.
        $frequency = $settings['frequency'] ?? 'monthly';

        $month_names = array(
            '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
            '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
            '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December',
        );

        $month_abbrev = array(
            '01' => 'JAN', '02' => 'FEB', '03' => 'MAR', '04' => 'APR',
            '05' => 'MAY', '06' => 'JUN', '07' => 'JUL', '08' => 'AUG',
            '09' => 'SEP', '10' => 'OCT', '11' => 'NOV', '12' => 'DEC',
        );

        // Determine display values based on frequency.
        if ( 'quarterly' === $frequency && $quarter ) {
            $period_short = $quarter; // Q1, Q2, Q3, Q4
            $period_name  = $quarter . ' ' . $year;
        } else {
            $period_short = $month_abbrev[ $month ] ?? '';
            $period_name  = ( $month_names[ $month ] ?? '' ) . ' ' . $year;
        }

        $org_name = $settings['organization_name'] ?? get_bloginfo( 'name' );
        ?>
        <div class="unstne-featured-section">
            <div class="unstne-featured-content">
                <div class="unstne-featured-left">
                    <div class="unstne-newsletter-mockup">
                        <div class="unstne-mockup-header">
                            <div class="unstne-mockup-logo">
                                <svg viewBox="0 0 40 40" width="40" height="40">
                                    <circle cx="20" cy="20" r="18" fill="var(--unstne-primary)" />
                                    <text x="20" y="26" text-anchor="middle" fill="white" font-size="16" font-weight="bold"><?php echo esc_html( substr( $org_name, 0, 1 ) ); ?></text>
                                </svg>
                            </div>
                            <div class="unstne-mockup-title"><?php echo esc_html( strtoupper( $org_name ) ); ?></div>
                        </div>
                        <div class="unstne-mockup-month"><?php echo esc_html( $period_short ); ?></div>
                        <?php if ( $tagline ) : ?>
                            <div class="unstne-mockup-tagline"><?php echo esc_html( $tagline ); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="unstne-featured-right">
                    <span class="unstne-featured-badge"><?php esc_html_e( 'Current Issue', 'unstoppable-newsletters' ); ?></span>
                    <h2 class="unstne-featured-title"><?php echo esc_html( $period_name ); ?></h2>

                    <?php if ( $quote ) : ?>
                        <blockquote class="unstne-featured-quote">
                            "<?php echo esc_html( $quote ); ?>"
                            <?php if ( $quote_author ) : ?>
                                <cite>— <?php echo esc_html( $quote_author ); ?></cite>
                            <?php endif; ?>
                        </blockquote>
                    <?php endif; ?>

                    <?php if ( ! empty( $events ) && is_array( $events ) ) : ?>
                        <div class="unstne-featured-events">
                            <h3><?php esc_html_e( 'Upcoming Events', 'unstoppable-newsletters' ); ?></h3>
                            <ul>
                                <?php foreach ( $events as $event ) : ?>
                                    <?php if ( ! empty( $event['title'] ) ) : ?>
                                        <li>
                                            <strong><?php echo esc_html( $event['title'] ); ?></strong>
                                            <?php if ( ! empty( $event['details'] ) ) : ?>
                                                <span><?php echo esc_html( $event['details'] ); ?></span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ( $has_pdf || $has_web ) : ?>
                        <div class="unstne-featured-buttons">
                            <?php if ( $has_web && $has_pdf ) : ?>
                                <!-- Both web and PDF available -->
                                <a href="<?php echo esc_url( $web_url ); ?>" class="unstne-btn-primary" target="_blank">
                                    <?php esc_html_e( 'Read Online', 'unstoppable-newsletters' ); ?>
                                </a>
                                <a href="<?php echo esc_url( $pdf_url ); ?>" class="unstne-btn-secondary" download>
                                    <?php esc_html_e( 'Download PDF', 'unstoppable-newsletters' ); ?>
                                </a>
                            <?php elseif ( $has_web ) : ?>
                                <!-- Only web version available -->
                                <a href="<?php echo esc_url( $web_url ); ?>" class="unstne-btn-primary" target="_blank">
                                    <?php esc_html_e( 'Read Online', 'unstoppable-newsletters' ); ?>
                                </a>
                            <?php else : ?>
                                <!-- Only PDF available -->
                                <a href="<?php echo esc_url( $pdf_url ); ?>" class="unstne-btn-primary" target="_blank">
                                    <?php esc_html_e( 'Read Now', 'unstoppable-newsletters' ); ?>
                                </a>
                                <a href="<?php echo esc_url( $pdf_url ); ?>" class="unstne-btn-secondary" download>
                                    <?php esc_html_e( 'Download PDF', 'unstoppable-newsletters' ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render a newsletter card.
     *
     * @param string       $month      Month number.
     * @param string       $year       Year.
     * @param WP_Post|null $newsletter Newsletter post or null.
     */
    /**
     * Render a monthly newsletter card.
     *
     * @param string       $month          Month number (01-12).
     * @param string       $year           Year.
     * @param WP_Post|null $newsletter     Newsletter post or null.
     * @param bool         $show_card_year Whether to show year on the card.
     */
    private function render_newsletter_card( $month, $year, $newsletter, $show_card_year = false ) {
        $month_names = array(
            '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
            '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
            '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December',
        );

        $month_name = $month_names[ $month ] ?? '';
        $pdf_url    = $newsletter ? get_post_meta( $newsletter->ID, '_unstne_pdf_url', true ) : '';
        $web_url    = $newsletter ? get_post_meta( $newsletter->ID, '_unstne_web_url', true ) : '';
        $desc       = $newsletter ? get_post_meta( $newsletter->ID, '_unstne_description', true ) : '';

        $has_pdf     = ! empty( $pdf_url );
        $has_web     = ! empty( $web_url );
        $is_coming_soon = ! $newsletter || ( ! $has_pdf && ! $has_web );

        // Month gradients.
        $gradients = array(
            '01' => 'linear-gradient(135deg, #2271b1 0%, #135e96 100%)',
            '02' => 'linear-gradient(135deg, #a51890 0%, #f93822 100%)',
            '03' => 'linear-gradient(135deg, #007672 0%, #f0b849 100%)',
            '04' => 'linear-gradient(135deg, #2271b1 0%, #a51890 100%)',
            '05' => 'linear-gradient(135deg, #f0b849 0%, #f93822 100%)',
            '06' => 'linear-gradient(135deg, #007672 0%, #2271b1 100%)',
            '07' => 'linear-gradient(135deg, #f93822 0%, #2271b1 100%)',
            '08' => 'linear-gradient(135deg, #a51890 0%, #007672 100%)',
            '09' => 'linear-gradient(135deg, #2271b1 0%, #f0b849 100%)',
            '10' => 'linear-gradient(135deg, #f93822 0%, #a51890 100%)',
            '11' => 'linear-gradient(135deg, #007672 0%, #a51890 100%)',
            '12' => 'linear-gradient(135deg, #2271b1 0%, #f93822 100%)',
        );

        $gradient = $gradients[ $month ] ?? $gradients['01'];

        // Determine primary link (web version takes priority for "Read" action).
        $primary_url   = $has_web ? $web_url : $pdf_url;
        $primary_label = $has_web ? __( 'Read Online', 'unstoppable-newsletters' ) : __( 'Read Newsletter', 'unstoppable-newsletters' );
        ?>
        <div class="unstne-newsletter-card <?php echo $is_coming_soon ? 'coming-soon' : ''; ?>">
            <div class="unstne-card-header" style="background: <?php echo esc_attr( $gradient ); ?>;">
                <?php if ( $show_card_year ) : ?>
                    <span class="unstne-card-year-badge"><?php echo esc_html( $year ); ?></span>
                <?php endif; ?>
                <span class="unstne-card-month"><?php echo esc_html( $month_name ); ?></span>
                <span class="unstne-card-year"><?php echo esc_html( $year ); ?></span>
            </div>
            <div class="unstne-card-body">
                <?php if ( $is_coming_soon ) : ?>
                    <p class="unstne-card-desc"><?php esc_html_e( 'Coming Soon', 'unstoppable-newsletters' ); ?></p>
                    <span class="unstne-card-link coming-soon"><?php esc_html_e( 'Not Yet Available', 'unstoppable-newsletters' ); ?></span>
                <?php else : ?>
                    <p class="unstne-card-desc"><?php echo esc_html( $desc ?: __( 'Click to read this issue', 'unstoppable-newsletters' ) ); ?></p>
                    <a href="<?php echo esc_url( $primary_url ); ?>" class="unstne-card-link" target="_blank">
                        <?php echo esc_html( $primary_label ); ?> →
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render a quarterly newsletter card.
     *
     * @param string       $quarter    Quarter (Q1, Q2, Q3, Q4).
     * @param string       $year       Year.
     * @param WP_Post|null $newsletter Newsletter post or null.
     */
    /**
     * Render a quarterly newsletter card.
     *
     * @param string       $quarter        Quarter (Q1, Q2, Q3, Q4).
     * @param string       $year           Year.
     * @param WP_Post|null $newsletter     Newsletter post or null.
     * @param bool         $show_card_year Whether to show year on the card.
     */
    private function render_quarter_card( $quarter, $year, $newsletter, $show_card_year = false ) {
        $quarter_names = array(
            'Q1' => __( 'Q1', 'unstoppable-newsletters' ),
            'Q2' => __( 'Q2', 'unstoppable-newsletters' ),
            'Q3' => __( 'Q3', 'unstoppable-newsletters' ),
            'Q4' => __( 'Q4', 'unstoppable-newsletters' ),
        );

        $quarter_subtitles = array(
            'Q1' => __( 'Jan - Mar', 'unstoppable-newsletters' ),
            'Q2' => __( 'Apr - Jun', 'unstoppable-newsletters' ),
            'Q3' => __( 'Jul - Sep', 'unstoppable-newsletters' ),
            'Q4' => __( 'Oct - Dec', 'unstoppable-newsletters' ),
        );

        $quarter_name     = $quarter_names[ $quarter ] ?? '';
        $quarter_subtitle = $quarter_subtitles[ $quarter ] ?? '';
        $pdf_url          = $newsletter ? get_post_meta( $newsletter->ID, '_unstne_pdf_url', true ) : '';
        $web_url          = $newsletter ? get_post_meta( $newsletter->ID, '_unstne_web_url', true ) : '';
        $desc             = $newsletter ? get_post_meta( $newsletter->ID, '_unstne_description', true ) : '';

        $has_pdf     = ! empty( $pdf_url );
        $has_web     = ! empty( $web_url );
        $is_coming_soon = ! $newsletter || ( ! $has_pdf && ! $has_web );

        // Quarter gradients.
        $gradients = array(
            'Q1' => 'linear-gradient(135deg, #2271b1 0%, #135e96 100%)',
            'Q2' => 'linear-gradient(135deg, #007672 0%, #f0b849 100%)',
            'Q3' => 'linear-gradient(135deg, #f93822 0%, #2271b1 100%)',
            'Q4' => 'linear-gradient(135deg, #a51890 0%, #007672 100%)',
        );

        $gradient = $gradients[ $quarter ] ?? $gradients['Q1'];

        // Determine primary link (web version takes priority for "Read" action).
        $primary_url   = $has_web ? $web_url : $pdf_url;
        $primary_label = $has_web ? __( 'Read Online', 'unstoppable-newsletters' ) : __( 'Read Newsletter', 'unstoppable-newsletters' );
        ?>
        <div class="unstne-newsletter-card <?php echo $is_coming_soon ? 'coming-soon' : ''; ?>">
            <div class="unstne-card-header" style="background: <?php echo esc_attr( $gradient ); ?>;">
                <?php if ( $show_card_year ) : ?>
                    <span class="unstne-card-year-badge"><?php echo esc_html( $year ); ?></span>
                <?php endif; ?>
                <span class="unstne-card-month"><?php echo esc_html( $quarter_name ); ?></span>
                <span class="unstne-card-year"><?php echo esc_html( $quarter_subtitle ); ?></span>
            </div>
            <div class="unstne-card-body">
                <?php if ( $is_coming_soon ) : ?>
                    <p class="unstne-card-desc"><?php esc_html_e( 'Coming Soon', 'unstoppable-newsletters' ); ?></p>
                    <span class="unstne-card-link coming-soon"><?php esc_html_e( 'Not Yet Available', 'unstoppable-newsletters' ); ?></span>
                <?php else : ?>
                    <p class="unstne-card-desc"><?php echo esc_html( $desc ?: __( 'Click to read this issue', 'unstoppable-newsletters' ) ); ?></p>
                    <a href="<?php echo esc_url( $primary_url ); ?>" class="unstne-card-link" target="_blank">
                        <?php echo esc_html( $primary_label ); ?> →
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler for newsletter search.
     */
    public function ajax_search_newsletters() {
        check_ajax_referer( 'unstne_search_nonce', 'nonce' );

        $search_term = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

        if ( empty( $search_term ) || strlen( $search_term ) < 2 ) {
            wp_send_json_error( array( 'message' => __( 'Please enter at least 2 characters.', 'unstoppable-newsletters' ) ) );
        }

        $newsletters = get_posts( array(
            'post_type'      => 'unstne_newsletter',
            'posts_per_page' => 10,
            'post_status'    => 'publish',
            's'              => $search_term,
        ) );

        if ( empty( $newsletters ) ) {
            wp_send_json_success( array(
                'results' => array(),
                'message' => __( 'No newsletters found.', 'unstoppable-newsletters' ),
            ) );
        }

        $results = array();
        foreach ( $newsletters as $newsletter ) {
            $year    = get_post_meta( $newsletter->ID, '_unstne_year', true );
            $month   = get_post_meta( $newsletter->ID, '_unstne_month', true );
            $pdf_url = get_post_meta( $newsletter->ID, '_unstne_pdf_url', true );
            $web_url = get_post_meta( $newsletter->ID, '_unstne_web_url', true );
            $url     = $web_url ?: $pdf_url;

            if ( ! $url ) {
                continue;
            }

            $month_name = '';
            if ( $month ) {
                $month_name = date_i18n( 'F', mktime( 0, 0, 0, (int) $month, 1 ) );
            }

            $results[] = array(
                'id'    => $newsletter->ID,
                'title' => $newsletter->post_title,
                'date'  => $month_name . ' ' . $year,
                'url'   => $url,
                'desc'  => get_post_meta( $newsletter->ID, '_unstne_description', true ),
            );
        }

        wp_send_json_success( array( 'results' => $results ) );
    }
}

/**
 * Get the plugin instance.
 *
 * @return UNSTNE_Plugin
 */
function unstne() {
    return UNSTNE_Plugin::get_instance();
}

// Initialize the plugin.
unstne();
