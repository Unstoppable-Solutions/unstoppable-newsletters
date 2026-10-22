<?php
/**
 * Admin Notices Class
 *
 * Handles all admin dashboard notices for the plugin.
 *
 * @package Unstoppable_Newsletters
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin Notices Class.
 */
class UNSTNE_Admin_Notices {

    /**
     * Plugin prefix.
     *
     * @var string
     */
    private $prefix = 'unstne';

    /**
     * Plugin name.
     *
     * @var string
     */
    private $plugin_name = 'Unstoppable Newsletters';

    /**
     * Pro upgrade URL.
     *
     * @var string
     */
    private $pro_url = 'https://unstoppablesolutions.app/plugins/unstoppable-newsletters-pro/';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_notices', array( $this, 'display_notices' ) );
        add_action( 'admin_notices', array( $this, 'display_flash_notices' ) );
        add_action( 'wp_ajax_' . $this->prefix . '_dismiss_notice', array( $this, 'ajax_dismiss_notice' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Enqueue admin scripts for notice dismissal.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_scripts( $hook ) {
        $screen = get_current_screen();

        // Only load on relevant pages.
        if ( ! $screen || ( 'unstne_newsletter' !== $screen->post_type && strpos( $hook, 'unstne-' ) === false && 'dashboard' !== $screen->id ) ) {
            return;
        }

        wp_add_inline_script(
            'jquery',
            '
            jQuery(document).ready(function($) {
                $(document).on("click", ".' . esc_js( $this->prefix ) . '-notice .notice-dismiss", function() {
                    var $notice = $(this).closest(".' . esc_js( $this->prefix ) . '-notice");
                    var noticeType = $notice.data("notice");
                    
                    if (noticeType) {
                        $.ajax({
                            url: ajaxurl,
                            type: "POST",
                            data: {
                                action: "' . esc_js( $this->prefix ) . '_dismiss_notice",
                                notice: noticeType,
                                nonce: "' . esc_js( wp_create_nonce( $this->prefix . '_dismiss_notice' ) ) . '"
                            }
                        });
                    }
                });
            });
            '
        );
    }

    /**
     * Display all active notices.
     */
    public function display_notices() {
        // Welcome/Setup notice (shows once after activation).
        $this->maybe_show_welcome_notice();

        // Upgrade to Pro notice (shows on plugin pages, re-shows after 30 days).
        $this->maybe_show_upgrade_notice();

        // Review request (shows after 7 days of use).
        $this->maybe_show_review_notice();
    }

    /**
     * Welcome/Setup notice after plugin activation.
     */
    private function maybe_show_welcome_notice() {
        // Check if already dismissed.
        if ( get_option( $this->prefix . '_welcome_notice_dismissed' ) ) {
            return;
        }

        // Only show to admins.
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Only show on dashboard and plugin pages.
        $screen = get_current_screen();
        if ( ! $screen || ( 'dashboard' !== $screen->id && 'unstne_newsletter' !== $screen->post_type ) ) {
            return;
        }

        ?>
        <div class="notice notice-success is-dismissible <?php echo esc_attr( $this->prefix ); ?>-notice" data-notice="welcome">
            <p>
                <strong><?php echo esc_html( $this->plugin_name ); ?></strong> —
                <?php esc_html_e( 'Thanks for installing! Get started by adding your first newsletter.', 'unstoppable-newsletters' ); ?>
                <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=unstne_newsletter' ) ); ?>" class="button button-primary" style="margin-left: 10px;">
                    <?php esc_html_e( 'Add Newsletter', 'unstoppable-newsletters' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=unstne_newsletter&page=una-settings' ) ); ?>" class="button" style="margin-left: 5px;">
                    <?php esc_html_e( 'Settings', 'unstoppable-newsletters' ); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Upgrade to Pro notice on plugin settings pages.
     */
    private function maybe_show_upgrade_notice() {
        // Check if Pro is already active.
        if ( defined( 'UNSTNE_PRO_VERSION' ) ) {
            return;
        }

        // Check if dismissed recently (re-show after 30 days).
        $dismissed_date = get_option( $this->prefix . '_upgrade_notice_dismissed_date' );
        if ( $dismissed_date && time() < ( $dismissed_date + ( 30 * DAY_IN_SECONDS ) ) ) {
            return;
        }

        // Only show to admins.
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Only show on plugin settings page (not on all plugin pages).
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'una-settings' ) === false ) {
            return;
        }

        ?>
        <div class="notice notice-info is-dismissible <?php echo esc_attr( $this->prefix ); ?>-notice" data-notice="upgrade">
            <p>
                <strong>🚀 <?php esc_html_e( 'Unlock Pro Features', 'unstoppable-newsletters' ); ?></strong> —
                <?php esc_html_e( 'Get email notifications, subscriber management, and priority support with Pro.', 'unstoppable-newsletters' ); ?>
                <a href="<?php echo esc_url( $this->pro_url ); ?>" target="_blank" class="button button-primary" style="margin-left: 10px;">
                    <?php esc_html_e( 'Learn More', 'unstoppable-newsletters' ); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Review request notice after 7 days.
     */
    private function maybe_show_review_notice() {
        // Check if already dismissed.
        if ( get_option( $this->prefix . '_review_notice_dismissed' ) ) {
            return;
        }

        // Check install date.
        $install_date = get_option( $this->prefix . '_install_date' );
        if ( ! $install_date ) {
            return;
        }

        // Show after 7 days.
        if ( time() < ( $install_date + ( 7 * DAY_IN_SECONDS ) ) ) {
            return;
        }

        // Only show to admins.
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Only show on plugin pages.
        $screen = get_current_screen();
        if ( ! $screen || 'unstne_newsletter' !== $screen->post_type ) {
            return;
        }

        ?>
        <div class="notice notice-info is-dismissible <?php echo esc_attr( $this->prefix ); ?>-notice" data-notice="review">
            <p>
                <?php
                printf(
                    /* translators: %s: Plugin name */
                    esc_html__( 'Enjoying %s? Please consider leaving a review to help others discover the plugin!', 'unstoppable-newsletters' ),
                    '<strong>' . esc_html( $this->plugin_name ) . '</strong>'
                );
                ?>
                <a href="https://wordpress.org/support/plugin/unstoppable-newsletters/reviews/#new-post" target="_blank" class="button button-primary" style="margin-left: 10px;">
                    <?php esc_html_e( 'Leave a Review', 'unstoppable-newsletters' ); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * AJAX handler to dismiss notices.
     */
    public function ajax_dismiss_notice() {
        // Verify nonce.
        check_ajax_referer( $this->prefix . '_dismiss_notice', 'nonce' );

        // Verify permissions.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied.' );
        }

        $notice = isset( $_POST['notice'] ) ? sanitize_key( $_POST['notice'] ) : '';

        if ( empty( $notice ) ) {
            wp_send_json_error( 'Invalid notice.' );
        }

        // Validate notice type.
        $valid_notices = array( 'welcome', 'review', 'upgrade' );
        if ( ! in_array( $notice, $valid_notices, true ) ) {
            wp_send_json_error( 'Invalid notice type.' );
        }

        // Save dismissal.
        update_option( $this->prefix . '_' . $notice . '_notice_dismissed', true );

        // For upgrade notice, save the date so it re-shows after 30 days.
        if ( 'upgrade' === $notice ) {
            update_option( $this->prefix . '_' . $notice . '_notice_dismissed_date', time() );
        }

        wp_send_json_success();
    }

    /**
     * Display flash notices (success/error messages).
     * These are one-time notices triggered by user actions.
     */
    public function display_flash_notices() {
        $notices = get_transient( $this->prefix . '_flash_notices' );

        if ( empty( $notices ) || ! is_array( $notices ) ) {
            return;
        }

        foreach ( $notices as $notice ) {
            $type    = isset( $notice['type'] ) ? $notice['type'] : 'info';
            $message = isset( $notice['message'] ) ? $notice['message'] : '';

            if ( empty( $message ) ) {
                continue;
            }

            $allowed_types = array( 'success', 'error', 'warning', 'info' );
            if ( ! in_array( $type, $allowed_types, true ) ) {
                $type = 'info';
            }

            ?>
            <div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible">
                <p><?php echo wp_kses_post( $message ); ?></p>
            </div>
            <?php
        }

        // Clear flash notices after displaying.
        delete_transient( $this->prefix . '_flash_notices' );
    }

    /**
     * Add a flash notice to be displayed on next page load.
     *
     * @param string $message The notice message.
     * @param string $type    Notice type: success, error, warning, info.
     */
    public static function add_flash_notice( $message, $type = 'success' ) {
        $prefix  = 'unstne';
        $notices = get_transient( $prefix . '_flash_notices' );

        if ( ! is_array( $notices ) ) {
            $notices = array();
        }

        $notices[] = array(
            'message' => $message,
            'type'    => $type,
        );

        set_transient( $prefix . '_flash_notices', $notices, 60 );
    }
}
