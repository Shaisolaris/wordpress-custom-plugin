<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPLM_REST_API {

    private static ?WPLM_REST_API $instance = null;
    public static function instance(): WPLM_REST_API {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private string $namespace = 'wplm/v1';

    public function register_routes(): void {
        register_rest_route( $this->namespace, '/leads', [
            [ 'methods' => WP_REST_Server::READABLE,  'callback' => [ $this, 'get_leads' ],  'permission_callback' => [ $this, 'check_auth' ] ],
            [ 'methods' => WP_REST_Server::CREATABLE,  'callback' => [ $this, 'create_lead' ], 'permission_callback' => [ $this, 'check_auth' ], 'args' => $this->get_lead_args() ],
        ] );

        register_rest_route( $this->namespace, '/leads/(?P<id>[\d]+)', [
            [ 'methods' => WP_REST_Server::READABLE,  'callback' => [ $this, 'get_lead' ],    'permission_callback' => [ $this, 'check_auth' ] ],
            [ 'methods' => WP_REST_Server::EDITABLE,  'callback' => [ $this, 'update_lead' ], 'permission_callback' => [ $this, 'check_auth' ] ],
            [ 'methods' => WP_REST_Server::DELETABLE, 'callback' => [ $this, 'delete_lead' ], 'permission_callback' => [ $this, 'check_auth' ] ],
        ] );

        register_rest_route( $this->namespace, '/leads/(?P<id>[\d]+)/notes', [
            [ 'methods' => WP_REST_Server::READABLE,  'callback' => [ $this, 'get_notes' ],   'permission_callback' => [ $this, 'check_auth' ] ],
            [ 'methods' => WP_REST_Server::CREATABLE, 'callback' => [ $this, 'create_note' ], 'permission_callback' => [ $this, 'check_auth' ] ],
        ] );

        register_rest_route( $this->namespace, '/stats', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_stats' ],
            'permission_callback' => [ $this, 'check_auth' ],
        ] );
    }

    public function check_auth(): bool {
        return current_user_can( 'edit_posts' );
    }

    public function get_leads( WP_REST_Request $request ): WP_REST_Response {
        $args = [
            'post_type'      => 'wplm_lead',
            'posts_per_page' => (int) $request->get_param( 'per_page' ) ?: 20,
            'paged'          => (int) $request->get_param( 'page' ) ?: 1,
            'post_status'    => 'publish',
            'orderby'        => $request->get_param( 'orderby' ) ?: 'date',
            'order'          => $request->get_param( 'order' ) ?: 'DESC',
        ];

        if ( $status = $request->get_param( 'status' ) ) {
            $args['tax_query'] = [ [ 'taxonomy' => 'wplm_status', 'field' => 'slug', 'terms' => $status ] ];
        }

        $query = new WP_Query( $args );
        $leads = array_map( [ $this, 'format_lead' ], $query->posts );

        $response = new WP_REST_Response( $leads, 200 );
        $response->header( 'X-WP-Total',      $query->found_posts );
        $response->header( 'X-WP-TotalPages', $query->max_num_pages );
        return $response;
    }

    public function get_lead( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $post = get_post( (int) $request['id'] );
        if ( ! $post || $post->post_type !== 'wplm_lead' ) return new WP_Error( 'not_found', 'Lead not found', [ 'status' => 404 ] );
        return new WP_REST_Response( $this->format_lead( $post ), 200 );
    }

    public function create_lead( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $post_id = wp_insert_post( [
            'post_type'   => 'wplm_lead',
            'post_title'  => sanitize_text_field( $request->get_param( 'name' ) ),
            'post_status' => 'publish',
        ] );
        if ( is_wp_error( $post_id ) ) return $post_id;

        $this->save_lead_meta( $post_id, $request );
        do_action( 'wplm_lead_created', $post_id );
        return new WP_REST_Response( $this->format_lead( get_post( $post_id ) ), 201 );
    }

    public function update_lead( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $post = get_post( (int) $request['id'] );
        if ( ! $post || $post->post_type !== 'wplm_lead' ) return new WP_Error( 'not_found', 'Lead not found', [ 'status' => 404 ] );

        if ( $name = $request->get_param( 'name' ) ) {
            wp_update_post( [ 'ID' => $post->ID, 'post_title' => sanitize_text_field( $name ) ] );
        }
        $this->save_lead_meta( $post->ID, $request );
        return new WP_REST_Response( $this->format_lead( get_post( $post->ID ) ), 200 );
    }

    public function delete_lead( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $post = get_post( (int) $request['id'] );
        if ( ! $post || $post->post_type !== 'wplm_lead' ) return new WP_Error( 'not_found', 'Lead not found', [ 'status' => 404 ] );
        wp_delete_post( $post->ID, true );
        return new WP_REST_Response( [ 'deleted' => true, 'id' => $post->ID ], 200 );
    }

    public function get_notes( WP_REST_Request $request ): WP_REST_Response {
        global $wpdb;
        $notes = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wplm_lead_notes WHERE lead_id = %d ORDER BY created_at DESC", (int) $request['id'] )
        );
        return new WP_REST_Response( $notes, 200 );
    }

    public function create_note( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $note = sanitize_textarea_field( $request->get_param( 'note' ) );
        if ( ! $note ) return new WP_Error( 'invalid', 'Note content required', [ 'status' => 400 ] );

        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'wplm_lead_notes', [
            'lead_id'    => (int) $request['id'],
            'user_id'    => get_current_user_id(),
            'note'       => $note,
            'created_at' => current_time( 'mysql' ),
        ] );
        return new WP_REST_Response( [ 'id' => $wpdb->insert_id, 'note' => $note ], 201 );
    }

    public function get_stats(): WP_REST_Response {
        $counts = wp_count_posts( 'wplm_lead' );
        $total  = (int) ( $counts->publish ?? 0 );

        $terms = get_terms( [ 'taxonomy' => 'wplm_status', 'hide_empty' => false ] );
        $by_status = [];
        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $by_status[ $term->name ] = $term->count;
            }
        }

        global $wpdb;
        $total_value = (float) $wpdb->get_var(
            "SELECT SUM(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = 'wplm_value'"
        );

        return new WP_REST_Response( [
            'total_leads'  => $total,
            'total_value'  => $total_value,
            'by_status'    => $by_status,
        ], 200 );
    }

    private function format_lead( WP_Post $post ): array {
        $meta_keys = [ 'wplm_email', 'wplm_phone', 'wplm_company', 'wplm_website', 'wplm_value', 'wplm_priority', 'wplm_close_date', 'wplm_assigned_to' ];
        $meta = [];
        foreach ( $meta_keys as $key ) {
            $meta[ str_replace( 'wplm_', '', $key ) ] = get_post_meta( $post->ID, $key, true );
        }

        $status = wp_get_post_terms( $post->ID, 'wplm_status' );
        $source = wp_get_post_terms( $post->ID, 'wplm_source' );

        return array_merge( [
            'id'     => $post->ID,
            'name'   => $post->post_title,
            'status' => ! is_wp_error( $status ) && $status ? $status[0]->name : '',
            'source' => ! is_wp_error( $source ) && $source ? $source[0]->name : '',
            'date'   => $post->post_date,
        ], $meta );
    }

    private function save_lead_meta( int $post_id, WP_REST_Request $request ): void {
        $fields = [ 'email', 'phone', 'company', 'website', 'value', 'priority', 'close_date', 'assigned_to' ];
        foreach ( $fields as $field ) {
            if ( null !== $request->get_param( $field ) ) {
                update_post_meta( $post_id, "wplm_{$field}", sanitize_text_field( $request->get_param( $field ) ) );
            }
        }
        if ( $status = $request->get_param( 'status' ) ) {
            wp_set_post_terms( $post_id, [ $status ], 'wplm_status' );
        }
        if ( $source = $request->get_param( 'source' ) ) {
            wp_set_post_terms( $post_id, [ $source ], 'wplm_source' );
        }
    }

    private function get_lead_args(): array {
        return [
            'name' => [ 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
        ];
    }
}
