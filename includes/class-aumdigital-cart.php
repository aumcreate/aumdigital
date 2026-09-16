<?php
/**
 * The one question every module asks: is this cart all digital?
 *
 * @package AumDigital
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cart inspection, plus the Store API extension that lets the block checkout
 * ask the same question from JavaScript.
 */
final class AumDigital_Cart {

	/**
	 * Store API extension namespace.
	 */
	const EXT = 'aumdigital';

	/**
	 * Hook up.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'extend_store_api' ) );
	}

	/**
	 * Whether every line in the cart is a virtual product.
	 *
	 * Decided per item with is_virtual(), not with the cart's needs_shipping():
	 * that one also answers false when shipping is switched off store-wide, at
	 * which point a cart full of physical goods would lose its address fields.
	 *
	 * An empty or unavailable cart answers false. Every caller treats false as
	 * "leave the checkout alone", so the safe direction when in doubt is to show
	 * the fields, never to hide them.
	 *
	 * @return bool
	 */
	public static function is_virtual_only() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}

		$items = WC()->cart->get_cart();

		if ( empty( $items ) ) {
			return false;
		}

		foreach ( $items as $item ) {
			$product = isset( $item['data'] ) ? $item['data'] : null;

			if ( ! $product instanceof WC_Product || ! $product->is_virtual() ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Whether every line on an existing order is a virtual product.
	 *
	 * @param WC_Order $order Order.
	 * @return bool
	 */
	public static function order_is_virtual_only( $order ) {
		$items = $order->get_items();

		if ( empty( $items ) ) {
			return false;
		}

		foreach ( $items as $item ) {
			$product = is_callable( array( $item, 'get_product' ) ) ? $item->get_product() : null;

			if ( ! $product instanceof WC_Product || ! $product->is_virtual() ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Expose virtual_only on the Store API cart response.
	 *
	 * The block checkout evaluates field rules against the cart it fetched, so
	 * the answer has to travel with that response. This is the same channel the
	 * rules in AumDigital_Waiver read from.
	 *
	 * @return void
	 */
	public static function extend_store_api() {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			return;
		}

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
				'namespace'       => self::EXT,
				'data_callback'   => static function () {
					return array( 'virtual_only' => self::is_virtual_only() );
				},
				'schema_callback' => static function () {
					return array(
						'virtual_only' => array(
							'description' => __( 'Whether every item in the cart is a virtual product.', 'aumdigital' ),
							'type'        => 'boolean',
							'readonly'    => true,
						),
					);
				},
				'schema_type'     => ARRAY_A,
			)
		);
	}
}
