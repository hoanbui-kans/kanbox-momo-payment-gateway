<?php 
require ('load-v5p3.php');

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://tail.kanbox.vn/wp-json/resource/update-check/kanbox-momo-payment-gateway',
    __FILE__,
    'unique-plugin-or-theme-slug'
);

//Here's how you can add query arguments to the URL.
function addSecretKey2($query){
    $query['secret'] = '12577cc88e30b6f63865524c6cde64ce';
    return $query;
}

$myUpdateChecker->addQueryArgFilter('addSecretKey2');