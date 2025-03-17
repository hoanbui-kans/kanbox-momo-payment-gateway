<?php
if (!class_exists('Kanbox_MoMo_WooCommerce_User_Dashboard')) {
    class Kanbox_MoMo_WooCommerce_User_Dashboard {
        private static $instance = null;
        private $controller;

        private const TRANSACTION_FIELDS = [
            'orderId' => ['label' => 'ID giao dịch', 'mark' => true],
            'transId' => ['label' => 'Mã thanh toán', 'mark' => true],
            'message' => ['label' => 'Trạng thái', 'mark' => true],
            'amount' => ['label' => 'Tổng thanh toán'],
            'responseTime' => ['label' => 'Thời gian', 'callback' => 'format_time'],
        ];

        private function __construct(WC_Payment_Gateway $controller) {
            $this->controller = $controller;
            add_action('woocommerce_order_details_after_order_table', [$this, 'render_payment_dashboard']);
        }

        public static function init(WC_Payment_Gateway $controller): self {
            if (self::$instance === null || self::$instance->controller->id !== $controller->id) {
                self::$instance = new self($controller);
            }
            return self::$instance;
        }

        public function render_payment_dashboard(WC_Order $order): void {
            if ($this->controller->id !== $order->get_payment_method()) {
                return;
            }

            $is_paid = $order->is_paid();
            $order_status = $order->get_status();

            echo '<section class="momo-payment-dashboard">';
            !$is_paid && $order_status !== 'refunded'
                ? $this->render_pending_payment($order)
                : $this->render_payment_info($order, $order_status);
            echo '</section>';
        }

        private function render_pending_payment(WC_Order $order): void {
            $payment = $this->controller->process_payment($order->get_id());
            $this->render_table('Thanh toán', [
                ['Thông tin thanh toán', __('Bạn chưa thanh toán cho đơn hàng này', 'kanbox')],
                !$order->is_paid() && empty($payment['error'])
                    ? [
                        'Thanh toán lại đơn hàng',
                        sprintf(
                            '<a href="%s" class="checkout-button button alt wc-forward wp-element-button">%s</a>',
                            esc_url($payment['redirect']),
                            esc_html($order->get_payment_method_title())
                        ),
                    ]
                    : ['Thông tin lỗi', esc_html($payment['message'])],
            ]);
        }

        private function render_payment_info(WC_Order $order, string $order_status): void {
            $transaction = $order_status === 'refunded'
                ? $this->get_refunded_transaction($order)
                : $this->controller->query_transaction($order->get_id());

            if (!$transaction) {
                return;
            }

            $rows = [];
            foreach (self::TRANSACTION_FIELDS as $key => $config) {
                if (!isset($transaction[$key]) || !$transaction[$key]) {
                    continue;
                }

                $value = $config['callback'] ?? false
                    ? $this->{$config['callback']}($transaction[$key])
                    : esc_html($transaction[$key]);
                $rows[] = [
                    $config['label'],
                    $config['mark'] ?? false ? "<mark>{$value}</mark>" : $value,
                ];
            }

            $this->render_table('Thông tin thanh toán', $rows);
        }

        private function render_table(string $title, array $rows): void {
            ?>
            <h2><?php esc_html_e($title, 'kanbox'); ?></h2>
            <table class="woocommerce-table shop_table payment_info">
                <tbody>
                    <?php foreach ($rows as [$label, $value]): ?>
                        <tr>
                            <th><?php esc_html_e($label, 'kanbox'); ?></th>
                            <td><?php echo $value; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        }

        private function get_refunded_transaction(WC_Order $order): array {
            return [
                'message' => __('Đã hoàn lại tiền', 'kanbox'),
                'orderId' => $order->get_meta('_billing_momo_order_id', true) ?: '',
            ];
        }

        private function format_time($timestamp): string {
            return get_date_from_gmt(date('Y-m-d H:i:s', $timestamp / 1000));
        }
    }

    $paymentGateways = [
        new MoMo_Qr_Payment_GateWay_Controller(),
        new MoMo_Atm_Payment_GateWay_Controller(),
        new MoMo_Credit_Payment_GateWay_Controller(),
    ];

    foreach ($paymentGateways as $gateway) {
        Kanbox_MoMo_WooCommerce_User_Dashboard::init($gateway);
    }
}