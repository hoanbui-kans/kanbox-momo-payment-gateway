<?php
/* Main class Handle MoMo payment gateway */
if(!class_exists('MoMo_Credit_Payment_GateWay_Controller')){

    class MoMo_Credit_Payment_GateWay_Controller extends WC_Payment_Gateway {
            
        function __construct ()
        {
            $this->id = 'momo-credit'; // Payment gateway plugin ID
            $this->icon = KANBOX_MOMO_URL . 'assets/logo-visa-master-jcb.png'; // URL of the icon that will be displayed on checkout page near your gateway name
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
            $this->lang = $this->get_option('lang');
            $this->enabled_user_info = 'yes' === $this->get_option('enabled_user_info');

            // Order tracking
            $this->tracking = 'yes' === $this->get_option( 'tracking_order' );
            $this->session_key = $this->get_option('session_key');
            $this->order_group_ids = [];

            $group_ids = $this->get_option( 'order_group_ids' );
                
            if($group_ids){
                $this->order_group_ids = json_decode($group_ids);
            }

            if(!$this->order_info){
                $this->order_info = __('MH_', 'kanbox');
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

            // Add script to admin
            if ( isset($_GET['section']) &&  $_GET['section'] == $this->id ) {
                add_action('admin_enqueue_scripts', [$this, 'init_setting_scripts']);
            }
        }

        // Ipn URL
        static function get_momo_payment_ipn_url(){
            return untrailingslashit( get_home_url() ) . '/wc-api/momo_credit_ipn';
        }
    
        // Redirect URL
        static function get_momo_payment_redirect_url(){
            return untrailingslashit( get_home_url() ). '/wc-api/momo_credit_redirect_url';
        }
    
        /**
        * Kanbox MoMo Payment Gateway setting fields
        */
        public function init_form_fields()
        {
            $this->form_fields = include( KANBOX_MOMO_DIR . 'inc/settings/momo-credit-settings.php');
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
               <p><?php esc_attr_e('Thanh toán trực tuyến bằng mã quét momo, xin vui lòng sử dụng app', 'kanbox');?> <a href="https://referral.momo.vn/ref/MDkwMzg4ODc4MSZndGJiMjAyMg==/referral_others"><?php esc_attr_e('ví điện tử MoMo', 'kanbox');?></a> <?php esc_attr_e('để thanh toán miễn phí', 'kanbox');?></p>
               <?php
            }
        }

        /*
        * We're processing the payments here
        */
        function process_payment( $wc_order_id ) {
            // we need it to get any order detailes
            $order = wc_get_order( $wc_order_id );
            $orderInfo = $this->order_info . $wc_order_id;
            $jsonResult = false;
            $extraData = $wc_order_id;
            $orderGroupId = "";

            if($this->tracking && $this->session_key && !$this->testmode){
                $session_key = $order->get_meta($this->session_key, true);
                foreach($this->order_group_ids as $key => $value){
                    if($value->label == $session_key){
                        $orderGroupId = $value->value;
                    }
                }
            }

            if (!empty($wc_order_id) && !$order->is_paid()) {

                $orderId = time().''; // Mã đơn hàng
                $amount = $order->get_total();
                $requestId = time(). $wc_order_id;
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
                    'orderGroupId' => $orderGroupId,
                    'orderInfo' => $orderInfo,
                    'redirectUrl' => $this->redirectUrl,
                    'ipnUrl' => $this->ipnUrl,
                    'lang' => $this->lang,
                    'extraData' => $extraData,
                    'accessKey' => $this->accessKey,
                    'requestType' => $requestType,
                    'signature' => $signature
                );

                if($this->enabled_user_info){
                    $billing_email      = $order->get_billing_email();
                    $billing_phone      = $order->get_billing_phone();
                    $billing_name = $order->get_formatted_billing_full_name();
                    $data['userInfo'] = array(
                        'name' => $billing_name,
                        'phoneNumber' => $billing_phone,
                        'email' => $billing_email,
                    );
                }
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
                    $order->update_status( 'on-pending', __('Đơn hàng đang tiến hành thanh toán!', 'kanbox') );
                    return array(
                        'result' => 'success',
                        'redirect' => $jsonResult['payUrl'],
                    );
                } else {
                    wc_add_notice($jsonResult['message'], 'error' );
                    $order->update_status( 'on-hold', __('Đơn hàng tạo dữ liệu thanh toán không thành công', 'kanbox') );
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

        public function process_refund( $wc_order_id, $amount = NULL, $refund_reason = '' ){

            $order = wc_get_order( $wc_order_id );

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
            $requestId = time(). $wc_order_id;
            $transId = $order->get_meta('_billing_momo_transid', true );

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
                'lang' => $this->lang,
                'description' => $refund_reason,
                'signature' => $signature,
            );

            if($this->enabled_user_info){
                $billing_email      = $order->get_billing_email();
                $billing_phone      = $order->get_billing_phone();
                $billing_name = $order->get_formatted_billing_full_name();
                $data['userInfo'] = array(
                    'name' => $billing_name,
                    'phoneNumber' => $billing_phone,
                    'email' => $billing_email,
                );
            }

            $result = execPostRequest($this->refund_endpoint, wp_json_encode($data));

            $jsonResult = json_decode($result, true);  // decode json

            if (!$jsonResult || $jsonResult['resultCode'] != 0) {
                return new WP_Error( 'wc-order' ,  $jsonResult['message'] );
            } else {
                $order->update_status( 'refunded', __('Đã hoàn lại tiền bằng thanh toán MoMo', 'kanbox') );
                return true;
            }
        }

        public function query_transaction( $wc_order_id ){
            
            $order = wc_get_order( $wc_order_id );

            $orderId = $order->get_meta('_billing_momo_order_id', true );

            $requestId = time()."";

            if(!$orderId) return;

            //before sign HMAC SHA256 signature
            $rawHash = "accessKey=".$this->accessKey."&orderId=".$orderId."&partnerCode=".$this->partnerCode."&requestId=".$requestId;
            $signature = hash_hmac("sha256", $rawHash, $this->secretkey);

            $data = array(
                'partnerCode' => $this->partnerCode,
                'requestId' => $requestId,
                'orderId' => $orderId,
                'signature' => $signature,
                'lang' => $this->lang
            );
            $jsonResult = [];
            $result = execPostRequest($this->query_endpoint, wp_json_encode($data));
            $jsonResult = json_decode($result, true);
            // check signature response
            return $jsonResult;
        }

        /*
        * Redirect Webhook
        */
        function webhook_api_momo_credit_redirect_url() {
        
            $wc_order_id = sanitize_text_field( $_GET['extraData'] );
            $order = wc_get_order( $wc_order_id );

            try {
                $payment = rest_sanitize_object( $_GET );
                $partnerCode = sanitize_text_field( $payment["partnerCode"] );
                $orderId = sanitize_text_field( $payment["orderId"] );
                $requestId = sanitize_text_field( $payment["requestId"] );
                $amount = sanitize_text_field( $payment["amount"] );
                $orderInfo = sanitize_text_field( $payment["orderInfo"] );
                $orderType = sanitize_text_field( $payment["orderType"] );
                $transId = sanitize_text_field( $payment["transId"] );
                $resultCode = sanitize_text_field( $payment["resultCode"] );
                $message = sanitize_text_field( $payment["message"] );
                $payType = sanitize_text_field( $payment["payType"] );
                $responseTime = sanitize_text_field( $payment["responseTime"] );
                $extraData = sanitize_text_field( $payment["extraData"] );
                $m2signature = sanitize_text_field( $payment["signature"] ); //MoMo signature
                
                // Checksum
                $rawHash = "accessKey=" . $this->accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&message=" . $message . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo .
                    "&orderType=" . $orderType . "&partnerCode=" . $this->partnerCode . "&payType=" . $payType . "&requestId=" . $requestId . "&responseTime=" . $responseTime .
                    "&resultCode=" . $resultCode . "&transId=" . $transId;
    
                $partnerSignature = hash_hmac("sha256", $rawHash, $this->secretkey);
    
                // Update transaction id to dashboard
                $this->admin_field->update_payment_meta_data($wc_order_id, $payment);
                
                if ($m2signature == $partnerSignature && $resultCode == 0) {
                    $order->update_status('processing', __('Đơn hàng đã thanh toán thành công và đang được xử lý.'), 'kanbox');
                    wc_reduce_stock_levels($wc_order_id);
                } else {
                    $order->update_status('failed', __('Đơn hàng đã thanh toán không thành công và đã chuyển thành chờ thanh toán lại.'), 'kanbox');
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
            $payment = json_decode($jsonStr, true);

            if (!empty($payment)) {

                $response = array();
                $wc_order_id = $payment['extraData'];
                $order = wc_get_order( $wc_order_id );

                try {
                    $partnerCode = $payment['partnerCode'];
                    $orderId = $payment['orderId'];
                    $requestId = $payment['requestId'];
                    $amount = $payment['amount'];
                    $orderInfo = $payment['orderInfo'];
                    $orderType = $payment['orderType'];
                    $transId = $payment['transId'];
                    $resultCode = $payment['resultCode'];
                    $message = $payment['message'];
                    $payType = $payment['payType'];
                    $responseTime = $payment['responseTime'];
                    $extraData = $payment['extraData'];
                    $m2signature = $payment['signature']; //MoMo signature

                    //Checksum
                    $rawHash = "accessKey=" . $this->accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&message=" . $message . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo .
                        "&orderType=" . $orderType . "&partnerCode=" . $partnerCode . "&payType=" . $payType . "&requestId=" . $requestId . "&responseTime=" . $responseTime .
                        "&resultCode=" . $resultCode . "&transId=" . $transId;

                    $partnerSignature = hash_hmac("sha256", $rawHash, $this->secretkey);

                    // Update transaction id to dashboard
                    $this->admin_field->update_payment_meta_data($wc_order_id, $payment);

                    if ($m2signature == $partnerSignature) {
                        if($order->get_status() != 'processing' && $resultCode == 0) {
                            $order->update_status('processing', 'Mã thanh toán ' . $orderId . ' được xác nhận bằng IPN và đang được xử lý!');
                            wc_reduce_stock_levels($wc_order_id);
                        } 
                        return wp_send_json( 1, 204, 1 );
                    } else {
                        $order->update_status('pending', 'Mã thanh toán ' . $orderId . ' được xác nhận bằng IPN và đang được xử lý!');
                        return wp_send_json( 0, 204, 1 );
                    }

                } catch (Exception $e) {
                    return wp_send_json( 0, 204, 1 );

                }
            } else {
                return wp_send_json( 0, 204, 1 );
            }
        }

        /*
        * Validate Amount
        */
        function turn_off_payment_gateway( $available_gateways ) {
            if ( ! WC()->cart ) return $available_gateways;
            $order_total = (float) WC()->cart->get_cart_contents_total();
            if(isset($_GET['key'])){
                $wc_order_id = wc_get_order_id_by_order_key($_GET['key']);
                if($wc_order_id){
                    $order = wc_get_order( $wc_order_id );
                    $order_total = $order->get_total();
                }
            }
            // Disable payment gateway if order/cart total is less than 1000 and more than 50.000.000
            if ( ($order_total > 50000000) || ($order_total < 1000) && isset( $available_gateways[$this->id] ) ) {
                unset( $available_gateways[ $this->id ] );
            }
            return $available_gateways;
        }

        /*
        * Script for customize option for orderGroupId
        */
        function init_setting_scripts(){
            wp_enqueue_script('_kanbox_repeater', KANBOX_MOMO_URL . 'assets/js/repeater.js', ['jquery'], false, []);
            wp_enqueue_script('_kanbox_setting', KANBOX_MOMO_URL . 'assets/js/settings.js', ['jquery'], false, []);
            wp_localize_script('_kanbox_setting', 'script_data', json_decode($this->get_option( 'order_group_ids' )));
        }

        /*
        * Process update admin settings
        */
        function process_admin_options(){
            $this->init_settings();

            $post_data = $this->get_post_data();

            foreach ( $this->get_form_fields() as $key => $field ) {
                if ( 'title' !== $this->get_field_type( $field ) ) {
                    try {
                        if( 'order_group_ids' == $key ){
                            $this->settings[ $key ] = json_encode($post_data['woocommerce_' . $this->id . '_order_group_ids']);
                        } else {
                            $this->settings[ $key ] = $this->get_field_value( $key, $field, $post_data );
                        }
                    } catch ( Exception $e ) {
                        $this->add_error( $e->getMessage() );
                    }
                }
            }
            return update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ), 'yes' );
        }

        /*
        * Customize option for orderGroupId
        */
        public function generate_json_html( $key, $data ) {
            $field_key = $this->get_field_key( $key );
            $defaults  = array(
                'title'             => '',
                'disabled'          => false,
                'class'             => '',
                'css'               => '',
                'placeholder'       => '',
                'type'              => 'json',
                'desc_tip'          => false,
                'description'       => '',
                'custom_attributes' => array(),
            );
            $data = wp_parse_args( $data, $defaults );
            $data['id'] 	= 'woocommerce_' . $this->id . '_order_group_ids';
            $data['value'] 	= $this->get_option( 'order_group_ids' );
            $session_key = $this->get_option( 'session_key' );

            ob_start(); ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr( $data['id'] ); ?>">
                        <?php echo esc_html( $data['title'] ); ?>
                        <span class="woocommerce-help-tip" data-tip="<?php echo esc_html( $data['desc'] ); ?>"></span>
                    </label>
                </th>
                <td class="forminp forminp-<?php echo esc_attr( $data['type'] ) ?>">
                    <div style="max-width: 500px">
                        <div style="display: flex">
                            <p style="flex: 1">
                                <?php echo $this->get_option( 'session_key' );?>
                            </p>
                            <p style="flex: 1">
                                <?php esc_attr_e('orderGroupId', 'kanbox');?>
                            </p>
                        </div>
                        <div class="repeater">
                            <div data-repeater-list="<?php echo esc_attr( $data['id'] ); ?>">
                                <div data-repeater-item style="margin-bottom: 15px;">
                                    <input
                                        type="text"
                                        name="label"
                                        style="width: 220px"
                                    />
                                    <input
                                        type="number"
                                        name="value"
                                        style="width: 220px"
                                    />
                                    <button data-repeater-delete type="button" value="<?php esc_attr_e('Delete', 'kanbox');?>" class="button-secondary">
                                        <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="m112 112 20 320c.95 18.49 14.4 32 32 32h184c17.67 0 30.87-13.51 32-32l20-320"></path><path stroke-linecap="round" stroke-miterlimit="10" stroke-width="32" d="M80 112h352"></path><path fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M192 112V72h0a23.93 23.93 0 0 1 24-24h80a23.93 23.93 0 0 1 24 24h0v40m-64 64v224m-72-224 8 224m136-224-8 224"></path></svg>
                                    </button>
                                </div>
                            </div>
                            <button data-repeater-create type="button" class="button-primary">
                                <?php esc_attr_e('Thêm mới', 'kanbox');?>
                            </button>
                        </div>
                    </div>
                </td>
            </tr>
            <?php
            return ob_get_clean();
        }

    }
}