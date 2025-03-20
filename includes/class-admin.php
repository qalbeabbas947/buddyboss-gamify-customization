<?php
/**
 * Manage frontend hooks
 *
 * Do not allow directly accessing this file.
 */
if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class PSC_Admin
 */
class PSC_Admin {

	/**
     * @var self
     */
    private static $instance = null;

    /**
     * @since 1.0
     * @return $this
     */
    public static function instance() {

      if ( is_null( self::$instance ) && ! ( self::$instance instanceof PSC_Admin ) ) {

          self::$instance = new self;
          self::$instance->hooks();
      }

      return self::$instance;
    }

    /**
     * plugin hooks
     */
    private function hooks() {

		add_action( 'show_user_profile', [ $this, 'user_profile_fields' ], 10, 1 );
        add_action( 'edit_user_profile', [ $this, 'user_profile_fields' ], 10, 1 );

        add_action( 'personal_options_update', [ $this, 'save_extra_user_profile_fields' ], 10, 1 );
        add_action( 'edit_user_profile_update', [ $this, 'save_extra_user_profile_fields' ], 10, 1 );

        add_filter( 'authenticate', [ $this, 'block_banned_user' ], 999, 3 );
    }
    
    
    /**
     * Add a custom condition to the login process.
     *
     * @param WP_User|WP_Error $user  The user object or WP_Error if an error occurred.
     * @param string $username The username entered by the user.
     * @param string $password The password entered by the user.
     *
     * @return WP_User|WP_Error|null
     */
    public function block_banned_user( $user, $username, $password ) {
        
        // Add your custom condition here.
        if( $user ) {
            $user_id = $user->ID;
            $user_login_status = get_the_author_meta( 'psc_user_login_status', $user_id );
            if( $user_login_status == 'permanent_ban' ) {
                return new WP_Error( 'block_banned_user', 'Your account has been suspended.' );
            }
        }
        
        // If the condition is met, allow the login.
        return $user;
    }


    /**
     * Override the stream template
     * 
     * @param $sFile
	   * 
     * @return $sFile
     */
    public function user_profile_fields( $user ) { 
        $user_login_status = get_the_author_meta( 'psc_user_login_status', $user->ID );
        ?>
            <h3><?php _e("Warning System for Violations", "bgc-customization"); ?></h3>

            <table class="form-table">
                <tr>
                    <th><label for="psc_user_login_status"><?php _e("Select User Status", "bgc-customization"); ?>:</label></th>
                    <td>
                        <select  name="psc_user_login_status" id="psc_user_login_status" class="regular-text">
                            <option value=""><?php _e("No Warning"); ?></option>
                            <option value="yellow_card" <?php echo trim( $user_login_status ) == 'yellow_card'?'selected':''; ?>><?php _e("Yellow Card"); ?></option>
                            <option value="permanent_ban" <?php echo trim( $user_login_status ) == 'permanent_ban'?'selected':''; ?>><?php _e("Permanent Ban"); ?></option>
                        </select>
                        <p class="description"><?php _e("A yellow card serves as a first warning. If a permanent ban is issued, the user will be unable to log in.", "bgc-customization"); ?></p>
                    </td>
                </tr>
            
            </table>
        <?php 
    }

    /**
     * Override the stream template
     * 
     * @param $sFile
	   * 
     * @return $sFile
     */
    function save_extra_user_profile_fields( $user_id ) {
        if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'update-user_' . $user_id ) ) {
            return;
        }
        
        if ( !current_user_can( 'edit_user', $user_id ) ) { 
            return false; 
        }
        
        update_user_meta( $user_id, 'psc_user_login_status', sanitize_text_field( $_POST['psc_user_login_status'] ) );
    }
}

PSC_Admin::instance();