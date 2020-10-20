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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/External_Checkout/Google-Pay
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\External_Checkout\Google_Pay;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\Handlers\Script_Handler;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\External_Checkout\Google_Pay;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Exception;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Plugin;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\Payment_Gateway\\External_Checkout\\Google_Pay\\Frontend' ) ) :


/**
 * Sets up the Google Pay front-end functionality.
 *
 * @since 5.10.0
 */
class Frontend extends Script_Handler {


	/** @var SV_WC_Payment_Gateway_Plugin $plugin the gateway plugin instance */
	protected $plugin;

	/** @var Google_Pay $handler the Google Pay handler instance */
	protected $handler;

	/** @var SV_WC_Payment_Gateway $gateway the gateway instance */
	protected $gateway;

	/** @var string JS handler base class name, without the FW version */
	protected $js_handler_base_class_name = 'SV_WC_Google_Pay_Handler';


	/**
	 * Constructs the class.
	 *
	 * @since 5.10.0
	 *
	 * @param SV_WC_Payment_Gateway_Plugin $plugin the gateway plugin instance
	 * @param Google_Pay $handler the Google Pay handler instance
	 */
	public function __construct( SV_WC_Payment_Gateway_Plugin $plugin, Google_Pay $handler ) {

		$this->plugin = $plugin;

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

		if ( $this->get_handler()->is_available() ) {

			parent::add_hooks();

			add_action( 'wp', [ $this, 'init' ] );

			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		}
	}


	/**
	 * Initializes the scripts and hooks.
	 *
	 * @since 5.10.0
	 */
	public function init() {

		$locations = $this->get_display_locations();

		if ( is_product() && in_array( 'product', $locations, true ) ) {
			$this->init_product();
		} else if ( is_cart() && in_array( 'cart', $locations, true ) ) {
			$this->init_cart();
		} else if ( is_checkout() && in_array( 'checkout', $locations, true ) ) {
			$this->init_checkout();
		}
	}


	/**
	 * Gets the script ID.
	 *
	 * @since 5.10.0
	 *
	 * @return string
	 */
	public function get_id() {

		return $this->get_gateway()->get_id() . '_google_pay';
	}


	/**
	 * Gets the script ID, dasherized.
	 *
	 * @since 5.10.0
	 *
	 * @return string
	 */
	public function get_id_dasherized() {

		return $this->get_gateway()->get_id_dasherized() . '-google-pay';
	}


	/**
	 * Gets the configured display locations.
	 *
	 * @since 5.10.0
	 *
	 * @return array
	 */
	protected function get_display_locations() {

		return get_option( 'sv_wc_google_pay_display_locations', [] );
	}


	/**
	 * Enqueues the scripts.
	 *
	 * @since 5.10.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( 'google-pay-js-library', 'https://pay.google.com/gp/p/js/pay.js', array(), null, true );
		wp_enqueue_script( 'sv-wc-google-pay-v5_10_0', $this->get_plugin()->get_payment_gateway_framework_assets_url() . '/dist/frontend/sv-wc-payment-gateway-google-pay.js', [ 'google-pay-js-library', 'jquery' ], $this->get_plugin()->get_version(), true );
	}


	/**
	 * Gets the JS handler arguments.
	 *
	 * @since 5.10.0
	 *
	 * @return array
	 */
	protected function get_js_handler_args() {

		/**
		 * Filters the Google Pay JS handler params.
		 *
		 * @since 5.10.0
		 *
		 * @param array $params the JS params
		 */
		return (array) apply_filters( 'wc_' . $this->get_gateway()->get_id() . '_google_pay_js_handler_params', [
			'plugin_id'                => $this->get_gateway()->get_plugin()->get_id(),
			'merchant_id'              => $this->get_handler()->get_merchant_id(),
			'merchant_name'            => get_bloginfo( 'name' ),
			'gateway_id'               => $this->get_gateway()->get_id(),
			'gateway_id_dasherized'    => $this->get_gateway()->get_id_dasherized(),
			'ajax_url'                 => admin_url( 'admin-ajax.php' ),
			'recalculate_totals_nonce' => wp_create_nonce( 'wc_' . $this->get_gateway()->get_id() . '_google_pay_recalculate_totals' ),
			'process_nonce'            => wp_create_nonce( 'wc_' . $this->get_plugin()->get_gateway()->get_id() . '_google_pay_process_payment' ),
			'button_style'             => $this->get_handler()->get_button_style(),
			'card_types'               => $this->get_handler()->get_supported_networks(),
			'available_countries'	   => $this->get_handler()->get_available_countries(),
			'currency_code'			   => get_woocommerce_currency(),
			'generic_error'            => __( 'An error occurred, please try again or try an alternate form of payment', 'woocommerce-plugin-framework' ),
		] );
	}


