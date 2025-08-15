<?php
/**
 * Plugin Name:       dynamic product pricing – Plus/Minus with AJAX Add to Cart & Buy Now
 * Plugin URI:        https://qaziwebsitewala.com/
 * Description:       A lightweight WooCommerce widget with plus/minus quantity controls, dynamic total price, and AJAX Add to Cart/Buy Now.
 * Version:           1.0.0
 * Author:            Hasan Alam Qazi
 * Author URI:        https://qaziwebsitewala.com/
 * Text Domain:       dynamic-product-pricing
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package dynamic-product-pricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check WooCommerce active
 */
function dpp_wc_active() {
	return class_exists( 'WooCommerce' );
}

/**
 * Admin notice if WooCommerce missing
 */
function dpp_requires_woocommerce_notice() {
	if ( current_user_can( 'activate_plugins' ) && ! dpp_wc_active() ) {
		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'dynamic-product-pricing requires WooCommerce to be installed and active.', 'dynamic-product-pricing' );
		echo '</p></div>';
	}
}
add_action( 'admin_notices', 'dpp_requires_woocommerce_notice' );

/**
 * Enqueue front assets
 */
function dpp_enqueue_assets() {
	if ( ! dpp_wc_active() ) {
		return;
	}

	$ver = '1.0.0';

	wp_enqueue_style(
		'dpp-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/dpp-style.css',
		array(),
		$ver
	);

	wp_enqueue_script(
		'dpp-script',
		plugin_dir_url( __FILE__ ) . 'assets/js/dpp-script.js',
		array( 'jquery' ),
		$ver,
		true
	);

	wp_localize_script(
		'dpp-script',
		'dppVars',
		array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'dpp_nonce' ),
			'checkoutUrl' => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '',
		)
	);

	// Ensure Woo cart fragments are present so mini-cart updates without reload.
	wp_enqueue_script( 'wc-cart-fragments' );
}
add_action( 'wp_enqueue_scripts', 'dpp_enqueue_assets' );

/**
 * Shortcode: [dynamic_product_pricing product_id="123"]
 * On single product pages, product_id can be omitted to auto-detect current product.
 */
function dpp_shortcode( $atts ) {
	if ( ! dpp_wc_active() ) {
		if ( current_user_can( 'activate_plugins' ) ) {
			return '<p><strong>' . esc_html__( 'WooCommerce is not active.', 'dynamic-product-pricing' ) . '</strong></p>';
		}
		return '';
	}

	$atts = shortcode_atts(
		array(
			'product_id' => 0,
		),
		$atts,
		'dynamic_product_pricing'
	);

	$product_id = (int) $atts['product_id'];

	// Auto-detect on single product page.
	if ( ! $product_id && function_exists( 'is_product' ) && is_product() ) {
		global $product;
		if ( $product instanceof WC_Product ) {
			$product_id = $product->get_id();
		}
	}

	if ( ! $product_id ) {
		return '<p><strong>' . esc_html__( 'No product ID provided.', 'dynamic-product-pricing' ) . '</strong></p>';
	}

	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return '<p><strong>' . esc_html__( 'Invalid product ID.', 'dynamic-product-pricing' ) . '</strong></p>';
	}

	$price_raw  = (float) $product->get_price();
	$price_html = $product->get_price_html();

	ob_start();
	?>
	<div class="dpp-widget"
		data-product-id="<?php echo esc_attr( $product_id ); ?>"
		data-price="<?php echo esc_attr( $price_raw ); ?>">

		<div class="dpp-price">
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: product price HTML */
					__( 'Price: %s', 'dynamic-product-pricing' ),
					$price_html
				)
			);
			?>
			<span class="dpp-total-wrap">
				<strong><?php esc_html_e( 'Total:', 'dynamic-product-pricing' ); ?></strong>
				<span class="dpp-total"><?php echo wp_kses_post( wc_price( $price_raw ) ); ?></span>
			</span>
		</div>

		<div class="dpp-qty-wrap">
			<button type="button" class="dpp-minus" aria-label="<?php esc_attr_e( 'Decrease quantity', 'dynamic-product-pricing' ); ?>">−</button>
			<input type="number" class="dpp-qty" value="1" min="1" inputmode="numeric" aria-label="<?php esc_attr_e( 'Quantity', 'dynamic-product-pricing' ); ?>">
			<button type="button" class="dpp-plus" aria-label="<?php esc_attr_e( 'Increase quantity', 'dynamic-product-pricing' ); ?>">+</button>
		</div>

		<div class="dpp-button-row">
			<a href="#"
				class="button ajax_add_to_cart add_to_cart_button dpp-add-to-cart"
				data-product_id="<?php echo esc_attr( $product_id ); ?>"
				data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
				data-quantity="1"
				aria-label="<?php echo esc_attr( $product->add_to_cart_description() ); ?>"
				rel="nofollow">
				<?php esc_html_e( 'Add to Cart', 'dynamic-product-pricing' ); ?>
			</a>

			<a href="#"
				class="button dpp-buy-now"
				data-product_id="<?php echo esc_attr( $product_id ); ?>">
				<?php esc_html_e( 'Buy Now', 'dynamic-product-pricing' ); ?>
			</a>
		</div>

		<div class="dpp-message" role="status" aria-live="polite"></div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'dynamic_product_pricing', 'dpp_shortcode' );

