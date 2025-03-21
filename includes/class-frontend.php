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

    $selected_page = get_option( 'bp-pages' );
    $name = 'community_guidelines';

    if( is_array($selected_page) && array_key_exists( $name, $selected_page ) && !empty( $selected_page[ 'community_guidelines' ] ) ) {
      $community_guidelines = $selected_page[ 'community_guidelines' ];
      
      $content_post = get_post($community_guidelines);
      $title = $content_post->post_title;
      $content = $content_post->post_content;
      $content = apply_filters('the_content', $content);
      $content = str_replace(']]>', ']]&gt;', $content);
      

      $community_guidelines_link   = '<a class="popup-modal-register popup-terms" href="#terms-modal">' . $title . '</a>';
      ?>
        <div class="input-options checkbox-options">
          <div class="bp-checkbox-wrap">
            <input type="checkbox" name="legal_agreement" id="legal_agreement" value="1" class="bs-styled-checkbox">
            <label for="legal_agreement" class="option-label"><?php printf( __( 'I agree to the %1$s.', 'bgc-customization' ), $community_guidelines_link ); ?></label>
          </div>
        </div>

        <div id="terms-modal" class="mfp-hide registration-popup bb-modal">
          <h1><?php echo esc_html( $title ); ?></h1>
          <?php echo apply_filters( 'bp_community_guidelines_content', apply_filters( 'the_content', $content ), $content );?>
          <button title="<?php esc_attr_e( 'Close (Esc)', 'bgc-customization' ); ?>" type="button" class="mfp-close"><?php esc_html_e( '×', 'bgc-customization' ); ?></button>
        </div>
      
      <?php

    }
    

			
}