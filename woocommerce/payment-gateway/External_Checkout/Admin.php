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

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_Helper;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( __NAMESPACE__ . '\\Admin' ) ) :

/**
 * Base class to set up a Payments settings screen, used by external checkout integrations.
 *
 * @since 5.10.0-dev.1
 */
abstract class Admin {


	/** @var string settings section ID */
	protected $section_id;


	/**
	 * Construct the class.
	 *
	 * @since 5.10.0-dev.1
	 */
	public function __construct() {

		$this->add_hooks();
	}


	/**
	 * Sets up the necessary hooks.
	 *
	 * @since 5.10.0-dev.1
	 */
	protected function add_hooks() {

		// bail if another plugin has already added the settings for this external checkout integration
		$sections = apply_filters( 'woocommerce_get_sections_checkout', [] );
		if ( ! empty( $sections[ $this->section_id ] ) ) {
			return;
		}

		add_filter( 'woocommerce_get_sections_checkout',  [ $this, 'add_settings_section' ], 99 );
		add_action( 'woocommerce_settings_checkout',      [ $this, 'add_settings' ] );
		add_action( 'woocommerce_settings_save_checkout', [ $this, 'save_settings' ] );

		// render the special "static" gateway select
		if ( ! has_action( 'woocommerce_admin_field_static' ) ) {
			add_action( 'woocommerce_admin_field_static', [ $this, 'render_static_setting' ] );
		}

		// add admin notices for configuration issues
		add_action( 'admin_footer', [ $this, 'add_admin_notices' ], 10 );
	}


	/**
	 * Adds the checkout settings section.
	 *
	 * @internal
	 *
	 * @since 5.10.0-dev.1
	 *
	 * @param array $sections the existing sections
	 * @return array
	 */
	public function add_settings_section( $sections ) {

		$sections[ $this->section_id ] = $this->get_settings_section_name();

		return $sections;
	}


	/**
	 * Gets the name of the settings section.
	 *
	 * @since 5.10.0-dev.1
	 *
	 * @return string
	 */
	abstract protected function get_settings_section_name();


	/**
	 * Gets all of the combined settings.
	 *
	 * @since 5.10.0-dev.1
	 *
	 * @return array $settings combined settings.
	 */
	abstract public function get_settings();


	/**
	 * Adds the definition for a Processing Gateway setting.
	 *
	 * @since 5.10.0-dev.1
	 *
	 * @param array $settings setting definitions
	 * @return array
	 */
	protected function add_processing_gateway_settings( $settings ) {

		$gateway_options = $this->get_processing_gateway_options();

		if ( 1 === count( $gateway_options ) ) {

			$settings[] = [
				'id'    => $this->get_processing_gateway_setting_id(),
				'title' => __( 'Processing Gateway', 'woocommerce-plugin-framework' ),
				'type'  => 'static',
				'value' => key( $gateway_options ),
				'label' => current( $gateway_options ),
			];

		} else {

			$settings[] = [
				'id'    => $this->get_processing_gateway_setting_id(),
				'title'   => __( 'Processing Gateway', 'woocommerce-plugin-framework' ),
				'type'    => 'select',
				'options' => $gateway_options
			];
		}

		return $settings;
	}


	/**
	 * Gets the ID for the Processing Gateway setting.
	 *
	 * @since 5.10.0-dev.1
	 *
	 * @return string
	 */
	protected function get_processing_gateway_setting_id() {

		return sprintf( 'sv_wc_%s_payment_gateway', str_replace( '-', '_', $this->section_id ) );
	}


	/**
	 * Gets an array IDs and names of payment gateways that declare support.
	 *
	 * @since 5.10.0-dev.1
	 *
	 * @return array
	 */
	protected function get_processing_gateway_options() {

		return array_map(
			function( $gateway ) {
				return $gateway->get_method_title();
			},
			$this->get_supporting_gateways()
		);
	}


