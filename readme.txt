=== dynamic product pricing – Plus/Minus with AJAX Add to Cart & Buy Now ===
Contributors: qaziwebsitewala
Tags: woocommerce, ajax, add to cart, buy now, quantity buttons, product types
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WooCommerce widget with plus/minus quantity buttons, dynamic total price, and AJAX Add to Cart/Buy Now. Supports multiple product types.

== Description ==
A lightweight WooCommerce widget that:
- Adds plus/minus quantity controls
- Updates total price dynamically
- Supports AJAX **Add to Cart**
- Supports AJAX **Buy Now** (direct checkout)
- Works with multiple product types (Simple, Variable, Grouped, Subscription)
- Avoids forced external links or upsells

Use the shortcode anywhere:
- `[dynamic_product_pricing]`  
- `[dynamic_product_pricing product_id="123"]`  

== Supported Product Types ==
- ✅ Simple Products  
- ✅ Variable Products  
- ✅ Grouped Products  
- ✅ Subscription Products  

== Installation ==
1. Upload the `dynamic-product-pricing` folder to `/wp-content/plugins/`
2. Activate via **Plugins → Installed Plugins**
3. Add the shortcode to a page, template, or single product content.

== Frequently Asked Questions ==

= Does it work with all product types? =
Yes, it supports Simple, Variable, Grouped, and Subscription products. More advanced product types may require custom integration.

= Can I style the buttons? =
Yes. Use or override the default styles in `assets/css/dpp-style.css`.

= Can I change the currency symbol? =
Yes. The plugin uses WooCommerce’s default currency settings, so your store currency will display automatically.

== Changelog ==
= 1.0.0 =
* Initial release with Simple, Variable, Grouped, and Subscription product support.

== Upgrade Notice ==
* 1.0.0 – First release with support for multiple product types.
