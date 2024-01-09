<?php 
/* Version 1.0.0 For free user */
/* Add payment settings with MoMo enterprise e-wallet, deploy 2 real and test environments */

return array(
    'enabled' => array(
        'title'       => 'Enable/Disable',
        'label'       => __('Bật thanh toán quét mã MoMo', 'kanbox'),
        'type'        => 'checkbox',
        'description' => '',
        'default'     => 'no'
    ),
    'title' => array(
        'title'       => 'Tiêu đề',
        'type'        => 'text',
        'description' => __('Tiêu đề mà người dùng có thể thấy được trong quá trình thanh toán.', 'kanbox'),
        'default'     => __('Cổng thanh toán trực tuyến MoMo (ATM)', 'kanbox'),
        'description' => __('Để sử dụng được chính xác, hãy xem thêm <a href="https://developers.momo.vn/v3/docs/app-center/design-guideline/branding-guideline/"> hướng dẫn sử dụng thương hiệu MoMo</a> trong quá trình thanh toán', 'kanbox'),
        'desc_tip'    => true,
    ),
    'description' => array(
        'title'       => __('Mô tả ngắn', 'kanbox'),
        'type'        => 'textarea',
        'description' => __('Thêm mô tả ngắn để mô tả sơ lược tính năng thanh toán bằng ví điện tử khi mua hàng.', 'kanbox'),
        'default'     => __('Thanh toán bằng thẻ Atm bạn qua cổng thanh toán trực tuyến MoMo.', 'kanbox'),
    ),
    'lang'           => array(
        'type'          => 'select',
        'title'       => __('Ngôn ngữ', 'kanbox'),
        'required'    => true,
        'options'     => array(
                        'vi' => __('Vietnamese'),
                        'en' => __('English')
        ),
        'default' => 'vi'
    ),
    'enabled_refund' => array(
        'title'       => 'Tính năng hoàn tiền',
        'label'       => __('Bật tính tính năng hoàn tiền trên đơn hàng', 'kanbox'),
        'type'        => 'checkbox',
        'description' => __('Để sử dụng được tính năng hoàn tiền, bạn cần được cho phép sử dụng API hoàn tiền của MoMo Business', 'kanbox'),
        'default'     => 'no'
    ),
    'store_id' => array(
        'title'       => __('Store ID', 'kanbox'),
        'type'        => 'text',
        'description' => __('Tùy chỉnh, mã cửa hàng sẽ được thêm thông tin vào đơn thanh toán', 'kanbox'),
        'desc_tip'    => true,
    ),
    'partner_name' => array(
        'title'       => __('Tên cửa hàng', 'kanbox'),
        'type'        => 'text',
        'description' => __('Tùy chỉnh, tên cửa hàng sẽ được thêm thông tin vào đơn thanh toán', 'kanbox'),
        'desc_tip'    => true,
    ),
    'order_info' => array(
        'title'       => __('Thông tin đơn hàng', 'kanbox'),
        'type'        => 'text',
        'description' => __('Tùy chỉnh, thông tin thêm được định dạng cộng thêm mã đơn hàng khi khách hàng thanh toán', 'kanbox'),
        'desc_tip'    => true,
    ),
    'partner_code' => array(
        'title'       => __('Partner code', 'kanbox'),
        'type'        => 'text',
        'description' => __('Partner code được cấp bởi MoMo Business - môi trường thực tế', 'kanbox'),
        'desc_tip'    => true,
    ),
    'access_key' => array(
        'title'       => __('Access Key', 'kanbox'),
        'type'        => 'password',
        'description' => __('Access Key được cấp bởi MoMo Business - môi trường thực tế', 'kanbox'),
        'desc_tip'    => true,
    ),
    'secret_key' => array(
        'title'       => __('Secret key', 'kanbox'),
        'type'        => 'password',
        'description' => __('Secret key được cấp bởi MoMo Business - môi trường thực tế', 'kanbox'),
        'desc_tip'    => true,
    ),
    'api_enpoint' => array(
        'title'       => __('API endpoint', 'kanbox'),
        'type'        => 'text',
        'description' => __('Được cấp bởi MoMo Business - môi trường thực tế', 'kanbox'),
        'desc_tip'    => true,
        'default'     => 'https://payment.momo.vn'
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
        'title'       => __('Partner code', 'kanbox'),
        'type'        => 'text',
        'description' => __('Được cấp bởi MoMo Business - môi trường thử nghiệm', 'kanbox'),
        'desc_tip'    => true,
        'default'     => 'MOMOBKUN20180529'
    ),
    'access_key_test' => array(
        'title'       => __('Access Key', 'kanbox'),
        'type'        => 'password',
        'description' => __('Được cấp bởi MoMo Business - môi trường thử nghiệm', 'kanbox'),
        'desc_tip'    => true,
        'default'     => 'klm05TvNBzhg7h7j'
    ),
    'secret_key_test' => array(
        'title'       => __('Secret key', 'kanbox'),
        'type'        => 'password',
        'description' => __('Được cấp bởi MoMo Business - môi trường thử nghiệm', 'kanbox'),
        'desc_tip'    => true,
        'default'     => 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa'
    ),
    'api_enpoint_test' => array(
        'title'       => __('API endpoint', 'kanbox'),
        'type'        => 'text',
        'description' => __('Được cấp bởi MoMo Business - môi trường thử nghiệm', 'kanbox'),
        'desc_tip'    => true,
        'default'     => 'https://test-payment.momo.vn'
    )
);