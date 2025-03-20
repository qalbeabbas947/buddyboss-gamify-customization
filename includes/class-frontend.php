<?php
/**
 * Manage frontend hooks
 *
 * Do not allow directly accessing this file.
 */
if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class PSC_Frontend
 */
class PSC_Frontend {

	/**
     * @var self
     */
    private static $instance = null;

    /**
     * @since 1.0
     * @return $this
     */
    public static function instance() {

      if ( is_null( self::$instance ) && ! ( self::$instance instanceof PSC_Frontend ) ) {

          self::$instance = new self;
          self::$instance->hooks();
      }

      return self::$instance;
    }

    /**
     * plugin hooks
     */
    private function hooks() {

		  add_filter( 'bp_get_template_stack', [ $this, 'override_template' ], 10, 1 );
      add_action( 'bb_admin_setting_general_registration_fields', [ $this, 'general_registration_fields' ], 10, 1  );

      add_filter( 'bp_core_get_directory_page_default_titles', [ $this, 'directory_page_default_titles_callback' ], 10, 1 );
      add_filter( 'bp_static_pages', [ $this, 'bp_static_pages_callback' ], 10, 1 );
    }
    /**
     * Override the stream template
     * 
     * @param $sFile
	   * 
     * @return $sFile
     */
    public function directory_page_default_titles_callback( $page_ids ) {
	    
      $page_ids['communityguidlines'] = __( 'Community Guidelines', 'buddyboss' );

      return $page_ids;

    }
    /**
     * Override the stream template
     * 
     * @param $sFile
	   * 
     * @return $sFile
     */
    public function bp_static_pages_callback( $page_ids ) {
	    
      $page_ids['communityguidlines'] = __( 'Community Guidelines', 'buddyboss' );

      return $page_ids;

    }
    /**
     * Override the stream template
     * 
     * @param $sFile
	   * 
     * @return $sFile
     */
    public function general_registration_fields( $obj ) {
      // $sso_args           = array();
      // $obj->add_field(
      //     'bb-enable-community-guidlines',
      //     __( 'Add Community Guidelines checkbox to register form', 'buddyboss' ) . $sso_notice,
      //     array(
      //       $obj,
      //       'bb_admin_setting_callback_enable_sso_registration',
      //     ),
      //     'intval',
      //     $sso_args
      //   );
      
        $args          = array();
        $args['class'] = 'child-no-padding psc-register-community-guidlines-checkbox';
        $obj->add_field( 'bb-enable-community-guidlines', '', [ $this, 'bb_admin_setting_callback_register_show_community_agreement' ], 'intval', $args );

    }
    
    /**
     * Override the stream template
     * 
     * @param $sFile
	 * 
     * @return $sFile
     */
    public function bb_admin_setting_callback_register_show_community_agreement( $stacks ) {
      
      $community_guidelines = apply_filters( 'bb_register_legal_agreement', (bool) bp_get_option( 'register-community-guidlines-agreement', true ) );

      ?>
      <input id="register-community-guidlines-agreement" name="register-community-guidlines-agreement" type="checkbox" value="1" <?php checked( $community_guidelines ); ?> />
      <label for="register-community-guidlines-agreement"><?php esc_html_e( 'Add community guidelines Agreement checkbox to register form', 'buddyboss' ); ?></label>
      <?php
        printf(
          '<p class="description">%s</p>',
          esc_html__( 'Require non-members to explicitly agree to your Community Guidelines before registering.', 'buddyboss' )
        );
    }
    /**
     * Override the stream template
     * 
     * @param $sFile
	 * 
     * @return $sFile
     */
    public function override_template( $stacks ) {
     
      $stacks[0] = BGC_INCLUDES_DIR;
      
      return $stacks;
    }
}

PSC_Frontend::instance();

/**
 * Output a terms of service and privacy policy pages if activated
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_signup_community_guidelines() {

		$terms_link   = '<a class="popup-modal-register popup-terms" href="#terms-modal">' . get_the_title( $terms ) . '</a>';
		?>
    <div class="input-options checkbox-options">
      <div class="bp-checkbox-wrap">
        <input type="checkbox" name="legal_agreement" id="legal_agreement" value="1" class="bs-styled-checkbox">
        <label for="legal_agreement" class="option-label">hello<?php printf( __( 'I agree to the %1$s.', 'buddyboss' ), $terms_link ); ?></label>
      </div>
    </div>

		<div id="terms-modal" class="mfp-hide registration-popup bb-modal">
			<h1><?php echo esc_html( get_the_title( $terms ) ); ?></h1>
			<?php
			$get_terms = get_post( $terms );
			echo apply_filters( 'bp_term_of_service_content', apply_filters( 'the_content', $get_terms->post_content ), $get_terms->post_content );
			?>
			<button title="<?php esc_attr_e( 'Close (Esc)', 'buddyboss' ); ?>" type="button" class="mfp-close"><?php esc_html_e( '×', 'buddyboss' ); ?></button>
		</div>
		
		<?php
	
}