	/**
	 * Enqueues a Google Pay JS handler.
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
	 * Renders a Google Pay button.
	 *
	 * @since 5.10.0
	 */
	public function render_button() {

		?>
		<div id="sv-wc-google-pay-button-container"></div>
		<?php
	}


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
				'<a href="' . esc_url( get_permalink( wc_terms_and_conditions_page_id() ) ) . '" class="sv-wc-google-pay-terms-and-conditions-link" target="_blank">',
				'</a>'
			);

			/**
			 * Allows to filter the text for the terms & conditions notice.
			 *
			 * @since 5.10.0
			 *
			 * @params string $default_text default notice text
			 */
			$text = apply_filters( 'sv_wc_google_pay_terms_notice_text', $default_text );

			?>
			<div class="sv-wc-google-pay-terms woocommerce-terms-and-conditions-wrapper">
				<p><small><?php echo wp_kses_post( $text ); ?></small></p>
			</div>
			<?php
		}
	}


	/**
	 * Initializes Google Pay on the single product page.
	 *
	 * @since 5.10.0
	 */
	public function init_product() {

		$product = wc_get_product( get_the_ID() );

		if ( ! $product ) {
			return;
		}

		try {
			$this->get_handler()->validate_product( $product );
		} catch ( SV_WC_Payment_Gateway_Exception $exception ) {
			return;
		}

		$this->enqueue_js_handler( $this->get_product_js_handler_args( $product ) );

		add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'render_button' ] );
		add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'render_terms_notice' ] );
	}


	/**
	 * Gets the args passed to the product JS handler.
	 *
	 * @since 5.10.0
	 *
	 * @param \WC_Product $product product object
	 * @return array
	 */
	protected function get_product_js_handler_args( \WC_Product $product ) {

		$args = [
			'product_id' => get_the_ID(),
		];

		/**
		 * Filters the gateway Google Pay cart handler args.
		 *
		 * @since 5.10.0
		 *
		 * @param array $args JS handler arguments
		 * @param \WC_Product $product product object
		 */
		return (array) apply_filters( 'wc_' . $this->get_gateway()->get_id() . '_google_pay_product_js_handler_args', $args, $product );
	}


	/** Cart functionality ****************************************************/


	/**
	 * Initializes Google Pay on the cart page.
	 *
	 * @since 5.10.0
	 */
	public function init_cart() {

		// bail if the cart is missing or empty
		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			return;
		}

		try {
			$this->get_handler()->validate_cart( WC()->cart );
		} catch ( SV_WC_Payment_Gateway_Exception $exception ) {
			return;
		}

		$this->enqueue_js_handler( $this->get_cart_js_handler_args( WC()->cart ) );

		add_action( 'woocommerce_proceed_to_checkout', [ $this, 'render_button' ] );
		add_action( 'woocommerce_proceed_to_checkout', [ $this, 'render_terms_notice' ] );
	}


	/**
	 * Gets the args passed to the cart JS handler.
	 *
	 * @since 5.10.0
	 *
	 * @param \WC_Cart $cart cart object
	 * @return array
	 */
	protected function get_cart_js_handler_args( \WC_Cart $cart ) {

		/**
		 * Filters the gateway Google Pay cart handler args.
		 *
		 * @since 5.10.0
		 *
		 * @param array $args JS handler arguments
		 * @param \WC_Cart $cart cart object
		 */
		return (array) apply_filters( 'wc_' . $this->get_gateway()->get_id() . '_google_pay_cart_js_handler_args', [], $cart );
	}


	/** Checkout functionality ************************************************/


	/**
	 * Initializes Google Pay on the checkout page.
	 *
	 * @since 5.10.0
	 */
	public function init_checkout() {

		try {
			$this->get_handler()->validate_cart( WC()->cart );
		} catch ( SV_WC_Payment_Gateway_Exception $exception ) {
			return;
		}

		$this->enqueue_js_handler( $this->get_checkout_js_handler_args() );

		if ( $this->get_plugin()->is_plugin_active( 'woocommerce-checkout-add-ons.php' ) ) {
			add_action( 'woocommerce_review_order_before_payment', [ $this, 'render_button' ] );
			add_action( 'woocommerce_review_order_before_payment', [ $this, 'render_terms_notice' ] );
		} else {
			add_action( 'woocommerce_before_checkout_form', [ $this, 'render_checkout_button' ], 15 );
		}
	}


	/**
	 * Gets the args passed to the checkout JS handler.
	 *
	 * @since 5.10.0
	 *
	 * @return array
	 */
	protected function get_checkout_js_handler_args() {

		/**
		 * Filters the gateway Google Pay checkout handler args.
		 *
		 * @since 5.10.0
		 *
		 * @param array $args JS handler arguments
		 */
		return (array) apply_filters( 'wc_' . $this->get_gateway()->get_id() . '_google_pay_checkout_js_handler_args', [] );
	}


	/**
	 * Renders the Google Pay button for checkout.
	 *
	 * @since 5.10.0
	 */
	public function render_checkout_button() {

		?>

		<div class="sv-wc-google-pay-checkout">

			<?php
				$this->render_button();
				$this->render_terms_notice();
			?>

			<span class="divider">
				<?php /** translators: "or" as in "Pay with Google Pay [or] regular checkout" */
				esc_html_e( 'or', 'woocommerce-plugin-framework' ); ?>
			</span>

		</div>

		<?php
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
	 * Gets the Google Pay handler instance.
	 *
	 * @since 5.10.0
	 *
	 * @return Google_Pay
	 */
	protected function get_handler() {

		return $this->handler;
	}


}


endif;
