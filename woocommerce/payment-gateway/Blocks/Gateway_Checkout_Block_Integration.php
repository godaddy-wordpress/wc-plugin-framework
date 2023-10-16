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
use SkyVerge\WooCommerce\PluginFramework\v5_11_10\SV_WC_Payment_Gateway;
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

		return $this->get_setting( 'enabled' ) === 'yes';
	}


	/**
	 * Gets the payment method script handles.
	 *
	 * Defaults to {@see get_script_handles()} but concrete implementations may override this.
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
		return [
			'title'       => $this->gateway->method_title,
			'description' => $this->gateway->method_description,
			'supports'    => $this->gateway->supports,
			'flags' => [
				'is_credit_card_gateway' => $this->gateway->is_credit_card_gateway(),
				'is_echeck_gateway'      => $this->gateway->is_echeck_gateway(),
				'csc_enabled'            => $this->gateway->csc_enabled(),
				'csc_enabled_for_tokens' => $this->gateway->csc_enabled_for_tokens(),
				'tokenization_enabled'   => $this->gateway->tokenization_enabled(),
			]
		];
	}


	/**
	 * Prepare payment token for processing by the gateway.
	 *
	 * This method does not actually process the payment - it simply prepares the payment token for processing.
	 * The checkout block has built-in support for payment tokens, but it sends the internal (core) token ID instead of
	 * the gateway-specific token ID. This method fetches the token based on core ID and injects the gateway-specific
	 * token ID into the `PaymentContext::$payment_data` array so that the gateway can process the payment.
	 *
	 * `PaymentContext::$payment_data` is converted to `$_POST` by WC core when handling legacy payments.
	 *
	 * @see \Automattic\WooCommerce\StoreApi\Legacy::process_legacy_payment()
	 *
	 * @since 5.12.0
	 *
	 * @param PaymentContext $payment_context
	 * @param PaymentResult $payment_result
	 * @return PaymentResult
	 */
	public function prepare_payment_token( PaymentContext $payment_context, PaymentResult $payment_result ) : PaymentResult {

		if (
			( $core_token_id = $payment_context->payment_data['token'] ) &&
			$payment_context->payment_method === $this->gateway->get_id() &&
			( $token = $this->gateway->get_payment_tokens_handler()->get_token_by_core_id( get_current_user_id(), $core_token_id ) )
		) {
			// taking advantage of the fact that objects are passed "by reference" (actually handles) in PHP
			// https://dev.to/nicolus/are-php-objects-passed-by-reference--2gp3
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
}

endif;
