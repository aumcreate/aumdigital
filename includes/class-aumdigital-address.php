<?php
/**
 * No address for an all-digital cart.
 *
 * @package AumDigital
 */

defined( 'ABSPATH' ) || exit;

/**
 * Drops the street-address fields from checkout when nothing in the cart can
 * be shipped, on both checkouts.
 *
 * WooCommerce has one mechanism that both the shortcode checkout and the block
 * checkout obey: the per-country address locale, where a field marked
 * `required => false, hidden => true` is neither rendered nor validated. Core
 * uses it to drop the postcode for the UAE and the state for Afghanistan. This
 * class applies the same marking to the same fields, with the condition changed
 * from "which country" to "is anything in the cart physical".
 *
 * Country stays. Tax is calculated from it, and several payment gateways refuse
 * a payment without one. Name and email stay because the order is useless
 * without them. Company and phone are governed by WooCommerce's own settings
 * and are not touched here.
 */
final class AumDigital_Address {

	/**
	 * Fields that only mean something when a parcel is going somewhere.
	 *
	 * @var string[]
	 */
	const STREET_FIELDS = array( 'address_1', 'address_2', 'city', 'postcode', 'state' );

	/**
	 * Hook up.
	 *
	 * @return void
	 */
	public static function register() {
		// Both checkouts and the Store API read from these.
		add_filter( 'woocommerce_default_address_fields', array( __CLASS__, 'mark_base_fields' ), 100 );
		add_filter( 'woocommerce_get_country_locale_default', array( __CLASS__, 'mark_base_fields' ), 100 );
		add_filter( 'woocommerce_get_country_locale_base', array( __CLASS__, 'mark_base_fields' ), 100 );
		add_filter( 'woocommerce_get_country_locale', array( __CLASS__, 'mark_country_locales' ), 100 );

		// The shortcode checkout additionally lets the fields be removed outright.
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'remove_classic_fields' ), 100 );

		// WC_Countries caches the locale for the request the first time anything
		// asks for it. If that happened before the cart existed, the cached copy
		// says "show everything" and the filters above never get another chance.
		// Clearing it whenever the cart (re)loads makes the next read current.
		add_action( 'woocommerce_load_cart_from_session', array( __CLASS__, 'reset_locale_cache' ) );
		add_action( 'woocommerce_cart_loaded_from_session', array( __CLASS__, 'reset_locale_cache' ) );
		add_action( 'wp_loaded', array( __CLASS__, 'reset_locale_cache' ), 20 );
	}

	/**
	 * Whether this request should lose its address fields.
	 *
	 * Admin screens and the My Account address editor are never touched: an
	 * address being edited there is a real address, whatever is in the cart.
	 *
	 * @return bool
	 */
	public static function applies() {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return false;
		}

		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			return false;
		}

		return AumDigital_Cart::is_virtual_only();
	}

	/**
	 * Mark the street fields hidden and optional in a base field set.
	 *
	 * @param array $fields Address field definitions keyed by field name.
	 * @return array
	 */
	public static function mark_base_fields( $fields ) {
		if ( ! self::applies() || ! is_array( $fields ) ) {
			return $fields;
		}

		foreach ( self::STREET_FIELDS as $key ) {
			if ( isset( $fields[ $key ] ) && is_array( $fields[ $key ] ) ) {
				$fields[ $key ]['required'] = false;
				$fields[ $key ]['hidden']   = true;
			}
		}

		return $fields;
	}

	/**
	 * Mark the street fields hidden and optional for every country.
	 *
	 * Per-country entries override the base, and a country that declares
	 * `postcode => required` would bring the field back. So every country gets
	 * the same explicit marking; the base alone is not enough.
	 *
	 * @param array $locales Locale definitions keyed by country code.
	 * @return array
	 */
	public static function mark_country_locales( $locales ) {
		if ( ! self::applies() || ! is_array( $locales ) ) {
			return $locales;
		}

		foreach ( $locales as $country => $fields ) {
			if ( ! is_array( $fields ) ) {
				continue;
			}
			foreach ( self::STREET_FIELDS as $key ) {
				$current                             = isset( $fields[ $key ] ) && is_array( $fields[ $key ] ) ? $fields[ $key ] : array();
				$current['required']                 = false;
				$current['hidden']                   = true;
				$locales[ $country ][ $key ]         = $current;
			}
		}

		return $locales;
	}

	/**
	 * Remove the street fields from the shortcode checkout form entirely.
	 *
	 * The locale marking already stops them rendering; removing them as well
	 * means they are not in the posted data at all, which is the cleaner state
	 * for anything downstream that inspects the form.
	 *
	 * @param array $fields Checkout field groups.
	 * @return array
	 */
	public static function remove_classic_fields( $fields ) {
		if ( ! self::applies() || empty( $fields['billing'] ) ) {
			return $fields;
		}

		foreach ( self::STREET_FIELDS as $key ) {
			unset( $fields['billing'][ 'billing_' . $key ] );
		}

		return $fields;
	}

	/**
	 * Forget the request-level locale cache so the next read sees the cart.
	 *
	 * @return void
	 */
	public static function reset_locale_cache() {
		if ( function_exists( 'WC' ) && isset( WC()->countries ) && is_object( WC()->countries ) ) {
			WC()->countries->locale = array();
		}
	}
}
