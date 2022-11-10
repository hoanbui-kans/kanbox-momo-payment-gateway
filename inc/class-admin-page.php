<?php
/* Add admin page for Kanbox Momo payment gateway */

if(!class_exists('Kanbox_Momo_Admin_Page')){
    class Kanbox_Momo_Admin_Page {
        function __construct(){
            add_action( 'admin_menu', [$this, 'kanbox_momo_admin_menu'] );
            add_action( 'admin_enqueue_scripts', [$this, 'register_plugin_scripts'] );
            add_action( 'admin_enqueue_scripts', [$this, 'load_plugin_scripts'] );
        }
    
        function kanbox_momo_admin_menu() {
            add_menu_page(
                __( 'Thanh toán Momo', 'kanbox' ),
                __( 'Thanh toán Momo', 'kanbox' ),
                'manage_options',
                'kanbox-momo',
                [$this, 'kanbox_momo_admin_page_contents'],
                KANBOX_MOMO_URL . 'assets/img/kanbox-favicon.png',
                3
            );
        }

        function get_plugin_infor(){
            if( ! function_exists('get_plugin_data') ){
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            $plugin_data = get_plugin_data( KANBOX_MOMO_DIR . 'kanbox-momo.php' );
            return $plugin_data;
        }
    
        function kanbox_momo_admin_page_contents() {
            $installed_payment_methods = WC()->payment_gateways()->get_available_payment_gateways();
            $pluginInfo = $this->get_plugin_infor(); 
            $enableMomoPaymentGateWay = false;

            $MomoPaymentSettingUrl = get_home_url() . '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=momo';
            $PaymentSettingUrl = get_home_url() . '/wp-admin/admin.php?page=wc-settings&tab=checkout';

            if(isset($installed_payment_methods['momo'])){
                $enableMomoPaymentGateWay = true;
            }?>

            </pre>
            <div id="fb-root"></div>
            <script async defer crossorigin="anonymous" src="https://connect.facebook.net/vi_VN/sdk.js#xfbml=1&version=v15.0&appId=564565127481018&autoLogAppEvents=1" nonce="bta8zEcJ"></script>
            <main class="container">
                <div class="row">
                    <div class="col-12 col-md-8">
                        <div class="d-flex align-items-center p-3 my-3 text-white bg-white rounded shadow-sm border">
                            <a href="https://kansite.com.vn" class="me-auto">
                                <img src="<?php echo esc_url( KANBOX_MOMO_URL . '/assets/img/logo.svg' );?>" width="120" height="40" alt="">
                            </a>
                            <?php 
                                if($enableMomoPaymentGateWay): ?>
                                    <a href="<?php echo esc_url( $MomoPaymentSettingUrl ); ?>" class="btn btn-dark">
                                        Cài đặt thanh toán
                                    </a>
                                    <?php
                                        else: 
                                    ?>
                                    <a href="<?php echo esc_url( $PaymentSettingUrl ); ?>" class="btn btn-dark position-relative">
                                        Bật thanh toán với Momo   
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            1
                                            <span class="visually-hidden">Bật thanh toán</span>
                                        </span>
                                    </a>
                                    <?php
                                endif;
                            ?>
                        </div>

                        <div class="my-3 p-3 bg-body rounded shadow-sm border">
                            <h6 class="border-bottom pb-2 mb-3">Các dịch vụ của chúng tôi</h6>
                            <div class="d-flex text-muted p-3 bg-light rounded mb-3">
                                <img class="bd-placeholder-img flex-shrink-0 me-3 rounded shadow-sm p-2" width="48" height="48" src="<?php echo esc_url( KANBOX_MOMO_URL . 'assets/img/design-svgrepo-com.svg' );?>" alt="">
                                <a target="_blank" class="text-decoration-none text-muted" href="https://kansite.com.vn/dich-vu/thiet-ke-website-tron-goi-cho-doanh-nghiep">
                                    <p class="pb-3 mb-0 small lh-sm">
                                        <strong class="d-block text-dark mb-1 d-block">Thiết kế website</strong>
                                        Dịch vụ tạo website nhanh theo mẫu, thiết kế website cho doanh nghiệp quản trị nội dung bằng website có sẵn, tối ưu chi phí thiết lập ban đầu.
                                    </p>
                                </a>
                            </div>
                            <div class="d-flex text-muted p-3 bg-light rounded mb-3">
                                <img class="bd-placeholder-img flex-shrink-0 me-3 rounded shadow-sm p-2" width="48" height="48" src="<?php echo esc_url( KANBOX_MOMO_URL . 'assets/img/marketing-svgrepo-com.svg' );?>" alt="">
                                <a target="_blank" class="text-decoration-none text-muted" href="https://kansite.com.vn/dich-vu/giai-phap-marketing-online-cho-doanh-nghiep">
                                    <p class="pb-3 mb-0 small lh-sm">
                                        <strong class="d-block text-dark mb-1 d-block">Dịch vụ marketing online</strong>
                                        Hỗ trợ xây dựng các chiến dịch quảng cáo, quảng bá thương hiệu, nghiên cứu, phân tích, tư vấn hỗ trợ.
                                    </p>
                                </a>
                            </div>
                            <div class="d-flex text-muted bg-light rounded mb-3 p-3">
                                <img class="bd-placeholder-img flex-shrink-0 me-3 rounded shadow-sm p-2" width="48" height="48" src="<?php echo esc_url( KANBOX_MOMO_URL . 'assets/img/user-seo-and-web-svgrepo-com.svg' );?>" alt="">
                                <a target="_blank" class="text-decoration-none text-muted" href="https://kansite.com.vn/dich-vu/giai-phap-quan-tri-noi-dung-website-cho-doanh-nghiep">
                                    <p class="pb-3 mb-0 small lh-sm">
                                            <strong class="d-block text-dark mb-1 d-block">Quản trị website</strong>
                                        Dịch vụ quản trị, quản lý, vận hành, sản xuất nội dung cho website, tối ưu chi phí quản lý, nâng cao hiệu quả chiến dịch.
                                    </p>
                                </a>
                            </div>
                        </div>

                        <div class="my-3 p-3 bg-body rounded shadow-sm border">
                            <h6 class="border-bottom pb-2 mb-3">Hướng dẫn chi tiết</h6>
                            <div class="d-flex text-muted p-3 bg-light rounded mb-3">
                                <img class="bd-placeholder-img flex-shrink-0 me-3 rounded shadow-sm p-2" width="48" height="48" src="<?php echo esc_url( KANBOX_MOMO_URL . 'assets/img/momo.png' );?>" alt="">
                                <div class="pb-3 mb-0 small lh-sm w-100">
                                    <a target="_blank" class="text-decoration-none text-muted" href="https://business.momo.vn/signup">
                                        <strong class="text-dark mb-1 d-block">Đăng ký Momo Business</strong>
                                        <span class="d-block">Đăng ký quyền quản trị Merchant đối với doanh nghiệp có website bán hàng/dịch vụ trực tuyến</span>
                                    </a>
                                </div>
                            </div>
                            <div class="d-flex text-muted p-3 bg-light rounded mb-3">
                                <img class="bd-placeholder-img flex-shrink-0 me-3 rounded shadow-sm p-2" width="48" height="48" src="<?php echo esc_url( KANBOX_MOMO_URL . 'assets/img/shop-svgrepo-com.svg' );?>" alt="">
                                <div class="pb-3 mb-0 small lh-sm w-100">
                                    <a target="_blank" class="text-decoration-none text-muted" href="https://kansite.com.vn/bai-viet/huong-dan-cai-dat-thanh-toan-momo-voi-woocommerce">
                                        <p>
                                            <strong class=" text-dark mb-1 d-block">Hướng dẫn cài đặt thanh toán</strong>
                                            <span class="d-block text-start">Hướng dẫn cài đặt cấu hình thanh toán trực tuyến với Quét mã thanh toán Momo</span>
                                        </p>
                                    </a>
                                </div>
                            </div>
                            <div class="d-flex text-muted bg-light rounded mb-3 p-3">
                                <img class="bd-placeholder-img flex-shrink-0 me-3 rounded shadow-sm p-2" width="48" height="48" src="<?php echo esc_url( KANBOX_MOMO_URL . 'assets/img/online-support-svgrepo-com.svg' );?>" alt="">
                                <div class="pb-3 mb-0 small lh-sm w-100">
                                    <a target="_blank" class="text-decoration-none text-muted" href="https://kansite.com.vn/lien-he">
                                        <p>
                                            <strong class=" text-dark mb-1 d-block">Thông tin góp ý, hỗ trợ</strong>
                                            <span class="d-block text-start">Bạn cần hỗ trợ, góp ý, hoặc nhận thêm thông tin cập nhật plugin chính thức từ Kan solution</span>
                                        </p>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="p-3 mt-3 border rounded shadow-sm mb-3">
                            <h6 class="border-bottom pb-2 mb-3">Fanpage facebook</h6>
                            <div class="">
                                <div class="fb-page" data-href="https://www.facebook.com/profile.php?id=100084739885212" data-tabs="" data-height="" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="true"><blockquote cite="https://www.facebook.com/profile.php?id=100084739885212" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/profile.php?id=100084739885212">KanS</a></blockquote></div>
                            </div>
                        </div>
                        <div class="lh-1">
                            <h1 class="h6 mb-0 lh-1">Thanh toán Momo</h1>
                            <small class="text-muted">Version: <?php echo $pluginInfo['Version']; ?></small>
                        </div>
                    </div>
                </div>
            </main>
           <?php
        }

        function register_plugin_scripts(){
            wp_register_style( 'bootstrap', KANBOX_MOMO_URL . 'assets/css/bootstrap.min.css', false, true);
        }

        function load_plugin_scripts( $hook ) {
            // Load only on ?page=kanbox-momo
            if( $hook != 'toplevel_page_kanbox-momo' ) {
                return;
            }
            
            // Load style & scripts.
            wp_enqueue_style('bootstrap');
            wp_enqueue_style('kanbox-momo-plugin');
            wp_enqueue_script('bootstrap');
            wp_enqueue_script('kanbox-momo-plugin');
        }
            
    }
    new Kanbox_Momo_Admin_Page();
}
