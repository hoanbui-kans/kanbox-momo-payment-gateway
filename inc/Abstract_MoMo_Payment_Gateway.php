<?php
if (!class_exists('Abstract_MoMo_Payment_Gateway')) {
    abstract class Abstract_MoMo_Payment_Gateway extends WC_Payment_Gateway {
        protected const BASE_ICON_URL = KANBOX_MOMO_URL . 'assets/primary-logo.png';

        public $id;
        public $icon = self::BASE_ICON_URL;
        public $has_fields = true;
        public $method_title = '';
        public $method_description = '';
        public $payment_type = 'captureWallet';
        public $supports = ['products'];
        protected $testmode;
        protected $admin_field;

        // Common properties
        protected $private_key;
        protected $publishable_key;
        protected $partnerName;
        protected $storeId;
        protected $order_info;
        protected $lang;
        protected $enabled_user_info;
        protected $tracking;
        protected $session_key;
        protected $order_group_ids;
        protected $partnerCode;
        protected $accessKey;
        protected $secretkey;
        protected $create_endpoint;
        protected $refund_endpoint;
        protected $query_endpoint;
        protected $redirectUrl;
        protected $ipnUrl;

        public function __construct() {
            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
            $this->testmode = 'yes' === $this->get_option('testmode');

            $this->init_credentials();
            $this->setup_supports();

            $this->admin_field = Kanbox_MoMo_Payment_Admin_field::get_instance();
            $this->register_hooks();
        }

        protected function init_credentials(): void {
            $this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
            $this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );

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

            $this->create_endpoint = "$endpoint/v2/gateway/api/create";
            $this->refund_endpoint = "$endpoint/v2/gateway/api/refund";
            $this->query_endpoint = "$endpoint/v2/gateway/api/query";

            $this->partnerName = $this->get_option('partner_name');
            $this->storeId = $this->get_option('store_id');
            $this->order_info = $this->get_option('order_info') ?: __('MH_', 'kanbox');
            $this->lang = $this->get_option('lang');
            $this->enabled_user_info = 'yes' === $this->get_option('enabled_user_info');
            $this->tracking = 'yes' === $this->get_option('tracking_order');
            $this->session_key = $this->get_option('session_key');
            $this->order_group_ids = json_decode($this->get_option('order_group_ids') ?: '[]', true);
            $this->redirectUrl = $this->get_redirect_url();
            $this->ipnUrl = $this->get_ipn_url();
        }

        protected function setup_supports(): void {
            if ('yes' === $this->get_option('enabled_refund')) {
                $this->supports[] = 'refunds';
            }
        }

        protected function register_hooks(): void {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
            add_action("woocommerce_api_{$this->id}_ipn", [$this, 'webhook_api_momo_ipn']);
            add_action("woocommerce_api_{$this->id}_redirect_url", [$this, 'webhook_api_momo_redirect_url']);
            add_filter('woocommerce_available_payment_gateways', [$this, 'turn_off_payment_gateway']);
            if (isset($_GET['section']) && $_GET['section'] === $this->id) {
                add_action('admin_enqueue_scripts', [$this, 'init_setting_scripts']);
            }
        }

        // Ghi đè init_form_fields() và gọi phương thức trừu tượng
        public function init_form_fields(): void {
            $this->form_fields = $this->initialize_form_fields();
        }

        // Phương thức trừu tượng mới để các class con triển khai
        abstract protected function initialize_form_fields(): array;

        abstract protected function get_ipn_url(): string;
        abstract protected function get_redirect_url(): string;

        // Các phương thức chung khác (giữ nguyên như trước)
        public function payment_fields(): void {
            $description = $this->description;
            if ($this->testmode) {
                $description .= ' ' . __('Chế độ thử nghiệm được bật, xin vui lòng sử dụng ứng dụng <a href="https://developers.momo.vn/v3/download/">MoMo test</a> để trải nghiệm.', 'kanbox');
            }
            echo wpautop(wp_kses_post(trim($description ?: sprintf(
                '<p>%s <a href="%s">%s</a> %s</p>',
                esc_html__('Thanh toán trực tuyến bằng mã quét momo, xin vui lòng sử dụng app', 'kanbox'),
                'https://referral.momo.vn/ref/MDkwMzg4ODc4MSZndGJiMjAyMg==/referral_others',
                esc_html__('ví điện tử MoMo', 'kanbox'),
                esc_html__('để thanh toán miễn phí', 'kanbox')
            ))));
        }

        private function prepare_payment_data(WC_Order $order): array {
            $order_id = $order->get_id();
            $timestamp = time();
            $request_id = $timestamp . $order_id;
            $order_ref = $timestamp . '';
            $amount = $order->get_total();
            $order_info = $this->order_info . $order_id;

            $raw_hash = "accessKey={$this->accessKey}&amount={$amount}&extraData={$order_id}&ipnUrl={$this->ipnUrl}&orderId={$order_ref}&orderInfo={$order_info}&partnerCode={$this->partnerCode}&redirectUrl={$this->redirectUrl}&requestId={$request_id}&requestType={$this->payment_type}";
            $signature = hash_hmac('sha256', $raw_hash, $this->secretkey);

            $data = [
                'partnerCode' => $this->partnerCode,
                'partnerName' => $this->partnerName,
                'storeId' => $this->storeId,
                'requestId' => $request_id,
                'amount' => $amount,
                'orderId' => $order_ref,
                'orderInfo' => $order_info,
                'redirectUrl' => $this->redirectUrl,
                'ipnUrl' => $this->ipnUrl,
                'lang' => $this->lang,
                'extraData' => (string) $order_id,
                'accessKey' => $this->accessKey,
                'requestType' => $this->payment_type,
                'signature' => $signature,
                'orderGroupId' => $this->get_order_group_id($order),
            ];

            if ($this->enabled_user_info) {
                $data['userInfo'] = [
                    'name' => $order->get_formatted_billing_full_name(),
                    'phoneNumber' => $order->get_billing_phone(),
                    'email' => $order->get_billing_email(),
                ];
            }

            return $data;
        }

        private function get_order_group_id(WC_Order $order): string {
            if (!$this->tracking || !$this->session_key || $this->testmode) {
                return '';
            }

            $session_value = $order->get_meta($this->session_key, true);
            foreach ($this->order_group_ids as $group) {
                if ($group['label'] === $session_value) {
                    return $group['value'];
                }
            }
            return '';
        }

        private function handle_payment_response(WC_Order $order, ?array $json_result): array {
            if ($json_result && $json_result['resultCode'] === 0) {
                WC()->cart->empty_cart();
                $order->update_status('on-pending', __('Đơn hàng đang tiến hành thanh toán!', 'kanbox'));
                return ['result' => 'success', 'redirect' => $json_result['payUrl']];
            }

            $message = $json_result['message'] ?? __('Lỗi khởi tạo thanh toán, xin vui lòng kiểm tra lại cài đặt và thử lại sau.', 'kanbox');
            return $this->handle_payment_error($order, false, $message, true);
        }

        private function handle_payment_error(WC_Order $order, bool $is_paid, string $message = '', bool $update_status = false): array {
            $default_message = $is_paid 
                ? __('Đơn hàng đã được thanh toán, xin vui lòng liên hệ quản trị viên để được hỗ trợ.', 'kanbox')
                : __('Lỗi khởi tạo thanh toán, xin vui lòng kiểm tra lại cài đặt và thử lại sau.', 'kanbox');
            
            wc_add_notice($message ?: $default_message, 'error');
            if ($update_status) {
                $order->update_status('on-hold', __('Đơn hàng tạo dữ liệu thanh toán không thành công', 'kanbox'));
            }
            return [
                'result' => 'success',
                'error' => true,
                'message' => $message ?: $default_message,
                'redirect' => $this->get_return_url($order),
            ];
        }

        public function process_payment($order_id): array {
            $order = wc_get_order($order_id);
            if (!$order || $order->is_paid()) {
                return $this->handle_payment_error($order, $order->is_paid());
            }

            $payment_data = $this->prepare_payment_data($order);
            $result = execPostRequest($this->create_endpoint, wp_json_encode($payment_data));

            if (!$result) {
                return $this->handle_payment_error($order, false, __('Lỗi khởi tạo thanh toán, xin vui lòng kiểm tra lại cài đặt và thử lại sau.', 'kanbox'));
            }

            $json_result = json_decode($result, true);
            return $this->handle_payment_response($order, $json_result);
        }
        public function process_refund($order_id, $amount = null, $reason = '') {
            $order = wc_get_order($order_id);
            if (!$this->validate_refund($order, $amount)) {
                return new WP_Error('wc-order', $this->get_refund_error_message($order, $amount));
            }

            $refund_data = $this->prepare_refund_data($order, $amount, $reason);
            $result = execPostRequest($this->refund_endpoint, wp_json_encode($refund_data));
            $json_result = json_decode($result, true);

            if (!$json_result || $json_result['resultCode'] !== 0) {
                return new WP_Error('wc-order', $json_result['message'] ?? __('Refund failed', 'kanbox'));
            }

            $order->update_status('refunded', __('Đã hoàn lại tiền bằng thanh toán MoMo', 'kanbox'));
            return true;
        }
        public function query_transaction($order_id): ?array {
            $order = wc_get_order($order_id);
            $order_ref = $order->get_meta('_billing_momo_order_id', true);
            if (!$order_ref) {
                return null;
            }

            $request_id = time() . '';
            $raw_hash = "accessKey={$this->accessKey}&orderId={$order_ref}&partnerCode={$this->partnerCode}&requestId={$request_id}";
            $signature = hash_hmac('sha256', $raw_hash, $this->secretkey);

            $data = [
                'partnerCode' => $this->partnerCode,
                'requestId' => $request_id,
                'orderId' => $order_ref,
                'signature' => $signature,
                'lang' => $this->lang,
            ];

            $result = execPostRequest($this->query_endpoint, wp_json_encode($data));
            return json_decode($result, true) ?: null;
        }
        public function webhook_api_momo_redirect_url(): void {
            $order_id = sanitize_text_field($_GET['extraData'] ?? '');
            $order = wc_get_order($order_id);
            if (!$order) {
                return;
            }

            $payment = rest_sanitize_object($_GET);

            if ($this->validate_signature($payment, $this->create_signature($payment))) {
                $this->admin_field->update_payment_meta_data($order_id, $payment);
                $order->update_status(
                    $payment['resultCode'] == 0 ? 'processing' : 'failed',
                    $payment['resultCode'] == 0 
                        ? __('Đơn hàng đã thanh toán thành công và đang được xử lý.', 'kanbox')
                        : __('Đơn hàng đã thanh toán không thành công và đã chuyển thành chờ thanh toán lại.', 'kanbox')
                );
                if ($payment['resultCode'] == 0) {
                    wc_reduce_stock_levels($order_id);
                }
            } else {
                $order->add_order_note(__('Một giao dịch giả mạo vừa được thực hiện cho đơn hàng', 'kanbox'));
            }

            wp_redirect(esc_url($this->get_return_url($order)));
            exit;
        }
        public function webhook_api_momo_ipn(): void {
            $json = file_get_contents('php://input');
            $payment = json_decode($json, true);
            if (empty($payment)) {
                wp_send_json(0, 204);
                return;
            }

            $order = wc_get_order($payment['extraData']);
            if (!$order || !$this->validate_signature($payment, $this->create_signature($payment))) {
                $order->add_order_note(__('Một giao dịch giả mạo vừa được thực hiện cho đơn hàng', 'kanbox'));
                wp_send_json(0, 204);
                return;
            }

            $this->admin_field->update_payment_meta_data($order->get_id(), $payment);

            if (in_array($order->get_status(), ['completed', 'processing']) || $payment['resultCode'] != 0) {
                wp_send_json(1, 204);
                return;
            }
            $order->update_status('processing', sprintf(__('Mã thanh toán %s được xác nhận bằng IPN và đang được xử lý!', 'kanbox'), $payment['orderId']));
            wc_reduce_stock_levels($order->get_id());
            wp_send_json(1, 204);
        }
        public function turn_off_payment_gateway($gateways) {
            if (!WC()->cart) {
                return $gateways;
            }

            $total = (float) WC()->cart->get_cart_contents_total();
            if (isset($_GET['key'])) {
                $order_id = wc_get_order_id_by_order_key($_GET['key']);
                if ($order_id) {
                    $total = wc_get_order($order_id)->get_total();
                }
            }

            if (($total < 1000 || $total > 50000000) && isset($gateways[$this->id])) {
                unset($gateways[$this->id]);
            }
            return $gateways;
        }

        private function create_signature(array $payment): string {
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

            $rawHash = "accessKey=" . $this->accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&message=" . $message . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo .
            "&orderType=" . $orderType . "&partnerCode=" . $partnerCode . "&payType=" . $payType . "&requestId=" . $requestId . "&responseTime=" . $responseTime .
            "&resultCode=" . $resultCode . "&transId=" . $transId;

            return hash_hmac("sha256", $rawHash, $this->secretkey);
        }

        private function validate_signature(array $payment, string $expected_signature): bool {
            return isset($payment['signature']) && hash_equals($payment['signature'], $expected_signature);
        }

        public function process_admin_options(): bool {
            $this->init_settings();
            $post_data = $this->get_post_data();
            foreach ($this->get_form_fields() as $key => $field) {
                if ($this->get_field_type($field) === 'title') {
                    continue;
                }
                try {
                    $this->settings[$key] = $key === 'order_group_ids'
                        ? json_encode($post_data['woocommerce_' . $this->id . '_order_group_ids'] ?? [])
                        : $this->get_field_value($key, $field, $post_data);
                } catch (Exception $e) {
                    $this->add_error($e->getMessage());
                }
            }
            return update_option($this->get_option_key(), apply_filters('woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings), 'yes');
        }

        public function init_setting_scripts(): void {
            wp_enqueue_script('_kanbox_repeater', KANBOX_MOMO_URL . 'assets/js/repeater.js', ['jquery'], false, true);
            wp_enqueue_script('_kanbox_setting', KANBOX_MOMO_URL . 'assets/js/settings.js', ['jquery'], false, true);
            wp_localize_script('_kanbox_setting', 'script_data', $this->order_group_ids);
        }
        public function generate_json_html($key, $data): string {
            $field_key = $this->get_field_key($key);
            $defaults = [
                'title' => '', 'disabled' => false, 'class' => '', 'css' => '', 'placeholder' => '',
                'type' => 'json', 'desc_tip' => false, 'description' => '', 'custom_attributes' => [],
            ];
            $data = wp_parse_args($data, $defaults);
            $data['id'] = 'woocommerce_' . $this->id . '_order_group_ids';
            $data['value'] = $this->get_option('order_group_ids');
            $session_key = $this->session_key;

            ob_start();
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($data['id']); ?>">
                        <?php echo esc_html($data['title']); ?>
                        <span class="woocommerce-help-tip" data-tip="<?php echo esc_html($data['desc']); ?>"></span>
                    </label>
                </th>
                <td class="forminp forminp-<?php echo esc_attr($data['type']); ?>">
                    <div style="max-width: 500px">
                        <div style="display: flex">
                            <p style="flex: 1"><?php echo esc_html($session_key); ?></p>
                            <p style="flex: 1"><?php esc_html_e('orderGroupId', 'kanbox'); ?></p>
                        </div>
                        <div class="repeater">
                            <div data-repeater-list="<?php echo esc_attr($data['id']); ?>">
                                <div data-repeater-item style="margin-bottom: 15px;">
                                    <input type="text" name="label" style="width: 220px" />
                                    <input type="number" name="value" style="width: 220px" />
                                    <button data-repeater-delete type="button" class="button-secondary">
                                        <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg">
                                            <path fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="m112 112 20 320c.95 18.49 14.4 32 32 32h184c17.67 0 30.87-13.51 32-32l20-320"></path>
                                            <path stroke-linecap="round" stroke-miterlimit="10" stroke-width="32" d="M80 112h352"></path>
                                            <path fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M192 112V72h0a23.93 23.93 0 0 1 24-24h80a23.93 23.93 0 0 1 24 24h0v40m-64 64v224m-72-224 8 224m136-224-8 224"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <button data-repeater-create type="button" class="button-primary">
                                <?php esc_html_e('Thêm mới', 'kanbox'); ?>
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