<?php

class Kanbox_Momo_Payment_Admin_field {
    private static $instance;
    public static function get_instance(){
        if(NULL === self::$instance){
            self::$instance = new Kanbox_Momo_Payment_Admin_field();
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
            'label' => __( 'Mã đơn thanh toán Momo', 'kanbox' ),
            'show'  => true,
            'wrapper_class' => 'form-field-wide',
        );

        $fields['momo_transid'] = array(
            'label' => __( 'Mã giao dịch thanh toán Momo', 'kanbox' ),
            'show'  => true,
            'wrapper_class' => 'form-field-wide',
        );
        return $fields;
    }

    public function update_payment_meta_data($order_id, $momo_order_id, $momo_transid){
        if(!$order_id) return false;
        if ( $momo_order_id ) {
            update_post_meta( $order_id, '_billing_momo_order_id', $momo_order_id );
        }
        if ( $momo_transid ) {
            update_post_meta( $order_id, '_billing_momo_transid', $momo_transid );
        }
    }
}

Kanbox_Momo_Payment_Admin_field::get_instance();