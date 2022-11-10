<?php 
/* Main class Handle Momo payment gateway */
require(KANBOX_MOMO_DIR . "inc/common/helper.php");
require(KANBOX_MOMO_DIR . "inc/class-admin-field.php");

if(!class_exists('Kanbox_Momo_Payment_GateWay_Controller')){

    class Kanbox_Momo_Payment_GateWay_Controller extends WC_Payment_Gateway {
            
        function __construct ()
        {
            $this->id = 'momo'; // Payment gateway plugin ID
            $this->icon = ''; // KANBOX_URL . 'assets/kanbox_momo_qr.svg' URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = __('Cổng thanh toán quét mã QR Momo', 'kanbox');
            $this->method_description = __('Hỗ trợ thanh toán quét mã qua ứng dụng ví điện tử Momo', 'kanbox'); // will be displayed on the options page
            
            // Gateways can support subscriptions, refunds, saved payment methods,
            $supports = array(
                'products'
            );         

            // Add refunds function if enable 
            if($this->get_option('enabled_refund') == 'yes'){
                array_push( $supports , 'refunds');
            }

            $this->supports = $supports;

            // Method with all the options fields
            $this->init_form_fields();
            // This action hook saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            // Load the settings.
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->enabled = $this->get_option( 'enabled' );
            $this->testmode = 'yes' === $this->get_option( 'testmode' );
            $this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
            $this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );
            $this->partnerName = $this->get_option('partner_name');
            $this->storeId = $this->get_option('store_id');

            // Initialize variables 
            $this->partnerCode = $this->testmode == 'yes' 
                ? $this->get_option('partner_code_test') 
                : $this->get_option('partner_code');
    
            $this->accessKey = $this->testmode == 'yes' 
                ? $this->get_option('access_key_test') 
                : $this->get_option('access_key');
    
            $this->serectkey = $this->testmode == 'yes' 
                ? $this->get_option('serect_key_test') 
                : $this->get_option('serect_key');
    
            $endpoint = $this->testmode == 'yes' 
                ? 'https://test-payment.momo.vn'
                : 'https://payment.momo.vn';

            

            $this->create_endpoint = $endpoint . '/v2/gateway/api/create';
            $this->refund_endpoint = $endpoint . '/v2/gateway/api/refund';
            $this->query_endpoint = $endpoint . '/v2/gateway/api/query';
            
            $this->redirectUrl = self::get_momo_payment_redirect_url();
            $this->ipnUrl = self::get_momo_payment_ipn_url(); 

            add_action( 'woocommerce_api_momo_ipn', [$this, 'webhook_api_momo_ipn'] );    
            add_action( 'woocommerce_api_momo_redirect_url', [$this, 'webhook_api_momo_redirect_url'] ); 

            $this->admin_field = Kanbox_Momo_Payment_Admin_field::get_instance();
        }
    
        // IPN URL
        static function get_momo_payment_ipn_url(){
            return get_home_url() . '/wc-api/momo_ipn';
        }
    
        // Redirect URL
        static function get_momo_payment_redirect_url(){
            return get_home_url() . '/wc-api/momo_redirect_url';
        }
    
        /**
        * Kanbox Momo Payment Gateway setting fields
        */
        public function init_form_fields()
        {
            $this->form_fields = include( KANBOX_MOMO_DIR . 'inc/settings/momo-settings.php');
        }
    
        /**
        * You will need it if you want your custom credit card form, Step 4 is about it
        */
        public function payment_fields() {
            
                // ok, let's display some description before the payment form
            if ( $this->description ) {
                // you can instructions for test mode, I mean test card numbers etc.
                if ( $this->testmode ) {
                    $this->description .= ' Chế độ thử nghiệm được bật, xin vui lòng sử dụng ứng dụng <a href="https://developers.momo.vn/v3/download/">Momo test</a> để trải nghiệm.';
                    $this->description = trim( $this->description );
                }
                // display the description with <p> tags etc.
                echo wpautop( wp_kses_post( $this->description ));
            } else {
               ?>
               <p><?php echo esc_attr_e('Thanh toán trực tuyến bằng mã quét momo, xin vui lòng xử dụng app', 'kanbox');?> <a href="https://referral.momo.vn/ref/MDkwMzg4ODc4MSZndGJiMjAyMg==/referral_others"><?php echo esc_attr_e('ví điện tử Momo', 'kanbox');?></a> <?php echo esc_attr_e('để thanh toán miễn phí', 'kanbox');?></p>
               <?php
            }
        
        }

        /*
        * We're processing the payments here, everything about it is in Step 5
        */
        function process_payment( $order_id ) {
        
            global $woocommerce;
            // we need it to get any order detailes
            $order = wc_get_order( $order_id );
            $orderInfo = 'Thanh toán đơn hàng: ' . $order_id;
            $jsonResult = false;
            $extraData = $order_id;
    
            if (!empty($order_id)) {
                $orderId = time().''; // Mã đơn hàng
                $amount = $order->get_total();
                $requestId = time(). $order_id;
                $requestType = "captureWallet";
                //before sign HMAC SHA256 signature
                $rawHash = "accessKey=" . $this->accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&ipnUrl=" . $this->ipnUrl . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&partnerCode=" . $this->partnerCode . "&redirectUrl=" . $this->redirectUrl . "&requestId=" . $requestId . "&requestType=" . $requestType;
                $signature = hash_hmac("sha256", $rawHash, $this->serectkey);
                $data = array(
                    'partnerCode' => $this->partnerCode,
                    'partnerName' => $this->partnerName,
                    'storeId' => $this->storeId,
                    'requestId' => $requestId,
                    'amount' => $amount,
                    'orderId' => $orderId,
                    'orderInfo' => $orderInfo,
                    'redirectUrl' => $this->redirectUrl,
                    'ipnUrl' => $this->ipnUrl,
                    'lang' => 'vi',
                    'extraData' => $extraData,
                    'accessKey' => $this->accessKey,
                    'requestType' => $requestType,
                    'signature' => $signature
                );
    
                $result = execPostRequest($this->create_endpoint, json_encode($data));
                $jsonResult = json_decode($result, true);  // decode json
                if($jsonResult){
                    $order->update_status( 'on-hold', __('Đang tiến hành thanh toán', 'kanbox') );
                    return array(
                        'result' => 'success',
                        'redirect' => esc_url( $jsonResult['payUrl'] ),
                    );
                }
            } else {
                wc_add_notice('Lỗi khởi tạo thanh toán, xin vui lòng kiểm tra lại cài đặt và thử lại sau.', 'error' );
                return;
            }
        }
    
        public function process_refund( $order_id, $amount = NULL, $refund_reason = '' ){
            
            $order = wc_get_order( $order_id );
            if(!$amount){
                return new WP_Error( 'wc-order', __( 'Bạn chưa nhập số tiền cần hoàn trả', 'kanbox' ) );
            }
            // If it's something else such as a WC_Order_Refund, we don't want that.
            if( ! is_a( $order, 'WC_Order') ) {
                return new WP_Error( 'wc-order', __( 'ID thanh toán không nằm trong Database', 'kanbox' ) );
            }
            
            if( 'refunded' == $order->get_status() ) {
                return new WP_Error( 'wc-order', __( 'Đơn hàng này đã được hoàn trả', 'kanbox' ) );
            }

            $orderId = time().''; // Mã đơn hàng
            $requestId = time(). $order_id;
            $transId = get_post_meta( $order_id, '_billing_momo_transid', true );

            if(!$transId){
                return new WP_Error( 'wc-order', __( 'Không tìm thấy ID giao dịch', 'kanbox' ) );
            }

            //Checksum
            $rawHash = "accessKey=" . $this->accessKey . "&amount=" . $amount . 
            "&description=" . $refund_reason . "&orderId=" . $orderId .
            "&partnerCode=" . $this->partnerCode . "&requestId=" . $requestId . "&transId=" . $transId;

            $signature = hash_hmac("sha256", $rawHash, $this->serectkey);

            $data = array (
                'partnerCode' => $this->partnerCode,
                'orderId' => $orderId,
                'requestId' => $requestId,
                'amount' => $amount,
                'transId' => $transId,
                'lang' => 'vi',
                'description' => $refund_reason,
                'signature' => $signature,
            );

            $result = execPostRequest($this->refund_endpoint, json_encode($data));

            $jsonResult = json_decode($result, true);  // decode json

            if (!$jsonResult || $jsonResult['resultCode'] != 0) {
                return new WP_Error( 'wc-order' ,  $jsonResult['message'] );
            } else {
                $order->update_status( 'refunded', __('Đã hoàn lại tiền bằng thanh toán Momo', 'kanbox') );
                return true;
            }
        }

        public function query_transaction( $order_id ){

            $transId = get_post_meta( $order_id, '_billing_momo_order_id', true );
            $requestId = time()."";

            if(!$transId) return;

            //before sign HMAC SHA256 signature
            $rawHash = "accessKey=".$this->accessKey."&orderId=".$transId."&partnerCode=".$this->partnerCode."&requestId=".$requestId;
            $signature = hash_hmac("sha256", $rawHash, $this->serectkey);
            $requestType = "captureWallet";

            $data = array(
                'partnerCode' => $this->partnerCode,
                'requestId' => $requestId,
                'orderId' => $transId,
                'requestType' => $requestType,
                'signature' => $signature,
                'lang' => 'vi'
            );
            $jsonResult = [];
            $result = execPostRequest($this->query_endpoint, json_encode($data));
            $jsonResult = json_decode($result, true);  // decode json
            return $jsonResult;
        }

                /*
        * In case you need a webhook, like PayPal IPN etc
        */
        function webhook_api_momo_redirect_url() {
        
            $wc_order_id = sanitize_text_field( $_GET['extraData'] );
            $order = wc_get_order( $wc_order_id );

            try {
                $partnerCode = sanitize_text_field( $_GET["partnerCode"] );
                $orderId = sanitize_text_field( $_GET["orderId"] );
                $requestId = sanitize_text_field( $_GET["requestId"] );
                $amount = sanitize_text_field( $_GET["amount"] );	
                $orderInfo = sanitize_text_field( $_GET["orderInfo"] );
                $orderType = sanitize_text_field( $_GET["orderType"] );
                $transId = sanitize_text_field( $_GET["transId"] );
                $resultCode = sanitize_text_field( $_GET["resultCode"] );
                $message = sanitize_text_field( $_GET["message"] );
                $payType = sanitize_text_field( $_GET["payType"] );
                $responseTime = sanitize_text_field( $_GET["responseTime"] );
                $extraData = sanitize_text_field( $_GET["extraData"] );
                $m2signature = sanitize_text_field( $_GET["signature"] ); //MoMo signature
                
                // Checksum
                $rawHash = "accessKey=" . $this->accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&message=" . $message . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo .
                    "&orderType=" . $orderType . "&partnerCode=" . $this->partnerCode . "&payType=" . $payType . "&requestId=" . $requestId . "&responseTime=" . $responseTime .
                    "&resultCode=" . $resultCode . "&transId=" . $transId;
    
                $partnerSignature = hash_hmac("sha256", $rawHash, $this->serectkey);
    
                // Update transaction id to dashboard
                $this->admin_field->update_payment_meta_data($wc_order_id, $orderId, $transId);
                
                if ($m2signature == $partnerSignature && $order->get_status() != 'processing' && $resultCode == 0) {
                    $order->update_status('processing', 'Đơn hàng đã thanh toán thành công và đang được xử lý');
                    wc_reduce_stock_levels($wc_order_id);
                } else {
                    $order->update_status('pending', 'Đơn hàng đã thanh toán không thành công và đã chuyển thành chờ thanh toán lại');
                }
                
                header("Location:" . esc_url($this->get_return_url( $order )));
            
            } catch (Exception $e) {
                echo $response['message'] = $e;
            }
        }

        function webhook_api_momo_ipn(){

            $jsonStr = file_get_contents("php://input"); //read the HTTP body.
            $json = json_decode($jsonStr);
           
            if (!empty($json)) {
                
                $response = array();
                $wc_order_id = $json->extraData;
                $order = wc_get_order( $wc_order_id );

                try {
                    $partnerCode = $json->partnerCode;
                    $orderId = $json->orderId;
                    $requestId = $json->requestId;
                    $amount = $json->amount;	
                    $orderInfo = $json->orderInfo;
                    $orderType = $json->orderType;
                    $transId = $json->transId;
                    $resultCode = $json->resultCode;
                    $message = $json->message;
                    $payType = $json->payType;
                    $responseTime = $json->responseTime;
                    $extraData = $json->extraData;
                    $m2signature = $json->signature; //MoMo signature
                
                    //Checksum
                    $rawHash = "accessKey=" . $this->accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&message=" . $message . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo .
                        "&orderType=" . $orderType . "&partnerCode=" . $partnerCode . "&payType=" . $payType . "&requestId=" . $requestId . "&responseTime=" . $responseTime .
                        "&resultCode=" . $resultCode . "&transId=" . $transId;
    
                    $partnerSignature = hash_hmac("sha256", $rawHash, $this->serectkey);
    
                    // Update transaction id to dashboard
                    $this->admin_field->update_payment_meta_data($wc_order_id, $orderId, $transId);

                    if ($m2signature == $partnerSignature) {
                        $order->update_status('processing', 'Đơn hàng đã thanh toán thành công và đang được xử lý');
                        wc_reduce_stock_levels($wc_order_id);
                        return wp_send_json( 1, 200, 1 );
                    } else {
                        $order->update_status('pending', 'Đơn hàng đã thanh toán không thành công và đã chuyển thành chờ thanh toán lại');
                        return wp_send_json( 0, 200, 1 );
                    }
                    
                } catch (Exception $e) {
                    echo $response['message'] = $e;
                }
            } else {
                return wp_send_json( 1, 204, 1 );
            }
        }

    }
}