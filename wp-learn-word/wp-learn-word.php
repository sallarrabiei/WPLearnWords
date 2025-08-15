<?php
/**
 * Plugin Name: WP Learn Word
 * Description: Learn English vocabulary using the Leitner System with books, CSV import, and Zarinpal payments.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: wp-learn-word
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
	exit;
}

// Constants
if (!defined('RASWP_VERSION')) {
	define('RASWP_VERSION', '1.0.0');
}
if (!defined('RASWP_PLUGIN_FILE')) {
	define('RASWP_PLUGIN_FILE', __FILE__);
}
if (!defined('RASWP_PLUGIN_DIR')) {
	define('RASWP_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('RASWP_PLUGIN_URL')) {
	define('RASWP_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Includes
require_once RASWP_PLUGIN_DIR . 'includes/class-raswp-activator.php';
require_once RASWP_PLUGIN_DIR . 'includes/class-raswp-cpt.php';
require_once RASWP_PLUGIN_DIR . 'includes/class-raswp-admin.php';
require_once RASWP_PLUGIN_DIR . 'includes/class-raswp-frontend.php';
require_once RASWP_PLUGIN_DIR . 'includes/class-raswp-zarinpal.php';

// Activation
function raswp_activate() {
	RASWP_Activator::raswp_activate();
}
register_activation_hook(__FILE__, 'raswp_activate');

// Load plugin textdomain
function raswp_load_textdomain() {
	load_plugin_textdomain('wp-learn-word', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'raswp_load_textdomain');

// Init components
function raswp_init_components() {
	RASWP_CPT::raswp_register_cpts();
}
add_action('init', 'raswp_init_components');

function raswp_admin_init_components() {
	RASWP_Admin::raswp_register_admin();
}
add_action('admin_init', 'raswp_admin_init_components');

function raswp_plugins_loaded() {
	RASWP_Frontend::raswp_bootstrap_frontend();
	RASWP_Zarinpal::raswp_register_routes();
}
add_action('init', 'raswp_plugins_loaded', 20);

// Shortcode
function raswp_register_shortcodes() {
	add_shortcode('raswp_learn', ['RASWP_Frontend', 'raswp_render_shortcode']);
}
add_action('init', 'raswp_register_shortcodes', 30);