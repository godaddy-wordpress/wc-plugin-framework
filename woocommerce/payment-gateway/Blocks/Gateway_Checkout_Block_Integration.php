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

namespace SkyVerge\WooCommerce\PluginFramework\v5_11_10\Payment_Gateway\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;
use SkyVerge\WooCommerce\PluginFramework\v5_11_10\Payment_Gateway\External_Checkout\Google_Pay\Frontend;
use SkyVerge\WooCommerce\PluginFramework\v5_11_10\SV_WC_Payment_Gateway;
use SkyVerge\WooCommerce\PluginFramework\v5_11_10\SV_WC_Payment_Gateway_Helper;
use SkyVerge\WooCommerce\PluginFramework\v5_11_10\SV_WC_Payment_Gateway_Payment_Token;
use SkyVerge\WooCommerce\PluginFramework\v5_11_10\SV_WC_Payment_Gateway_Plugin;
use SkyVerge\WooCommerce\PluginFramework\v5_11_10\Blocks\Traits\Block_Integration_Trait;

if ( ! class_exists( '\SkyVerge\WooCommerce\PluginFramework\v5_11_10\Payment_Gateway\Blocks\Gateway_Checkout_Block_Integration' ) ) :

/**
 * Base class for handling support for the WooCommerce Checkout block in gateways.
 *
 * For support in non-gateways, {@see Block_Integration}.
 *
 * @since 5.12.0
 */
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

		add_action( 'woocommerce_rest_checkout_process_payment_with_context', [ $this, 'prepare_payment_token' ], 10, 2 );
	}


	/**
	 * Gets the integration name.
	 *
	 * @since 5.12.0
	 *
	 * @return string
	 */
	public function get_name() : string {

		return $this->gateway->get_id();
	}


	/**
	 * Determines if the payment method is active in the checkout block context.
	 *
	 * @since 5.12.0
	 *
	 * @return bool
	 */
	public function is_active() : bool {

		// @TODO perhaps we should update this to $this->gateway->is_available() so that we don't display a misconfigured gateway?
		return $this->get_setting( 'enabled' ) === 'yes';
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
			'id'          => $this->gateway->get_id_dasherized(), // dashes
			'name'        => $this->gateway->get_id(), // underscores
			'type'        => $this->gateway->get_payment_type(),
			'title'       => $this->gateway->get_title(), // user-facing display title
			'description' => $this->gateway->get_description(), // user-facing description
			'icons'       => $this->get_gateway_icons(), // icon or card icons displayed next to title
			'card_types'  => $this->gateway->get_card_types(), // configured card types
			'supports'    => $this->gateway->supports,
			'flags' => [
				'is_test_environment'    => $this->gateway->is_test_environment(),
				'is_credit_card_gateway' => $this->gateway->is_credit_card_gateway(),
				'is_echeck_gateway'      => $this->gateway->is_echeck_gateway(),
				'csc_enabled'            => $this->gateway->csc_enabled(),
				'csc_enabled_for_tokens' => $this->gateway->csc_enabled_for_tokens(),
				'tokenization_enabled'   => $this->gateway->supports_tokenization() && $this->gateway->tokenization_enabled(),
			],
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
		];

		// Apple Pay
		if ( $this->gateway->supports_apple_pay() ) {

			$apple_pay = $this->plugin->get_apple_pay_instance();

			if ( $apple_pay && $this->gateway->id === $apple_pay->get_processing_gateway()->id ) {

				$payment_method_data['apple_pay'] = [
					'merchant_id'              => $apple_pay->get_merchant_id(),
					'validate_nonce'           => wp_create_nonce( 'wc_' . $this->gateway->get_id() . '_apple_pay_validate_merchant' ),
					'recalculate_totals_nonce' => wp_create_nonce( 'wc_' . $this->gateway->get_id() . '_apple_pay_recalculate_totals' ),
					'process_nonce'            => wp_create_nonce( 'wc_' . $this->gateway->get_id() . '_apple_pay_process_payment' ),
					'currencies'               => $this->gateway->get_apple_pay_currencies(),
					'capabilities'             => $this->gateway->get_apple_pay_capabilities(),
					'flags'                    => [
						'is_available' => $apple_pay->is_available(),
						'is_enabled'   => $apple_pay->is_enabled(),
						'is_test_mode' => $apple_pay->is_test_mode(),
					],
				];
			}
		}

		// Google Pay
		if ( $this->gateway->supports_google_pay() ) {

			$google_pay = $this->plugin->get_google_pay_instance();

			if ( $google_pay && $this->gateway->id === $google_pay->get_processing_gateway()->id ) {

				$payment_method_data['google_pay'] = [
					'merchant_id'              => $google_pay->get_merchant_id(),
					'merchant_name'            => get_bloginfo( 'name' ),
					'recalculate_totals_nonce' => wp_create_nonce( 'wc_' . $this->gateway->get_id() . '_google_pay_recalculate_totals' ),
					'process_nonce'            => wp_create_nonce( 'wc_' . $this->gateway->get_id() . '_google_pay_process_payment' ),
					'button_style'             => $google_pay->get_button_style(),
					'card_types'               => $google_pay->get_supported_networks(),
					'available_countries'      => $google_pay->get_available_countries(),
					'currency_code'            => get_woocommerce_currency(),
					'flags'                    => [
						'is_enabled'   => $google_pay->is_enabled(),
						'is_available' => $google_pay->is_available(),
						'is_test_mode' => $google_pay->is_test_mode(),
					],
				];
			}
		}

		/**
		 * Filters gateway-specific payment method data for the Checkout Block.
		 *
		 * @since 5.12.0
		 *
		 * @param $params array<string, mixed>
		 * @param $gateway SV_WC_Payment_Gateway
		 */
		return apply_filters( "wc_{$this->gateway->get_id()}_checkout_block_payment_method_data", $payment_method_data, $this->gateway );
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

		$icon = $this->gateway->icon;

		if ( $icon ) {
			return [ $this->gateway->get_method_title() => $icon ];
		} elseif ( ! $this->gateway->supports_card_types() ) {
			return [];
		}

		$icons = [];

		foreach ( $this->gateway->get_card_types() as $card_type ) {

			$card_type = SV_WC_Payment_Gateway_Helper::normalize_card_type( $card_type );
			$card_name = SV_WC_Payment_Gateway_Helper::payment_type_to_name( $card_type );

			if ( $url = $this->gateway->get_payment_method_image_url( $card_type ) ) {
				$icons[ $card_name ] = $url;
			}
		}

		return $icons;
	}


	/**
	 * Prepare payment token for processing by the gateway.
	 *
	 * This method does not actually process the payment - it simply prepares the payment token for processing.
	 * The checkout block has built-in support for payment tokens, but it sends the internal (core) token ID instead of
	 * the gateway-specific token ID. This method fetches the token based on core ID and injects the gateway-specific
	 * token ID into the `PaymentContext::$payment_data` array so that the gateway can process the payment.
	 *
	 * @see PaymentContext::$payment_data is converted to `$_POST` by WC core when handling legacy payments.
	 * @see \Automattic\WooCommerce\StoreApi\Legacy::process_legacy_payment()
	 *
	 * @since 5.12.0
	 *
	 * @param PaymentContext $payment_context
	 * @param PaymentResult $payment_result
	 * @return PaymentResult
	 */
	public function prepare_payment_token( PaymentContext $payment_context, PaymentResult $payment_result ) : PaymentResult {

		if ( $token = $this->get_payment_token_for_context( $payment_context ) ) {
			/**
			 * Taking advantage of the fact that objects are passed 'by reference' (actually handles) in PHP:
			 * @link https://dev.to/nicolus/are-php-objects-passed-by-reference--2gp3
			 */
			$payment_context->set_payment_data(
				array_merge(
					$payment_context->payment_data,
					[ 'wc-' . $this->gateway->get_id_dasherized() . '-payment-token' => $token->get_id() ]
				)
			);
		}

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
	protected function get_payment_token_for_context( PaymentContext $payment_context ) {

		$core_token_id = $payment_context->payment_data['token'] ?: null;

		if ( ! $core_token_id || $payment_context->payment_method !== $this->gateway->get_id() ) {
			return null;
		}

		return $this->gateway->get_payment_tokens_handler()->get_token_by_core_id( get_current_user_id(), $core_token_id );
	}


}

endif;
