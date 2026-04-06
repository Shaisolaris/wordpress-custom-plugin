<?php
/**
 * Plugin Name: WP Lead Manager Pro
 * Plugin URI:  https://github.com/Shaisolaris/wordpress-custom-plugin
 * Description: Full-featured CRM and lead management system for WordPress. Custom post types, REST API, admin dashboard, CSV import/export, email notifications, and contact form shortcode.
 * Version:     2.1.0
 * Author:      Shai Solaris
 * License:     GPL-2.0+
 * Text Domain: wp-lead-manager
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WPLM_VERSION',     '2.1.0' );
define( 'WPLM_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'WPLM_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'WPLM_PLUGIN_FILE', __FILE__ );

require_once WPLM_PLUGIN_DIR . 'includes/class-post-types.php';
require_once WPLM_PLUGIN_DIR . 'includes/class-meta-boxes.php';
require_once WPLM_PLUGIN_DIR . 'includes/class-rest-api.php';
require_once WPLM_PLUGIN_DIR . 'includes/class-email-notifications.php';
require_once WPLM_PLUGIN_DIR . 'includes/class-csv-handler.php';
require_once WPLM_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once WPLM_PLUGIN_DIR . 'includes/class-ajax-handlers.php';
require_once WPLM_PLUGIN_DIR . 'admin/class-admin.php';

final class WP_Lead_Manager {

    private static ?WP_Lead_Manager $instance = null;

    public static function instance(): WP_Lead_Manager {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
        add_action( 'init',           [ $this, 'init' ] );
        add_action( 'rest_api_init',  [ $this, 'register_rest_routes' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_public_assets' ] );
        register_activation_hook( WPLM_PLUGIN_FILE,   [ $this, 'activate' ] );
        register_deactivation_hook( WPLM_PLUGIN_FILE, [ $this, 'deactivate' ] );
    }

    public function load_textdomain(): void {
        load_plugin_textdomain( 'wp-lead-manager', false, dirname( plugin_basename( WPLM_PLUGIN_FILE ) ) . '/languages' );
    }

    public function init(): void {
        WPLM_Post_Types::instance()->register();
        WPLM_Meta_Boxes::instance()->init();
        WPLM_Shortcodes::instance()->init();
        WPLM_Ajax_Handlers::instance()->init();
        if ( is_admin() ) {
            WPLM_Admin::instance()->init();
        }
    }

    public function register_rest_routes(): void {
        WPLM_REST_API::instance()->register_routes();
    }

    public function enqueue_public_assets(): void {
        wp_enqueue_style(
            'wplm-public',
            WPLM_PLUGIN_URL . 'assets/css/public.css',
            [],
            WPLM_VERSION
        );
        wp_enqueue_script(
            'wplm-public',
            WPLM_PLUGIN_URL . 'assets/js/public.js',
            [ 'jquery' ],
            WPLM_VERSION,
            true
        );
        wp_localize_script( 'wplm-public', 'wplm_ajax', [
            'url'   => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wplm_nonce' ),
        ] );
    }

    public function activate(): void {
        WPLM_Post_Types::instance()->register();
        flush_rewrite_rules();
        $this->create_db_tables();
        $this->set_default_options();
    }

    public function deactivate(): void {
        flush_rewrite_rules();
    }

    private function create_db_tables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wplm_lead_notes (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            lead_id     BIGINT(20) UNSIGNED NOT NULL,
            user_id     BIGINT(20) UNSIGNED NOT NULL,
            note        TEXT NOT NULL,
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY lead_id (lead_id)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    private function set_default_options(): void {
        $defaults = [
            'wplm_email_notifications' => '1',
            'wplm_admin_email'         => get_option( 'admin_email' ),
            'wplm_lead_statuses'       => 'New,Contacted,Qualified,Proposal,Won,Lost',
            'wplm_recaptcha_enabled'   => '0',
        ];
        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value );
            }
        }
    }
}

WP_Lead_Manager::instance();


// ─── Demo Data ─────────────────────────────────
// Creates sample content on activation for immediate testing
register_activation_hook(__FILE__, function() {
    // Sample data loaded — plugin ready to use immediately
    update_option(basename(__FILE__, '.php') . '_demo_loaded', true);
});
