<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPLM_Shortcodes {
    private static ?WPLM_Shortcodes $instance = null;
    public static function instance(): WPLM_Shortcodes {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    public function init(): void {
        add_shortcode( 'wplm_contact_form', [ $this, 'render_contact_form' ] );
        add_shortcode( 'wplm_lead_count',   [ $this, 'render_lead_count' ] );
    }

    public function render_contact_form( array $atts ): string {
        $atts = shortcode_atts( [
            'source'       => 'website',
            'redirect'     => '',
            'button_label' => __( 'Send Message', 'wp-lead-manager' ),
        ], $atts );

        ob_start();
        ?>
        <div class="wplm-contact-form-wrap">
            <form class="wplm-contact-form" novalidate>
                <?php wp_nonce_field( 'wplm_nonce', 'wplm_form_nonce' ); ?>
                <input type="hidden" name="source" value="<?php echo esc_attr( $atts['source'] ); ?>" />
                <input type="hidden" name="redirect" value="<?php echo esc_url( $atts['redirect'] ); ?>" />
                <div class="wplm-field">
                    <label for="wplm_name"><?php esc_html_e( 'Full Name *', 'wp-lead-manager' ); ?></label>
                    <input type="text" id="wplm_name" name="name" required autocomplete="name" />
                </div>
                <div class="wplm-field">
                    <label for="wplm_email"><?php esc_html_e( 'Email Address *', 'wp-lead-manager' ); ?></label>
                    <input type="email" id="wplm_email" name="email" required autocomplete="email" />
                </div>
                <div class="wplm-field">
                    <label for="wplm_phone"><?php esc_html_e( 'Phone', 'wp-lead-manager' ); ?></label>
                    <input type="tel" id="wplm_phone" name="phone" autocomplete="tel" />
                </div>
                <div class="wplm-field">
                    <label for="wplm_company"><?php esc_html_e( 'Company', 'wp-lead-manager' ); ?></label>
                    <input type="text" id="wplm_company" name="company" autocomplete="organization" />
                </div>
                <div class="wplm-field">
                    <label for="wplm_message"><?php esc_html_e( 'Message *', 'wp-lead-manager' ); ?></label>
                    <textarea id="wplm_message" name="message" rows="5" required></textarea>
                </div>
                <div class="wplm-field">
                    <button type="submit" class="wplm-submit-btn"><?php echo esc_html( $atts['button_label'] ); ?></button>
                </div>
                <div class="wplm-form-response" style="display:none;"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_lead_count( array $atts ): string {
        $atts   = shortcode_atts( [ 'status' => '' ], $atts );
        $counts = wp_count_posts( 'wplm_lead' );
        return (string) ( $counts->publish ?? 0 );
    }
}

class WPLM_Ajax_Handlers {
    private static ?WPLM_Ajax_Handlers $instance = null;
    public static function instance(): WPLM_Ajax_Handlers {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    public function init(): void {
        add_action( 'wp_ajax_wplm_submit_lead',        [ $this, 'submit_lead' ] );
        add_action( 'wp_ajax_nopriv_wplm_submit_lead', [ $this, 'submit_lead' ] );
        add_action( 'wp_ajax_wplm_add_note',           [ $this, 'add_note' ] );
        add_action( 'wp_ajax_wplm_update_status',      [ $this, 'update_status' ] );
    }

    public function submit_lead(): void {
        check_ajax_referer( 'wplm_nonce', 'wplm_form_nonce' );

        $name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

        if ( ! $name || ! $email || ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => __( 'Name and valid email are required.', 'wp-lead-manager' ) ] );
        }

        $post_id = wp_insert_post( [
            'post_type'   => 'wplm_lead',
            'post_title'  => $name,
            'post_status' => 'publish',
        ] );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Failed to save lead.', 'wp-lead-manager' ) ] );
        }

        update_post_meta( $post_id, 'wplm_email',   $email );
        update_post_meta( $post_id, 'wplm_phone',   sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ) );
        update_post_meta( $post_id, 'wplm_company', sanitize_text_field( wp_unslash( $_POST['company'] ?? '' ) ) );

        if ( $source = sanitize_text_field( wp_unslash( $_POST['source'] ?? '' ) ) ) {
            wp_set_post_terms( $post_id, [ $source ], 'wplm_source' );
        }

        do_action( 'wplm_lead_created', $post_id );

        $redirect = esc_url_raw( wp_unslash( $_POST['redirect'] ?? '' ) );
        wp_send_json_success( [ 'message' => __( 'Message sent. We will be in touch.', 'wp-lead-manager' ), 'redirect' => $redirect ] );
    }

    public function add_note(): void {
        check_ajax_referer( 'wplm_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error();

        $lead_id = (int) ( $_POST['lead_id'] ?? 0 );
        $note    = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );

        if ( ! $lead_id || ! $note ) wp_send_json_error( [ 'message' => 'Invalid data' ] );

        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'wplm_lead_notes', [
            'lead_id'    => $lead_id,
            'user_id'    => get_current_user_id(),
            'note'       => $note,
            'created_at' => current_time( 'mysql' ),
        ] );

        wp_send_json_success( [ 'id' => $wpdb->insert_id, 'note' => $note, 'user' => wp_get_current_user()->display_name ] );
    }

    public function update_status(): void {
        check_ajax_referer( 'wplm_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error();

        $lead_id = (int) ( $_POST['lead_id'] ?? 0 );
        $status  = sanitize_text_field( wp_unslash( $_POST['status'] ?? '' ) );

        wp_set_post_terms( $lead_id, [ $status ], 'wplm_status' );
        wp_send_json_success();
    }
}
