<?php
if ( ! defined( 'ABSPATH' ) ) { die; }

add_action('woocommerce_init', 'mep_pp_includes');
if (!function_exists('mep_pp_includes')) {
    function mep_pp_includes() {    
        require_once(dirname(__DIR__) . "/inc/admin_settings.php");
        require_once(dirname(__DIR__) . "/inc/functions.php");
        require_once(dirname(__DIR__) . "/inc/wcpp_payment.php");
        require_once(dirname(__DIR__) . "/inc/class-wcpp-payment.php");
        require_once(dirname(__DIR__) . "/inc/meta.php");    
        require_once(dirname(__DIR__) . "/src/Cart.php");
        require_once(dirname(__DIR__) . "/inc/payment_history_cpt.php");
        require_once(dirname(__DIR__) . "/src/Checkout.php");    
        require_once(dirname(__DIR__) . "/src/Deposits_View.php");
        require_once(dirname(__DIR__) . "/src/Order.php");
        require_once(dirname(__DIR__) . "/inc/admin-order-detail.php");    
        require_once(dirname(__DIR__) . "/inc/class_admin_menu.php");
        require_once(dirname(__DIR__) . "/inc/class-welcome-page.php");
    }
}