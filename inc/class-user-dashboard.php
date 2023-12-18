<?php
/* Main class Handle MoMo payment gateway */

if(!class_exists('Kanbox_MoMo_WooCommerce_User_Dashboard')) {
    class Kanbox_MoMo_WooCommerce_User_Dashboard extends WC_Payment_Gateway {
        function __construct(){
            add_action( 'woocommerce_order_details_after_order_table', [$this, 'kanbox_momo_payment_dashboard_section'] );
        }

        function kanbox_momo_payment_dashboard_section( $order ){

            $is_paid = $order->is_paid();
            $payment_method = $order->get_payment_method();
            $controller = false;

            switch($payment_method): 
                case "momo":
                    $controller = new MoMo_Qr_Payment_GateWay_Controller();
                break;
                case "momo-atm":
                    $controller = new MoMo_Atm_Payment_GateWay_Controller();
                break;
                case "momo-credit":
                    $controller = new MoMo_Credit_Payment_GateWay_Controller();
                break;
            endswitch;
            if( $controller ):
                if( !$is_paid && $order->get_status() != 'refunded' ):
                    $payment = $controller->process_payment($order->get_id());
                ?>
                    <h2><?php echo __('Thanh toán', 'kanbox');?></h2>
                    <table class="woocommerce-table shop_table payment_info">
                        <tbody>
                                <tr>
                                    <th><?php echo __('Thông tin thanh toán', 'kanbox');?></th>
                                    <td><?php echo __('Bạn chưa thanh toán cho đơn hàng này', 'kanbox',); ?></td>
                                </tr>
                                <?php if( !$is_paid ) : ?>   
                                <tr>
                                    <th><?php echo __('Thanh toán lại đơn hàng', 'kanbox');?></th>
                                    <td>
                                        <a href="<?php echo esc_url($payment['redirect']);?>" class="checkout-button button alt wc-forward wp-element-button">Thanh toán bằng MoMo</a>
                                    </td>
                                </tr>
                                <?php endif; ?>
                        </tbody>
                    </table>
                <?php
                else :
                    if($order->get_status() == 'refunded'){
                        $query_transaction = array(
                            'message' => __( 'Đã hoàn lại tiền', 'kanbox'),
                            'orderId' => get_post_meta($order->get_id(), '_billing_momo_order_id', true)
                        );
                    } else {
                        $query_transaction = $controller->query_transaction($order->get_id()); 
                    }
                    
                    if($query_transaction): 
                    ?>
                            <h2><?php echo __('Thông tin thanh toán', 'kanbox');?></h2>
                            <table class="woocommerce-table shop_table payment_info">
                                <tbody>
                                    <tr>
                                        <th><?php echo __('ID giao dịch', 'kanbox');?></th>
                                        <td>#<mark><?php echo esc_html( $query_transaction['orderId'] );?></mark></td>
                                    </tr>
                                    <tr>
                                        <th><?php echo __( 'Trạng thái', 'kanbox' );?></th>
                                        <td>
                                            <?php echo esc_html( $query_transaction['message'] , 'kanbox' );?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        <?php
                    endif;
                endif;
            endif;
        }
    }

    new Kanbox_MoMo_WooCommerce_User_Dashboard();
}