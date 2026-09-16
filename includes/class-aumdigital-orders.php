<?php
/**
 * Paid orders for digital goods complete themselves.
 *
 * @package AumDigital
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lets a paid all-virtual order go straight to Completed.
 *
 * WooCommerce already completes a paid order on its own -- but only when every
 * item is virtual AND downloadable. A virtual item that is not downloadable
 * (a licence key, a consultation, a course seat) "needs processing", so the
 * order sits in Processing until someone clicks. That is the right default for
 * a parcel and the wrong one for a licence key.
 *
 * Rather than changing order statuses ourselves, this answers WooCommerce's
 * own question -- "does this item need processing?" -- with "not if it is
 * virtual". Everything downstream (the status change, the emails, the
 * download permissions, the hooks other plugins listen to) then happens in
 * core, in the order core does it.
 *
 * Only the paid path is affected. Orders awaiting payment (bank transfer,
 * cheque, cash on delivery) never reach payment_complete() and are untouched.
 */
final class AumDigital_Orders {

	/**
	 * Hook up.
	 *
	 * @return void
	 */
	public static function register() {
		add_filter( 'woocommerce_order_item_needs_processing', array( __CLASS__, 'virtual_needs_no_processing' ), 10, 3 );
	}

	/**
	 * A virtual product needs no processing.
	 *
	 * @param bool       $needs_processing Core's answer so far.
	 * @param WC_Product $product          The product on the line.
	 * @param int        $order_id         Order ID.
	 * @return bool
	 */
	public static function virtual_needs_no_processing( $needs_processing, $product, $order_id ) {
		if ( $product instanceof WC_Product && $product->is_virtual() ) {
			return false;
		}
		return $needs_processing;
	}
}
