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
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_8_1\Payment_Gateway\Visa_Checkout;

use SkyVerge\WooCommerce\PluginFramework\v5_8_1 as Framework;
use SkyVerge\WooCommerce\PluginFramework\v5_8_1\Payment_Gateway\Settings_Screen;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( __NAMESPACE__ . '\\Admin' ) ) :

/**
 * Sets up the Visa Checkout settings screen.
 *
 * @since 5.10.0-dev.1
 */
class Admin extends Settings_Screen {


	/** @var Visa_Checkout the Visa Checkout handler instance */
	protected $handler;


	/**
	 * Construct the class.
	 *
	 * @since 5.10.0-dev.1
	 *
	 * @param Visa_Checkout $handler main Visa Checkout handler instance
	 */
	public function __construct( $handler ) {

		parent::__construct();

		$this->section_id = 'visa-checkout';
		$this->handler    = $handler;
	}


	/**
	 * Sets up the necessary hooks.
	 *
	 * @since 5.10.0-dev.1
	 */
	protected function add_hooks() {

		parent::add_hooks();
	}


	/**
	 * Gets the name of the Visa Checkout settings section.
	 *
	 * @since 5.10.0-dev.1
	 *
	 * @return string
	 */
	protected function get_settings_section_name() {

		return __( 'Visa Checkout', 'woocommerce-plugin-framework' );
	}


	/**
	 * Gets all of the combined settings.
	 *
	 * @since 5.10.0-dev.1
	 *
	 * @return array $settings combined settings.
	 */
	public function get_settings() {

		$settings = [

			[
				'title' => __( 'Visa Checkout', 'woocommerce-plugin-framework' ),
				'type'  => 'title',
			],

			[
				'id'      => 'sv_wc_visa_checkout_enabled',
				'title'   => __( 'Enable / Disable', 'woocommerce-plugin-framework' ),
				'desc'    => __( 'Accept Visa Checkout', 'woocommerce-plugin-framework' ),
				'type'    => 'checkbox',
				'default' => 'no',
			],

			[
				'type' => 'sectionend',
			],
		];

		$settings = array_merge( $settings, $this->get_connection_settings() );

		/**
		 * Filter the settings fields for Visa Checkout.
		 *
		 * @since 5.10.0-dev.1
		 *
		 * @param array $settings combined settings.
		 */
		return apply_filters( 'woocommerce_get_settings_visa_checkout', $settings );
	}


	/**
	 * Gets the connection settings for Visa Checkout.
	 *
	 * @since 5.10.0-dev.1
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

		$connection_settings[] = [
			'id'       => 'sv_wc_visa_checkout_environment',
			'title'    => esc_html__( 'Environment', 'woocommerce-plugin-framework' ),
			'type'     => 'select',
			'default'  => Framework\SV_WC_Payment_Gateway::ENVIRONMENT_PRODUCTION,
			'desc_tip' => esc_html__( 'Select the gateway environment to use for Visa Checkout transactions.', 'woocommerce-plugin-framework' ),
			'options'  => [
				Framework\SV_WC_Payment_Gateway::ENVIRONMENT_PRODUCTION => esc_html__( 'Production', 'woocommerce-plugin-framework' ),
				Framework\SV_WC_Payment_Gateway::ENVIRONMENT_TEST       => esc_html__( 'Test', 'woocommerce-plugin-framework' ),
			]
		];

		if ( $this->handler->requires_api_key() ) {

			$connection_settings[] = [
				'id'      => 'sv_wc_visa_checkout_api_key',
				'title'   => __( 'API Key', 'woocommerce-plugin-framework' ),
				'type'    => 'text',
				'desc'  => sprintf(
					__( 'The API key is required by Visa Checkout for encryption of sensitive payment credentials.', 'woocommerce-plugin-framework' )
				),
			];
		}

		$connection_settings = $this->add_processing_gateway_settings( $connection_settings );

		$connection_settings[] = array(
			'type' => 'sectionend',
		);

		return $connection_settings;
	}


	/**
	 * Gets the gateways that declare support for Visa Checkout.
	 *
	 * @since 5.10.0-dev.1
	 *
	 * @return array
	 */
	protected function get_supporting_gateways() {

		return $this->handler->get_supporting_gateways();
	}


}

endif;
