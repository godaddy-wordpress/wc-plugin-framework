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

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Payment_Gateway_Plugin;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\Payment_Gateway\\External_Checkout\\Frontend' ) ) :


/**
 * Sets up the external checkout front-end functionality.
 *
 * @since 5.10.0
 */
class Frontend {


	/** @var SV_WC_Payment_Gateway_Plugin $plugin the gateway plugin instance */
	protected $plugin;

	/** @var External_Checkout $handler the external checkout handler instance */
	protected $handler;


	/**
	 * Constructs the class.
	 *
	 * @since 5.10.0
	 *
	 * @param SV_WC_Payment_Gateway_Plugin $plugin the gateway plugin instance
	 * @param External_Checkout $handler the external checkout handler instance
	 */
	public function __construct( SV_WC_Payment_Gateway_Plugin $plugin, External_Checkout $handler ) {

		$this->plugin = $plugin;

		$this->handler = $handler;

		// add the action and filter hooks
		$this->add_hooks();
	}


	/**
	 * Adds the action and filter hooks.
	 *
	 * @since 5.10.0
	 */
	protected function add_hooks() {

		if ( $this->get_handler()->is_available() ) {

			add_action( 'wp', [ $this, 'init' ] );

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}
	}


	/**
	 * Initializes the scripts and hooks.
	 *
	 * @since 5.10.0
	 */
	public function init() {

		$locations = $this->get_handler()->get_display_locations();

		if ( is_product() && in_array( 'product', $locations, true ) ) {
			$this->init_product();
		} else if ( is_cart() && in_array( 'cart', $locations, true ) ) {
			$this->init_cart();
		} else if ( is_checkout() && in_array( 'checkout', $locations, true ) ) {
			$this->init_checkout();
		}
	}


	/**
	 * Initializes external checkout on the single product page.
	 *
	 * @since 5.10.0
	 */
	public function init_product() {

		add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'render_external_checkout_buttons' ] );
		add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'render_terms_notice' ] );
	}


	/**
	 * Initializes external checkout on the cart page.
	 *
	 * @since 5.10.0
	 */
	public function init_cart() {

		add_action( 'woocommerce_proceed_to_checkout', [ $this, 'render_external_checkout_buttons' ] );
		add_action( 'woocommerce_proceed_to_checkout', [ $this, 'render_terms_notice' ] );
	}


	/**
	 * Initializes external checkout on the checkout page.
	 *
	 * @since 5.10.0
	 */
	public function init_checkout() {

		if ( $this->get_handler()->get_plugin()->is_plugin_active( 'woocommerce-checkout-add-ons.php' ) ) {
			add_action( 'woocommerce_review_order_before_payment', [ $this, 'render_external_checkout_buttons' ] );
			add_action( 'woocommerce_review_order_before_payment', [ $this, 'render_terms_notice' ] );
		} else {
			add_action( 'woocommerce_before_checkout_form', [ $this, 'render_external_checkout_buttons_with_divider' ], 15 );
		}
	}


	/**
	 * Renders the external checkout buttons.
	 *
	 * @since 5.10.0
	 */
	public function render_external_checkout_buttons() {

		?>
		<div id="sv-wc-external-checkout-buttons-container">
			<?php do_action( 'sv_wc_external_checkout_buttons' ); ?>
		</div>
		<?php
	}


	/**
	 * Renders the external checkout buttons with a divider.
	 *
	 * @since 5.10.0
	 */
	public function render_external_checkout_buttons_with_divider() {

		?>

		<div class="sv-wc-external-checkout">

			<?php
			$this->render_external_checkout_buttons();
			$this->render_terms_notice();
			?>

			<span class="divider">
				<?php /** translators: "or" as in "Pay with XYZ [or] regular checkout" */
				esc_html_e( 'or', 'woocommerce-plugin-framework' ); ?>
			</span>

		</div>

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
			/** transalators: Placeholders: %1$s - opening HTML link tag pointing to the terms & conditions page, %2$s closing HTML link tag */
				__( 'By submitting your payment, you agree to our %1$sterms and conditions%2$s.', 'woocommerce-plugin-framework' ),
				'<a href="' . esc_url( get_permalink( wc_terms_and_conditions_page_id() ) ) . '" class="sv-wc-external-checkout-terms-and-conditions-link" target="_blank">',
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
			<div class="sv-wc-external-checkout-terms woocommerce-terms-and-conditions-wrapper">
				<p><small><?php echo wp_kses_post( $text ); ?></small></p>
			</div>
			<?php
		}
	}


	/**
	 * Enqueues the scripts.
	 *
	 * @since 5.10.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_style( 'sv-wc-external-checkout-v5_10_0', $this->get_plugin()->get_payment_gateway_framework_assets_url() . '/css/frontend/sv-wc-payment-gateway-external-checkout.css', array(), $this->get_plugin()->get_version() ); // TODO: min
	}


	/**
	 * Gets the external checkout handler instance.
	 *
	 * @since 5.10.0
	 *
	 * @returns External_Checkout
	 */
	protected function get_handler() {

		return $this->handler;
	}


}


endif;
