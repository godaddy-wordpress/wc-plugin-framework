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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/Apple-Pay
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Sets up the Apple Pay settings screen.
 *
 * @since 4.6.0-dev
 */
class SV_WC_Payment_Gateway_Apple_Pay_Admin {


	/** @var \SV_WC_Payment_Gateway_Apple_Pay the Apple Pay handler instance */
	protected $handler;


	/**
	 * Construct the class.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $handler ) {

		$this->handler = $handler;

		// add Apple Pay to the checkout settings sections
		add_filter( 'woocommerce_get_sections_checkout', array( $this, 'add_settings_section' ), 99 );

		// output the settings
		add_action( 'woocommerce_settings_checkout', array( $this, 'add_settings' ) );

		// save the settings
		add_action( 'woocommerce_settings_save_checkout', array( $this, 'save_settings' ) );
	}


	/**
	 * Adds Apple Pay to the checkout settings sections.
	 *
	 * @since 4.6.0-dev
	 * @param array $sections the existing sections
	 * @return array
	 */
	public function add_settings_section( $sections ) {

		$sections['apple-pay'] = __( 'Apple Pay', 'woocommerce-plugin-framework' );

		return $sections;
	}


	/**
	 * Gets all of the combined settings.
	 *
	 * @since 1.0.0
	 * @return array $settings The combined settings.
	 */
	public function get_settings() {

		$settings = array(

			array(
				'title' => __( 'Apple Pay', 'woocommerce-plugin-framework' ),
				'type'  => 'title',
			),

			array(
				'id'              => 'sv_wc_apple_pay_enabled',
				'title'           => __( 'Enable / Disable', 'woocommerce-plugin-framework' ),
				'desc'            => __( 'Accept Apple Pay', 'woocommerce-plugin-framework' ),
				'type'            => 'checkbox',
				'default'         => 'no',
				'checkboxgroup'   => 'start',
				'show_if_checked' => 'option',
			),

			array(
				'id'              => 'sv_wc_apple_pay_checkout',
				'desc'            => __( 'At checkout', 'woocommerce-plugin-framework' ),
				'type'            => 'checkbox',
				'default'         => 'yes',
				'checkboxgroup'   => '',
				'show_if_checked' => 'yes',
			),

			array(
				'id'              => 'sv_wc_apple_pay_cart',
				'desc'            => __( 'On the Cart page', 'woocommerce-plugin-framework' ),
				'type'            => 'checkbox',
				'default'         => 'no',
				'checkboxgroup'   => '',
				'show_if_checked' => 'yes',
			),

			array(
				'id'              => 'sv_wc_apple_pay_single_product',
				'desc'            => __( 'On single product pages', 'woocommerce-plugin-framework' ),
				'type'            => 'checkbox',
				'default'         => 'no',
				'checkboxgroup'   => '',
				'show_if_checked' => 'yes',
			),

			array(
				'type' => 'sectionend',
			),

			array(
				'title' => __( 'Buy Now', 'woocommerce-plugin-framework' ),
				'type'  => 'title',
			),

			array(
				'id'      => 'sv_wc_apple_pay_buy_now_tax_rate',
				'title'   => __( 'Tax Rate', 'woocommerce-plugin-framework' ),
				'type'    => 'text',
			),

			array(
				'id'      => 'sv_wc_apple_pay_buy_now_shipping_cost',
				'title'   => __( 'Shipping Cost', 'woocommerce-plugin-framework' ),
				'type'    => 'text',
			),

			array(
				'type' => 'sectionend',
			),

			array(
				'title' => __( 'Connection Settings', 'woocommerce-plugin-framework' ),
				'type'  => 'title',
			),

			array(
				'id'      => 'sv_wc_apple_pay_merchant_id',
				'title'   => __( 'Apple Merchant ID', 'woocommerce-plugin-framework' ),
				'type'    => 'text',
			),

			array(
				'id'      => 'sv_wc_apple_pay_cert_path',
				'title'   => __( 'Certificate Path', 'woocommerce-plugin-framework' ),
				'type'    => 'text',
			),

			array(
				'id'      => 'sv_wc_apple_pay_payment_gateway',
				'title'   => __( 'Processing Gateway', 'woocommerce-plugin-framework' ),
				'type'    => 'select',
				'options' => $this->get_gateway_options(),
			),

			array(
				'type' => 'sectionend',
			),
		);

		/**
		 * Filter the combined settings.
		 *
		 * @since 1.0.0
		 * @param array $settings The combined settings.
		 */
		return apply_filters( 'woocommerce_get_settings_apple_pay', $settings );
	}


	/**
	 * Replace core Tax settings with our own when the AvaTax section is being viewed.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function add_settings() {
		global $current_section;

		if ( 'apple-pay' === $current_section ) {
			WC_Admin_Settings::output_fields( $this->get_settings() );
		}
	}


	/**
	 * Save the settings.
	 *
	 * @since 1.0.0
	 * @global string $current_section The current settings section.
	 */
	public function save_settings() {

		global $current_section;

		// Output the general settings
		if ( 'apple-pay' == $current_section ) {

			WC_Admin_Settings::save_fields( $this->get_settings() );
		}
	}


	protected function get_gateway_options() {

		$gateways = $this->handler->get_supporting_gateways();

		foreach ( $gateways as $id => $gateway ) {
			$gateways[ $id ] = $gateway->get_method_title();
		}

		return $gateways;
	}


}
