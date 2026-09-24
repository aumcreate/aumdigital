<?php
/**
 * Plugin Name:       AumDigital – Digital Goods Checkout for WooCommerce
 * Plugin URI:       https://aumcreate.com/plugins/aumdigital
 * Description:       Checkout built for digital goods. No address when the cart is all virtual, paid virtual orders complete themselves, and the EU withdrawal-right consent a digital store needs. Works with both the block checkout and the shortcode checkout.
 * Version:           1.0.2
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * WC requires at least: 8.7
 * WC tested up to:   10.9
 * Author:            AumCreate
 * Author URI:        https://aumcreate.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       aumdigital
 *
 * @package AumDigital
 */

defined( 'ABSPATH' ) || exit;

define( 'AUMDIGITAL_VERSION', '1.0.2' );
define( 'AUMDIGITAL_FILE', __FILE__ );
define( 'AUMDIGITAL_DIR', plugin_dir_path( __FILE__ ) );

/*
 * Declared before WooCommerce initialises, which is the only moment these
 * declarations are read. Without them WooCommerce lists the plugin as
 * incompatible with HPOS and with the block checkout, and the whole point of
 * this plugin is that it supports both checkouts.
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'AumDigital needs WooCommerce to be installed and active.', 'aumdigital' ) . '</p></div>';
				}
			);
			return;
		}

		require_once AUMDIGITAL_DIR . 'includes/class-aumdigital-cart.php';
		require_once AUMDIGITAL_DIR . 'includes/class-aumdigital-address.php';
		require_once AUMDIGITAL_DIR . 'includes/class-aumdigital-orders.php';
		require_once AUMDIGITAL_DIR . 'includes/class-aumdigital-waiver.php';
		require_once AUMDIGITAL_DIR . 'includes/class-aumdigital.php';

		AumDigital::instance();
	}
);
