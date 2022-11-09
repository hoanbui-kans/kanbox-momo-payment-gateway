<?php 
/* Version 1.0.0 For free user */
/* Add payment settings with momo enterprise e-wallet, deploy 2 real and test environments */

return array(
    'enabled' => array(
        'title'       => 'Enable/Disable',
        'label'       => __('Bật thanh toán quét mã Momo', 'kanbox'),
        'type'        => 'checkbox',
        'description' => '',
        'default'     => 'no'
    ),
    'title' => array(
        'title'       => 'Tiêu đề',
        'type'        => 'text',
        'description' => __('Tiêu đề mà người dùng có thể thấy được trong quá trình thanh toán.', 'kanbox'),
        'default'     => 'Thanh toán Momo',
        'desc_tip'    => true,
    ),
    'description' => array(
        'title'       => __('Mô tả ngắn', 'kanbox'),
        'type'        => 'textarea',
        'description' => __('Thêm mô tả ngắn để mô tả sơ lược tính năng thanh toán bằng ví điện tử khi mua hàng.', 'kanbox'),
        'default'     => __('Thanh toán bằng quét mã QR bạn trên ứng dụng Momo.', 'kanbox'),
    ),
    'enabled_refund' => array(
        'title'       => 'Tính năng hoàn tiền',
        'label'       => __('Bật tính tính năng hoàn tiền trên đơn hàng', 'kanbox'),
        'type'        => 'checkbox',
        'description' => 'Để sử dụng được tính năng hoàn tiền, bạn cần được cho phép sử dụng API hoàn tiền của Momo Business',
        'default'     => 'no'
    ),
    'partner_name' => array(
        'title'       => __('Tên cửa hàng', 'kanbox'),
        'type'        => 'text',
        'description' => __('Tùy chỉnh', 'kanbox'),
        'desc_tip'    => true,
    ),
    'extra_data' => array(
        'title'       => __('Thông tin thêm', 'kanbox'),
        'type'        => 'text',
        'description' => __('Tùy chỉnh', 'kanbox'),
        'desc_tip'    => true,
    ),
    'partner_code' => array(
        'title'       => __('Partner code', 'kanbox'),
        'type'        => 'text',
        'description' => __('Được cấp bởi Momo Business - môi trường thử nghiệm', 'kanbox'),
        'desc_tip'    => true,
    ),
    'access_key' => array(
        'title'       => __('Access Key', 'kanbox'),
        'type'        => 'password',
        'description' => __('Được cấp bởi Momo Business', 'kanbox'),
        'desc_tip'    => true,
    ),
    'serect_key' => array(
        'title'       => __('Serect key', 'kanbox'),
        'type'        => 'password',
        'description' => __('Được cấp bởi Momo Business', 'kanbox'),
        'desc_tip'    => true,
    ),
    'testmode' => array(
        'title'       => __('Chế độ thử nghiệm', 'kanbox'),
        'label'       => __('Bật chế độ thử nghiệm', 'kanbox'),
        'type'        => 'checkbox',
        'description' => __('Đặt cổng thanh toán ở chế độ thử nghiệm bằng cách sử dụng các khóa API thử nghiệm.', 'kanbox'),
        'default'     => 'yes',
        'desc_tip'    => false,
    ),
    'partner_code_test' => array(
        'title'       => __('Partner code test', 'kanbox'),
        'type'        => 'text',
        'description' => __('Được cấp bởi Momo Business - môi trường thử nghiệm', 'kanbox'),
        'desc_tip'    => true,
    ),
    'access_key_test' => array(
        'title'       => __('Access Key test', 'kanbox'),
        'type'        => 'password',
        'description' => __('Được cấp bởi Momo Business - môi trường thử nghiệm', 'kanbox'),
        'desc_tip'    => true,
    ),
    'serect_key_test' => array(
        'title'       => __('Serect key test', 'kanbox'),
        'type'        => 'password',
        'description' => __('Được cấp bởi Momo Business - môi trường thử nghiệm', 'kanbox'),
        'desc_tip'    => true,
    )
);