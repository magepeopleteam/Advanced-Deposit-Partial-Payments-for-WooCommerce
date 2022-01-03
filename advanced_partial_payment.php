<?php
/**
 * Plugin Name: Advanced Partial/Deposit Payment For Woocommerce
 * Plugin URI: http://mage-people.com
 * Description: This plugin will add Partial Payment System in the Woocommerce Plugin its also support Woocommerce Event Manager Plugin.
 * Version: 1.0.5
 * Author: MagePeople Team
 * Author URI: http://www.mage-people.com/
 * Text Domain: advanced-partial-payment-or-deposit-for-woocommerce
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}




/**
 * Initialize the plugin tracker
 *
 * @return void
 */
function appsero_init_tracker_advanced_partial_payment_or_deposit_for_woocommerce() {

    if ( ! class_exists( 'Appsero\Client' ) ) {
      require_once __DIR__ . '/lib/appsero/src/Client.php';
    }

    $client = new Appsero\Client( '32e33bac-f5fd-446b-8451-4bf9942e57e8', 'Advanced Partial Payment and Deposit For Woocommerce', __FILE__ );

    // Active insights
    $client->insights()->init();

}

appsero_init_tracker_advanced_partial_payment_or_deposit_for_woocommerce();




/**
 * Check if WooCommerce is active
 */
function wcpp_woocommerce_is_active()
{
    if (!function_exists('is_plugin_active_for_network'))
        require_once(ABSPATH . '/wp-admin/includes/plugin.php');
    // Check if WooCommerce is active
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        return is_plugin_active_for_network('woocommerce/woocommerce.php');
    }
    return true;
}

include_once(ABSPATH . 'wp-admin/includes/plugin.php');
if (wcpp_woocommerce_is_active()) {
    define('WCPP_PLUGIN_URL', plugin_dir_url(__FILE__));
    require_once(dirname(__FILE__) . "/inc/file_include.php");

} else {

    function mep_pp_not_active_warning()
    {
        $class = 'notice notice-error';
        $message = __('Advanced Partial/Deposit Payment For Woocommerce is Dependent on Woocommerce', 'advanced-partial-payment-or-deposit-for-woocommerce');
        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }

    add_action('admin_notices', 'mep_pp_not_active_warning');
}
