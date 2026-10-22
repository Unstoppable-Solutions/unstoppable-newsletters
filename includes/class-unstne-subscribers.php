<?php
/**
 * Subscribers Management Class
 *
 * Handles subscriber storage, management, and admin display.
 *
 * @package Unstoppable_Newsletters
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Subscribers Class.
 */
class UNSTNE_Subscribers {

    /**
     * Database table name.
     *
     * @var string
     */
    private $table_name;

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'unstne_subscribers';
        
        $this->init_hooks();
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        // AJAX handlers.
        add_action( 'wp_ajax_unstne_subscribe', array( $this, 'ajax_subscribe' ) );
        add_action( 'wp_ajax_nopriv_unstne_subscribe', array( $this, 'ajax_subscribe' ) );
        add_action( 'wp_ajax_unstne_delete_subscriber', array( $this, 'ajax_delete_subscriber' ) );
        add_action( 'wp_ajax_unstne_export_subscribers', array( $this, 'ajax_export_csv' ) );
    }

    /**
     * Create database table.
     */
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'unstne_subscribers';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            name varchar(255) DEFAULT '',
            status varchar(20) DEFAULT 'active',
            subscribed_at datetime DEFAULT CURRENT_TIMESTAMP,
            unsubscribed_at datetime DEFAULT NULL,
            ip_address varchar(100) DEFAULT '',
            PRIMARY KEY  (id),
            UNIQUE KEY email (email),
            KEY status (status)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Add a subscriber.
     *
     * @param string $email Subscriber email.
     * @param string $name  Subscriber name.
     * @return int|WP_Error Subscriber ID or error.
     */
    public function add_subscriber( $email, $name = '' ) {
        global $wpdb;

        $email = sanitize_email( $email );
        $name  = sanitize_text_field( $name );

        if ( ! is_email( $email ) ) {
            return new WP_Error( 'invalid_email', __( 'Please enter a valid email address.', 'unstoppable-newsletters' ) );
        }

        // Check if already subscribed.
        $existing = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE email = %s", $email )
        );

        if ( $existing ) {
            if ( 'active' === $existing->status ) {
                return new WP_Error( 'already_subscribed', __( 'This email is already subscribed.', 'unstoppable-newsletters' ) );
            }
            // Reactivate if previously unsubscribed.
            $wpdb->update(
                $this->table_name,
                array(
                    'status'          => 'active',
                    'name'            => $name,
                    'subscribed_at'   => current_time( 'mysql' ),
                    'unsubscribed_at' => null,
                ),
                array( 'id' => $existing->id ),
                array( '%s', '%s', '%s', '%s' ),
                array( '%d' )
            );
            return $existing->id;
        }

        // Insert new subscriber.
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'email'         => $email,
                'name'          => $name,
                'status'        => 'active',
                'subscribed_at' => current_time( 'mysql' ),
                'ip_address'    => $this->get_client_ip(),
            ),
            array( '%s', '%s', '%s', '%s', '%s' )
        );

        if ( false === $result ) {
            return new WP_Error( 'db_error', __( 'Could not save subscription. Please try again.', 'unstoppable-newsletters' ) );
        }

        return $wpdb->insert_id;
    }

    /**
     * Get client IP address.
     *
     * @return string IP address.
     */
    private function get_client_ip() {
        $ip = '';
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
        } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }
        return $ip;
    }

    /**
     * Unsubscribe by email.
     *
     * @param string $email Subscriber email.
     * @return bool Success.
     */
    public function unsubscribe( $email ) {
        global $wpdb;

        $email = sanitize_email( $email );

        return $wpdb->update(
            $this->table_name,
            array(
                'status'          => 'unsubscribed',
                'unsubscribed_at' => current_time( 'mysql' ),
            ),
            array( 'email' => $email ),
            array( '%s', '%s' ),
            array( '%s' )
        );
    }

    /**
     * Delete subscriber.
     *
     * @param int $id Subscriber ID.
     * @return bool Success.
     */
    public function delete_subscriber( $id ) {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            array( 'id' => absint( $id ) ),
            array( '%d' )
        );
    }

    /**
     * Get subscribers.
     *
     * @param array $args Query arguments.
     * @return array Subscribers.
     */
    public function get_subscribers( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'status'   => 'active',
            'per_page' => 20,
            'page'     => 1,
            'orderby'  => 'subscribed_at',
            'order'    => 'DESC',
            'search'   => '',
        );

        $args   = wp_parse_args( $args, $defaults );
        $offset = ( $args['page'] - 1 ) * $args['per_page'];

        $where = array( '1=1' );
        $values = array();

        if ( ! empty( $args['status'] ) && 'all' !== $args['status'] ) {
            $where[]  = 'status = %s';
            $values[] = $args['status'];
        }

        if ( ! empty( $args['search'] ) ) {
            $where[]  = '(email LIKE %s OR name LIKE %s)';
            $search   = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $values[] = $search;
            $values[] = $search;
        }

        $where_clause = implode( ' AND ', $where );

        $orderby = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );
        if ( ! $orderby ) {
            $orderby = 'subscribed_at DESC';
        }

        $sql = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$orderby} LIMIT %d OFFSET %d";
        $values[] = $args['per_page'];
        $values[] = $offset;

        if ( count( $values ) > 2 ) {
            $results = $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
        } else {
            $results = $wpdb->get_results( $wpdb->prepare( $sql, $args['per_page'], $offset ) );
        }

        return $results;
    }

    /**
     * Get total subscriber count.
     *
     * @param string $status Status filter.
     * @return int Count.
     */
    public function get_count( $status = 'active' ) {
        global $wpdb;

        if ( 'all' === $status ) {
            return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
        }

        return (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table_name} WHERE status = %s", $status )
        );
    }

    /**
     * AJAX handler for subscribe.
     */
    public function ajax_subscribe() {
        check_ajax_referer( 'unstne_subscribe_nonce', 'nonce' );

        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';

        $result = $this->add_subscriber( $email, $name );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array( 'message' => __( 'Thank you for subscribing!', 'unstoppable-newsletters' ) ) );
    }

    /**
     * AJAX handler for delete subscriber.
     */
    public function ajax_delete_subscriber() {
        check_ajax_referer( 'unstne_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'unstoppable-newsletters' ) ) );
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid subscriber ID.', 'unstoppable-newsletters' ) ) );
        }

        $this->delete_subscriber( $id );

        wp_send_json_success( array( 'message' => __( 'Subscriber deleted.', 'unstoppable-newsletters' ) ) );
    }

    /**
     * AJAX handler for CSV export.
     */
    public function ajax_export_csv() {
        check_ajax_referer( 'unstne_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Permission denied.' );
        }

        $subscribers = $this->get_subscribers( array(
            'status'   => 'all',
            'per_page' => 99999,
        ) );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=newsletter-subscribers-' . gmdate( 'Y-m-d' ) . '.csv' );

        $output = fopen( 'php://output', 'w' );

        // Header row.
        fputcsv( $output, array( 'Email', 'Name', 'Status', 'Subscribed Date', 'Unsubscribed Date' ) );

        // Data rows.
        foreach ( $subscribers as $sub ) {
            fputcsv( $output, array(
                $sub->email,
                $sub->name,
                $sub->status,
                $sub->subscribed_at,
                $sub->unsubscribed_at,
            ) );
        }

        fclose( $output );
        exit;
    }

    /**
     * Render admin subscribers page.
     */
    public function render_admin_page() {
        $current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $status       = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'active';
        $search       = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

        $subscribers = $this->get_subscribers( array(
            'status'   => $status,
            'page'     => $current_page,
            'per_page' => 20,
            'search'   => $search,
        ) );

        $total_active       = $this->get_count( 'active' );
        $total_unsubscribed = $this->get_count( 'unsubscribed' );
        $total_all          = $this->get_count( 'all' );

        $total_items = 'active' === $status ? $total_active : ( 'unsubscribed' === $status ? $total_unsubscribed : $total_all );
        $total_pages = ceil( $total_items / 20 );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Newsletter Subscribers', 'unstoppable-newsletters' ); ?></h1>
            
            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=unstne_export_subscribers' ), 'unstne_admin_nonce', 'nonce' ) ); ?>" class="page-title-action">
                <?php esc_html_e( 'Export CSV', 'unstoppable-newsletters' ); ?>
            </a>

            <hr class="wp-header-end">

            <ul class="subsubsub">
                <li>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=unstne-subscribers&status=all' ) ); ?>" <?php echo 'all' === $status ? 'class="current"' : ''; ?>>
                        <?php esc_html_e( 'All', 'unstoppable-newsletters' ); ?> <span class="count">(<?php echo esc_html( $total_all ); ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=unstne-subscribers&status=active' ) ); ?>" <?php echo 'active' === $status ? 'class="current"' : ''; ?>>
                        <?php esc_html_e( 'Active', 'unstoppable-newsletters' ); ?> <span class="count">(<?php echo esc_html( $total_active ); ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=unstne-subscribers&status=unsubscribed' ) ); ?>" <?php echo 'unsubscribed' === $status ? 'class="current"' : ''; ?>>
                        <?php esc_html_e( 'Unsubscribed', 'unstoppable-newsletters' ); ?> <span class="count">(<?php echo esc_html( $total_unsubscribed ); ?>)</span>
                    </a>
                </li>
            </ul>

            <form method="get">
                <input type="hidden" name="page" value="unstne-subscribers">
                <input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>">
                <p class="search-box">
                    <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search subscribers...', 'unstoppable-newsletters' ); ?>">
                    <input type="submit" class="button" value="<?php esc_attr_e( 'Search', 'unstoppable-newsletters' ); ?>">
                </p>
            </form>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="column-email"><?php esc_html_e( 'Email', 'unstoppable-newsletters' ); ?></th>
                        <th scope="col" class="column-name"><?php esc_html_e( 'Name', 'unstoppable-newsletters' ); ?></th>
                        <th scope="col" class="column-status"><?php esc_html_e( 'Status', 'unstoppable-newsletters' ); ?></th>
                        <th scope="col" class="column-date"><?php esc_html_e( 'Date', 'unstoppable-newsletters' ); ?></th>
                        <th scope="col" class="column-actions"><?php esc_html_e( 'Actions', 'unstoppable-newsletters' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $subscribers ) ) : ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e( 'No subscribers found.', 'unstoppable-newsletters' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $subscribers as $sub ) : ?>
                            <tr data-id="<?php echo esc_attr( $sub->id ); ?>">
                                <td class="column-email">
                                    <strong><?php echo esc_html( $sub->email ); ?></strong>
                                </td>
                                <td class="column-name"><?php echo esc_html( $sub->name ); ?></td>
                                <td class="column-status">
                                    <span class="unstne-status-<?php echo esc_attr( $sub->status ); ?>">
                                        <?php echo esc_html( ucfirst( $sub->status ) ); ?>
                                    </span>
                                </td>
                                <td class="column-date">
                                    <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $sub->subscribed_at ) ) ); ?>
                                </td>
                                <td class="column-actions">
                                    <button type="button" class="button button-small unstne-delete-subscriber" data-id="<?php echo esc_attr( $sub->id ); ?>">
                                        <?php esc_html_e( 'Delete', 'unstoppable-newsletters' ); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ( $total_pages > 1 ) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links( array(
                            'base'      => add_query_arg( 'paged', '%#%' ),
                            'format'    => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total'     => $total_pages,
                            'current'   => $current_page,
                        ) );
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.unstne-delete-subscriber').on('click', function() {
                if (!confirm('<?php esc_html_e( 'Are you sure you want to delete this subscriber?', 'unstoppable-newsletters' ); ?>')) {
                    return;
                }
                
                var $button = $(this);
                var id = $button.data('id');
                
                $.post(ajaxurl, {
                    action: 'unstne_delete_subscriber',
                    id: id,
                    nonce: '<?php echo esc_js( wp_create_nonce( 'unstne_admin_nonce' ) ); ?>'
                }, function(response) {
                    if (response.success) {
                        $button.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data.message);
                    }
                });
            });
        });
        </script>

        <style>
            .unstne-status-active { color: #46b450; font-weight: 600; }
            .unstne-status-unsubscribed { color: #dc3232; }
            .column-email { width: 30%; }
            .column-name { width: 20%; }
            .column-status { width: 15%; }
            .column-date { width: 20%; }
            .column-actions { width: 15%; }
        </style>
        <?php
    }
}
