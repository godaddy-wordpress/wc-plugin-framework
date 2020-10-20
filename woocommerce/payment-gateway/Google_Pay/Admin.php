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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/Google-Pay
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\Google_Pay;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Helper;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\Payment_Gateway\\Google_Pay\\Admin' ) ) :


/**
 * Sets up the Google Pay settings screen.
 *
 * @since 5.10.0
 */
class Admin {


	/** @var Google_Pay the Google Pay handler instance */
	protected $handler;


	/**
	 * Construct the class.
	 *
	 * @since 5.10.0
	 *
	 * @param \SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\Google_Pay $handler main Google Pay handler instance
	 */
	public function __construct( \SkyVerge\WooCommerce\PluginFramework\v5_10_0\Payment_Gateway\Google_Pay $handler ) {

		$this->handler = $handler;

		// add Google Pay to the checkout settings sections
		add_filter( 'woocommerce_get_sections_checkout', [ $this, 'add_settings_section' ], 99 );

		// output the settings
		add_action( 'woocommerce_settings_checkout', [ $this, 'add_settings' ] );

		// render the special "static" gateway select
		add_action( 'woocommerce_admin_field_static', [ $this, 'render_static_setting' ] );

		// save the settings
		add_action( 'woocommerce_settings_save_checkout', [ $this, 'save_settings' ] );

		// add admin notices for configuration options that need attention
		add_action( 'admin_footer', [ $this, 'add_admin_notices' ], 10 );
	}


	/**
	 * Adds Google Pay to the checkout settings sections.
	 *
	 * @internal
	 *
	 * @since 5.10.0
	 *
	 * @param array $sections the existing sections
	 * @return array
	 */
	public function add_settings_section( $sections ) {

		$sections['google-pay'] = __( 'Google Pay', 'woocommerce-plugin-framework' );

		return $sections;
	}


	/**
	 * Gets all of the combined settings.
	 *
	 * @since 5.10.0
	 *
	 * @return array $settings The combined settings.
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

		$connection_settings = [
			[
				'title' => __( 'Connection Settings', 'woocommerce-plugin-framework' ),
				'type'  => 'title',
			],
		];

		$gateway_setting_id = 'sv_wc_google_pay_payment_gateway';
		$gateway_options    = $this->get_gateway_options();

		if ( 1 === count( $gateway_options ) ) {

			$connection_settings[] = [
				'id'    => $gateway_setting_id,
				'title' => __( 'Processing Gateway', 'woocommerce-plugin-framework' ),
				'type'  => 'static',
				'value' => key( $gateway_options ),
				'label' => current( $gateway_options ),
			];

		} else {

			$connection_settings[] = [
				'id'      => $gateway_setting_id,
				'title'   => __( 'Processing Gateway', 'woocommerce-plugin-framework' ),
				'type'    => 'select',
				'options' => $this->get_gateway_options(),
			];
		}

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

		$settings = array_merge( $settings, $connection_settings );

		/**
		 * Filter the combined settings.
		 *
		 * @since 5.10.0
		 * @param array $settings The combined settings.
		 */
		return apply_filters( 'woocommerce_get_settings_google_pay', $settings );
	}


	/**
	 * Outputs the settings fields.
	 *
	 * @internal
	 *
	 * @since 5.10.0
	 */
	public function add_settings() {
		global $current_section;

		if ( 'google-pay' === $current_section ) {
			\WC_Admin_Settings::output_fields( $this->get_settings() );
		}
	}


	/**
	 * Saves the settings.
	 *
	 * @internal
	 *
	 * @since 5.10.0
	 *
	 * @global string $current_section The current settings section.
	 */
	public function save_settings() {
		global $current_section;

		// Output the general settings
		if ( 'google-pay' == $current_section ) {

			\WC_Admin_Settings::save_fields( $this->get_settings() );
		}
	}


	/**
	 * Renders a static setting.
	 *
	 * This "setting" just displays simple text instead of a <select> with only
	 * one option.
	 *
	 * @since 5.10.0
	 *
	 * @param array $setting
	 */
	public function render_static_setting( $setting ) {

		if ( ! $this->is_settings_screen() ) {
			return;
		}

		?>

		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $setting['id'] ); ?>"><?php echo esc_html( $setting['title'] ); ?></label>
			</th>
			<td class="forminp forminp-<?php echo sanitize_title( $setting['type'] ) ?>">
				<?php echo esc_html( $setting['label'] ); ?>
				<input
					name="<?php echo esc_attr( $setting['id'] ); ?>"
					id="<?php echo esc_attr( $setting['id'] ); ?>"
					value="<?php echo esc_html( $setting['value'] ); ?>"
					type="hidden"
					>
			</td>
		</tr><?php
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


	/**
	 * Determines if the user is currently on the settings screen.
	 *
	 * @since 5.10.0
	 *
	 * @return bool
	 */
	protected function is_settings_screen() {

		return 'wc-settings' === SV_WC_Helper::get_requested_value( 'page' ) && 'google-pay' === SV_WC_Helper::get_requested_value( 'section' );
	}


	/**
	 * Gets the available display location options.
	 *
	 * @since 5.10.0
	 *
	 * @return array
	 */
	protected function get_display_location_options() {

		return [
			'product'  => __( 'Single products', 'woocommerce-plugin-framework' ),
			'cart'     => __( 'Cart', 'woocommerce-plugin-framework' ),
			'checkout' => __( 'Checkout', 'woocommerce-plugin-framework' ),
		];
	}


	/**
	 * Gets the available gateway options.
	 *
	 * @since 5.10.0
	 *
	 * @return array
	 */
	protected function get_gateway_options() {

		$gateways = $this->handler->get_supporting_gateways();

		foreach ( $gateways as $id => $gateway ) {
			$gateways[ $id ] = $gateway->get_method_title();
		}

		return $gateways;
	}


}


endif;
