<?php 
/**
 * @snippet       Update Self-Hosted Plugin @ WordPress Dashboard
 * @how-to        Get CustomizeWoo.com FREE
 * @author        Rodolfo Melogli
 * @compatible    WooCommerce 7
 * @community     https://businessbloomer.com/club/
 */

 require KANBOX_PATH . 'inc/plugin-update-checker.php';

 $ExampleUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
     'https://tail.kanbox.vn/wp-json/resource/update-check/kanbox-momo-payment-gateway',
     __FILE__
 );
 
 //Here's how you can add query arguments to the URL.
 function addSecretKey($query){
     $query['secret'] = 'foo';
     return $query;
 }
 $ExampleUpdateChecker->addQueryArgFilter('addSecretKey');
 