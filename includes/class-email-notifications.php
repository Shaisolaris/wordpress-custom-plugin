<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPLM_Email_Notifications {
    private static ?WPLM_Email_Notifications $instance = null;
    public static function instance(): WPLM_Email_Notifications {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    public function init(): void {
        add_action( 'wplm_lead_created', [ $this, 'notify_admin_new_lead' ] );
    }

    public function notify_admin_new_lead( int $lead_id ): void {
        if ( ! get_option( 'wplm_email_notifications', '1' ) ) return;

        $to      = get_option( 'wplm_admin_email', get_option( 'admin_email' ) );
        $lead    = get_post( $lead_id );
        $subject = sprintf( __( 'New Lead: %s', 'wp-lead-manager' ), $lead->post_title );

        $email   = get_post_meta( $lead_id, 'wplm_email', true );
        $phone   = get_post_meta( $lead_id, 'wplm_phone', true );
        $company = get_post_meta( $lead_id, 'wplm_company', true );

        $message  = "A new lead has been submitted.\n\n";
        $message .= "Name: {$lead->post_title}\n";
        $message .= "Email: {$email}\n";
        $message .= "Phone: {$phone}\n";
        $message .= "Company: {$company}\n\n";
        $message .= 'View lead: ' . admin_url( "post.php?post={$lead_id}&action=edit" );

        wp_mail( $to, $subject, $message );
    }

    public function notify_lead_assigned( int $lead_id, int $user_id ): void {
        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) return;

        $lead    = get_post( $lead_id );
        $subject = sprintf( __( 'Lead Assigned to You: %s', 'wp-lead-manager' ), $lead->post_title );
        $message = "You have been assigned a lead.\n\nName: {$lead->post_title}\n" . admin_url( "post.php?post={$lead_id}&action=edit" );

        wp_mail( $user->user_email, $subject, $message );
    }
}
