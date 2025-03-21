<?php
/**
* Plugin Name: Kanbox MoMo Payment Gateway
* Plugin URI: https://kanbox.vn/resource-plugin/kanbox-momo-payment-gateway/
* Description: Simple and easy integration of MoMo payment gateways with your Woocommerce website.
* Author: Kan Solution
* Version: 1.1.2
* Author URI: https://zalo.me/0903888781
* Text Domain: kanbox
* Domain Path: /languages
* WC requires at least: 3.0
* WC tested up to: 8.4.0
* License:     GPLv2+
*/

require ('inc/load-v5p3.php');

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KANBOX_MOMO_URL', plugins_url( '/', __FILE__ ) );
define( 'KANBOX_MOMO_DIR', plugin_dir_path( __FILE__ ) );
                    
/**
 * The main class of the plugin
 *
 * @author   Kan solution // BUI HOAN
 * @since    2018
 */

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

if(!class_exists('Kanbox_MoMo_Payment_GateWay')){
    
    class Kanbox_MoMo_Payment_GateWay {

        function __construct() {
            add_action( 'init', array( $this, 'init' ) );
        }
        
        function notice_if_not_woocommerce() 
        {
            $class = 'notice notice-warning';
            $message = __( 'Thanh toán với MoMo không chạy vì Plugin WooCommerce chưa hoạt động. Vui lòng kích hoạt plugin WooCommerce trước.', 'kanbox' );
            printf( '<div class="%1$s"><p><strong>%2$s</strong></p></div>', $class, $message );
        }

        function decimals_is_not_zero() 
        {
            $class = 'notice notice-warning';
            $message = __( 'Lỗi khi cài đặt thanh toán MoMo với Woocommerce: số thập phân giá trị tiền tệ không phải 0.', 'kanbox' );
            printf( '<div class="%1$s"><p><strong>%2$s</strong></p></div>', $class, $message );
        }

        function notice_if_not_vnd_currency() 
        {
            $class = 'notice notice-warning';
            $message = __( 'Bạn cần sử dụng Đồng Việt Nam <code>(₫)</code> làm đơn vị tiền tệ để sử dụng thanh toán MoMo đúng cách.', 'kanbox' );
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
                $this->init_update();
            } else {
                // Throw a notice if WooCommerce is NOT active
                add_action( 'admin_notices', array( $this, 'notice_if_not_woocommerce' ) );
            }
        }

        function addSecretKey($query)
        {
            $query['secret'] = '$2y$06$ITV6frP4sgIm5U1tRk4jouFf9fBjO4u.f7Tw9GIHAFKDdKVFYM2Ge';
            return $query;
        }

        public function main()
        {
            if( class_exists('WC_Payment_Gateway')) {
                if( 0 != get_option('woocommerce_price_num_decimals')) {
                    add_action( 'admin_notices', array( $this, 'decimals_is_not_zero' ) );
                }
                else if('VND' != get_woocommerce_currency()){
                    add_action( 'admin_notices', array( $this, 'notice_if_not_vnd_currency' ) );
                } else {
                    require ( KANBOX_MOMO_DIR . 'inc/common/helper.php');
                    require ( KANBOX_MOMO_DIR . 'inc/class-admin-field.php');
                    require ( KANBOX_MOMO_DIR . 'inc/Abstract_MoMo_Payment_Gateway.php' );
                    require ( KANBOX_MOMO_DIR . 'inc/class-payment-momo-qr.php' );
                    require ( KANBOX_MOMO_DIR . 'inc/class-payment-momo-atm.php' );
                    require ( KANBOX_MOMO_DIR . 'inc/class-payment-momo-credit.php' );
                    require ( KANBOX_MOMO_DIR . 'inc/class-user-dashboard.php' );

                    add_filter( 'woocommerce_payment_gateways', function ( $gateways ) {
                        $gateways[] = 'MoMo_Qr_Payment_GateWay_Controller';
                        $gateways[] = 'MoMo_Atm_Payment_GateWay_Controller';
                        $gateways[] = 'MoMo_Credit_Payment_GateWay_Controller';
                        return $gateways;
                    } );

                }
            }
        }

        public function init_update()
        {
            $update = PucFactory::buildUpdateChecker(
                'https://kanbox.vn/wp-json/resource/update-check/kanbox-momo-payment-gateway',
                __FILE__,
                'kanbox-momo-payment-gateway'
            );
            $update->addQueryArgFilter([$this, 'addSecretKey']);
        }

    }

    new Kanbox_MoMo_Payment_GateWay();
}

