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

            $this->supports = array(
                'products',
                'refunds',
            );                

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
                    $this->description .= 'Chế độ thử nghiệm được bật, xin vui lòng sử dụng ứng dụng <a href="https://developers.momo.vn/v3/download/">Momo test</a> để trải nghiệm.';
                    $this->description .= trim( $this->description );
                }
                // display the description with <p> tags etc.
                echo wpautop( wp_kses_post( $this->description )  . ' xin vui lòng xử dụng app <a href="https://referral.momo.vn/ref/MDkwMzg4ODc4MSZndGJiMjAyMg==/referral_others">ví điện tử Momo</a> để thanh toán miễn phí');
            } else {
                // I will echo() the form, but you can close PHP tags and print it directly in HTML
                if ( $this->testmode ) {
                    echo '<p>Chế độ thử nghiệm được bật, xin vui lòng sử dụng ứng dụng <a href="https://developers.momo.vn/v3/download/">Momo test</a> để trải nghiệm.</p>';
                }
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
            $partnerName = $this->get_option('partner_name');
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
                    'partnerName' => $partnerName,
                    'storeId' 	=> 'Kansite',
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
                        'redirect' => $jsonResult['payUrl'],
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
            $transId = $order->get_meta('momo_transid');

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
            }

            return true;
        }

        public function query_transaction( $order_id ){

            $order = wc_get_order( $order_id );

            $transId = $order->get_meta('momo_order_id');

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
            $wc_order_id = $_GET['extraData'];
            $order = wc_get_order( $wc_order_id );
            try {
                $partnerCode = $_GET["partnerCode"];
                $orderId = $_GET["orderId"];
                $requestId = $_GET["requestId"];
                $amount = $_GET["amount"];	
                $orderInfo = $_GET["orderInfo"];
                $orderType = $_GET["orderType"];
                $transId = $_GET["transId"];
                $resultCode = $_GET["resultCode"];
                $message = $_GET["message"];
                $payType = $_GET["payType"];
                $responseTime = $_GET["responseTime"];
                $extraData = $_GET["extraData"];
                $m2signature = $_GET["signature"]; //MoMo signature
                
                //Checksum
                $rawHash = "accessKey=" . $this->accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&message=" . $message . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo .
                    "&orderType=" . $orderType . "&partnerCode=" . $this->partnerCode . "&payType=" . $payType . "&requestId=" . $requestId . "&responseTime=" . $responseTime .
                    "&resultCode=" . $resultCode . "&transId=" . $transId;
    
                $partnerSignature = hash_hmac("sha256", $rawHash, $this->serectkey);
    
                // Update transaction id to dashboard
                $this->admin_field->update_payment_meta_data($wc_order_id, $orderId, $transId);
                
                if ($m2signature == $partnerSignature && $order->get_status() != 'processing' && $resultCode == 0) {
                    $order->update_status('processing', 'Đơn hàng đã thanh toán thành công và đang được xử lý');
                    $order->reduce_order_stock();
                } else {
                    $order->update_status('pending', 'Đơn hàng đã thanh toán không thành công và đã chuyển thành chờ thanh toán lại');
                }
                
                header("Location:" . $this->get_return_url( $order ));
                
            } catch (Exception $e) {
                echo $response['message'] = $e;
            }
        }

        function webhook_api_momo_ipn(){

            if (!empty($_POST)) {
                $response = array();

                $wc_order_id = $_POST['extraData'];

                $order = wc_get_order( $wc_order_id );
                try {
                    $partnerCode = $_POST["partnerCode"];
                    $orderId = $_POST["orderId"];
                    $requestId = $_POST["requestId"];
                    $amount = $_POST["amount"];	
                    $orderInfo = $_POST["orderInfo"];
                    $orderType = $_POST["orderType"];
                    $transId = $_POST["transId"];
                    $resultCode = $_POST["resultCode"];
                    $message = $_POST["message"];
                    $payType = $_POST["payType"];
                    $responseTime = $_POST["responseTime"];
                    $extraData = $_POST["extraData"];
                    $m2signature = $_POST["signature"]; //MoMo signature
                    
    
                    //Checksum
                    $rawHash = "accessKey=" . $this->accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&message=" . $message . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo .
                        "&orderType=" . $orderType . "&partnerCode=" . $this->partnerCode . "&payType=" . $payType . "&requestId=" . $requestId . "&responseTime=" . $responseTime .
                        "&resultCode=" . $resultCode . "&transId=" . $transId;
    
                    $partnerSignature = hash_hmac("sha256", $rawHash, $this->serectkey);

                    // Update transaction id to dashboard
                    $this->admin_field->update_payment_meta_data($wc_order_id, $orderId, $transId);

                    if ($m2signature == $partnerSignature) {
                        $order->update_status('processing', 'Đơn hàng đã thanh toán thành công và đang được xử lý');
                        $order->reduce_order_stock();
                        return;
                    } else {
                        $order->update_status('pending', 'Đơn hàng đã thanh toán không thành công và đã chuyển thành chờ thanh toán lại');
                        return;
                    }
                    
                } catch (Exception $e) {
                    echo $response['message'] = $e;
                }
            }
        }

    }
}