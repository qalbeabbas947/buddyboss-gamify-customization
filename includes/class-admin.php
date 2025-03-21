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

        add_action( 'admin_init', [ $this, 'add_custom_page_dropdown_to_bp_settings' ], 9999, 0 );
        add_action( 'bp_directory_pages', [ $this, 'over_directory_pages' ] , 0, 1 );
    }

    /**
     * Add community guidelines page selection field
     *
     * @param $directory_pages
     *
     * @return $directory_pages
     */
    public function over_directory_pages($directory_pages){

        $directory_pages['community_guidelines'] = __( 'Custom Page Dropdown', 'bgc-customization' );

        return $directory_pages;
    }

    /**
     * Add a custom community guidelines field.
     *
     * @return null
     */
    public function add_custom_page_dropdown_to_bp_settings() {
        
        // Register the custom setting so that it can be saved
        $existing_pages  = bp_core_get_directory_page_ids();
        $selected_page = get_option( 'bp-pages' );
        $name = 'community_guidelines';

        $existing_pages[ $name ] = $selected_page[ $name ];
        
        $description = __( 'This page will be displayed in community guidelines popup on the frontend', 'bgc-customization' );
        $label = __( 'Community Guidelines', 'bgc-customization' );
        register_setting( 'bp-pages', $name );
        
        // Add the custom dropdown field to the BuddyPress General settings page
        add_settings_field(
            $name,        // Field ID
            $label,         // Field Label
            'bp_admin_setting_callback_page_directory_dropdown', // Callback function
            'bp-pages',                  // Settings page
            'bp_pages',                        // Section
            [ 'existing_pages'=>$existing_pages, 'name'=>$name,  'label' => $label, 'description'=> $description ]
        );
    }


    /**
     * Add a custom condition to the login process.
     *
     * @param string $user
     * @param string $username
     * @param string $password
     *
     * @return WP_User|WP_Error|null
     */
    public function block_banned_user( $user, $username, $password ) {
        
        // Add your custom condition here.
        if( ! is_wp_error( $user ) ) {
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
     * Add the warning dropdown on he admin profile page.
     * 
     * @param $user
	   * 
     * @return null
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
     * save warning field in user meta
     * 
     * @param $user_id
	   * 
     * @return null
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
