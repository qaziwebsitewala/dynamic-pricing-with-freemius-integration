<?php
/**
 * Plugin Name:       dynamic product pricing – Plus/Minus with AJAX Add to Cart & Buy Now
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

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// 1. Register your plugin menu + submenu
add_action('admin_menu', 'dpp_register_admin_menu');
function dpp_register_admin_menu() {
    // Main plugin menu
    add_menu_page(
        __( 'Dynamic Product Pricing', 'dynamic-product-pricing' ),
        __( 'Dynamic Pricing', 'dynamic-product-pricing' ),
        'manage_options',
        'dpp-settings',
        'dpp_render_settings_page',
        'dashicons-cart',
        56
    );

    // Example submenu
    add_submenu_page(
        'dpp-settings',
        __( 'Settings', 'dynamic-product-pricing' ),
        __( 'Settings', 'dynamic-product-pricing' ),
        'manage_options',
        'dpp-settings',
        'dpp_render_settings_page'
    );
    
    add_submenu_page(
        'dpp-settings',
        __( 'All Products', 'dynamic-product-pricing' ),
        __( 'All Products', 'dynamic-product-pricing' ),
        'manage_options',
        'dpp-products',
        'dpp_render_products_page'
    );


    // Add Upgrade to Pro submenu
    add_submenu_page(
        'dpp-settings',
        __( 'Upgrade to Pro', 'dynamic-product-pricing' ),
        __( 'Upgrade to Pro', 'dynamic-product-pricing' ),
        'manage_options',
        'dpp-upgrade',
        'dpp_upgrade_redirect'
    );
}

// 2. Redirect the "Upgrade to Pro" submenu to Freemius upgrade URL
function dpp_upgrade_redirect() {
    $fs = dppwpmb_fs(); // Call Freemius instance
    if ( $fs ) {
        // Redirect to Freemius upgrade page
        wp_redirect( $fs->get_upgrade_url() );
        exit;
    } else {
        echo '<div class="wrap"><h1>Upgrade Error</h1><p>Freemius is not initialized.</p></div>';
    }

}

// 2. Freemius init
if ( ! function_exists( 'dppwpmb_fs' ) ) {
    function dppwpmb_fs() {
        global $dppwpmb_fs;

        if ( ! isset( $dppwpmb_fs ) ) {
            require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';
            $dppwpmb_fs = fs_dynamic_init( array(
                'id'              => '20317',
                'slug'            => 'dynamic-product-pricing-with-plus-minus-button',
                'premium_slug'    => 'dynamic-product-pricing-pro',
                'type'            => 'plugin',
                'public_key'      => 'pk_fd3ff01167013b5daadf568206e08',
                'is_premium'      => false,
                'is_premium_only' => false,
                'has_addons'      => true,
                'has_paid_plans'  => true,

                //  ONLY tell Freemius to attach inside your menu
                'parent' => array(
                    'slug' => 'dpp-settings',
                ),
            ) );
        }

        return $dppwpmb_fs;
    }

    // Initialize Freemius
    add_action( 'plugins_loaded', function() {
        dppwpmb_fs();
        do_action( 'dppwpmb_fs_loaded' );
    });
}

// ==============================
// Free Plan Activation
// ==============================
register_activation_hook(__FILE__, 'dpp_free_plan_activate');
function dpp_free_plan_activate() {
    if (get_option('dynamic_products_free_plan') === false) {
        update_option('dynamic_products_free_plan', 'free');
    }
}

// ==============================
// Admin Notices for Free Plan
// ==============================
add_action('admin_notices', 'dpp_free_plan_admin_notice');
function dpp_free_plan_admin_notice() {
    if (!current_user_can('manage_options')) return;

    if (get_option('dynamic_products_free_plan') === 'free') {
        $fs = dppwpmb_fs();
        $upgrade_url = $fs && method_exists($fs, 'get_upgrade_url') ? esc_url($fs->get_upgrade_url()) : '#';
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong>Upgrade to Dynamic Products Pro!</strong><br>
                Unlock multi-widgets and single product page support by upgrading to our Pro plan.<br>
                <a href="<?php echo $upgrade_url; ?>" target="_blank" class="button button-primary">Upgrade Now</a>
            </p>
        </div>
        <?php
    }
}

// ==============================
// WooCommerce Check
// ==============================
function dpp_wc_active() {
    return class_exists( 'WooCommerce' );
}

function dpp_requires_woocommerce_notice() {
    if ( current_user_can( 'activate_plugins' ) && ! dpp_wc_active() ) {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__( 'dynamic-product-pricing requires WooCommerce to be installed and active.', 'dynamic-product-pricing' );
        echo '</p></div>';
    }
}
add_action( 'admin_notices', 'dpp_requires_woocommerce_notice' );

// ==============================
// Frontend Assets
// ==============================
// In your dpp_enqueue_assets() function:
function dpp_enqueue_assets() {
    if (!dpp_wc_active()) return;

    $ver = '1.0.0';

    wp_enqueue_style(
        'dpp-style',
        plugin_dir_url(__FILE__) . 'assets/css/dpp-style.css',
        array(),
        $ver
    );
	
	wp_enqueue_script( 'wc-add-to-cart-variation' );

    wp_enqueue_script(
        'dpp-script',
        plugin_dir_url(__FILE__) . 'assets/js/dpp-script.js',
        array('jquery'),
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

function dpp_admin_enqueue_styles() {
    wp_enqueue_style( 'dpp-admin-style', plugin_dir_url( __FILE__ ) . 'assets/css/dpp-style.css' );
}
add_action( 'admin_enqueue_scripts', 'dpp_admin_enqueue_styles' );


// In your activation hook
register_activation_hook(__FILE__, function() {
    if (!get_option('dpp_free_widget_location')) {
        update_option('dpp_free_widget_location', '');
    }
});

function dpp_is_pro_plan_active() {
    // For demonstration, this is set to false.
    // Replace this with your actual check, for example:
    // return get_option( 'dpp_pro_license_status' ) === 'valid';
    return false;
}

function dpp_shortcode( $atts ) {
    // Static variable to count how many times this shortcode is rendered on a page.
    static $widget_count = 0;

    // === Plan Restriction Logic ===
    // If the Pro plan is NOT active and a widget has already been displayed,
    // return an empty string to prevent rendering another one.
    if ( ! dpp_is_pro_plan_active() && $widget_count > 0 ) {
        return ''; // Stop execution for subsequent widgets on the free plan.
    }

    if ( ! function_exists( 'dpp_wc_active' ) || ! dpp_wc_active() ) {
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

    // Get product type
    $product_type = $product->get_type();

    // === Handle Variable Products (Pro only) ===
    if ( $product_type === 'variable' ) {
        if ( ! dpp_is_pro_plan_active() ) {
            $fs = function_exists('dppwpmb_fs') ? dppwpmb_fs() : null;
            $upgrade_url = $fs ? esc_url( $fs->get_upgrade_url() ) : '#';
            return '<div class="dpp-upgrade-message">
                <p><strong>' . __( 'Variable product support is a Pro feature.', 'dynamic-product-pricing' ) . '</strong></p>
                <a href="' . $upgrade_url . '" target="_blank" class="button button-primary">' . __( 'Upgrade to Pro', 'dynamic-product-pricing' ) . '</a>
            </div>';
        }
        // ✅ Pro users: render variation form
        return dpp_render_variable_product( $product );
    }

    // === Handle Simple Products (Free + Pro) ===
    if ( $product_type === 'simple' ) {
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

        // The widget is about to be rendered, so we increment the counter.
        $widget_count++;

        return ob_get_clean();
    }

    // === All Other Product Types → Show Upgrade Notice ===
    $fs = function_exists('dppwpmb_fs') ? dppwpmb_fs() : null;
    $upgrade_url = $fs ? esc_url( $fs->get_upgrade_url() ) : '#';

    return '<div class="dpp-upgrade-message">
        <p><strong>' . sprintf( __( 'Product type "%s" is not supported in Free.', 'dynamic-product-pricing' ), esc_html( ucfirst( $product_type ) ) ) . '</strong></p>
        <a href="' . $upgrade_url . '" target="_blank" class="button button-primary">' . __( 'Upgrade to Pro', 'dynamic-product-pricing' ) . '</a>
    </div>';
}
add_shortcode( 'dynamic_product_pricing', 'dpp_shortcode' );

function dpp_render_variable_product( $product ) {
    ob_start();

    $available_variations = $product->get_available_variations();
    $attributes           = $product->get_variation_attributes();
    $attribute_keys       = array_keys( $attributes );

    ?>
    <div class="dpp-widget dpp-variable-widget" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
        <form class="variations_form cart" 
              data-product_id="<?php echo esc_attr( $product->get_id() ); ?>" 
              data-product_variations="<?php echo esc_attr( wp_json_encode( $available_variations ) ); ?>">

            <?php foreach ( $attributes as $attribute_name => $options ) : ?>
                <div class="dpp-attribute">
                    <label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>">
                        <?php echo wc_attribute_label( $attribute_name ); ?>
                    </label>
                    <?php
                        wc_dropdown_variation_attribute_options( array(
                            'options'   => $options,
                            'attribute' => $attribute_name,
                            'product'   => $product,
                        ) );
                    ?>
                </div>
            <?php endforeach; ?>

            <div class="dpp-price">
                <span class="price"><?php echo $product->get_price_html(); ?></span>
            </div>

            <div class="dpp-qty-wrap">
                <button type="button" class="dpp-minus">−</button>
                <input type="number" class="dpp-qty" value="1" min="1" />
                <button type="button" class="dpp-plus">+</button>
            </div>

            <div class="dpp-button-row">
                <button type="submit" class="single_add_to_cart_button button alt">
                    <?php esc_html_e( 'Add to Cart', 'dynamic-product-pricing' ); ?>
                </button>
            </div>
        </form>
    </div>
    <?php

    return ob_get_clean();
}

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


function dpp_fs_redirect_upgrade() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $fs = dppwpmb_fs();
    $url = $fs && method_exists( $fs, 'get_upgrade_url' ) ? $fs->get_upgrade_url() : '';
    if ( $url ) wp_safe_redirect( $url ); exit;
    dpp_fs_fallback_output( __( 'Upgrade', 'dynamic-product-pricing' ) );
}

function dpp_fs_fallback_output( $title = '' ) {
    echo '<div class="wrap"><h1>' . esc_html( $title ) . '</h1>';
    echo '<p>' . esc_html__( 'Unable to load Freemius page. Please ensure the Freemius SDK is loaded.', 'dynamic-product-pricing' ) . '</p></div>';
}

// ==============================
// Render Pages
// ==============================
function dpp_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    $fs = function_exists('dppwpmb_fs') ? dppwpmb_fs() : null;
    $upgrade_url = $fs ? esc_url( $fs->get_upgrade_url() ) : '#';
    $is_pro = function_exists('dpp_is_pro_plan_active') ? dpp_is_pro_plan_active() : false;
    ?>

    <div class="my-plugin-upgrade">
 <header>
  <h1>Unlock the Full Power of Dynamic Product Pricing!</h1>
  <p>Upgrade to Pro and supercharge your WooCommerce store today.</p>
</header>

<div class="container">
 <section class="my-plugin-usage">
  <h2>Use the Dynamic Product Pricing Widget Anywhere</h2>
  <p>
    Add our lightweight, conversion-focused pricing widget to any page or post with a simple shortcode. 
    For single product pages, the widget auto-detects the current product – just drop in:
    <code>[dynamic_product_pricing]</code>.
  </p>

  <p>
    Want to place the widget on landing pages or custom content? Target a specific product by ID:
    <code>[dynamic_product_pricing product_id="123"]</code>.
    This renders plus/minus quantity controls, real-time total pricing, and AJAX Add to Cart / Buy Now.
  </p>

  <p>
    <strong>Variable Products (Pro):</strong> Use the same shortcode to support variations (size, color, etc.). Example:
    <code>[dynamic_product_pricing product_id="456"]</code>.
    Customers can pick variations inside the widget with live price updates. (Free users will see an upgrade notice.)
  </p>

  <p>
    <strong>Template/PHP usage:</strong> Insert the widget directly in theme files or builders:
    <code>&lt;?php echo do_shortcode('[dynamic_product_pricing product_id="123"]'); ?&gt;</code>
  </p>

  <p>
    <strong>Other product types (Pro):</strong> Unlock support for Grouped, Bundled, External/Affiliate, and more with the Pro plan.
  </p>

  <!-- Optional CTA (uses your existing PHP vars if present) -->
  <?php if ( isset($is_pro) && isset($upgrade_url) && ! $is_pro ) : ?>
    <div class="cta">
      <a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener" class="upgrade-btn"> Upgrade to Pro</a>
      <p>Unlock Variable & advanced product types, priority support, and more.</p>
    </div>
  <?php endif; ?>
</section>
  <!-- Features Cards -->
  <div class="cards">
    <div class="card">
      <h2>✨ Free Features</h2>
      <ul>
        <li>Plus/Minus quantity buttons</li>
        <li>Real-time total price updates</li>
        <li>AJAX Add to Cart</li>
        <li>Lightweight & fast</li>
      </ul>
    </div>
    <div class="card">
      <h2>🚀 Pro Features</h2>
      <ul>
        <li>Full support for Variable Products</li>
        <li>Works with Grouped & Bundled Products</li>
        <li>Priority Support & Updates</li>
        <li>Unlock unlimited widgets per page</li>
        <li>Smooth Variation UX for size/color</li>
      </ul>
    </div>
  </div>

  <!-- Comparison Table -->
  <div class="comparison">
    <table>
      <tr>
        <th>Feature</th>
        <th>Free</th>
        <th>Pro</th>
      </tr>
      <tr>
        <td>Simple Products</td>
        <td class="check">✔</td>
        <td class="check">✔</td>
      </tr>
      <tr>
        <td>Variable Products</td>
        <td class="cross">✘</td>
        <td class="check">✔</td>
      </tr>
      <tr>
        <td>Multiple Widgets per Page</td>
        <td class="cross">✘</td>
        <td class="check">✔</td>
      </tr>
      <tr>
        <td>Priority Support</td>
        <td class="cross">✘</td>
        <td class="check">✔</td>
      </tr>
    </table>
  </div>

  <!-- Guarantee -->
  <div class="guarantee">
    30-Day Money-Back Guarantee: If Pro doesn’t boost your conversions, we’ll refund you—no questions asked.
  </div>

  <!-- FAQ -->
  <div class="faq">
    <h2>Frequently Asked Questions</h2>
    <div class="faq-item">
      <strong>Can I use this on multiple sites?</strong>
      Yes, with the Pro license you can activate it on multiple websites.
    </div>
    <div class="faq-item">
      <strong>Do I need coding skills?</strong>
      Not at all—our widget works out of the box with any WooCommerce store.
    </div>
    <div class="faq-item">
      <strong>What if I don’t like it?</strong>
      You’re covered with a 30-day money-back guarantee.
    </div>
    <div class="faq-item">
      <strong>Does Pro work with my theme?</strong>
      Yes, it integrates seamlessly with all major WooCommerce themes.
    </div>
    <div class="faq-item">
      <strong>Will it work with caching?</strong>
      Absolutely, the widget is built lightweight and cache-friendly.
    </div>
  </div>

  <!-- CTA -->
 <div class="cta">
  <?php if ( ! $is_pro ) : ?>
    <a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener" class="upgrade-btn">
      Upgrade to Pro Now
    </a>
    <p>Unlock the full power of Dynamic Product Pricing today.</p>
  <?php endif; ?>
	       <p style="opacity:.8;margin-top:10px">
        Need help? <a href="mailto:hasanqazi36@gmail.com">Contact support</a>.
      </p>
</div>
      </div>
    </div>
    <?php
}


function dpp_render_products_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    if ( ! dpp_wc_active() ) {
        dpp_requires_woocommerce_notice();
        return;
    }

    $products = get_posts(array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    )); ?>

    <div class="wrap">
        <h1><?php esc_html_e( 'All Active Products', 'dynamic-product-pricing' ); ?></h1>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Product ID', 'dynamic-product-pricing' ); ?></th>
                    <th><?php esc_html_e( 'Product Name', 'dynamic-product-pricing' ); ?></th>
                    <th><?php esc_html_e( 'Product Type', 'dynamic-product-pricing' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $products ) : 
                    foreach ( $products as $p ) :
                        $product = wc_get_product( $p->ID );
                        if ( ! $product ) continue;
                        ?>
                        <tr>
                            <td><?php echo esc_html( $p->ID ); ?></td>
                            <td><?php echo esc_html( $p->post_title ); ?></td>
                            <td><?php echo ucfirst( esc_html( $product->get_type() ) ); ?></td>
                        </tr>
                    <?php endforeach; 
                else : ?>
                    <tr><td colspan="3"><?php esc_html_e( 'No active products found.', 'dynamic-product-pricing' ); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php }
