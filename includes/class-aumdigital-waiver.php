<?php
/**
 * EU right-of-withdrawal consent for digital content.
 *
 * @package AumDigital
 */

defined( 'ABSPATH' ) || exit;

/**
 * A consent checkbox that appears only when the cart is all digital, is
 * required when it appears, and is recorded on the order with a timestamp.
 *
 * Under the EU Consumer Rights Directive a consumer keeps a 14-day right of
 * withdrawal on digital content unless they expressly agreed, before delivery,
 * to immediate delivery and to losing that right. A store that delivers a
 * download the moment payment clears has no way to comply after the fact; the
 * consent has to be collected at checkout and kept with the order.
 *
 * On the block checkout this is a native additional checkout field: WooCommerce
 * renders it, validates it, stores it on the order and shows it on the order
 * confirmation. Where WooCommerce supports conditional field rules, the field
 * also hides itself for carts with physical goods. On the shortcode checkout
 * it is an ordinary required checkbox in the order section.
 */
final class AumDigital_Waiver {

	/**
	 * Additional-field id on the block checkout.
	 */
	const BLOCK_FIELD = 'aumdigital/withdrawal-waiver';

	/**
	 * Field name on the shortcode checkout.
	 */
	const CLASSIC_FIELD = 'aumdigital_withdrawal_waiver';

	/**
	 * Order meta written by both paths.
	 */
	const META_CONSENT = '_aumdigital_withdrawal_waiver';
	const META_TIME    = '_aumdigital_withdrawal_waiver_at';

