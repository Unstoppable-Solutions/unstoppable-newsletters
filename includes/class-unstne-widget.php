<?php
/**
 * Newsletter Signup Widget
 *
 * @package Unstoppable_Newsletters
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Newsletter Signup Widget Class.
 */
class UNSTNE_Signup_Widget extends WP_Widget {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(
            'unstne_signup_widget',
            __( 'Newsletter Signup', 'unstoppable-newsletters' ),
            array(
                'description' => __( 'Email signup form for newsletter subscribers.', 'unstoppable-newsletters' ),
                'classname'   => 'unstne-signup-widget',
            )
        );
    }

    /**
     * Front-end display of widget.
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget( $args, $instance ) {
        $title       = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Subscribe to Our Newsletter', 'unstoppable-newsletters' );
        $description = ! empty( $instance['description'] ) ? $instance['description'] : '';
        $show_name   = ! empty( $instance['show_name'] ) ? true : false;
        $button_text = ! empty( $instance['button_text'] ) ? $instance['button_text'] : __( 'Subscribe', 'unstoppable-newsletters' );

        $settings      = get_option( UNSTNE_OPTION_PREFIX . '_settings', array() );
        $primary_color = $settings['primary_color'] ?? '#2271b1';

        echo $args['before_widget'];

        if ( ! empty( $title ) ) {
            echo $args['before_title'] . esc_html( apply_filters( 'widget_title', $title ) ) . $args['after_title'];
        }
        ?>
        <div class="unstne-widget-form-wrap">
            <?php if ( ! empty( $description ) ) : ?>
                <p class="unstne-widget-description"><?php echo esc_html( $description ); ?></p>
            <?php endif; ?>

            <form class="unstne-subscribe-form" data-widget="true">
                <?php wp_nonce_field( 'unstne_subscribe_nonce', 'unstne_subscribe_nonce' ); ?>
                
                <?php if ( $show_name ) : ?>
                    <p class="unstne-form-field">
                        <input type="text" name="unstne_name" placeholder="<?php esc_attr_e( 'Your Name', 'unstoppable-newsletters' ); ?>" class="unstne-input">
                    </p>
                <?php endif; ?>
                
                <p class="unstne-form-field">
                    <input type="email" name="unstne_email" placeholder="<?php esc_attr_e( 'Your Email', 'unstoppable-newsletters' ); ?>" required class="unstne-input">
                </p>
                
                <p class="unstne-form-field">
                    <button type="submit" class="unstne-subscribe-btn" style="background-color: <?php echo esc_attr( $primary_color ); ?>">
                        <?php echo esc_html( $button_text ); ?>
                    </button>
                </p>
                
                <div class="unstne-form-message" style="display: none;"></div>
            </form>
        </div>
        <?php
        echo $args['after_widget'];
    }

    /**
     * Back-end widget form.
     *
     * @param array $instance Previously saved values from database.
     */
    public function form( $instance ) {
        $title       = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Subscribe to Our Newsletter', 'unstoppable-newsletters' );
        $description = ! empty( $instance['description'] ) ? $instance['description'] : '';
        $show_name   = ! empty( $instance['show_name'] ) ? true : false;
        $button_text = ! empty( $instance['button_text'] ) ? $instance['button_text'] : __( 'Subscribe', 'unstoppable-newsletters' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'unstoppable-newsletters' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'description' ) ); ?>"><?php esc_html_e( 'Description:', 'unstoppable-newsletters' ); ?></label>
            <textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'description' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'description' ) ); ?>" rows="3"><?php echo esc_textarea( $description ); ?></textarea>
        </p>
        <p>
            <input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_name' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_name' ) ); ?>" <?php checked( $show_name ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_name' ) ); ?>"><?php esc_html_e( 'Show name field', 'unstoppable-newsletters' ); ?></label>
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'button_text' ) ); ?>"><?php esc_html_e( 'Button Text:', 'unstoppable-newsletters' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'button_text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'button_text' ) ); ?>" type="text" value="<?php echo esc_attr( $button_text ); ?>">
        </p>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     * @return array Updated safe values.
     */
    public function update( $new_instance, $old_instance ) {
        $instance                = array();
        $instance['title']       = ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['description'] = ! empty( $new_instance['description'] ) ? sanitize_textarea_field( $new_instance['description'] ) : '';
        $instance['show_name']   = ! empty( $new_instance['show_name'] ) ? true : false;
        $instance['button_text'] = ! empty( $new_instance['button_text'] ) ? sanitize_text_field( $new_instance['button_text'] ) : __( 'Subscribe', 'unstoppable-newsletters' );

        return $instance;
    }
}

/**
 * Register the widget.
 */
function unstne_register_widget() {
    register_widget( 'UNSTNE_Signup_Widget' );
}
add_action( 'widgets_init', 'unstne_register_widget' );