/**
 * AJAX: Add to Cart
 */
add_action( 'wp_ajax_dpp_add_to_cart', 'dpp_handle_add_to_cart' );
add_action( 'wp_ajax_nopriv_dpp_add_to_cart', 'dpp_handle_add_to_cart' );

function dpp_handle_add_to_cart() {
	if ( ! dpp_wc_active() ) {
		wp_send_json_error(
			array( 'message' => __( 'WooCommerce not active.', 'dynamic-product-pricing' ) )
		);
	}

	check_ajax_referer( 'dpp_nonce', 'nonce' );

	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$quantity   = isset( $_POST['quantity'] ) ? max( 1, absint( $_POST['quantity'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if ( ! $product_id || ! get_post( $product_id ) ) {
		wp_send_json_error(
			array( 'message' => __( 'Invalid product.', 'dynamic-product-pricing' ) )
		);
	}

	$added = WC()->cart->add_to_cart( $product_id, $quantity );

	if ( $added ) {
		// Build mini-cart fragment like Woo does.
		ob_start();
		woocommerce_mini_cart();
		$mini_cart = ob_get_clean();

		$fragments = apply_filters(
			'woocommerce_add_to_cart_fragments',
			array(
				'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
			)
		);

		wp_send_json_success(
			array(
				'message'   => __( 'Added to cart.', 'dynamic-product-pricing' ),
				'fragments' => $fragments,
				'cart_hash' => WC()->cart->get_cart_hash(),
			)
		);
	}

	wp_send_json_error( array( 'message' => __( 'Could not add to cart.', 'dynamic-product-pricing' ) ) );
}

/**
 * AJAX: Buy Now (empty cart -> add -> checkout)
 */
add_action( 'wp_ajax_dpp_buy_now', 'dpp_ajax_buy_now' );
add_action( 'wp_ajax_nopriv_dpp_buy_now', 'dpp_ajax_buy_now' );

function dpp_ajax_buy_now() {
	if ( ! dpp_wc_active() ) {
		wp_send_json_error( array( 'message' => __( 'WooCommerce not active.', 'dynamic-product-pricing' ) ) );
	}

	check_ajax_referer( 'dpp_nonce', 'nonce' );

	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$quantity   = isset( $_POST['quantity'] ) ? max( 1, absint( $_POST['quantity'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if ( $product_id <= 0 ) {
		wp_send_json_error( array( 'message' => __( 'Invalid product.', 'dynamic-product-pricing' ) ) );
	}

	WC()->cart->empty_cart();
	$added = WC()->cart->add_to_cart( $product_id, $quantity );

	if ( $added ) {
		wp_send_json_success(
			array(
				'message'      => __( 'Proceeding to checkout…', 'dynamic-product-pricing' ),
				'checkout_url' => wc_get_checkout_url(),
			)
		);
	}

	wp_send_json_error( array( 'message' => __( 'Could not add to cart.', 'dynamic-product-pricing' ) ) );
}

/**
 * Simple admin menu (no external links auto-inserted)
 */
function dpp_register_admin_menu() {
	add_menu_page(
		__( 'dynamic-product-pricing', 'dynamic-product-pricing' ),
		__( 'dynamic-product-pricing', 'dynamic-product-pricing' ),
		'manage_options',
		'dpp-settings',
		'dpp_render_settings_page',
		'dashicons-cart',
		56
	);

	add_submenu_page(
		'dpp-settings',
		__( 'All Products', 'dynamic-product-pricing' ),
		__( 'All Products', 'dynamic-product-pricing' ),
		'manage_options',
		'dpp-products',
		'dpp_render_products_page'
	);
}
add_action( 'admin_menu', 'dpp_register_admin_menu' );

function dpp_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'dynamic-product-pricing', 'dynamic-product-pricing' ); ?></h1>
		<p><?php esc_html_e( 'Use the shortcode below to render the widget:', 'dynamic-product-pricing' ); ?></p>
		<code>[dynamic_product_pricing]</code>
		<p><?php esc_html_e( 'Optionally pass a product ID:', 'dynamic-product-pricing' ); ?> <code>[dynamic_product_pricing product_id="123"]</code></p>
	</div>
	<?php
}

function dpp_render_products_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! dpp_wc_active() ) {
		dpp_requires_woocommerce_notice();
		return;
	}

	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	$products = get_posts( $args );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'All Active Products', 'dynamic-product-pricing' ); ?></h1>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Product ID', 'dynamic-product-pricing' ); ?></th>
					<th><?php esc_html_e( 'Product Name', 'dynamic-product-pricing' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( $products ) : ?>
					<?php foreach ( $products as $p ) : ?>
						<tr>
							<td><?php echo esc_html( $p->ID ); ?></td>
							<td><?php echo esc_html( $p->post_title ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="2"><?php esc_html_e( 'No active products found.', 'dynamic-product-pricing' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}
