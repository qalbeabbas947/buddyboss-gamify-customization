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
      add_action( 'wp_enqueue_scripts', [ $this, 'theme_scripts' ], 999 );
      add_action( 'admin_init', [ $this, 'save_points_to_gamipress'] );
      add_action('football_pool_score_calculation_final_before',[ $this, 'score_calculation_final'], 999, 0);
      add_filter('bp_notifications_get_notifications_for_user', [ $this, 'format_custom_friend_notification'], 10, 5);
      add_filter( 'bp_notifications_get_registered_components', [ $this, 'register_notifications_component'] );
      add_action('bp_actions', [ $this, 'handle_custom_notification_click']);
      
      add_action( 'activity_loop_start', [ $this, 'activity_loop_start_callback' ] );
      
    }

    /**
     * Format the custom notification
     */
    public function activity_loop_start_callback(){

      $user_id = get_current_user_id();
      $user_name = '';
      $earned_question_points = bp_get_user_meta( $user_id, 'bp_last_earned_question_points', true );
      $earned_points = bp_get_user_meta( $user_id , 'bp_last_earned_points', true );
      $earned_point_type = bp_get_user_meta( $user_id , 'bp_last_earned_point_type', true );
      $points_type = get_page_by_path(BGC_POINT_TYPE, OBJECT, 'points-type'); // Change 'post' to your CPT if needed
      $pt_url = '';
      if ($points_type) {
          $post_id = $points_type->ID;
          $pt_url = get_the_post_thumbnail_url( $post_id, 'medium' );
      }
      
      if( $user_id ) {
        $user_info = get_userdata($user_id);
        if ($user_info) {
            $user_name = $user_info->user_login;
        }
      }
      if( intval( $earned_points ) > 0 ) {
        ?>
          <div class="bgc_activity_loop_points_notices">
            <div class="bgc_points_user_image">
              <img height="50px" src="<?php echo get_avatar_url( $user_id );?>">
            </div>
            <div class="bgc_points_user_description">
              <?php echo sprintf(__( '%s ha guadagnato %d Bandierine', 'buddyboss', 'bgc-customization' ), $user_name,  intval($earned_points)); ?><br>
              <?php echo sprintf(__( '%s earned %d %s', 'buddyboss', 'bgc-customization' ), $user_name, intval($earned_points), $earned_point_type ); ?>
            </div>
          </div> 
          <div class="bgc_activity_loop_preditions_notices">
            <div class="bgc_points_user_image">
              <img height="50px" src="<?php echo $pt_url;?>">
            </div>
            <div class="bgc_points_user_description">
              <?php if( intval( $earned_question_points ) > 0 ) { ?>
                <?php echo sprintf(__( '%d flags for predicting one question correctly', 'buddyboss', 'bgc-customization' ), intval($earned_question_points)); ?><br>
              <?php } ?>
              <?php echo sprintf(__( '%d stars for correct result prediction', 'buddyboss', 'bgc-customization' ), intval($earned_points)); ?>
            </div>
          </div>
        <?php
      }
      
    }

    /**
     * Format the custom notification
     */
    public function format_custom_friend_notification($action, $item_id, $secondary_item_id, $total_items, $format = 'string') {
        if ('flag_points_award_msg' === $action) {
            $initiator_name = bp_core_get_user_displayname($item_id);
            $custom_message = __('%d flag points are awarded', 'textdomain');
            
            // For string format (dropdown)
            if ('string' === $format) {
                return sprintf($custom_message, $item_id);
            } else {
                return [
                    'text' => sprintf($custom_message, $item_id),
                    'link' => bp_core_get_user_domain($item_id) // Link to user's profile
                ];
            }
        }
        
        return $action;
    }

    /**
     * creates the component
     */
    function register_notifications_component( $component_names = array() ) {

        // Force $component_names to be an array
        if ( ! is_array( $component_names ) ) {
            $component_names = array();
        }

        // Add the custom component
        array_push( $component_names, 'flag_points_award' );

        return $component_names;

    }

    /**
     * Handle notification clicks
     */
    public function handle_custom_notification_click() {
        if (bp_is_current_component('custom-friends') && isset($_GET['nid'])) {
            // Mark as read
            bp_notifications_mark_notification((int) $_GET['nid'], false);
            
            // Redirect to friend's profile
            bp_core_redirect(bp_core_get_user_domain((int) $_GET['item_id']));
        }
    }

    /**
     * process points
     */
    public function score_calculation_final(){
        
      global $wpdb;

      $prefix = FOOTBALLPOOL_DB_PREFIX;
      $pool = new Football_Pool_Pool( FOOTBALLPOOL_DEFAULT_SEASON );
      $new_history_table = $pool->get_score_table( false );
      $users = $pool->get_users( 0 );
      foreach ( $users as $user ) {
        $user_id = $user['user_id'];
        $footbal_points = $wpdb->get_var( $wpdb->prepare( "SELECT total_score FROM {$prefix}{$new_history_table} where user_id=%d order by score_date desc", $user_id ) );
        $user_points = gamipress_get_user_points( $user_id, BGC_POINT_TYPE );
        if( intval( $user_points ) < intval( $footbal_points ) ) {
          gamipress_award_points_to_user( $user_id, (intval( $footbal_points ) - intval( $user_points )), BGC_POINT_TYPE );
          
          update_user_meta( $user_id , 'bp_last_earned_points', (intval( $footbal_points ) - intval( $user_points )) );
          update_user_meta( $user_id , 'bp_last_earned_point_type', BGC_POINT_TYPE );

          // Add the notification to the user
          bp_notifications_add_notification([
                  'user_id'           => $user_id,
                  'item_id'           => ( intval( $footbal_points ) - intval( $user_points ) ),
                  'secondary_item_id' => 0,
                  'component_name'    => 'flag_points_award',
                  'component_action'  => 'flag_points_award_msg',
                  'date_notified'     => bp_core_current_time(),
                  'is_new'            => 1
              ] );
        }
      }
    }
    
    /**
     * save points to gamipress
     */
    public function save_points_to_gamipress() {
      
      $search = Football_Pool_Utils::request_str( 's' );

      $question_id = Football_Pool_Utils::request_int( 'item_id', 0 );
      $bulk_ids = Football_Pool_Utils::post_int_array( 'itemcheck' );
      $action = Football_Pool_Utils::request_string( 'action', 'list' );

      if ( count( $bulk_ids ) > 0 && $action === '-1' )
        $action = Football_Pool_Utils::request_string( 'action2', 'list' );

      $search_submit = ( Football_Pool_Utils::post_str( 'search_submit', '' ) !== '' );
      if ( $search_submit ) {
        $action = Football_Pool_Utils::post_str( 'prev_action', 'list' );
      }

      if( $action == 'user-answers-save' ) {
        global $wpdb;

        $prefix = FOOTBALLPOOL_DB_PREFIX;
        $pool = new Football_Pool_Pool( FOOTBALLPOOL_DEFAULT_SEASON );
        $new_history_table = $pool->get_score_table( false );

        $users = get_users( );
        foreach ( $users as $user ) {
          $user_points = gamipress_get_user_points( $user->ID, BGC_POINT_TYPE );
          $sql = "SELECT sum(points) as points FROM {$prefix}bonusquestions_useranswers where correct = 1 and user_id='".$user->ID."'";
          $footbal_points = $wpdb->get_var( $sql );
          $history_points = $wpdb->get_var( $wpdb->prepare("SELECT total_score FROM {$prefix}{$new_history_table} where user_id=%d order by score_date desc", $user->ID) );

          if( intval( $user_points ) < ( intval( $footbal_points ) + intval( $history_points ) ) ) {
            gamipress_award_points_to_user( $user->ID, (( intval( $footbal_points ) + intval( $history_points ) ) - intval( $user_points )), BGC_POINT_TYPE );
            bp_notifications_add_notification([
                  'user_id'           => $user->ID,
                  'item_id'           => (( intval( $footbal_points ) + intval( $history_points ) ) - intval( $user_points )),
                  'secondary_item_id' => 0,
                  'component_name'    => 'flag_points_award',
                  'component_action'  => 'flag_points_award_msg',
                  'date_notified'     => bp_core_current_time(),
                  'is_new'            => 1,
              ]);
            
            $sql = "SELECT points FROM {$prefix}bonusquestions_useranswers where correct = 1 and user_id='".$user->ID."' order by question_id desc";
            $footbal_points = $wpdb->get_var( $sql );
            
            update_user_meta( $user->ID , 'bp_last_earned_question_points', (intval( $footbal_points ) - intval( $user_points )) );
          }
        }
      }
    }

    /**
     * Add front end script file
     */
    public function theme_scripts() {

      wp_enqueue_style( 'psc-front-css', BGC_ASSETS_URL . 'css/front.css',  array(), BGC_VERSION, false);
      wp_enqueue_script( 'psc-front-stripe-js', BGC_ASSETS_URL.'js/front.js', ['jquery'], BGC_VERSION, true );

      wp_localize_script( 'psc-front-stripe-js', 'PSC_VARS', [ 
          'ajaxURL' => admin_url( 'admin-ajax.php' ),
          
      ] );
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
            <input type="checkbox" required name="community_guidelines_agreement" id="community_guidelines_agreement" value="1" class="bs-styled-checkbox">
            <label for="community_guidelines_agreement" class="option-label"><?php printf( __( 'I agree to the %1$s.', 'bgc-customization' ), $community_guidelines_link ); ?></label>
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