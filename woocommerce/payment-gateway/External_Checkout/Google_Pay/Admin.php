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

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Helper;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\Payment_Gateway\\External_Checkout\\Google_Pay\\Admin' ) ) :


/**
 * Sets up the Google Pay settings screen.
 *
 * @since 5.10.0
 */
class Admin extends \SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\External_Checkout\Admin {


	/** @var Google_Pay the Google Pay handler instance */
	protected $handler;


	/**
	 * Construct the class.
	 *
	 * @since 5.10.0
	 *
	 * @param Google_Pay $handler main Google Pay handler instance
	 */
	public function __construct( Google_Pay $handler ) {

		parent::__construct();

		$this->section_id = 'google-pay';
		$this->handler    = $handler;

	}


	/**
	 * Sets up the necessary hooks.
	 *
	 * @since 5.10.0
	 */
	protected function add_hooks() {

		parent::add_hooks();

		// add admin notices for configuration options that need attention
		add_action( 'admin_footer', [ $this, 'add_admin_notices' ], 10 );
	}


	/**
	 * Gets the name of the Google Pay settings section.
	 *
	 * @since 5.10.0
	 *
	 * @return string
	 */
	protected function get_settings_section_name() {

		return __( 'Google Pay', 'woocommerce-plugin-framework' );
	}


	/**
	 * Gets all of the combined settings.
	 *
	 * @since 5.10.0
	 *
	 * @return array $settings combined settings.
	 */
	public function get_settings() {

		$settings = [

			[
				'title' => __( 'Google Pay', 'woocommerce-plugin-framework' ),
				'type'  => 'title',
			],

			[
				'id'              => 'sv_wc_google_pay_enabled',
				'title'           => __( 'Enable / Disable', 'woocommerce-plugin-framework' ),
				'desc'            => __( 'Accept Google Pay', 'woocommerce-plugin-framework' ),
				'type'            => 'checkbox',
				'default'         => 'no',
			],

			[
				'id'      => 'sv_wc_google_pay_display_locations',
				'title'   => __( 'Allow Google Pay on', 'woocommerce-plugin-framework' ),
				'type'    => 'multiselect',
				'class'   => 'wc-enhanced-select',
				'css'     => 'width: 350px;',
				'options' => $this->get_display_location_options(),
				'default' => array_keys( $this->get_display_location_options() ),
			],

			[
				'id'      => 'sv_wc_google_pay_button_style',
				'title'   => __( 'Button Style', 'woocommerce-plugin-framework' ),
				'type'    => 'select',
				'options' => [
					'black'           => __( 'Black', 'woocommerce-plugin-framework' ),
					'white'           => __( 'White', 'woocommerce-plugin-framework' ),
				],
				'default' => 'black',
			],

			[
				'type' => 'sectionend',
			],
		];

		$settings = array_merge( $settings, $this->get_connection_settings() );

		/**
		 * Filter the settings fields for Google Pay.
		 *
		 * @since 5.10.0
		 * @param array $settings combined settings.
		 */
		return apply_filters( 'woocommerce_get_settings_google_pay', $settings );
	}


	/**
	 * Gets the connection settings for Google Pay.
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

		$connection_settings = $this->add_processing_gateway_settings( $connection_settings );

		$connection_settings[] = [
				'id'      => 'sv_wc_google_pay_test_mode',
				'title'   => __( 'Test Mode', 'woocommerce-plugin-framework' ),
				'desc'    => __( 'Enable to test Google Pay functionality throughout your sites without processing real payments.', 'woocommerce-plugin-framework' ),
				'type'    => 'checkbox',
				'default' => 'no',
		];

		$connection_settings[] = [
				'type' => 'sectionend',
		];

		return $connection_settings;
	}


	/**
	 * Gets the gateways that declare support for Google Pay.
	 *
	 * @since 5.10.0
	 *
	 * @return array
	 */
	protected function get_supporting_gateways() {

		return $this->handler->get_supporting_gateways();
	}


	/**
	 * Adds admin notices for configuration options that need attention.
	 *
	 * @since 5.10.0
	 */
	public function add_admin_notices() {

		// if the feature is not enabled, bail
		if ( ! $this->handler->is_enabled() ) {
			return;
		}

		// if not on the settings screen, bail
		if ( ! $this->is_settings_screen() ) {
			return;
		}

		$errors = [];

		// Currency notice
		$accepted_currencies = $this->handler->get_accepted_currencies();

		if ( ! empty( $accepted_currencies ) && ! in_array( get_woocommerce_currency(), $accepted_currencies, true ) ) {

			$errors[] = sprintf(
				/* translators: Placeholders: %1$s - plugin name, %2$s - a currency/comma-separated list of currencies, %3$s - <a> tag, %4$s - </a> tag */
				_n(
					'Accepts payment in %1$s only. %2$sConfigure%3$s WooCommerce to accept %1$s to enable Google Pay.',
					'Accepts payment in one of %1$s only. %2$sConfigure%3$s WooCommerce to accept one of %1$s to enable Google Pay.',
					count( $accepted_currencies ),
					'woocommerce-plugin-framework'
				),
				'<strong>' . implode( ', ', $accepted_currencies ) . '</strong>',
				'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=general' ) ) . '">',
				'</a>'
			);
		}

		if ( ! empty( $errors ) ) {

			$message = '<strong>' . __( 'Google Pay is disabled.', 'woocommerce-plugin-framework' ) . '</strong>';

			if ( 1 === count( $errors ) ) {
				$message .= ' ' . current( $errors );
			} else {
				$message .= '<ul><li>' . implode( '</li><li>', $errors ) . '</li></ul>';
			}

			$this->handler->get_plugin()->get_admin_notice_handler()->add_admin_notice( $message, 'google-pay-configuration-issue', [
				'notice_class' => 'error',
				'dismissible'  => false,
			] );
		}
	}


}


endif;
