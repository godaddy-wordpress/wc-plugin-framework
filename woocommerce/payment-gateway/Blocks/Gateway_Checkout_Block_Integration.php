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
 * @copyright Copyright (c) 2013-2023, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_12_1\Payment_Gateway\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;
use SkyVerge\WooCommerce\PluginFramework\v5_12_1\SV_WC_Payment_Gateway;
use SkyVerge\WooCommerce\PluginFramework\v5_12_1\SV_WC_Payment_Gateway_Helper;
use SkyVerge\WooCommerce\PluginFramework\v5_12_1\SV_WC_Payment_Gateway_Payment_Form;
use SkyVerge\WooCommerce\PluginFramework\v5_12_1\SV_WC_Payment_Gateway_Payment_Token;
use SkyVerge\WooCommerce\PluginFramework\v5_12_1\SV_WC_Payment_Gateway_Plugin;
use SkyVerge\WooCommerce\PluginFramework\v5_12_1\Blocks\Traits\Block_Integration_Trait;
use WC_HTTPS;
use WC_Subscriptions_Cart;

if ( ! class_exists( '\SkyVerge\WooCommerce\PluginFramework\v5_12_1\Payment_Gateway\Blocks\Gateway_Checkout_Block_Integration' ) ) :

/**
 * Base class for handling support for the WooCommerce Checkout block in gateways.
 *
 * For support in non-gateways, {@see Block_Integration}.
 *
 * @since 5.12.0
 */
#[\AllowDynamicProperties]
abstract class Gateway_Checkout_Block_Integration extends AbstractPaymentMethodType {


	use Block_Integration_Trait;


	/** @var SV_WC_Payment_Gateway_Plugin instance of the current plugin */
	protected SV_WC_Payment_Gateway_Plugin $plugin;

	/** @var SV_WC_Payment_Gateway gateway handling integration */
	protected SV_WC_Payment_Gateway $gateway;

	/** @var string supported block name */
	protected string $block_name = 'checkout';


	/**
	 * Block integration constructor.
	 *
	 * @since 5.12.0
	 *
	 * @param SV_WC_Payment_Gateway_Plugin $plugin
	 * @param SV_WC_Payment_Gateway $gateway
	 */
	public function __construct( SV_WC_Payment_Gateway_Plugin $plugin, SV_WC_Payment_Gateway $gateway ) {

		$this->plugin   = $plugin;
		$this->gateway  = $gateway;
		$this->settings = $gateway->settings;

		$this->add_hooks();
	}


	/**
	 * Adds hooks.
	 *
	 * @since 5.12.0
	 *
	 * @return void
	 */
	protected function add_hooks(): void {

		// extends the block payment data for the current gateway
		add_filter( 'wc_' . $this->get_name() . '_' . $this->block_name . '_block_payment_method_data', [ $this, 'add_payment_method_data' ], 10, 2 );

		// prepares payment data for processing in the backend
		add_action( 'woocommerce_rest_checkout_process_payment_with_context', [ $this, 'prepare_payment_data' ], 10, 2 );

		// AJAX endpoint hooks for front-end logging
		$this->add_ajax_logging();
	}


	/**
	 * Determines if the payment method is available in the checkout block context.
	 *
	 * @since 5.12.0
	 *
	 * @return bool
	 */
	public function is_active() : bool {

		return $this->gateway->is_available();
	}


	/**
	 * Determines if AJAX logging is enabled for the gateway.
	 *
	 * @since 5.12.0
	 *
	 * @return bool
	 */
	protected function is_ajax_logging_enabled(): bool {

		return ! $this->gateway->debug_off();
	}


