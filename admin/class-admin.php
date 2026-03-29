<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPLM_Admin {
    private static ?WPLM_Admin $instance = null;
    public static function instance(): WPLM_Admin {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    public function init(): void {
        add_action( 'admin_menu',            [ $this, 'add_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'admin_post_wplm_export', [ WPLM_CSV_Handler::instance(), 'export' ] );
        add_filter( 'dashboard_glance_items', [ $this, 'add_glance_items' ] );
    }

    public function add_menu(): void {
        add_menu_page(
            __( 'Lead Manager', 'wp-lead-manager' ),
            __( 'Lead Manager', 'wp-lead-manager' ),
            'edit_posts',
            'wplm-dashboard',
            [ $this, 'render_dashboard' ],
            'dashicons-businessman',
            25
        );
        add_submenu_page( 'wplm-dashboard', __( 'All Leads', 'wp-lead-manager' ),  __( 'All Leads', 'wp-lead-manager' ),  'edit_posts', 'edit.php?post_type=wplm_lead' );
        add_submenu_page( 'wplm-dashboard', __( 'Add Lead', 'wp-lead-manager' ),   __( 'Add Lead', 'wp-lead-manager' ),   'edit_posts', 'post-new.php?post_type=wplm_lead' );
        add_submenu_page( 'wplm-dashboard', __( 'Settings', 'wp-lead-manager' ),   __( 'Settings', 'wp-lead-manager' ),   'manage_options', 'wplm-settings', [ $this, 'render_settings' ] );
    }

    public function render_dashboard(): void {
        $counts      = wp_count_posts( 'wplm_lead' );
        $total_leads = (int) ( $counts->publish ?? 0 );
        global $wpdb;
        $total_value = (float) $wpdb->get_var( "SELECT SUM(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = 'wplm_value'" );
        $recent      = get_posts( [ 'post_type' => 'wplm_lead', 'posts_per_page' => 10, 'post_status' => 'publish' ] );
        include WPLM_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    public function render_settings(): void {
        if ( isset( $_POST['wplm_save_settings'] ) && check_admin_referer( 'wplm_settings' ) ) {
            update_option( 'wplm_email_notifications', isset( $_POST['wplm_email_notifications'] ) ? '1' : '0' );
            update_option( 'wplm_admin_email', sanitize_email( wp_unslash( $_POST['wplm_admin_email'] ?? '' ) ) );
            update_option( 'wplm_lead_statuses', sanitize_text_field( wp_unslash( $_POST['wplm_lead_statuses'] ?? '' ) ) );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'wp-lead-manager' ) . '</p></div>';
        }
        include WPLM_PLUGIN_DIR . 'admin/views/settings.php';
    }

    public function enqueue_assets( string $hook ): void {
        if ( ! in_array( $hook, [ 'toplevel_page_wplm-dashboard', 'lead-manager_page_wplm-settings', 'post.php', 'post-new.php' ], true ) ) return;
        wp_enqueue_style( 'wplm-admin', WPLM_PLUGIN_URL . 'assets/css/admin.css', [], WPLM_VERSION );
        wp_enqueue_script( 'wplm-admin', WPLM_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery', 'wp-api' ], WPLM_VERSION, true );
        wp_localize_script( 'wplm-admin', 'wplm', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wplm_nonce' ),
            'i18n'     => [ 'confirm_delete' => __( 'Delete this lead?', 'wp-lead-manager' ) ],
        ] );
    }

    public function add_glance_items( array $items ): array {
        $count   = wp_count_posts( 'wplm_lead' );
        $total   = (int) ( $count->publish ?? 0 );
        $items[] = sprintf( '<a href="%s">%d %s</a>', admin_url( 'edit.php?post_type=wplm_lead' ), $total, __( 'Leads', 'wp-lead-manager' ) );
        return $items;
    }
}
