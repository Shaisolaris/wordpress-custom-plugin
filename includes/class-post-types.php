<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPLM_Post_Types {

    private static ?WPLM_Post_Types $instance = null;

    public static function instance(): WPLM_Post_Types {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    public function register(): void {
        $this->register_lead_post_type();
        $this->register_lead_status_taxonomy();
        $this->register_lead_source_taxonomy();
    }

    private function register_lead_post_type(): void {
        $labels = [
            'name'               => __( 'Leads', 'wp-lead-manager' ),
            'singular_name'      => __( 'Lead', 'wp-lead-manager' ),
            'add_new'            => __( 'Add Lead', 'wp-lead-manager' ),
            'add_new_item'       => __( 'Add New Lead', 'wp-lead-manager' ),
            'edit_item'          => __( 'Edit Lead', 'wp-lead-manager' ),
            'search_items'       => __( 'Search Leads', 'wp-lead-manager' ),
            'not_found'          => __( 'No leads found', 'wp-lead-manager' ),
            'menu_name'          => __( 'Lead Manager', 'wp-lead-manager' ),
            'all_items'          => __( 'All Leads', 'wp-lead-manager' ),
        ];

        register_post_type( 'wplm_lead', [
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'show_in_rest'        => true,
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => [ 'title', 'custom-fields' ],
            'menu_icon'           => 'dashicons-businessman',
            'has_archive'         => false,
            'rewrite'             => false,
        ] );
    }

    private function register_lead_status_taxonomy(): void {
        register_taxonomy( 'wplm_status', 'wplm_lead', [
            'label'             => __( 'Status', 'wp-lead-manager' ),
            'public'            => false,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'hierarchical'      => false,
            'show_admin_column' => true,
            'rewrite'           => false,
        ] );
    }

    private function register_lead_source_taxonomy(): void {
        register_taxonomy( 'wplm_source', 'wplm_lead', [
            'label'             => __( 'Source', 'wp-lead-manager' ),
            'public'            => false,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'hierarchical'      => false,
            'show_admin_column' => true,
            'rewrite'           => false,
        ] );
    }
}
