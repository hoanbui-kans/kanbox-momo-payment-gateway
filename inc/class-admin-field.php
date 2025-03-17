<?php
if (!class_exists('Kanbox_MoMo_Payment_Admin_field')) {
    class Kanbox_MoMo_Payment_Admin_field {
        private static $instance = null;

        private const CUSTOM_FIELDS = [
            'momo_order_id' => [
                'label' => 'Mã đơn thanh toán MoMo',
                'meta_key' => '_billing_momo_order_id',
                'data_key' => 'orderId',
            ],
            'momo_transid' => [
                'label' => 'Mã giao dịch thanh toán MoMo',
                'meta_key' => '_billing_momo_transid',
                'data_key' => 'transId',
            ],
            'momo_total' => [
                'label' => 'Tổng thanh toán',
                'meta_key' => '_billing_momo_total',
                'data_key' => 'amount',
            ],
            'momo_message' => [
                'label' => 'Trạng thái',
                'meta_key' => '_billing_momo_message',
                'data_key' => 'message',
            ],
            'momo_resultcode' => [
                'label' => 'Mã kết quả',
                'meta_key' => '_billing_momo_resultcode',
                'data_key' => 'resultCode',
            ],
            'momo_time' => [
                'label' => 'Thời gian',
                'meta_key' => '_billing_momo_time',
                'data_key' => 'responseTime',
            ],
        ];

        private function __construct() {
            add_filter('woocommerce_admin_billing_fields', [$this, 'order_admin_custom_fields']);
            add_filter('woocommerce_admin_shipping_fields', [$this, 'order_admin_custom_fields']);
        }

        public static function get_instance(): self {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function order_admin_custom_fields(array $fields): array {
            foreach (self::CUSTOM_FIELDS as $key => $config) {
                $fields[$key] = [
                    'label' => __($config['label'], 'kanbox'),
                    'show' => true,
                    'wrapper_class' => 'form-field-wide',
                ];
            }
            return $fields;
        }

        public function update_payment_meta_data($wc_order_id, array $payment_data): bool {
            if (!$wc_order_id || !($order = wc_get_order($wc_order_id))) {
                return false;
            }
            foreach (self::CUSTOM_FIELDS as $field => $config) {
                if (isset($payment_data[$config['data_key']])) {
                    $value = $field === 'momo_time'
                        ? get_date_from_gmt(date('Y-m-d H:i:s', $payment_data[$config['data_key']] / 1000))
                        : $payment_data[$config['data_key']];
                    $order->update_meta_data($config['meta_key'], $value);
                }
            }
            $order->save();
            return true;
        }
    }

}