<?php 
// # DOCUMENTATION
// API get payment link from M4B server
// https://developers.momo.vn/v3/docs/payment/api/credit/onetime

function execPostRequest($url, $data) {
    $apiBody = false;
    $response = wp_remote_post( $url, array(
        'method'      => 'POST',
        'headers'     => array( 
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($data)
        ),
        'body'        => $data,
    )); 
    if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        wc_add_notice('Lỗi khởi tạo thanh toán, '. $error_message .' xin vui lòng kiểm tra lại cài đặt và thử lại sau.', 'error' );
    } else {
        $apiBody = wp_remote_retrieve_body( $response );
    }
    return $apiBody;
}