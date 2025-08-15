=== dynamic product pricing – Plus/Minus with AJAX Add to Cart & Buy Now ===
Contributors: qaziwebsitewala
Tags: woocommerce, ajax, add to cart, buy now, quantity buttons
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WooCommerce widget with plus/minus quantity buttons, dynamic total price, and AJAX Add to Cart/Buy Now.

== Description ==
A lightweight WooCommerce widget that:
- Shows plus/minus quantity controls
- Updates total price live on screen
- Supports AJAX **Add to Cart**
- Supports AJAX **Buy Now** (direct to checkout)
- Doesn’t inject forced external links

Use the shortcode on a product page or pass a product ID:
- `[dynamic_product_pricing]`
- `[dynamic_product_pricing product_id="123"]`

== Installation ==
1. Upload the `dynamic-product-pricing` folder to `/wp-content/plugins/`
2. Activate the plugin via **Plugins → Installed Plugins**
3. Add the shortcode to a page or single product content.

== Frequently Asked Questions ==

= Does it work with variable products? =
This version targets simple products. Extending to variations would require reading selected variation IDs and prices.

= Can I style the buttons? =
Yes. Override or extend styles in `assets/css/dpp-style.css`.

== Changelog ==
= 1.0.0 =
* Initial release.

== Upgrade Notice ==
* 1.0.0 – First release.
