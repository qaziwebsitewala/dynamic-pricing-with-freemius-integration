=== Dynamic Product Pricing – Plus/Minus with AJAX Add to Cart & Buy Now ===
Contributors: qaziwebsitewala
Donate link: https://qaziwebsitewala.com/
Tags: woocommerce, buy now, add to cart, quantity, ajax, plus minus
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight WooCommerce widget with **plus/minus quantity controls**, dynamic total price, and **AJAX Add to Cart** & **AJAX Buy Now** (direct to checkout). No forced external links.

== Description ==

This plugin improves the purchase flow for WooCommerce:

* Plus/Minus buttons to adjust quantity.
* Dynamic total price update on screen.
* AJAX **Add to Cart** (updates mini cart without reload).
* AJAX **Buy Now** (empties cart, adds product, redirects to checkout).
* Works as a shortcode: `[dynamic_product_pricing]` or `[dynamic_product_pricing product_id="123"]`.
* Translation-ready, GPLv2 or later, and adheres to WordPress.org plugin guidelines.

**No external links are injected into your public site.**  
This plugin does not track users and does not send data to third parties.

== Installation ==

1. Upload the `dynamic-product-pricing` folder to `/wp-content/plugins/`.
2. Activate via **Plugins → Installed Plugins**.
3. Ensure WooCommerce is active.
4. Add shortcode to a page or single product template:
   * `[dynamic_product_pricing]` (on single product pages)
   * `[dynamic_product_pricing product_id="123"]` (anywhere)

== Frequently Asked Questions ==

= Does it work without WooCommerce? =
No. The plugin requires WooCommerce.

= Will it add external “powered by” links? =
No. We never add public-facing external links without your explicit permission.

= Does it support variable products? =
The quantity/total UI works; the add-to-cart success depends on attribute selection on the page. Use on a product context where variations are properly selected.

== Screenshots ==

1. Plus/Minus with dynamic total and AJAX buttons on a product.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
First public release.

