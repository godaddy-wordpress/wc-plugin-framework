<?php

/**
 * WooCommerce Payment Gateway Framework
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the plugin to newer
 * versions in the future. If you wish to customize the plugin for your
 * needs please refer to http://www.skyverge.com
 *
 * @package   SkyVerge/WooCommerce/Payment-Gateway/External_Checkout
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\External_Checkout;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\Handlers\Script_Handler;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Helper;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Plugin;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\Payment_Gateway\\External_Checkout\\Frontend' ) ) :


/**
 * Base class to set up an external checkout front-end functionality.
 *
 * @since 5.10.0
 */
abstract class Frontend extends Script_Handler {


	/** @var SV_WC_Payment_Gateway_Plugin $plugin the gateway plugin instance */
	protected $plugin;

	/** @var External_Checkout $handler the external checkout handler instance */
	protected $handler;

	/** @var SV_WC_Payment_Gateway $gateway the gateway instance */
	protected $gateway;


	/**
	 * Constructs the class.
	 *
	 * @since 5.10.0
	 *
	 * @param SV_WC_Payment_Gateway_Plugin $plugin the gateway plugin instance
	 * @param External_Checkout the handler instance
	 */
	public function __construct( SV_WC_Payment_Gateway_Plugin $plugin, External_Checkout $handler ) {

		$this->plugin  = $plugin;
		$this->handler = $handler;
		$this->gateway = $this->get_handler()->get_processing_gateway();

		parent::__construct();
	}


	/**
	 * Adds the action and filter hooks.
	 *
	 * @since 5.10.0
	 */
	protected function add_hooks() {

		if ( ! $this->get_handler()->is_available() ) {
			return;
		}

		add_action( 'wp', [ $this, 'init' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		parent::add_hooks();
	}


	/**
	 * Initializes the scripts and hooks.
	 *
	 * @since 5.10.0
	 */
	public function init() {

		if ( ! $this->get_handler()->is_available() ) {
			return;
		}

		$locations    = $this->get_handler()->get_display_locations();
		$is_cart_ajax = is_ajax() && 'update_shipping_method' === SV_WC_Helper::get_requested_value( 'wc-ajax' );

		if ( is_product() && in_array( 'product', $locations, true ) ) {
			$this->init_product();
		} else if ( ( is_cart() || $is_cart_ajax ) && in_array( 'cart', $locations, true ) ) {
			$this->init_cart();
		} else if ( is_checkout() && in_array( 'checkout', $locations, true ) ) {
			$this->init_checkout();
		} else {
			return;
		}

		// only render external checkout container if not rendered yet
		if ( ! has_action( 'sv_wc_external_checkout' ) ) {
			add_action( 'sv_wc_external_checkout', [ $this, 'render_external_checkout' ] );
		}

		add_action( 'sv_wc_external_checkout_button', [ $this, 'render_button' ] );

		// only render terms notice if not rendered yet
		if ( ! has_action( 'sv_wc_external_checkout_terms_notice' ) ) {
			add_action( 'sv_wc_external_checkout_terms_notice', [ $this, 'render_terms_notice' ] );
		}
	}


	/**
	 * Initializes external checkout on the single product page.
	 *
	 * Each handler can override this method to add specific product validation.
	 *
	 * @since 5.10.0
	 */
	public function init_product() {

		$product = wc_get_product( get_the_ID() );

		if ( ! $product ) {
			return;
		}

		$this->enqueue_js_handler( $this->get_product_js_handler_args( $product ) );

		add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'maybe_render_external_checkout' ] );
	}


	/**
	 * Initializes external checkout on the cart page.
	 *
	 * Each handler can override this method to add specific cart validation.
	 *
	 * @since 5.10.0
	 */
	public function init_cart() {

		// bail if the cart is missing or empty
		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			return;
		}

		$this->enqueue_js_handler( $this->get_cart_js_handler_args( WC()->cart ) );

