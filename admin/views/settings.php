<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
    <h1><?php esc_html_e( 'Lead Manager Settings', 'wp-lead-manager' ); ?></h1>
    <form method="post">
        <?php wp_nonce_field( 'wplm_settings' ); ?>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'Email Notifications', 'wp-lead-manager' ); ?></th>
                <td><label><input type="checkbox" name="wplm_email_notifications" value="1" <?php checked( get_option( 'wplm_email_notifications', '1' ), '1' ); ?> /> <?php esc_html_e( 'Send email when a new lead is received', 'wp-lead-manager' ); ?></label></td>
            </tr>
            <tr>
                <th><label for="wplm_admin_email"><?php esc_html_e( 'Notification Email', 'wp-lead-manager' ); ?></label></th>
                <td><input type="email" id="wplm_admin_email" name="wplm_admin_email" value="<?php echo esc_attr( get_option( 'wplm_admin_email', get_option( 'admin_email' ) ) ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="wplm_lead_statuses"><?php esc_html_e( 'Lead Statuses', 'wp-lead-manager' ); ?></label></th>
                <td>
                    <input type="text" id="wplm_lead_statuses" name="wplm_lead_statuses" value="<?php echo esc_attr( get_option( 'wplm_lead_statuses', 'New,Contacted,Qualified,Proposal,Won,Lost' ) ); ?>" class="large-text" />
                    <p class="description"><?php esc_html_e( 'Comma-separated list of statuses.', 'wp-lead-manager' ); ?></p>
                </td>
            </tr>
        </table>
        <p class="submit"><input type="submit" name="wplm_save_settings" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'wp-lead-manager' ); ?>" /></p>
    </form>
</div>
