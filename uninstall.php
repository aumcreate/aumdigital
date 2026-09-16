<?php
/**
 * Remove the plugin's options on uninstall.
 *
 * Order meta written by the plugin (the withdrawal consent and its time) is
 * deliberately left in place: it is a record of what a customer agreed to,
 * and deleting it with the plugin would destroy the store's own evidence.
 *
 * @package AumDigital
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

foreach ( array( 'aumdigital_hide_address', 'aumdigital_autocomplete', 'aumdigital_waiver', 'aumdigital_waiver_text' ) as $aumdigital_option ) {
	delete_option( $aumdigital_option );
}
