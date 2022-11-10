<?php
/* Main class Handle Momo payment gateway */

if(!class_exists('Kanbox_Momo_WooCommerce_User_Dashboard')) {
    class Kanbox_Momo_WooCommerce_User_Dashboard extends WC_Payment_Gateway{
        function __construct(){
            add_action( 'woocommerce_order_details_after_order_table', [$this, 'kanbox_momo_payment_dashboard_section'] );
        }

        function kanbox_momo_payment_dashboard_section( $order ){

            $is_paid = $order->is_paid();
            $controller = new Kanbox_Momo_Payment_GateWay_Controller();

            if(!$is_paid):
                $payment = $controller->process_payment($order->get_id());
            ?>
                <h2><?php echo esc_attr_e('Thanh toán', 'kanbox');?></h2>
                <table class="woocommerce-table shop_table payment_info">
                    <tbody>
                            <tr>
                                <th><?php echo esc_attr_e('Thông tin thanh toán', 'kanbox');?></th>
                                <td><?php echo esc_attr_e('Bạn chưa thanh toán cho đơn hàng này', 'kanbox',); ?></td>
                            </tr>
                            <?php if( !$is_paid && $order->get_status() != 'refund' ) : ?>   
                            <tr>
                                <th><?php echo esc_attr_e('Thanh toán lại đơn hàng', 'kanbox');?></th>
                                <td>
                                    <a href="<?php echo $payment['redirect'];?>" class="checkout-button button alt wc-forward wp-element-button">Thanh toán bằng Momo</a>
                                </td>
                            </tr>
                            <?php endif; ?>
                    </tbody>
                </table>
            <?php
            else :
                $query_transaction = $controller->query_transaction($order->get_id()); 
                if($query_transaction): 
                    ?>
                        <h2><?php echo esc_attr_e('Thông tin thanh toán', 'kanbox');?></h2>
                        <table class="woocommerce-table shop_table payment_info">
                            <tbody>
                                <tr>
                                    <th><?php echo esc_attr_e('Id giao dịch', 'kanbox');?></th>
                                    <td><mark><?php echo printf('%d', $query_transaction['orderId']);?></mark></td>
                                </tr>
                                <tr>
                                    <th><?php echo esc_attr_e('Trạng thái', 'kanbox');?></th>
                                    <td><?php echo htmlspecialchars($query_transaction['message']);?></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php
                endif;
            endif;
        }
    }

    new Kanbox_Momo_WooCommerce_User_Dashboard();
}