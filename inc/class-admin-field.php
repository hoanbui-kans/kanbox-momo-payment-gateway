<?php
if(!class_exists('Kanbox_MoMo_Payment_Admin_field')) {
    class Kanbox_MoMo_Payment_Admin_field {

        private static $instance;
    
        public static function get_instance(){
            if(NULL === self::$instance){
                self::$instance = new Kanbox_MoMo_Payment_Admin_field();
            }
            return self::$instance;
        }
    
        function __construct(){
            // Backend: Display editable custom billing fields
            add_filter( 'woocommerce_admin_billing_fields' , array($this, 'order_admin_custom_fields') );
            add_filter( 'woocommerce_admin_shipping_fields' , array($this, 'order_admin_custom_fields') );
        }
    
        public function order_admin_custom_fields( $fields ) {
            global $the_order;
            $fields['momo_order_id'] = array(
                'label' => __( 'Mã đơn thanh toán MoMo', 'kanbox' ),
                'show'  => true,
                'wrapper_class' => 'form-field-wide',
            );
            $fields['momo_transid'] = array(
                'label' => __( 'Mã giao dịch thanh toán MoMo', 'kanbox' ),
                'show'  => true,
                'wrapper_class' => 'form-field-wide',
            );
            $fields['momo_type'] = array(
                'label' => __( 'Phương thức thanh toán', 'kanbox' ),
                'show'  => true,
                'wrapper_class' => 'form-field-wide',
            );
            $fields['momo_total'] = array(
                'label' => __( 'Tổng thanh toán', 'kanbox' ),
                'show'  => true,
                'wrapper_class' => 'form-field-wide',
            );
            $fields['momo_message'] = array(
                'label' => __( 'Trạng thái', 'kanbox' ),
                'show'  => true,
                'wrapper_class' => 'form-field-wide',
            );
            $fields['momo_resultcode'] = array(
                'label' => __( 'Mã kết quả', 'kanbox' ),
                'show'  => true,
                'wrapper_class' => 'form-field-wide',
            );
            $fields['momo_time'] = array(
                'label' => __( 'Thời gian', 'kanbox' ),
                'show'  => true,
                'wrapper_class' => 'form-field-wide',
            );
            return $fields;
        }
    
        public function update_payment_meta_data($order_id, $payment_data){

            if(!$order_id) return false;

            $momo_order_id = $payment_data['orderId'];
            $momo_transid = $payment_data['transId'];
            $momo_type = $payment_data['payType'];
            $momo_total = $payment_data['amount'];
            $momo_message = $payment_data['message'];
            $momo_resultcode = $payment_data['resultCode'];
            $momo_time = get_date_from_gmt(date("Y-m-d H:i:s", $payment_data['responseTime'] / 1000));
            if ( $momo_order_id ) {
                update_post_meta( $order_id, '_billing_momo_order_id', $momo_order_id );
            }
            if ( $momo_transid ) {
                update_post_meta( $order_id, '_billing_momo_transid', $momo_transid );
            }
            if ( $momo_type ) {
                update_post_meta( $order_id, '_billing_momo_type', $momo_type );
            }
            if ( $momo_total ) {
                update_post_meta( $order_id, '_billing_momo_total', $momo_total );
            }
            if ( $momo_message ) {
                update_post_meta( $order_id, '_billing_momo_message', $momo_message );
            }
            if ( $momo_resultcode ) {
                update_post_meta( $order_id, '_billing_momo_resultcode', $momo_resultcode );
            }
            if ( $momo_time ) {
                update_post_meta( $order_id, '_billing_momo_time', $momo_time );
            }
        }
    }
    Kanbox_MoMo_Payment_Admin_field::get_instance();
}