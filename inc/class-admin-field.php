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
    
        public function update_payment_meta_data($wc_order_id, $payment_data){

            if(!$wc_order_id) return false;

            $orderBilling = wc_get_order( $wc_order_id );

            if ( isset($payment_data['orderId']) ) {
                $orderBilling->update_meta_data( '_billing_momo_order_id', $payment_data['orderId'] );
            }
            if ( isset($payment_data['transId']) ) {
                $orderBilling->update_meta_data( '_billing_momo_transid', $payment_data['transId'] );
            }
            if ( isset($payment_data['payType']) ) {
                $orderBilling->update_meta_data( '_billing_momo_type', $payment_data['payType'] );
            }
            if ( isset($payment_data['amount']) ) {
                $orderBilling->update_meta_data( '_billing_momo_total', $payment_data['amount'] );
            }
            if ( isset($payment_data['message']) ) {
                $orderBilling->update_meta_data( '_billing_momo_message', $payment_data['message'] );
            }
            if ( isset($payment_data['resultCode']) ) {
                $orderBilling->update_meta_data( '_billing_momo_resultcode', $payment_data['resultCode'] );
            }
            if ( isset($payment_data['responseTime']) ) {
                $orderBilling->update_meta_data( '_billing_momo_time', get_date_from_gmt(date("Y-m-d H:i:s", $payment_data['responseTime'] / 1000)) );
            }

            $orderBilling->save();
        }
    }
    Kanbox_MoMo_Payment_Admin_field::get_instance();
}