	/**
	 * Hook up.
	 *
	 * @return void
	 */
	public static function register() {
		// Block checkout.
		add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'register_block_field' ) );
		add_action( 'woocommerce_validate_additional_field', array( __CLASS__, 'validate_block_field' ), 10, 3 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( __CLASS__, 'record_block_consent' ), 10, 2 );

		// Shortcode checkout.
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'add_classic_field' ), 110 );
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'record_classic_consent' ), 10, 2 );

		// Where the record shows up afterwards (the block field displays itself).
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'admin_order_row' ) );
		add_filter( 'woocommerce_email_order_meta_fields', array( __CLASS__, 'email_row' ), 10, 3 );
	}

	/**
	 * The consent wording.
	 *
	 * @return string
	 */
	public static function label() {
		$text = get_option( 'aumdigital_waiver_text', '' );
		return '' !== trim( (string) $text ) ? $text : self::default_label();
	}

	/**
	 * What the customer sees when the box is left unticked.
	 *
	 * @return string
	 */
	public static function error_message() {
		return __( 'Please confirm that you agree to immediate delivery of the digital content.', 'aumdigital' );
	}

	/**
	 * Default wording, tracking Article 16(m) of the Consumer Rights Directive.
	 *
	 * @return string
	 */
	public static function default_label() {
		return __( 'I expressly request that the digital content be delivered immediately, and I acknowledge that I thereby lose my right of withdrawal once delivery has begun.', 'aumdigital' );
	}

	/* ---------------------------------------------------------------------
	 * Block checkout
	 * ------------------------------------------------------------------ */

	/**
	 * Register the field with WooCommerce's additional checkout fields API.
	 *
	 * @return void
	 */
	public static function register_block_field() {
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}

		$options = array(
			'id'                         => self::BLOCK_FIELD,
			'label'                      => self::label(),
			'location'                   => 'order',
			'type'                       => 'checkbox',
			'required'                   => false,
			'show_in_order_confirmation' => true,
		);

		// Conditional rules arrived later than the field API itself. With them,
		// the field is required only for an all-digital cart and hidden
		// otherwise; without them it is always shown and validate_block_field()
		// still enforces the consent where it applies.
		if ( class_exists( '\Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsSchema\Validation' ) ) {
			$options['required']      = self::rule( true );
			$options['hidden']        = self::rule( false );
			// Without this the message is the whole consent sentence with
			// " is required" on the end. Only valid alongside a required rule.
			$options['error_message'] = self::error_message();
		}

		woocommerce_register_additional_checkout_field( $options );
	}

	/**
	 * A JSON-schema rule matching the Store API cart extension written by
	 * AumDigital_Cart: `cart.extensions.aumdigital.virtual_only === $value`.
	 *
	 * Every level is declared required, so a response without the extension
	 * fails the rule -- which reads as "not required, not hidden": the safe
	 * default is to show an optional checkbox, never to demand an invisible one.
	 *
	 * @param bool $value The virtual_only value the rule matches.
	 * @return array
	 */
	private static function rule( $value ) {
		return array(
			'cart' => array(
				'properties' => array(
					'extensions' => array(
						'properties' => array(
							AumDigital_Cart::EXT => array(
								'properties' => array(
									'virtual_only' => array( 'const' => $value ),
								),
								'required'   => array( 'virtual_only' ),
							),
						),
						'required'   => array( AumDigital_Cart::EXT ),
					),
				),
				'required'   => array( 'extensions' ),
			),
		);
	}

	/**
	 * Refuse an all-digital block-checkout order without consent.
	 *
	 * Runs whether or not conditional rules exist, so the consent is enforced
	 * server-side on every WooCommerce version the field API exists on.
	 *
	 * @param WP_Error $errors Validation errors.
	 * @param string   $key    Field id.
	 * @param mixed    $value  Submitted value.
	 * @return void
	 */
	public static function validate_block_field( $errors, $key, $value ) {
		if ( self::BLOCK_FIELD !== $key || ! AumDigital_Cart::is_virtual_only() ) {
			return;
		}

		if ( ! wc_string_to_bool( $value ) ) {
			$errors->add( 'aumdigital_withdrawal_waiver', self::error_message() );
		}
	}

	/**
	 * Stamp the consent on a block-checkout order.
	 *
	 * The field API stores the checkbox itself; this adds the time it was
	 * given, which is what a dispute actually turns on.
	 *
	 * @param WC_Order        $order   Order being created.
	 * @param WP_REST_Request $request Checkout request.
	 * @return void
	 */
	public static function record_block_consent( $order, $request ) {
		$fields = $request->get_param( 'additional_fields' );

		if ( is_array( $fields ) && ! empty( $fields[ self::BLOCK_FIELD ] ) && wc_string_to_bool( $fields[ self::BLOCK_FIELD ] ) ) {
			self::stamp( $order );
		}
	}

	/* ---------------------------------------------------------------------
	 * Shortcode checkout
	 * ------------------------------------------------------------------ */

	/**
	 * Add the checkbox to the order section of the shortcode checkout.
	 *
	 * Marked required, which is all WooCommerce's own validation needs: an
	 * unchecked checkbox posts as an empty string and is rejected as a missing
	 * required field, with the standard message.
	 *
	 * @param array $fields Checkout field groups.
	 * @return array
	 */
	public static function add_classic_field( $fields ) {
		if ( ! AumDigital_Cart::is_virtual_only() ) {
			return $fields;
		}

		if ( ! isset( $fields['order'] ) || ! is_array( $fields['order'] ) ) {
			$fields['order'] = array();
		}

		$fields['order'][ self::CLASSIC_FIELD ] = array(
			'type'     => 'checkbox',
			'label'    => self::label(),
			'required' => true,
			'priority' => 200,
			'class'    => array( 'form-row-wide', 'aumdigital-waiver' ),
		);

		return $fields;
	}

	/**
	 * Record consent given on the shortcode checkout.
	 *
	 * @param WC_Order $order Order being created.
	 * @param array    $data  Posted checkout data.
	 * @return void
	 */
	public static function record_classic_consent( $order, $data ) {
		if ( ! empty( $data[ self::CLASSIC_FIELD ] ) ) {
			self::stamp( $order );
		}
	}

	/* ---------------------------------------------------------------------
	 * Shared
	 * ------------------------------------------------------------------ */

	/**
	 * Write the consent and its time onto the order.
	 *
	 * @param WC_Order $order Order.
	 * @return void
	 */
	private static function stamp( $order ) {
		$order->update_meta_data( self::META_CONSENT, 'yes' );
		$order->update_meta_data( self::META_TIME, time() );
	}

	/**
	 * Whether an order carries the consent, from either checkout.
	 *
	 * @param WC_Order $order Order.
	 * @return bool
	 */
	public static function order_has_consent( $order ) {
		return 'yes' === $order->get_meta( self::META_CONSENT );
	}

	/**
	 * Show the consent on the admin order screen.
	 *
	 * @param WC_Order $order Order.
	 * @return void
	 */
	public static function admin_order_row( $order ) {
		if ( ! self::order_has_consent( $order ) ) {
			return;
		}

		$time = (int) $order->get_meta( self::META_TIME );
		$when = $time ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $time ) : '';

		echo '<p class="aumdigital-waiver-row"><strong>' . esc_html__( 'Withdrawal right waived:', 'aumdigital' ) . '</strong> ';
		echo esc_html( $when ? sprintf( /* translators: %s: date and time */ __( 'Yes, at %s', 'aumdigital' ), $when ) : __( 'Yes', 'aumdigital' ) );
		echo '</p>';
	}

	/**
	 * Include the consent in order emails.
	 *
	 * The block field prints itself on the confirmation through the field API;
	 * this covers orders from the shortcode checkout, and is harmless when both
	 * end up present.
	 *
	 * @param array    $fields        Meta fields to print.
	 * @param bool     $sent_to_admin Whether the email goes to the admin.
	 * @param WC_Order $order         Order.
	 * @return array
	 */
	public static function email_row( $fields, $sent_to_admin, $order ) {
		if ( $order instanceof WC_Order && self::order_has_consent( $order ) && '' === (string) $order->get_meta( '_wc_other/' . self::BLOCK_FIELD ) ) {
			$fields['aumdigital_waiver'] = array(
				'label' => __( 'Withdrawal right waived', 'aumdigital' ),
				'value' => __( 'Yes', 'aumdigital' ),
			);
		}
		return $fields;
	}
}
