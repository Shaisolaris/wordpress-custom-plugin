<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPLM_CSV_Handler {
    private static ?WPLM_CSV_Handler $instance = null;
    public static function instance(): WPLM_CSV_Handler {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    public function export(): void {
        if ( ! current_user_can( 'edit_posts' ) ) wp_die( 'Unauthorized' );
        check_admin_referer( 'wplm_export' );

        $query = new WP_Query( [
            'post_type'      => 'wplm_lead',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ] );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=leads-' . date( 'Y-m-d' ) . '.csv' );
        header( 'Pragma: no-cache' );

        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, [ 'ID', 'Name', 'Email', 'Phone', 'Company', 'Website', 'Status', 'Source', 'Value', 'Priority', 'Close Date', 'Date Created' ] );

        foreach ( $query->posts as $post ) {
            $status = wp_get_post_terms( $post->ID, 'wplm_status' );
            $source = wp_get_post_terms( $post->ID, 'wplm_source' );
            fputcsv( $output, [
                $post->ID,
                $post->post_title,
                get_post_meta( $post->ID, 'wplm_email', true ),
                get_post_meta( $post->ID, 'wplm_phone', true ),
                get_post_meta( $post->ID, 'wplm_company', true ),
                get_post_meta( $post->ID, 'wplm_website', true ),
                ! is_wp_error( $status ) && $status ? $status[0]->name : '',
                ! is_wp_error( $source ) && $source ? $source[0]->name : '',
                get_post_meta( $post->ID, 'wplm_value', true ),
                get_post_meta( $post->ID, 'wplm_priority', true ),
                get_post_meta( $post->ID, 'wplm_close_date', true ),
                $post->post_date,
            ] );
        }
        fclose( $output );
        exit;
    }

    public function import( string $file_path ): array {
        if ( ! current_user_can( 'edit_posts' ) ) return [ 'error' => 'Unauthorized' ];

        $handle  = fopen( $file_path, 'r' );
        $headers = fgetcsv( $handle );
        $created = 0;
        $errors  = 0;

        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            $data = array_combine( $headers, $row );
            if ( empty( $data['Name'] ) ) { $errors++; continue; }

            $post_id = wp_insert_post( [
                'post_type'   => 'wplm_lead',
                'post_title'  => sanitize_text_field( $data['Name'] ),
                'post_status' => 'publish',
            ] );

            if ( ! is_wp_error( $post_id ) ) {
                $meta_map = [ 'Email' => 'wplm_email', 'Phone' => 'wplm_phone', 'Company' => 'wplm_company', 'Value' => 'wplm_value' ];
                foreach ( $meta_map as $col => $meta_key ) {
                    if ( ! empty( $data[ $col ] ) ) update_post_meta( $post_id, $meta_key, sanitize_text_field( $data[ $col ] ) );
                }
                $created++;
            } else {
                $errors++;
            }
        }
        fclose( $handle );
        return [ 'created' => $created, 'errors' => $errors ];
    }
}
