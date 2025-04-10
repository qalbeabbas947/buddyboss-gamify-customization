<?php
/**
 * Plugin Name: Buddyboss Gamify Customization
 * Description: This add-on is used to customize the gamify and buddyboss. 
 * Version: 1.0.0
 * Author: ldninjas
 * Author URI: ldninjas.com
 * Text Domain: bgc-customization
 */

 if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Buddyboss_Gamify_Customization_Addon
 */
class Buddyboss_Gamify_Customization_Addon {

    /**
     * @var VERSION
     */
    const VERSION = '1.0.0';

    /**
     * @var self
     */
    private static $instance = null;

    /**
     * @since 1.0
     * @return $this
     */
    public static function instance() {

        if ( is_null( self::$instance ) && ! ( self::$instance instanceof Buddyboss_Gamify_Customization_Addon ) ) {

            self::$instance = new self;
            self::$instance->setup_constants();
            self::$instance->includes();
            self::$instance->enable_text_domain();
        }

        return self::$instance;
    } 

    /**
     * enable text domain
     */
    public function enable_text_domain() {

        add_action( 'init', [ $this, 'bgc_enable_text_domain' ] );
    }

    /**
     * callback function of bgc_enable_text_domain
     */
    public function bgc_enable_text_domain() {

        load_plugin_textdomain( 'bgc-customization' );
    }

    /**
     * Plugin Constants
     */
    private function setup_constants() {

        /**
         * Directory
        */
        define( 'BGC_DIR', plugin_dir_path ( __FILE__ ) );
        define( 'BGC_DIR_FILE', BGC_DIR . basename ( __FILE__ ) );
        define( 'BGC_INCLUDES_DIR', trailingslashit ( BGC_DIR . 'includes' ) );
        define( 'BGC_BASE_DIR', plugin_basename(__FILE__));

        /**
         * URLs
        */
        define( 'BGC_URL', trailingslashit ( plugins_url ( '', __FILE__ ) ) );
        define( 'BGC_ASSETS_URL', trailingslashit ( BGC_URL . 'assets' ) );

        /**
         * Version
         */
        define( 'BGC_VERSION', self::VERSION );
    }

    /**
     * Plugin requiered files
     */
    private function includes() {

        if( file_exists( BGC_INCLUDES_DIR.'class-frontend.php' ) ) {
            require_once BGC_INCLUDES_DIR . 'class-frontend.php';
        }

        if( file_exists( BGC_INCLUDES_DIR.'class-admin.php' ) ) {
            require_once BGC_INCLUDES_DIR . 'class-admin.php';
        }
    }
}

/**
 * @return bool
 */
function BGC_Instance() {

    return Buddyboss_Gamify_Customization_Addon::instance();
}
add_action( 'plugins_loaded', 'BGC_Instance' );