	/**
	 * Gets the gateways that declare support.
	 *
	 * @since 5.10.0-dev.1
	 *
	 * @return array the supporting gateways as `$gateway_id => \SV_WC_Payment_Gateway`
	 */
	abstract protected function get_supporting_gateways();


	/**
	 * Outputs the settings fields.
	 *
	 * @internal
	 *
	 * @since 5.10.0-dev.1
	 *
	 * @global string $current_section current settings section.
	 */
	public function add_settings() {
		global $current_section;

		if ( $current_section === $this->section_id ) {
			\WC_Admin_Settings::output_fields( $this->get_settings() );
		}
	}


	/**
	 * Saves the settings.
	 *
	 * @internal
	 *
	 * @since 5.10.0-dev.1
	 *
	 * @global string $current_section current settings section.
	 */
	public function save_settings() {
		global $current_section;

		if ( $current_section === $this->section_id ) {
			\WC_Admin_Settings::save_fields( $this->get_settings() );
		}
	}


	/**
	 * Renders a static setting.
	 *
	 * This "setting" just displays simple text instead of a <select> with only one option.
	 *
	 * @since 5.10.0-dev.1
	 *
	 * @param array $setting
	 */
	public function render_static_setting( $setting ) {

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
	 * Determines if the user is currently on the settings screen.
	 *
	 * @since 5.10.0
	 *
	 * @return bool
	 */
	protected function is_settings_screen() {

		return 'wc-settings' === SV_WC_Helper::get_requested_value( 'page' ) && $this->section_id === SV_WC_Helper::get_requested_value( 'section' );
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
	 * Adds admin notices for configuration issues.
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

		$this->add_configuration_errors_notices();
	}


	/**
	 * Adds error notices for configuration options that need attention.
	 *
	 * @since 5.10.0
	 */
	protected function add_configuration_errors_notices() {

		$errors = $this->get_configuration_errors();

		if ( ! empty( $errors ) ) {

			$message = sprintf(
				/* translators: Placeholders:  - external checkout label, %2$s - <strong> tag, %3$s - </strong> tag */
				__( '%2$S%1$s is disabled.%3$S', 'woocommerce-plugin-framework' ),
				$this->handler->get_label(),
				'<strong>', '</strong>',
			);

			if ( 1 === count( $errors ) ) {
				$message .= ' ' . current( $errors );
			} else {
				$message .= '<ul><li>' . implode( '</li><li>', $errors ) . '</li></ul>';
			}

			$this->handler->get_plugin()->get_admin_notice_handler()->add_admin_notice( $message, $this->section_id . '-configuration-issue', [
				'notice_class' => 'error',
				'dismissible'  => false,
			] );
		}
	}


	/**
	 * Gets the error messages for configuration issues that need attention.
	 *
	 * @since 5.10.0
	 *
	 * @return string[] error messages
	 */
	public function get_configuration_errors() {

		$errors = [];

		// currency notice
		$accepted_currencies = $this->handler->get_accepted_currencies();

		if ( ! empty( $accepted_currencies ) && ! in_array( get_woocommerce_currency(), $accepted_currencies, true ) ) {

			$errors[] = sprintf(
				/* translators: Placeholders: %1$s - plugin name, %2$s - a currency/comma-separated list of currencies, %3$s - <a> tag, %4$s - </a> tag, %5$s - external checkout label */
				_n(
					'Accepts payment in %1$s only. %2$sConfigure%3$s WooCommerce to accept %1$s to enable %5$s.',
					'Accepts payment in one of %1$s only. %2$sConfigure%3$s WooCommerce to accept one of %1$s to enable %5$s.',
					count( $accepted_currencies ),
					'woocommerce-plugin-framework'
				),
				'<strong>' . implode( ', ', $accepted_currencies ) . '</strong>',
				'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=general' ) ) . '">',
				'</a>',
				$this->handler->get_label(),
			);
		}

		return $errors;
	}


}

endif;
