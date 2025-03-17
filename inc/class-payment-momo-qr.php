<?php
if (!class_exists('MoMo_Qr_Payment_GateWay_Controller')) {
    class MoMo_Qr_Payment_GateWay_Controller extends Abstract_MoMo_Payment_Gateway {
        public $id = 'momo';

        public function __construct() {
            $this->method_title = __('Cổng thanh toán quét mã QR MoMo', 'kanbox');
            $this->method_description = __('Hỗ trợ thanh toán quét mã qua ứng dụng ví điện tử MoMo', 'kanbox');
            $this->payment_type = 'captureWallet';
            parent::__construct();
        }

        protected function initialize_form_fields(): array {
            return include KANBOX_MOMO_DIR . 'inc/settings/momo-qr-settings.php';
        }

        protected function get_ipn_url(): string {
            return untrailingslashit(get_home_url()) . "/wc-api/{$this->id}_ipn";
        }

        protected function get_redirect_url(): string {
            return untrailingslashit(get_home_url()) . "/wc-api/{$this->id}_redirect_url";
        }
    }
}