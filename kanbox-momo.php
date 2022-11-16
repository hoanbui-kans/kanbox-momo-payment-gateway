<?php
/**
* Plugin Name: Kanbox Momo Payment Gateway
* Plugin URI: https://kansite.com.vn
* Description: Simple and easy integration of <a href="https://business.momo.vn/">Momo</a> e-wallet payment with your Woocommerce e-commerce website, developed by <a href="https://kansite.com.vn/">Kan Solution</a> team.
* Author: Kan Solution
* Version: 1.0.0
* Author URI: https://www.facebook.com/hoan.me98
* Text Domain: kanbox
* Domain Path: /languages
* WC requires at least: 3.0
* WC tested up to: 6.0.2
* License:     GPLv2+
*/


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KANBOX_MOMO_DIR', plugin_dir_path( __FILE__ ) );
define( 'KANBOX_MOMO_URL', plugins_url( '/', __FILE__ ) );

/**
 * The main class of the plugin
 *
 * @author   Kan solution
 * @since    1.0
 */

if(!class_exists('Kanbox_Momo_Payment_GateWay')){
    class Kanbox_Momo_Payment_GateWay {
        function __construct()
        {
            add_action( 'init', array( $this, 'init' ) );
        }
        
        function notice_if_not_woocommerce() 
        {
            $class = 'notice notice-warning';
            $message = __( 'Thanh toán với Momo không chạy vì Plugin WooCommerce chưa hoạt động. Vui lòng kích hoạt plugin WooCommerce trước.', 'kanbox' );
            printf( '<div class="%1$s"><p><strong>%2$s</strong></p></div>', $class, $message );
        }

        function notice_if_not_vnd_currency() 
        {
            $class = 'notice notice-warning';
            $message = __( 'Bạn cần sử dụng Đồng Việt Nam <code>(₫)</code> làm đơn vị tiền tệ để sử dụng thanh toán Momo đúng cách.', 'kanbox' );
            printf( '<div class="%1$s"><p><strong>%2$s</strong></p></div>', $class, $message );
        }
            
        /**
        * Language hook
        */
        public function i18n() 
        {
            load_plugin_textdomain( 'kanbox', false, basename( dirname( __FILE__ ) ) . '/languages/' );
        }

        /**
        * Run this method under the "init" action
        */
        public function init() 
        {
            $this->i18n();
            if ( class_exists( 'WooCommerce' ) ) {
                // Run this plugin normally if WooCommerce is active
                $this->main();
            } else {
                // Throw a notice if WooCommerce is NOT active
                add_action( 'admin_notices', array( $this, 'notice_if_not_woocommerce' ) );
            }
        }

        public function main(){
            if( class_exists('WC_Payment_Gateway')) {
                require_once ( KANBOX_MOMO_DIR . 'inc/class-admin-page.php' );
                if('VND' == get_woocommerce_currency()){

                    require ( KANBOX_MOMO_DIR . 'inc/class-payment-momo.php' );
                    require ( KANBOX_MOMO_DIR . 'inc/class-user-dashboard.php' );

                    add_filter( 'woocommerce_payment_gateways', function ( $gateways ) {
                        $gateways[] = 'Kanbox_Momo_Payment_GateWay_Controller'; // your class name is here
                        return $gateways;
                    } );

                } else {
                    add_action( 'admin_notices', array( $this, 'notice_if_not_vnd_currency' ) );
                }
            }
        }
    }

    new Kanbox_Momo_Payment_GateWay();
}