		add_action( 'woocommerce_proceed_to_checkout', [ $this, 'maybe_render_external_checkout' ] );
	}


	/**
	 * Initializes external checkout on the checkout page.
	 *
	 * Each handler can override this method to add specific cart validation.
	 *
	 * @since 5.10.0
	 */
	public function init_checkout() {

		$this->enqueue_js_handler( $this->get_checkout_js_handler_args() );

		if ( $this->get_handler()->get_plugin()->is_plugin_active( 'woocommerce-checkout-add-ons.php' ) ) {
			add_action( 'woocommerce_review_order_before_payment', [ $this, 'maybe_render_external_checkout' ] );
		} else {
			add_action( 'woocommerce_before_checkout_form', [ $this, 'maybe_render_external_checkout_with_divider' ], 15 );
		}

		// only render external checkout container if not rendered yet
		if ( ! has_action( 'sv_wc_external_checkout_with_divider' ) ) {
			add_action( 'sv_wc_external_checkout_with_divider', [ $this, 'render_external_checkout_with_divider' ] );
		}
	}


	/**
	 * Maybe renders the external checkout buttons and possibly terms notice.
	 *
	 * @since 5.10.0
	 */
	public function maybe_render_external_checkout() {

		// only render external checkout container if not rendered yet
		if ( ! did_action( 'sv_wc_external_checkout' ) ) {

			do_action( 'sv_wc_external_checkout' );
		}
	}


	/**
	 * Renders the external checkout buttons and possibly terms notice.
	 *
	 * @since 5.10.0
	 */
	public function render_external_checkout() {
		?>
		<div class="sv-wc-external-checkout">
			<div class="buttons-container">
				<?php do_action( 'sv_wc_external_checkout_button' ); ?>
			</div>
			<?php do_action( 'sv_wc_external_checkout_terms_notice' ); ?>
		</div>
		<?php
	}


	/**
	 * Maybe renders the external checkout buttons and possibly terms notice with a divider.
	 *
	 * @since 5.10.0
	 */
	public function maybe_render_external_checkout_with_divider() {

		// only render external checkout container if not rendered yet
		if ( ! did_action( 'sv_wc_external_checkout_with_divider' ) ) {

			do_action( 'sv_wc_external_checkout_with_divider' );
		}
	}


	/**
	 * Renders the external checkout buttons and possibly terms notice with a divider.
	 *
	 * @since 5.10.0
	 */
	public function render_external_checkout_with_divider() {

		?>

		<div class="sv-wc-external-checkout">
			<div class="buttons-container">
				<?php do_action( 'sv_wc_external_checkout_button' ); ?>
			</div>
			<?php do_action( 'sv_wc_external_checkout_terms_notice' ); ?>
			<span class="divider">
				<?php /** translators: "or" as in "Pay with XYZ [or] regular checkout" */
				esc_html_e( 'or', 'woocommerce-plugin-framework' ); ?>
			</span>
		</div>

		<?php
	}


	/**
	 * Renders an external checkout button.
	 *
	 * Each handler should override this method to render its own button.
	 *
	 * @since 5.10.0
	 */
	abstract public function render_button();


	/**
	 * Renders a notice informing the customer that by purchasing they are accepting the website's terms and conditions.
	 *
	 * Only displayed if a Terms and conditions page is configured.
	 *
	 * @internal
	 *
	 * @since 5.10.0
	 */
	public function render_terms_notice() {

		/** This filter is documented by WooCommerce in templates/checkout/terms.php */
		if ( apply_filters( 'woocommerce_checkout_show_terms', true ) && function_exists( 'wc_terms_and_conditions_checkbox_enabled' ) && wc_terms_and_conditions_checkbox_enabled() ) {

			$default_text = sprintf(
				/** translators: Placeholders: %1$s - opening HTML link tag pointing to the terms & conditions page, %2$s closing HTML link tag */
				__( 'By submitting your payment, you agree to our %1$sterms and conditions%2$s.', 'woocommerce-plugin-framework' ),
				'<a href="' . esc_url( get_permalink( wc_terms_and_conditions_page_id() ) ) . '" class="terms-link" target="_blank">',
				'</a>'
			);

			/**
			 * Allows to filter the text for the terms & conditions notice.
			 *
			 * @since 5.10.0
			 *
			 * @params string $default_text default notice text
			 */
			$text = apply_filters( 'sv_wc_external_checkout_terms_notice_text', $default_text );

			?>
			<div class="terms-notice woocommerce-terms-and-conditions-wrapper">
				<p><small><?php echo wp_kses_post( $text ); ?></small></p>
			</div>
			<?php
		}
	}


	/**
	 * Enqueues the scripts.
	 *
	 * Each handler should override this method to add its specific JS.
	 *
	 * @since 5.10.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_style( 'sv-wc-external-checkout-v5_10_0', $this->get_handler()->get_plugin()->get_payment_gateway_framework_assets_url() . '/css/frontend/sv-wc-payment-gateway-external-checkout.css', array(), $this->get_handler()->get_plugin()->get_version() ); // TODO: min
	}


	/**
	 * Enqueues an external checkout JS handler.
	 *
	 * @since 5.10.0
	 *
	 * @param array $args handler arguments
	 * @param string $object_name JS object name
	 * @param string $handler_name handler class name
	 */
	protected function enqueue_js_handler( array $args, $object_name = '', $handler_name = '' ) {

		wc_enqueue_js( $this->get_safe_handler_js( $args, $handler_name, $object_name ) );
	}


	/**
	 * Gets the handler instantiation JS.
	 *
	 * @since 5.10.0
	 *
	 * @param array $additional_args additional handler arguments, if any
	 * @param string $handler_name handler name, if different from self::get_js_handler_class_name()
	 * @param string $object_name object name, if different from self::get_js_handler_object_name()
	 * @return string
	 */
	protected function get_handler_js( array $additional_args = [], $handler_name = '', $object_name = '' ) {

		$js = parent::get_handler_js( $additional_args, $handler_name );

		$js .= sprintf( 'window.%s.init();', $object_name ?: $this->get_js_handler_object_name() );

		return $js;
	}


	/**
	 * Adds a log entry.
	 *
	 * @since 5.10.0
	 *
	 * @param string $message message to log
	 */
	protected function log_event( $message ) {

		$this->get_gateway()->add_debug_message( $message );
	}


	/**
	 * Determines whether logging is enabled.
	 *
	 * @since 5.10.0
	 *
	 * @return bool
	 */
	protected function is_logging_enabled() {

		return $this->get_gateway()->debug_log();
	}


	/**
	 * Gets the gateway instance.
	 *
	 * @since 5.10.0
	 *
	 * @return SV_WC_Payment_Gateway
	 */
	protected function get_gateway() {

		return $this->gateway;
	}


	/**
	 * Gets the gateway plugin instance.
	 *
	 * @since 5.10.0
	 *
	 * @return SV_WC_Payment_Gateway_Plugin
	 */
	protected function get_plugin() {

		return $this->plugin;
	}


	/**
	 * Gets the external checkout handler instance.
	 *
	 * @since 5.10.0
	 *
	 * @return External_Checkout
	 */
	protected function get_handler()  {

		return $this->handler;
	}


}


endif;
