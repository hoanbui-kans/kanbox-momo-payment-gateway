<?php 
/* Main class Handle MoMo payment gateway */
if(!class_exists('MoMo_Credit_Payment_GateWay_Controller')){

    class MoMo_Credit_Payment_GateWay_Controller extends WC_Payment_Gateway {
            
        function __construct ()
        {
            $this->id = 'momo-credit'; // Payment gateway plugin ID
            $this->icon = KANBOX_URL . 'assets/logo-visa-master-jcb.png'; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = __('Cổng thanh toán MoMo cho thẻ quốc tế (Credit card)', 'kanbox');
            $this->method_description = __('Hỗ trợ thanh toán quét mã qua ứng dụng ví điện tử MoMo', 'kanbox'); // will be displayed on the options page
            
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
            $this->order_info = $this->get_option('order_info');
            
            if(!$this->order_info){
                $this->order_info = __('Thanh toán đơn hàng: ', 'kanbox');
            }

            // Initialize variables 
            $this->partnerCode = $this->testmode == 'yes' 
                ? $this->get_option('partner_code_test') 
                : $this->get_option('partner_code');
    
            $this->accessKey = $this->testmode == 'yes' 
                ? $this->get_option('access_key_test') 
                : $this->get_option('access_key');
    
            $this->secretkey = $this->testmode == 'yes' 
                ? $this->get_option('secret_key_test') 
                : $this->get_option('secret_key');
    
            $endpoint = $this->testmode == 'yes' 
            ? $this->get_option('api_enpoint_test') 
            : $this->get_option('api_enpoint');

            // https://developers.momo.vn/v3/docs/payment/api/credit/onetime
            $this->create_endpoint = $endpoint . '/v2/gateway/api/create';
            $this->refund_endpoint = $endpoint . '/v2/gateway/api/refund';
            $this->query_endpoint = $endpoint . '/v2/gateway/api/query';
            
            // Redirect and IPN Url Handle pages
            $this->redirectUrl = self::get_momo_payment_redirect_url();
            $this->ipnUrl = self::get_momo_payment_ipn_url(); 

            // Webhook Redirect and IPN
            add_action( 'woocommerce_api_momo_credit_ipn', [$this, 'webhook_api_momo_credit_ipn'] );    
            add_action( 'woocommerce_api_momo_credit_redirect_url', [$this, 'webhook_api_momo_credit_redirect_url'] ); 

            // Disabled avaiable payment method
            add_filter( 'woocommerce_available_payment_gateways', [$this, 'turn_off_payment_gateway'] );

            // Display Admin Order Id and Transaction Id
            $this->admin_field = Kanbox_MoMo_Payment_Admin_field::get_instance();
        }
    
        // Ipn URL
        static function get_momo_payment_ipn_url(){
            return get_home_url() . '/wc-api/momo_credit_ipn';
        }
    
        // Redirect URL
        static function get_momo_payment_redirect_url(){
            return get_home_url() . '/wc-api/momo_credit_redirect_url';
        }
    
        /**
        * Kanbox MoMo Payment Gateway setting fields
        */
        public function init_form_fields()
        {
            $this->form_fields = include( KANBOX_DIR . 'inc/settings/momo-credit-settings.php');
        }
    
        /**
        * Custom Kanbox MoMo Gateway payment method
        */
        public function payment_fields() {
            // ok, let's display some description before the payment form
            if ( $this->description ) {
                // you can instructions for test mode, I mean test card numbers etc.
                if ( $this->testmode ) {
                    $this->description .= ' Chế độ thử nghiệm được bật, xin vui lòng sử dụng ứng dụng <a href="https://developers.momo.vn/v3/download/">MoMo test</a> để trải nghiệm.';
                    $this->description = trim( $this->description );
                }
                // display the description with <p> tags etc.
                echo wpautop( wp_kses_post( $this->description ));
            } else {
               ?>
               <p><?php echo __('Thanh toán trực tuyến bằng mã quét momo, xin vui lòng xử dụng app', 'kanbox');?> <a href="https://referral.momo.vn/ref/MDkwMzg4ODc4MSZndGJiMjAyMg==/referral_others"><?php echo __('ví điện tử MoMo', 'kanbox');?></a> <?php echo __('để thanh toán miễn phí', 'kanbox');?></p>
               <?php
            }
        }

        /*
        * We're processing the payments here
        */
        function process_payment( $order_id ) {
        
            global $woocommerce;
            // we need it to get any order detailes
            $order = wc_get_order( $order_id );
            $orderInfo = $this->order_info . $order_id;
            $jsonResult = false;
            $extraData = $order_id;

            if (!empty($order_id) && !$order->is_paid()) {
                // Update Status to on-hold 
                $order->update_status( 'on-hold', __('Đang tiến hành thanh toán', 'kanbox') );
                $orderId = time().''; // Mã đơn hàng
                $amount = $order->get_total();
                $requestId = time(). $order_id;
                $requestType = "payWithCC";
                //before sign HMAC SHA256 signature
                $rawHash = "accessKey=" . $this->accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&ipnUrl=" . $this->ipnUrl . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&partnerCode=" . $this->partnerCode . "&redirectUrl=" . $this->redirectUrl . "&requestId=" . $requestId . "&requestType=" . $requestType;
                $signature = hash_hmac("sha256", $rawHash, $this->secretkey);
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
                
                // Send request to serve
                $result = execPostRequest($this->create_endpoint, wp_json_encode($data));
                // Out if the request failed
                if(!$result){
                    wc_add_notice(__('Lỗi khởi tạo thanh toán, xin vui lòng kiểm tra lại cài đặt và thử lại sau.', 'kanbox'), 'error' );
                    return array(
                        'result' => 'success',
                        'error' => true,
                        'message' => __('Lỗi khởi tạo thanh toán, xin vui lòng kiểm tra lại cài đặt và thử lại sau.', 'kanbox'),
                        'redirect' => $this->get_return_url( $order ),
                    );  
                }

                $jsonResult = json_decode($result, true);

                if($jsonResult && $jsonResult['resultCode'] == 0){
                    WC()->cart->empty_cart();
                    return array(
                        'result' => 'success',
                        'redirect' => $jsonResult['payUrl'],
                    );
                } else {
                    wc_add_notice($jsonResult['message'], 'error' );
                    return array(
                        'result' => 'success',
                        'error' => true,
                        'message' => $jsonResult['message'],
                        'redirect' => $this->get_return_url( $order ),
                    );  
                }
            } 
            
            if($order->is_paid()){
                wc_add_notice(__('Đơn hàng đã được thanh toán, xin vui lòng liên hệ quản trị viên để được hỗ trợ.', 'kanbox'), 'error' );
            } else {
                wc_add_notice(__('Lỗi khởi tạo thanh toán, xin vui lòng kiểm tra lại cài đặt và thử lại sau.', 'kanbox'), 'error' );
            }   

            // Redirect to the thank you page
            return array(
                'result' => 'success',
                'error' => true,
                'redirect' => $this->get_return_url( $order ),
            );  
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

            $signature = hash_hmac("sha256", $rawHash, $this->secretkey);

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

            $result = execPostRequest($this->refund_endpoint, wp_json_encode($data));

            $jsonResult = json_decode($result, true);  // decode json

            if (!$jsonResult || $jsonResult['resultCode'] != 0) {
                return new WP_Error( 'wc-order' ,  $jsonResult['message'] );
            } else {
                $order->update_status( 'refunded', __('Đã hoàn lại tiền bằng thanh toán MoMo', 'kanbox') );
                return true;
            }
        }

        public function query_transaction( $order_id ){

            $transId = get_post_meta( $order_id, '_billing_momo_order_id', true );
            $requestId = time()."";

            if(!$transId) return;

            //before sign HMAC SHA256 signature
            $rawHash = "accessKey=".$this->accessKey."&orderId=".$transId."&partnerCode=".$this->partnerCode."&requestId=".$requestId;
            $signature = hash_hmac("sha256", $rawHash, $this->secretkey);
            $requestType = "payWithCC";

            $data = array(
                'partnerCode' => $this->partnerCode,
                'requestId' => $requestId,
                'orderId' => $transId,
                'requestType' => $requestType,
                'signature' => $signature,
                'lang' => 'vi'
            );
            $jsonResult = [];
            $result = execPostRequest($this->query_endpoint, wp_json_encode($data));
            $jsonResult = json_decode($result, true);  // decode json
            return $jsonResult;
        }

        /*
        * Redirect Webhook
        */
        function webhook_api_momo_credit_redirect_url() {
        
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
    
                $partnerSignature = hash_hmac("sha256", $rawHash, $this->secretkey);
    
                // Update transaction id to dashboard
                $this->admin_field->update_payment_meta_data($wc_order_id, $orderId, $transId);
                
                if ($m2signature == $partnerSignature && $order->get_status() != 'processing' && $resultCode == 0) {
                    $order->update_status('processing', __('Đơn hàng đã thanh toán thành công và đang được xử lý'), 'kanbox');
                    wc_reduce_stock_levels($wc_order_id);
                } else {
                    $order->update_status('pending', __('Đơn hàng đã thanh toán không thành công và đã chuyển thành chờ thanh toán lại'), 'kanbox');
                }
                
                header("Location:" . esc_url($this->get_return_url( $order )));
            
            } catch (Exception $e) {
                return wp_send_json( $response['message'], 206, 1 );
            }
        }

        /*
        * IPN Webhook
        */
        function webhook_api_momo_credit_ipn(){

            $jsonStr = file_get_contents("php://input"); //read the HTTP body.
            $json = wp_json_encode($jsonStr);
           
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
    
                    $partnerSignature = hash_hmac("sha256", $rawHash, $this->secretkey);
    
                    // Update transaction id to dashboard
                    $this->admin_field->update_payment_meta_data($wc_order_id, $orderId, $transId);

                    if ($m2signature == $partnerSignature) {
                        if($order->get_status() != 'processing' && $resultCode == 0) {
                            $order->update_status('processing', __('Đơn hàng đã được xác nhận thanh toán thành công bằng IPN và đang được xử lý!', 'kanbox'));
                            wc_reduce_stock_levels($wc_order_id);
                        }
                    } else {
                        $order->update_status('pending', __('Đơn hàng đã thanh toán không thành công và đã chuyển thành chờ thanh toán lại', 'kanbox'));
                        return wp_send_json( 0, 200, 1 );
                    }
                    
                } catch (Exception $e) {
                    return wp_send_json( $response['message'], 206, 1 );
                }
            } else {
                return wp_send_json( 1, 204, 1 );
            }
        }

        /*
        * Validate Amount
        */
        function turn_off_payment_gateway( $available_gateways ) {
            global $woocommerce;
            $order_total = (float) $woocommerce->cart->get_cart_contents_total();
            // Disable payment gateway if order/cart total is less than 1000 and more than 50.000.000
            if ( ($order_total > 50000000) || ($order_total < 1000) ) {
                unset( $available_gateways[ $this->id ] );
            }
            return $available_gateways;
        }

    }
}