<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPLM_Meta_Boxes {

    private static ?WPLM_Meta_Boxes $instance = null;
    public static function instance(): WPLM_Meta_Boxes {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    public function init(): void {
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post_wplm_lead', [ $this, 'save_meta' ], 10, 2 );
    }

    public function add_meta_boxes(): void {
        add_meta_box( 'wplm_contact_details', __( 'Contact Details', 'wp-lead-manager' ), [ $this, 'render_contact_meta_box' ], 'wplm_lead', 'normal', 'high' );
        add_meta_box( 'wplm_lead_details',    __( 'Lead Details', 'wp-lead-manager' ),    [ $this, 'render_lead_meta_box' ],    'wplm_lead', 'normal', 'high' );
        add_meta_box( 'wplm_lead_notes',      __( 'Notes', 'wp-lead-manager' ),           [ $this, 'render_notes_meta_box' ],   'wplm_lead', 'normal', 'default' );
    }

    public function render_contact_meta_box( \WP_Post $post ): void {
        wp_nonce_field( 'wplm_save_meta', 'wplm_nonce' );
        $fields = [
            'wplm_email'   => [ 'label' => 'Email',   'type' => 'email' ],
            'wplm_phone'   => [ 'label' => 'Phone',   'type' => 'tel' ],
            'wplm_company' => [ 'label' => 'Company', 'type' => 'text' ],
            'wplm_website' => [ 'label' => 'Website', 'type' => 'url' ],
        ];
        echo '<table class="form-table"><tbody>';
        foreach ( $fields as $key => $field ) {
            $value = esc_attr( get_post_meta( $post->ID, $key, true ) );
            printf(
                '<tr><th><label for="%1$s">%2$s</label></th><td><input type="%3$s" id="%1$s" name="%1$s" value="%4$s" class="regular-text" /></td></tr>',
                esc_attr( $key ),
                esc_html( $field['label'] ),
                esc_attr( $field['type'] ),
                $value
            );
        }
        echo '</tbody></table>';
    }

    public function render_lead_meta_box( \WP_Post $post ): void {
        $value_field = get_post_meta( $post->ID, 'wplm_value', true );
        $assigned_to = get_post_meta( $post->ID, 'wplm_assigned_to', true );
        $close_date  = get_post_meta( $post->ID, 'wplm_close_date', true );
        $priority    = get_post_meta( $post->ID, 'wplm_priority', true ) ?: 'medium';
        $users       = get_users( [ 'role__in' => [ 'administrator', 'editor', 'author' ] ] );
        ?>
        <table class="form-table"><tbody>
        <tr>
            <th><label for="wplm_value"><?php esc_html_e( 'Deal Value ($)', 'wp-lead-manager' ); ?></label></th>
            <td><input type="number" id="wplm_value" name="wplm_value" value="<?php echo esc_attr( $value_field ); ?>" min="0" step="0.01" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="wplm_priority"><?php esc_html_e( 'Priority', 'wp-lead-manager' ); ?></label></th>
            <td>
                <select id="wplm_priority" name="wplm_priority">
                    <?php foreach ( [ 'low', 'medium', 'high' ] as $p ) : ?>
                        <option value="<?php echo esc_attr( $p ); ?>" <?php selected( $priority, $p ); ?>><?php echo esc_html( ucfirst( $p ) ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="wplm_close_date"><?php esc_html_e( 'Expected Close Date', 'wp-lead-manager' ); ?></label></th>
            <td><input type="date" id="wplm_close_date" name="wplm_close_date" value="<?php echo esc_attr( $close_date ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="wplm_assigned_to"><?php esc_html_e( 'Assigned To', 'wp-lead-manager' ); ?></label></th>
            <td>
                <select id="wplm_assigned_to" name="wplm_assigned_to">
                    <option value=""><?php esc_html_e( '-- Unassigned --', 'wp-lead-manager' ); ?></option>
                    <?php foreach ( $users as $user ) : ?>
                        <option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $assigned_to, $user->ID ); ?>><?php echo esc_html( $user->display_name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        </tbody></table>
        <?php
    }

    public function render_notes_meta_box( \WP_Post $post ): void {
        if ( ! $post->ID ) return;
        global $wpdb;
        $notes = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wplm_lead_notes WHERE lead_id = %d ORDER BY created_at DESC", $post->ID )
        );
        echo '<div id="wplm-notes-list">';
        if ( $notes ) {
            foreach ( $notes as $note ) {
                $user = get_user_by( 'id', $note->user_id );
                printf(
                    '<div class="wplm-note"><strong>%s</strong> <em>%s</em><p>%s</p></div>',
                    esc_html( $user ? $user->display_name : 'Unknown' ),
                    esc_html( $note->created_at ),
                    esc_html( $note->note )
                );
            }
        } else {
            echo '<p>' . esc_html__( 'No notes yet.', 'wp-lead-manager' ) . '</p>';
        }
        echo '</div>';
        echo '<textarea id="wplm-new-note" rows="3" style="width:100%;margin-top:10px;" placeholder="' . esc_attr__( 'Add a note...', 'wp-lead-manager' ) . '"></textarea>';
        echo '<button type="button" id="wplm-add-note" class="button button-secondary" data-lead-id="' . esc_attr( $post->ID ) . '">' . esc_html__( 'Add Note', 'wp-lead-manager' ) . '</button>';
    }

    public function save_meta( int $post_id, \WP_Post $post ): void {
        if ( ! isset( $_POST['wplm_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wplm_nonce'] ) ), 'wplm_save_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $fields = [ 'wplm_email', 'wplm_phone', 'wplm_company', 'wplm_website', 'wplm_value', 'wplm_priority', 'wplm_close_date', 'wplm_assigned_to' ];
        foreach ( $fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
            }
        }
    }
}
