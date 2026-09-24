=== AumDigital – Digital Goods Checkout for WooCommerce ===
Contributors: aumcreate
Tags: digital goods, virtual products, checkout fields, autocomplete orders, digital downloads
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

No address when the cart is all virtual, paid virtual orders complete themselves, and the EU withdrawal consent a digital store needs.

== Description ==

WooCommerce sells digital goods well once the order exists. Getting there is where it still assumes a parcel: the checkout asks for a street address nobody will ship to, a paid order for a licence key sits in *Processing* until someone clicks, and the consent an EU store must collect before delivering digital content has nowhere to go.

AumDigital fills those three gaps and nothing else. Every rule applies only while **every item in the cart is a virtual product**; the moment a physical item is added, the checkout is WooCommerce's own again.

**Works with both checkouts.** The block checkout and the shortcode checkout (`[woocommerce_checkout]`) are separate code paths in WooCommerce, and plugins in this space often support only one. AumDigital uses the mechanism both of them obey — the per-country address locale that WooCommerce itself uses to drop the postcode for some countries — so the behaviour is the same whichever your theme uses.

= Skip the address =

Street, apartment, city, postcode and state are removed from an all-digital checkout — not rendered, not validated. Country stays because tax is calculated from it and several payment gateways refuse a payment without one. Name and email stay because the order needs them. Company and phone follow your own WooCommerce settings.

= Complete paid virtual orders =

WooCommerce completes a paid order by itself only when every item is both *virtual* and *downloadable*. A virtual item that is not a download — a licence key, a booking, a course seat — leaves the order in *Processing*. AumDigital answers WooCommerce's own "does this need processing?" with *no* for virtual items, so the status change, the emails and every hook other plugins listen to still happen in core, in core's order. Orders awaiting payment (bank transfer, cheque, cash on delivery) are untouched.

= EU right of withdrawal =

A consumer in the EU keeps a 14-day right to withdraw from a purchase of digital content unless they expressly agreed, before delivery, to immediate delivery and to losing that right. AumDigital adds that consent as a required checkbox for all-digital carts, with wording you can replace, and records it on the order together with the time it was given. On the block checkout it is a native additional checkout field; on the shortcode checkout it is a standard required field.

= What it deliberately does not do =

File delivery, download limits and expiry, the customer's *Downloads* page, the terms-and-conditions checkbox and the company and phone fields are all WooCommerce's own and are left to it. Licence keys are a different plugin.

= External services =

None. The plugin makes no request to any external service, sends no data anywhere, and has no account with us. Everything runs inside your WordPress.

= Source code =

The released source is on GitHub at https://github.com/aumcreate/aumdigital — bug reports and pull requests are welcome there.

== Installation ==

1. Install and activate WooCommerce.
2. Install the plugin ZIP via Plugins → Add New → Upload, or upload the `aumdigital` folder to `/wp-content/plugins/`.
3. Activate the plugin.
4. Go to WooCommerce → Settings → Digital goods. Skipping the address and completing paid virtual orders are on by default; the EU consent checkbox is off until you turn it on.

== Frequently Asked Questions ==

= Does it work with the block checkout? =

Yes, and with the shortcode checkout. Both are tested; the address fields and the consent checkbox behave the same on each.

= Why does the country field stay? =

Tax is calculated from it, and several payment gateways decline a payment with no billing country. Removing it would break more stores than it would help.

= A physical product is in the cart. What happens? =

Nothing. Every rule applies only while the whole cart is virtual. Add a physical item and the full address comes back, orders wait for processing, and the consent checkbox disappears.

= Does completing orders automatically affect bank transfer orders? =

No. Only orders that actually receive a payment are completed. Orders waiting for a bank transfer, cheque or cash on delivery stay where WooCommerce puts them.

= Where is the consent recorded? =

On the order, as order meta, together with a timestamp. It shows on the admin order screen and in the order confirmation. Uninstalling the plugin leaves that record in place: it is the store's own evidence of what the customer agreed to.

== Changelog ==

= 1.0.1 =
* A line at the foot of the settings tab linking to the rest of the AumCreate ecosystem.
* Added a link from the Plugins list to the plugin's page on aumcreate.com.

= 1.0.0 =
* Initial release: address-free checkout for all-virtual carts on both the block and the shortcode checkout, automatic completion of paid virtual orders, and an EU right-of-withdrawal consent checkbox recorded on the order.
