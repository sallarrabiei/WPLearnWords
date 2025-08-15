<?php
/*
Plugin Name: WP Learn Word (raswp)
Description: Learn English vocabulary using the Leitner System with Zarinpal payment integration.
Version: 1.0.0
Author: Your Name
Text Domain: wp-learn-word
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Constants
if ( ! defined( 'RASWP_PLUGIN_FILE' ) ) {
	define( 'RASWP_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'RASWP_PLUGIN_PATH' ) ) {
	define( 'RASWP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'RASWP_PLUGIN_URL' ) ) {
	define( 'RASWP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Includes
require_once RASWP_PLUGIN_PATH . 'includes/class-raswp-activator.php';
require_once RASWP_PLUGIN_PATH . 'includes/class-raswp-cpt.php';
require_once RASWP_PLUGIN_PATH . 'includes/class-raswp-admin.php';
require_once RASWP_PLUGIN_PATH . 'includes/class-raswp-importer.php';
require_once RASWP_PLUGIN_PATH . 'includes/class-raswp-leitner.php';
require_once RASWP_PLUGIN_PATH . 'includes/class-raswp-ajax.php';
require_once RASWP_PLUGIN_PATH . 'includes/class-raswp-payment.php';
require_once RASWP_PLUGIN_PATH . 'includes/class-raswp-shortcodes.php';

/**
 * Activation hook
 */
function raswp_activate() {
	\RASWP_Activator::activate();
}
register_activation_hook( __FILE__, 'raswp_activate' );

/**
 * Deactivation hook
 */
function raswp_deactivate() {
	// Nothing for now
}
register_deactivation_hook( __FILE__, 'raswp_deactivate' );

/**
 * Init plugin
 */
function raswp_init() {
	// Register CPTs & taxonomies
	\RASWP_CPT::register();

	// Admin
	if ( is_admin() ) {
		\RASWP_Admin::init();
	}

	// Payment
	\RASWP_Payment::init();

	// AJAX
	\RASWP_Ajax::init();

	// Shortcodes
	\RASWP_Shortcodes::init();
}
add_action( 'init', 'raswp_init' );

/**
 * Load textdomain
 */
function raswp_load_textdomain() {
	load_plugin_textdomain( 'wp-learn-word', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'raswp_load_textdomain' );

/**
 * Handle payment callback early
 */
function raswp_maybe_handle_zarinpal_callback() {
	if ( isset( $_GET['raswp_zarinpal_callback'] ) && '1' === $_GET['raswp_zarinpal_callback'] ) {
		\RASWP_Payment::handle_callback();
	}
}
add_action( 'init', 'raswp_maybe_handle_zarinpal_callback', 0 );