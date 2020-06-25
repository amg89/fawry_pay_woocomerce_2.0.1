<?php

/**
 * @link              https://fawry.com/
 * @since             1.0.0
 * @package           fawry_pay
 *
 * @wordpress-plugin
 * Plugin Name:       Fawry Pay
 * Plugin URI:        https://www.atfawry.com/
 * Description:       Fawry integration plugin.
 * Version:           2.0.1
 * Author:            Fawry Cooperation
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
/**
 * Check if WooCommerce is active
 * */
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    exit;
}
////////////////CONSTANTS//////////////////////
define('MY_FAW_PAYMENT_METHOD','fawry_pay');
//////////////////////////////////////////////
// gets the absolute path to this plugin directory
function my_faw_plugin_path() {
    return untrailingslashit(plugin_dir_path(__FILE__));
}

if (!defined('MY_FAW_URL')) {
    define('MY_FAW_URL', plugin_dir_url(__FILE__));
}



//add class to woo commerce payment methods
function add_my_faw_gateway_class($methods) {
    $methods[] = 'wc_gateway_at_fawry_payment';
    return $methods;
}
add_filter('woocommerce_payment_gateways', 'add_my_faw_gateway_class');

//register class
function init_my_faw_gateway_class() {
    require_once 'inc/wc_gateway_at_fawry_payment.php';

}
add_action('plugins_loaded', 'init_my_faw_gateway_class');

/////////////////includes////////////////////////
require_once 'inc/thankyoupage_customizer.php';
require_once 'inc/cancel_unpaid_on_hold_schedule.php';
require_once 'inc/activation.php';

register_activation_hook( __FILE__, 'my_faw_activate' );
register_deactivation_hook(__FILE__, 'my_faw_deactivate');

