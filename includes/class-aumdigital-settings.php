<?php
/**
 * Settings tab under WooCommerce > Settings.
 *
 * @package AumDigital
 */

defined( 'ABSPATH' ) || exit;

/**
 * "Digital goods" tab, built on WooCommerce's own settings API so it looks and
 * saves like the rest of the settings screen.
 */
class AumDigital_Settings extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'aumdigital';
		$this->label = __( 'Digital goods', 'aumdigital' );

		parent::__construct();
	}

	/**
	 * The settings.
	 *
	 * @return array
	 */
	protected function get_settings_for_default_section() {
		$taxes_on = wc_tax_enabled();

		return array(
			array(
				'title' => __( 'Checkout for an all-digital cart', 'aumdigital' ),
				'type'  => 'title',
				'desc'  => __( 'These apply only while every item in the cart is a virtual product. As soon as one physical item is added, the checkout is WooCommerce\'s own again. Works with the block checkout and the shortcode checkout.', 'aumdigital' ),
				'id'    => 'aumdigital_checkout_section',
			),
			array(
				'title'    => __( 'Skip the address', 'aumdigital' ),
				'desc'     => __( 'Remove street, city, postcode and state from checkout.', 'aumdigital' ),
				'desc_tip' => $taxes_on
					? __( 'Country stays, because tax is calculated from it. Name and email stay because the order needs them. Company and phone follow WooCommerce\'s own Accounts settings.', 'aumdigital' )
					: __( 'Country stays, because some payment gateways refuse a payment without one. Name and email stay because the order needs them. Company and phone follow WooCommerce\'s own Accounts settings.', 'aumdigital' ),
				'id'       => 'aumdigital_hide_address',
				'type'     => 'checkbox',
				'default'  => 'yes',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'aumdigital_checkout_section',
			),

			array(
				'title' => __( 'Orders', 'aumdigital' ),
				'type'  => 'title',
				'desc'  => __( 'WooCommerce completes a paid order by itself only when every item is both virtual and downloadable. A virtual item that is not a download — a licence key, a booking, a course seat — leaves the order in Processing until someone completes it by hand.', 'aumdigital' ),
				'id'    => 'aumdigital_orders_section',
			),
			array(
				'title'    => __( 'Complete paid virtual orders', 'aumdigital' ),
				'desc'     => __( 'Treat every virtual item as needing no processing, so a paid all-virtual order goes straight to Completed.', 'aumdigital' ),
				'desc_tip' => __( 'Only orders that actually get paid are affected. Bank transfer, cheque and cash-on-delivery orders wait for payment as before.', 'aumdigital' ),
				'id'       => 'aumdigital_autocomplete',
				'type'     => 'checkbox',
				'default'  => 'yes',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'aumdigital_orders_section',
			),

			array(
				'title' => __( 'EU right of withdrawal', 'aumdigital' ),
				'type'  => 'title',
				'desc'  => __( 'In the EU a consumer keeps a 14-day right to withdraw from a purchase of digital content unless they expressly agreed, before delivery, to immediate delivery and to losing that right. This adds that consent as a required checkbox for all-digital carts and records it on the order with the time it was given.', 'aumdigital' ),
				'id'    => 'aumdigital_waiver_section',
			),
			array(
				'title'   => __( 'Ask for consent', 'aumdigital' ),
				'desc'    => __( 'Show a required consent checkbox at checkout when the cart is all digital.', 'aumdigital' ),
				'id'      => 'aumdigital_waiver',
				'type'    => 'checkbox',
				'default' => 'no',
			),
			array(
				'title'       => __( 'Consent wording', 'aumdigital' ),
				'desc_tip'    => __( 'Leave empty to use the default, which follows Article 16(m) of the Consumer Rights Directive. Your own legal advice comes first.', 'aumdigital' ),
				'id'          => 'aumdigital_waiver_text',
				'type'        => 'textarea',
				'css'         => 'min-height:80px;width:100%;max-width:640px;',
				'placeholder' => AumDigital_Waiver::default_label(),
				'default'     => '',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'aumdigital_waiver_section',
			),
		);
	}

	/**
	 * One line at the foot of the tab pointing at the rest of the ecosystem.
	 *
	 * WooCommerce renders its settings pages itself, so there is no template to
	 * append to; this hooks the action it fires after the fields of this tab.
	 *
	 * @return void
	 */
	public function output() {
		parent::output();

		$url = 'https://aumcreate.com/plugins/aumdigital/?utm_source=plugin&utm_medium=aumdigital&utm_campaign=settings';

		echo '<p class="aum-ecosystem-note" style="margin:24px 0 0;color:#646970;font-size:12px">';
		printf(
			/* translators: %s: link to aumcreate.com */
			esc_html__( 'Part of the AumCreate ecosystem — themes and templates built around it. %s', 'aumdigital' ),
			'<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">aumcreate.com</a>'
		);
		echo '</p>';
	}
}
