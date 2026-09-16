<?php
/**
 * Plugin runtime: settings access and module wiring.
 *
 * @package AumDigital
 */

defined( 'ABSPATH' ) || exit;

/**
 * Boots the modules and answers settings questions for them.
 */
final class AumDigital {

	/**
	 * Singleton.
	 *
	 * @var AumDigital|null
	 */
	private static $instance = null;

	/**
	 * Get the instance.
	 *
	 * @return AumDigital
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire everything up.
	 */
	private function __construct() {
		AumDigital_Cart::register();

		if ( self::enabled( 'aumdigital_hide_address', 'yes' ) ) {
			AumDigital_Address::register();
		}
		if ( self::enabled( 'aumdigital_autocomplete', 'yes' ) ) {
			AumDigital_Orders::register();
		}
		if ( self::enabled( 'aumdigital_waiver', 'no' ) ) {
			AumDigital_Waiver::register();
		}

		add_filter( 'woocommerce_get_settings_pages', array( $this, 'settings_page' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( AUMDIGITAL_FILE ), array( $this, 'action_links' ) );
	}

	/**
	 * Whether a yes/no option is on.
	 *
	 * @param string $option  Option name.
	 * @param string $default Default when unset.
	 * @return bool
	 */
	public static function enabled( $option, $default ) {
		return 'yes' === get_option( $option, $default );
	}

	/**
	 * Add the settings tab under WooCommerce > Settings.
	 *
	 * The settings live inside WooCommerce's own settings screen rather than on a
	 * page of their own: the people who install this are already in WooCommerce
	 * settings, and one more top-level menu entry is the last thing they need.
	 *
	 * @param array $pages Settings pages.
	 * @return array
	 */
	public function settings_page( $pages ) {
		require_once AUMDIGITAL_DIR . 'includes/class-aumdigital-settings.php';
		$pages[] = new AumDigital_Settings();
		return $pages;
	}

	/**
	 * "Settings" link on the Plugins screen.
	 *
	 * @param string[] $links Existing links.
	 * @return string[]
	 */
	public function action_links( $links ) {
		$url = admin_url( 'admin.php?page=wc-settings&tab=aumdigital' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'aumdigital' ) . '</a>' );
		return $links;
	}
}
