<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap wplm-dashboard">
    <h1><?php esc_html_e( 'Lead Manager Dashboard', 'wp-lead-manager' ); ?></h1>
    <div class="wplm-stats-row">
        <div class="wplm-stat-card">
            <span class="wplm-stat-number"><?php echo esc_html( $total_leads ); ?></span>
            <span class="wplm-stat-label"><?php esc_html_e( 'Total Leads', 'wp-lead-manager' ); ?></span>
        </div>
        <div class="wplm-stat-card">
            <span class="wplm-stat-number">$<?php echo esc_html( number_format( $total_value, 2 ) ); ?></span>
            <span class="wplm-stat-label"><?php esc_html_e( 'Pipeline Value', 'wp-lead-manager' ); ?></span>
        </div>
    </div>
    <div class="wplm-actions">
        <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wplm_lead' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Add Lead', 'wp-lead-manager' ); ?></a>
        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wplm_export' ), 'wplm_export' ) ); ?>" class="button"><?php esc_html_e( 'Export CSV', 'wp-lead-manager' ); ?></a>
    </div>
    <h2><?php esc_html_e( 'Recent Leads', 'wp-lead-manager' ); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead><tr>
            <th><?php esc_html_e( 'Name', 'wp-lead-manager' ); ?></th>
            <th><?php esc_html_e( 'Email', 'wp-lead-manager' ); ?></th>
            <th><?php esc_html_e( 'Company', 'wp-lead-manager' ); ?></th>
            <th><?php esc_html_e( 'Status', 'wp-lead-manager' ); ?></th>
            <th><?php esc_html_e( 'Value', 'wp-lead-manager' ); ?></th>
            <th><?php esc_html_e( 'Date', 'wp-lead-manager' ); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ( $recent as $lead ) :
            $status = wp_get_post_terms( $lead->ID, 'wplm_status' );
        ?>
        <tr>
            <td><a href="<?php echo esc_url( get_edit_post_link( $lead->ID ) ); ?>"><?php echo esc_html( $lead->post_title ); ?></a></td>
            <td><?php echo esc_html( get_post_meta( $lead->ID, 'wplm_email', true ) ); ?></td>
            <td><?php echo esc_html( get_post_meta( $lead->ID, 'wplm_company', true ) ); ?></td>
            <td><?php echo esc_html( ! is_wp_error( $status ) && $status ? $status[0]->name : '—' ); ?></td>
            <td>$<?php echo esc_html( number_format( (float) get_post_meta( $lead->ID, 'wplm_value', true ), 2 ) ); ?></td>
            <td><?php echo esc_html( get_the_date( 'M j, Y', $lead ) ); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
