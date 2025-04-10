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

      // try {
      //   remove_all_actions('wp_ajax_nopriv_footballpool_update_team_prediction');
      //   remove_all_actions('wp_ajax_footballpool_update_team_prediction');

      // } catch(Exception $e) {
      //   echo 'Message: ' .$e->getMessage();
      // }

      //add_action( 'wp_ajax_footballpool_update_team_prediction', [ $this, 'update_prediction' ] );    
      
      //add_filter( 'footballpool_score_calc_function_post',  [ $this, 'score_calc_function' ], 999, 2  );

      // add_action( 'init', function(){

      //   global $wpdb;
		  //   $prefix = FOOTBALLPOOL_DB_PREFIX;
      //   $user_id = 1;
      //   $pool = new Football_Pool_Pool( FOOTBALLPOOL_DEFAULT_SEASON );
      //   $matches = $pool->matches;
      //   $user_match_info = $matches->get_match_info_for_user_unfiltered( $user_id );

      //   $total_score = 0;
      //   $full = 0;
      //   $toto = 0;
      //   $goal_bonus = 0;
      //   $goal_diff_bonus = 0;
      //   $joker_used = 0;

      //   foreach ( $user_match_info as $record ) {

      //     // Get user prediction for the current match
      //     $sql = $wpdb->prepare( "SELECT home_score, away_score, has_joker 
      //                 FROM {$prefix}predictions 
      //                 WHERE user_id = %d AND match_id = %d"
      //                 , $user_id, $record['id'] );
      //     $row = $wpdb->get_row( $sql, ARRAY_A );
      //     if ( $row !== null ) {
      //       $user_home = is_numeric( $row['home_score'] ) ? (int) $row['home_score'] : null;
      //       $user_away = is_numeric( $row['away_score'] ) ? (int) $row['away_score'] : null;
      //       $has_joker = (int) $row['has_joker'];
      //     }

      //     $match_score = $pool->calc_score(
      //       (int) $record['home_score'],
      //       (int) $record['away_score'],
      //       $user_home,
      //       $user_away,
      //       (int) $has_joker,
      //       (int) $record['id'],
      //       $user_id
      //     );
      //     print_r($match_score);
      //     $total_score += $match_score['score'];
      //     $full += $match_score['full'];
      //     $toto += $match_score['toto'];
      //     $goal_bonus += $match_score['goal_bonus'];
      //     $goal_diff_bonus += $match_score['goal_diff_bonus'];
      //     $joker_used += (int) $row['has_joker'];
      //   }
      //   //echo '<pre>';
      //   echo '<br>total_score:'.$total_score;
      //   echo '<br>full:'.$full;
      //   echo '<br>toto:'.$toto;
      //   echo '<br>goal_bonus:'.$goal_bonus;
      //   echo '<br>goal_diff_bonus:'.$goal_diff_bonus;
      //   echo '<br>joker_used:'.$joker_used;
      //   //print_r($user_match_info);exit;
      // } );
    }
    
    

    /**
     * process points
     */
    function score_calculation_final(){
        
      global $wpdb;

      $prefix = FOOTBALLPOOL_DB_PREFIX;
      $pool = new Football_Pool_Pool( FOOTBALLPOOL_DEFAULT_SEASON );
      $new_history_table = $pool->get_score_table( false );
      $users = $pool->get_users( 0 );
      foreach ( $users as $user ) {
        $user_id = $user['user_id'];
        $footbal_points = $wpdb->get_var( $wpdb->prepare("SELECT SUM( score ) AS score FROM {$prefix}{$new_history_table} where user_id=%d", $user_id) );
        $user_points = gamipress_get_user_points( $user_id, 'flags' );
        if( intval( $user_points ) < intval( $footbal_points ) ) {
          gamipress_award_points_to_user( $user_id, (intval( $footbal_points ) - intval( $user_points )), 'flags' );
        }
      }
    }
    /**
     * save points to gamipress
     */
    public function score_calc_function( $score, $scoring_vars=[] ) {
      
      $user_id = $scoring_vars[ 'user_id' ];
      if( intval( $user_id ) <= 0 ) {
        $user_id = get_current_user_id();
      }

      if( intval( $user_id ) > 0 ) {
        $user_points = gamipress_get_user_points( $user_id, 'flags' );
        $footbal_points = $score[ 'score' ];
        
        if( intval( $user_points ) < intval( $footbal_points ) ) {

          gamipress_award_points_to_user( $user_id, (intval( $footbal_points ) - intval( $user_points )), 'flags' );
        }
      }

      return $score;
    }
    
    /**
     * save points to gamipress
     */
    public function update_prediction() {
      check_ajax_referer( FOOTBALLPOOL_NONCE_PREDICTION_SAVE, 'fp_match_nonce' );

      $user_id = get_current_user_id();
      $match_id = Football_Pool_Utils::post_int( 'match' );
      $type = Football_Pool_Utils::post_enum( 'type', [ 'home', 'away' ], 'home' ); // home or away
      $prediction = Football_Pool_Utils::post_string( 'prediction' );
  
      // get predictions for this user
      $pool = new Football_Pool_Pool( FOOTBALLPOOL_DEFAULT_SEASON );
      $matches = $pool->matches;
      $user_match_info = $matches->get_match_info_for_user_unfiltered( $user_id );

      $params = array();
      $params['return_code'] = true;
      $params['msg'] = '';
      $params['prev_prediction'] = $user_match_info[ $match_id ]["{$type}_score"] ?? null;

      do_action( 'footballpool_prediction_update_before', $params );

      if ( $user_id <= 0 || ! $pool->user_is_player( $user_id ) ) {
        $params['return_code'] = false;
        $params['msg'] = __( 'Permission denied!1', 'football-pool' );
        Football_Pool_Utils::log_message( $user_id, FOOTBALLPOOL_TYPE_MATCH, $match_id, 0, $params['msg'] );
      } else {
        if ( ! isset( $user_match_info[ $match_id ] ) || ! $user_match_info[ $match_id ]['match_is_editable'] ) {
          $params['return_code'] = false;
          $params['msg'] = __( 'Changing this prediction is not allowed.1', 'football-pool' );
          Football_Pool_Utils::log_message( $user_id, FOOTBALLPOOL_TYPE_MATCH, $match_id, 0, $params['msg'] );
        } else {
          // Good to go, let's save the prediction.
          global $wpdb;
          $prefix = FOOTBALLPOOL_DB_PREFIX;

          if ( $prediction === '' || ! is_numeric( $prediction ) ) {
            if ( $user_match_info[ $match_id ]['has_prediction'] === true ) {
              // prediction exists, so update it
              $sql = $wpdb->prepare(
                "UPDATE {$prefix}predictions SET {$type}_score = NULL 
                WHERE user_id = %d AND match_id = %d"
                , $user_id, $match_id
              );
            } else {
              // no values yet, so we can safely insert null for both scores
              $sql = $wpdb->prepare(
                "INSERT INTO {$prefix}predictions ( user_id, match_id, home_score, away_score, has_joker ) 
                VALUES ( %d, %d, NULL, NULL, 0 )" 
                , $user_id, $match_id
              );
            }
          } else {
            $prediction = (int) $prediction;

            if ( $user_match_info[ $match_id ]['has_prediction'] === true ) {
              $sql = $wpdb->prepare(
                "UPDATE {$prefix}predictions SET {$type}_score = %d
                WHERE user_id = %d AND match_id = %d"
                , $prediction, $user_id, $match_id
              );
            } else {
              // determine which value to set
              if ( $type === 'home' ) {
                $values = "{$prediction}, NULL";
              } else {
                $values = "NULL, {$prediction}";
              }

              $sql = $wpdb->prepare(
                "INSERT INTO {$prefix}predictions ( user_id, match_id, home_score, away_score, has_joker ) 
                VALUES ( %d, %d, {$values}, 0 )"
                , $user_id, $match_id
              );
            }
          }

          $result = $wpdb->query( $sql );
          if ( $result === false ) {
            $params['return_code'] = false;
            $params['msg'] = __( 'Something went wrong while saving the prediction.1', 'football-pool' );
            Football_Pool_Utils::log_error_message( $user_id, FOOTBALLPOOL_TYPE_MATCH, $match_id );
          } else { 
              $match = $pool->matches->all_matches[ $match_id ];
              $match = "{$match['home_team']}-{$match['away_team']}";
              Football_Pool_Utils::log_message( $user_id, FOOTBALLPOOL_TYPE_MATCH, $match_id, 1, "Match {$match_id}: {$match} {$type} value '{$prediction}' saved."
            );
          }
        }
      }

      $params = apply_filters( 'footballpool_prediction_params', $params, $user_match_info );
      do_action( 'footballpool_prediction_update_after', $params );

      // return the result
      Football_Pool_Utils::ajax_response( $params );
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
        $users = get_users( );
        foreach ( $users as $user ) {
          $user_points = gamipress_get_user_points( $user->ID, 'flags' );
          $sql = "SELECT sum(points) as points FROM {$prefix}bonusquestions_useranswers where correct = 1 and user_id='".$user->ID."'";
          $footbal_points = $wpdb->get_var( $sql );
          
          if( intval( $user_points ) < intval( $footbal_points ) ) {
            gamipress_award_points_to_user( $user->ID, (intval( $footbal_points ) - intval( $user_points )), 'flags' );
          }
        }
      }
    }

    /**
     * Add front end script file
     */
    public function theme_scripts() {

        
        wp_enqueue_script( 'psc-front-stripe-js', BGC_ASSETS_URL.'js/front.js', ['jquery'], time(), true );

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