	/**
	 * Gets the payment method script handles.
	 *
	 * Defaults to {@see get_script_handles()} but concrete implementations may override this.
	 *
	 * @since 5.12.0
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles() : array {

		return $this->get_script_handles();
	}


	/**
	 * Gets the payment method data.
	 *
	 * @since 5.12.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_payment_method_data() : array {

		$payment_method_data = [
			'id'                   => $this->gateway->get_id_dasherized(), // dashes
			'name'                 => $this->gateway->get_id(), // underscores
			'type'                 => $this->gateway->get_payment_type(), // typically 'credit-card' or 'echeck', for internal use
			'title'                => $this->gateway->get_title(), // user-facing display title
			'description'          => $this->gateway->get_description(), // user-facing description
			'icons'                => $this->get_gateway_icons(), // icon or card icons displayed next to title
			'supported_card_types' => $this->get_supported_card_types(), // card types that the gateway supports, regardless if enabled in settings or not
			'enabled_card_types'   => $this->get_enabled_card_types(), // card types that are enabled in settings
			'defaults'             => $this->get_gateway_defaults(), // used to pre-populate payment method fields (typically in test mode)
			'placeholders'         => $this->get_placeholders(), // used in some payment method fields
			'supports'             => array_values( $this->gateway->supports ), // list of supported features
			'flags'                => $this->get_gateway_flags(), // list of gateway configuration flags
			'gateway'              => $this->get_gateway_configuration(), // other gateway configuration values
			'apple_pay'            => $this->get_apple_pay_configuration(), // Apple Pay configuration values
			'google_pay'           => $this->get_google_pay_configuration(), // Google Pay configuration values
			'debug_mode'           => $this->get_debug_mode(), // the current debug mode (log, checkout, full, off)
			'i18n'                 => $this->gateway->get_gateway_payment_form_localized_params(),
			'date_format'          => wc_date_format(),
			'time_format'          => wc_time_format(),
			'sample_check'         => WC_HTTPS::force_https_url( $this->plugin->get_payment_gateway_framework_assets_url(). '/images/sample-check-sprite.png' ),
			'help_tip'             => WC_HTTPS::force_https_url( WC()->plugin_url() . '/assets/images/help.png' ),
			'ajax_url'             => WC_HTTPS::force_https_url( admin_url( 'admin-ajax.php' ) ),
			'ajax_log_nonce'       => wp_create_nonce( 'wc_' . $this->get_name() . '_' . $this->block_name . '_block_log' ),
		];

		/**
		 * Filters gateway-specific payment method data for the Checkout Block.
		 *
		 * @since 5.12.0
		 *
		 * @param array<string, mixed> $payment_method_data
		 * @param SV_WC_Payment_Gateway $gateway
		 */
		return (array) apply_filters( "wc_{$this->gateway->get_id()}_{$this->block_name}_block_payment_method_data", $payment_method_data, $this->gateway );
	}


	/**
	 * Gets the payment data as a JSON string.
	 *
	 * @since 5.12.0
	 *
	 * @return string JSON
	 */
	public function get_payment_method_data_json() : string {

		return wp_json_encode( $this->get_payment_method_data() );
	}


	/**
	 * Adds payment method data.
	 *
	 * Plugins that extend this class may override this method to add additional payment method data.
	 * @see Gateway_Checkout_Block_Integration::get_payment_method_data()
	 *
	 * @since 5.12.0
	 *
	 * @param array $payment_method_data
	 * @param SV_WC_Payment_Gateway $gateway
	 * @return array
	 */
	public function add_payment_method_data( array $payment_method_data, SV_WC_Payment_Gateway $gateway ) : array {

		return $payment_method_data;
	}


	/**
	 * Gets all the supported card types for this gateway.
	 *
	 * Some gateways don't support all card types.
	 *
	 * @since 5.12.0
	 *
	 * @return string[]
	 */
	protected function get_supported_card_types() : array {

		if ( ! $this->gateway->supports_card_types() ) {
			return [];
		}

		return array_map( [ SV_WC_Payment_Gateway_Helper::class, 'normalize_card_type' ], array_keys( $this->gateway->get_available_card_types() ) );
	}


	/**
	 * Gets all the enabled card types for this gateway.
	 *
	 * A gateway could support more card types, but the merchant may have disabled some.
	 *
	 * @since 5.12.0
	 *
	 * @return string[]
	 */
	protected function get_enabled_card_types() : array {

		if ( ! $this->gateway->supports_card_types() ) {
			return [];
		}

		return array_map( [ SV_WC_Payment_Gateway_Helper::class, 'normalize_card_type' ], $this->gateway->get_card_types() );
	}


	/**
	 * Gets a list of gateway logos as icon image URLs.
	 *
	 * If the gateway has a specific icon, it will return that item only.
	 * Otherwise, it will return a list of icon URLs for each card type supported by the gateway.
	 *
	 * @since 5.12.0
	 *
	 * @return array<string, string>
	 */
	protected function get_gateway_icons() : array {

		$icons = [];

		if ( $this->gateway->icon ) {

			$icons = [ $this->gateway->get_method_title() => $this->gateway->icon ];

		} elseif ( $this->gateway->is_echeck_gateway() ) {

			$icons = [ __( 'eCheck', 'woocommerce' ) => $this->gateway->get_payment_method_image_url( 'echeck' ) ];

		} elseif ( $card_types = $this->get_enabled_card_types() ) {

			foreach ( $card_types as $card_type ) {

				$card_type = SV_WC_Payment_Gateway_Helper::normalize_card_type( $card_type );
				$card_name = SV_WC_Payment_Gateway_Helper::payment_type_to_name( $card_type );

				if ( $url = $this->gateway->get_payment_method_image_url( $card_type ) ) {
					$icons[ $card_name ] = WC_HTTPS::force_https_url( $url );
				}
			}
		}

		/**
		 * Filters the payment gateway icons for the Checkout block.
		 *
		 * If the gateway specifies an icon or is an eCheck type, it will return that item only.
		 * If the gateway doesn't, but supports card types, it will return a list of icon URLs for each card type supported by the gateway.
		 *
		 * @since 5.12.0
		 *
		 * @param array<string, string> $icons list of icon URLs keyed by payment method or card name
		 * @param SV_WC_Payment_Gateway $gateway
		 */
		return apply_filters( "wc_{$this->gateway->get_id()}_{$this->block_name}_block_payment_method_icons", $icons, $this->gateway );
	}


	/**
	 * Gets the payment method fields placeholder.
	 *
	 * @since 5.12.0
	 *
	 * @return array<string, mixed>
	 */
	protected function get_placeholders() : array {

		$placeholders = [
			'credit_card_number'  => '•••• •••• •••• ••••',
			'credit_card_expiry'  => 'MM/YY',
			'credit_card_csc'     => '•••',
			'bank_routing_number' => '•••••••••',
		];

		/**
		 * Filters the payment gateway placeholders for the Checkout block.
		 *
		 * @since 5.12.0
		 *
		 * @param array<string, mixed> $placeholders
		 * @param SV_WC_Payment_Gateway $gateway
		 */
		return (array) apply_filters( "wc_{$this->gateway->get_id()}_{$this->block_name}_block_payment_method_placeholders", $placeholders, $this->gateway );
	}


	/**
	 * Gets the gateway defaults.
	 *
	 * @since 5.12.0
	 *
	 * @return array<string, mixed>
	 */
	protected function get_gateway_defaults() : array {

		if ( ! $this->gateway->supports_payment_form() ) {
			return [];
		}

		$defaults = [];

		// this is needed because some keys may use dashes instead of underscores, which could cause trouble when parsed as JS objects
		foreach ( $this->gateway->get_payment_method_defaults() as $default_key => $default_value ) {
			$defaults[ str_replace( '-', '_', $default_key ) ] = $default_value;
		}

		return $defaults;
	}


	/**
	 * Returns any gateway flags from configuration (boolean values only).
	 *
	 * @since 5.12.0
	 *
	 * @return array<string, bool>
	 */
	protected function get_gateway_flags() : array {

		$flags = [
			'settings_inherited'                => $this->gateway->inherit_settings(),
			'is_test_environment'               => $this->gateway->is_test_environment(),
			'is_credit_card_gateway'            => $this->gateway->is_credit_card_gateway(),
			'is_echeck_gateway'                 => $this->gateway->is_echeck_gateway(),
			'csc_enabled'                       => $this->gateway->csc_enabled(),
			'csc_enabled_for_tokens'            => $this->gateway->csc_enabled_for_tokens(),
			'tokenization_enabled'              => $this->gateway->supports_tokenization() && $this->gateway->tokenization_enabled(),
			'detailed_decline_messages_enabled' => $this->gateway->is_detailed_customer_decline_messages_enabled(),
			'logging_enabled'                   => 'off' !== $this->get_debug_mode(),
			'has_subscription'                  => false,
			'has_subscription_renewal'          => false,
		];

		$force_tokenization = false;

		if ( $this->plugin->is_subscriptions_active() && class_exists( 'WC_Subscriptions_Cart' ) ) {

			$cart_has_subscription = $force_tokenization = WC_Subscriptions_Cart::cart_contains_subscription();

			$flags['has_subscription']         = $cart_has_subscription;
			$flags['has_subscription_renewal'] = $cart_has_subscription && isset( WC()->cart->recurring_carts ) && ! empty( WC()->cart->recurring_carts );
		}

		$flags['tokenization_forced'] = $force_tokenization;

		return $flags;
	}


	/**
	 * Gets any gateway configuration values.
	 *
	 * @since 5.12.0
	 *
	 * @return array<string, mixed>
	 */
	protected function get_gateway_configuration() : array {

		return [
			'transaction_type'       => $this->gateway->supports_credit_card_authorization() & $this->gateway->supports_credit_card_charge() ? $this->gateway->get_option( 'transaction_type', 'charge' ) : null,
			'charge_virtual_orders'  => $this->gateway->supports_credit_card_charge_virtual() ? 'yes' === $this->gateway->get_option( 'charge_virtual', 'no' ) : null,
			'enable_partial_capture' => $this->gateway->supports_credit_card_partial_capture() ? 'yes' === $this->gateway->get_option( 'enable_partial_capture', 'no' ) : null,
			'enable_paid_capture'    => $this->gateway->supports_credit_card_capture() ? 'yes' === $this->gateway->get_option( 'enable_paid_capture', 'no' ) : null,
		];
	}


	/**
	 * Gets the Apple Pay configuration for the current gateway, if available.
	 *
	 * @since 5.12.0
	 *
	 * @return array<string, mixed>|null returns null if Apple Pay is unsupported by the gateway
	 */
	protected function get_apple_pay_configuration() : ?array {

		$apple_pay_configuration = null;

		if ( $this->gateway->supports_apple_pay() ) {

			$apple_pay          = $this->plugin->get_apple_pay_instance();
			$processing_gateway = $apple_pay ? $apple_pay->get_processing_gateway() : null;

			if ( $processing_gateway && $this->gateway->get_id() === $processing_gateway->get_id() ) {

				$apple_pay_configuration = [
					'merchant_id'              => $apple_pay->get_merchant_id(),
					'merchant_name'            => get_bloginfo( 'name' ),
					'validate_nonce'           => wp_create_nonce( 'wc_' . $this->gateway->get_id() . '_apple_pay_validate_merchant' ),
					'recalculate_totals_nonce' => wp_create_nonce( 'wc_' . $this->gateway->get_id() . '_apple_pay_recalculate_totals' ),
					'process_nonce'            => wp_create_nonce( 'wc_' . $this->gateway->get_id() . '_apple_pay_process_payment' ),
					'button_style'             => $apple_pay->get_button_style(),
					'card_types'               => $apple_pay->get_supported_networks(),
					'countries'                => $this->gateway->get_available_countries(),
					'currencies'               => $this->gateway->get_apple_pay_currencies(),
					'capabilities'             => $this->gateway->get_apple_pay_capabilities(),
					'flags'                    => [
						'is_enabled'          => $apple_pay->is_enabled(),
						'is_available'        => $apple_pay->is_available() && $apple_pay->supports_checkout_block(),
						'is_test_environment' => $apple_pay->is_test_mode(),
					],
				];
			}
		}

		return $apple_pay_configuration;
	}


	/**
	 * Gets the Google Pay configuration for the current gateway, if available.
	 *
	 * @since 5.12.0
	 *
	 * @return array<string, mixed>|null returns null if Google Pay is unsupported by the gateway
	 */
	protected function get_google_pay_configuration() : ?array {

		$google_pay_configuration = null;

		if ( $this->gateway->supports_google_pay() ) {

			$google_pay         = $this->plugin->get_google_pay_instance();
			$processing_gateway = $google_pay ? $google_pay->get_processing_gateway() : null;

			if ( $processing_gateway && $this->gateway->get_id() === $processing_gateway->get_id() ) {

				$google_pay_configuration = [
					'merchant_id'              => $google_pay->get_merchant_id(),
					'merchant_name'            => get_bloginfo( 'name' ),
					'recalculate_totals_nonce' => wp_create_nonce( 'wc_' . $this->gateway->get_id() . '_google_pay_recalculate_totals' ),
					'process_nonce'            => wp_create_nonce( 'wc_' . $this->gateway->get_id() . '_google_pay_process_payment' ),
					'button_style'             => $google_pay->get_button_style(),
					'card_types'               => $google_pay->get_supported_networks(),
					'countries'                => $google_pay->get_available_countries(),
					'currencies'               => [ get_woocommerce_currency() ],
					'flags'                    => [
						'is_enabled'          => $google_pay->is_enabled(),
						'is_available'        => $google_pay->is_available() && $google_pay->supports_checkout_block(),
						'is_test_environment' => $google_pay->is_test_mode(),
					],
				];
			}
		}

		return $google_pay_configuration;
	}


	/**
	 * Gets the debug mode configured for the gateway.
	 *
	 * - `off`: no logging
	 * - `log`: save to log file
	 * - `checkout`: save to log file and display in checkout page
	 * - `full`: save to log file and display in checkout page
	 *
	 * @since 5.12.0
	 *
	 * @return string one of off, log, checkout or full
	 */
	protected function get_debug_mode() : string {

		$debug_mode = $this->gateway->get_debug_mode();

		return 'both' === $debug_mode ? 'full' : $debug_mode;
	}


	/**
	 * Prepare payment data for processing by the gateway.
	 *
	 * This method does not actually process the payment - it simply adjusts the payment data to support legacy processing.
	 *
	 * The checkout block has built-in support for tokenization & payment tokens, but it sends the data from the frontend
	 * with field names and values that our existing payment processing does not expect.
	 *
	 * For example, it sends the internal (core) token ID instead of the gateway-specific token ID. This method fetches
	 * the token based on core ID and injects the gateway-specific token ID into the `PaymentContext::$payment_data`
	 * array so that the gateway can process the payment.
	 *
	 * @see PaymentContext::$payment_data is converted to `$_POST` by WC core when handling legacy payments.
	 * @see \Automattic\WooCommerce\StoreApi\Legacy::process_legacy_payment()
	 * @see SV_WC_Payment_Gateway::get_processing_context()
	 *
	 * @internal
	 *
	 * @since 5.12.0
	 *
	 * @param PaymentContext $payment_context
	 * @param PaymentResult $payment_result
	 * @return PaymentResult
	 */
	public function prepare_payment_data( PaymentContext $payment_context, PaymentResult $payment_result ) : PaymentResult {

		/**
		 * This is a flag to indicate we are processing a block-based checkout, helpful in some gateways that need different handling
		 * between blocks checkout and legacy shortcode checkout - this will be accessed in $_POST data along with the other field data.
		 * @see SV_WC_Payment_Gateway_Payment_Form::get_payment_fields()
		 */
		$additional_payment_data[ 'wc-' . $this->gateway->get_id_dasherized() . '-context' ] = $this->gateway::PROCESSING_CONTEXT_BLOCK;

		/**
		 * Fetch the provider-based token ID for the core token ID:
		 * @see SV_WC_Payment_Gateway_Direct::get_order()
		 */
		if ( $token = $this->get_payment_token_for_context( $payment_context ) ) {
			$additional_payment_data[ 'wc-' . $this->gateway->get_id_dasherized() . '-payment-token' ] = $token->get_id();
		}

		/**
		 * Convert the tokenization flag to the expected key-value pair:
		 * @see SV_WC_Payment_Gateway_Payment_Tokens_Handler::should_tokenize()
		 */
		if ( $should_tokenize = $payment_context->payment_data['wc-' . $this->gateway->get_id() . '-new-payment-method'] ) {
			$additional_payment_data[ 'wc-' . $this->gateway->get_id_dasherized() . '-tokenize-payment-method' ] = $should_tokenize;
		}

		/**
		 * Taking advantage of the fact that objects are passed 'by reference' (actually handles) in PHP:
		 * @link https://dev.to/nicolus/are-php-objects-passed-by-reference--2gp3
		 */
		$payment_context->set_payment_data(
			array_merge(
				$payment_context->payment_data,
				$additional_payment_data
			)
		);

		// return the original payment result
		return $payment_result;
	}


	/**
	 * Gets a payment token for a given payment context.
	 *
	 * @since 5.12.0
	 *
	 * @param PaymentContext $payment_context
	 * @return SV_WC_Payment_Gateway_Payment_Token|null
	 */
	protected function get_payment_token_for_context( PaymentContext $payment_context ): ?SV_WC_Payment_Gateway_Payment_Token {

		$core_token_id = $payment_context->payment_data['token'] ?: null;

		if ( ! $core_token_id || $payment_context->payment_method !== $this->gateway->get_id() ) {
			return null;
		}

		return $this->gateway->get_payment_tokens_handler()->get_token_by_core_id( get_current_user_id(), $core_token_id );
	}


}

endif;
