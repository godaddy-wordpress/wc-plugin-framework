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

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Exception;
use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Plugin;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\Payment_Gateway\\External_Checkout\\Google_Pay\\Frontend' ) ) :


/**
 * Sets up the Google Pay front-end functionality.
 *
 * @since 5.10.0
 */
class Frontend extends \SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\External_Checkout\Frontend {


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

		parent::__construct( $plugin, $handler );
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
	 * Enqueues the scripts.
	 *
	 * @since 5.10.0
	 */
	public function enqueue_scripts() {

		parent::enqueue_scripts();

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
			'currency_code'            => get_woocommerce_currency(),
			'generic_error'            => __( 'An error occurred, please try again or try an alternate form of payment', 'woocommerce-plugin-framework' ),
		] );
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

		parent::init_product();
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
			'product_id'     => get_the_ID(),
			'needs_shipping' => $product->needs_shipping(),
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

		try {
			$this->get_handler()->validate_cart( WC()->cart );
		} catch ( SV_WC_Payment_Gateway_Exception $exception ) {
			return;
		}

		parent::init_cart();
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

		$args = [
			'needs_shipping' => $cart->needs_shipping(),
		];

		/**
		 * Filters the gateway Google Pay cart handler args.
		 *
		 * @since 5.10.0
		 *
		 * @param array $args JS handler arguments
		 * @param \WC_Cart $cart cart object
		 */
		return (array) apply_filters( 'wc_' . $this->get_gateway()->get_id() . '_google_pay_cart_js_handler_args', $args, $cart );
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

		parent::init_checkout();
	}


	/**
	 * Gets the args passed to the checkout JS handler.
	 *
	 * @since 5.10.0
	 *
	 * @return array
	 */
	protected function get_checkout_js_handler_args() {

		$args = [
			'needs_shipping' => WC()->cart->needs_shipping(),
		];

		/**
		 * Filters the gateway Google Pay checkout handler args.
		 *
		 * @since 5.10.0
		 *
		 * @param array $args JS handler arguments
		 */
		return (array) apply_filters( 'wc_' . $this->get_gateway()->get_id() . '_google_pay_checkout_js_handler_args', $args );
	}


}


endif;
