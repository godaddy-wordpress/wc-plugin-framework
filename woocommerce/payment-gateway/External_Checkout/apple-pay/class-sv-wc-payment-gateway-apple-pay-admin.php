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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/External_Checkout/Apple-Pay
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_0;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\External_Checkout\Admin;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\SV_WC_Payment_Gateway_Apple_Pay_Admin' ) ) :


/**
 * Sets up the Apple Pay settings screen.
 *
 * @since 4.7.0
 */
class SV_WC_Payment_Gateway_Apple_Pay_Admin extends Admin {


	/** @var SV_WC_Payment_Gateway_Apple_Pay the Apple Pay handler instance */
	protected $handler;


	/**
	 * Construct the class.
	 *
	 * @since 4.7.0
	 *
	 * @param SV_WC_Payment_Gateway_Apple_Pay $handler main Apple Pay handler instance
	 */
	public function __construct( $handler ) {

		$this->section_id = 'apple-pay';
		$this->handler    = $handler;

		parent::__construct();
	}


	/**
	 * Gets the name of the Apple Pay settings section.
	 *
	 * @since 5.10.0
	 *
	 * @return string
	 */
	protected function get_settings_section_name() {

		return __( 'Apple Pay', 'woocommerce-plugin-framework' );
	}


	/**
	 * Gets all of the combined settings.
	 *
	 * @since 4.7.0
	 *
	 * @return array $settings combined settings.
	 */
	public function get_settings() {

		$settings = [

			[
				'title' => __( 'Apple Pay', 'woocommerce-plugin-framework' ),
				'type'  => 'title',
			],

			[
				'id'      => 'sv_wc_apple_pay_enabled',
				'title'   => __( 'Enable / Disable', 'woocommerce-plugin-framework' ),
				'desc'    => __( 'Accept Apple Pay', 'woocommerce-plugin-framework' ),
				'type'    => 'checkbox',
				'default' => 'no',
			],

			[
				'id'      => 'sv_wc_apple_pay_display_locations',
				'title'   => __( 'Allow Apple Pay on', 'woocommerce-plugin-framework' ),
				'type'    => 'multiselect',
				'class'   => 'wc-enhanced-select',
				'css'     => 'width: 350px;',
				'options' => $this->get_display_location_options(),
				'default' => array_keys( $this->get_display_location_options() ),
			],

			[
				'id'      => 'sv_wc_apple_pay_button_style',
				'title'   => __( 'Button Style', 'woocommerce-plugin-framework' ),
				'type'    => 'select',
				'options' => [
					'black'           => __( 'Black', 'woocommerce-plugin-framework' ),
					'white'           => __( 'White', 'woocommerce-plugin-framework' ),
					'white-with-line' => __( 'White with outline', 'woocommerce-plugin-framework' ),
				],
				'default' => 'black',
			],

			[
				'type' => 'sectionend',
			],
		];

		$settings = array_merge( $settings, $this->get_connection_settings() );

		/**
		 * Filter the settings fields for Apple Pay.
		 *
		 * @param array $settings The combined settings.
		 *
		 * @since 1.0.0
		 */
		return apply_filters( 'woocommerce_get_settings_apple_pay', $settings );
	}


	/**
	 * Gets the connection settings for Apple Pay.
	 *
	 * @since 5.10.0
	 *
	 * @return array $settings connection settings
	 */
	protected function get_connection_settings() {

		$connection_settings = [
			[
				'title' => __( 'Connection Settings', 'woocommerce-plugin-framework' ),
				'type'  => 'title',
			],
		];

		if ( $this->handler->requires_merchant_id() ) {

			$connection_settings[] = [
				'id'      => 'sv_wc_apple_pay_merchant_id',
				'title'   => __( 'Apple Merchant ID', 'woocommerce-plugin-framework' ),
				'type'    => 'text',
				'desc'  => sprintf(
					/** translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
					__( 'This is found in your %1$sApple developer account%2$s', 'woocommerce-plugin-framework' ),
					'<a href="https://developer.apple.com" target="_blank">', '</a>'
				),
			];
		}

		if ( $this->handler->requires_certificate() ) {

			$connection_settings[] = [
				'id'       => 'sv_wc_apple_pay_cert_path',
				'title'    => __( 'Certificate Path', 'woocommerce-plugin-framework' ),
				'type'     => 'text',
				'desc_tip' => 'The full system path to your certificate file from Apple. For security reasons you should store this outside of your web root.',
				'desc'     => sprintf(
					/* translators: Placeholders: %s - the server's web root path */
					__( 'For reference, your current web root path is: %s', 'woocommerce-plugin-framework' ),
					'<code>' . ABSPATH . '</code>'
				),
			];
		}

		$connection_settings = $this->add_processing_gateway_settings( $connection_settings );

		$connection_settings[] = [
			'id'      => 'sv_wc_apple_pay_test_mode',
			'title'   => __( 'Test Mode', 'woocommerce-plugin-framework' ),
			'desc'    => __( 'Enable to test Apple Pay functionality throughout your sites without processing real payments.', 'woocommerce-plugin-framework' ),
			'type'    => 'checkbox',
			'default' => 'no',
		];

		$connection_settings[] = array(
			'type' => 'sectionend',
		);

		return $connection_settings;
	}


	/**
	 * Gets the gateways that declare support for Apple Pay.
	 *
	 * @since 5.10.0
	 *
	 * @return array
	 */
	protected function get_supporting_gateways() {

		return $this->handler->get_supporting_gateways();
	}


	/**
	 * Gets the error messages for configuration issues that need attention.
	 *
	 * @since 5.10.0
	 *
	 * @return string[] error messages
	 */
	protected function get_configuration_errors() {

		$errors = parent::get_configuration_errors();

		// HTTPS notice
		if ( ! wc_site_is_https() ) {
			$errors[] = __( 'Your site must be served over HTTPS with a valid SSL certificate.', 'woocommerce-plugin-framework' );
		}

		// bad cert config notice
		// this first checks if the option has been set so the notice is not
		// displayed without the user having the chance to set it.
		if ( false !== $this->handler->get_cert_path() && ! $this->handler->is_cert_configured() ) {

			$errors[] = sprintf(
				/** translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag */
				__( 'Your %1$sMerchant Identity Certificate%2$s cannot be found. Please check your path configuration.', 'woocommerce-plugin-framework' ),
				'<strong>', '</strong>'
			);
		}

		return $errors;
	}


}


endif;
