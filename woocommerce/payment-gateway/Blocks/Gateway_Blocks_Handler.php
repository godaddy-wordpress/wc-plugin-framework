<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_11_8\Payment_Gateway\Blocks;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use SkyVerge\WooCommerce\PluginFramework\v5_11_8\Blocks\Blocks_Handler;
use SkyVerge\WooCommerce\PluginFramework\v5_11_8\SV_WC_Payment_Gateway_Plugin;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_11_8\\Payment_Gateway\Blocks\\Gateway_Blocks_Handler' ) ) :

/**
 * Extends the base {@see Blocks_Handler} for support o WooCommerce Blocks in payment gateways.
 *
 * Individual gateway plugins should override this class to load their own block integrations classes.
 *
 * @since 5.12.0
 */
class Gateway_Blocks_Handler extends Blocks_Handler
{


	/**
	 * Gateway blocks handler constructor.
	 *
	 * @since 5.12.0
	 *
	 * @param SV_WC_Payment_Gateway_Plugin $plugin
	 */
	public function __construct(SV_WC_Payment_Gateway_Plugin $plugin) {

		parent::__construct($plugin);

		// individual plugins should initialize their block integrations classes by overriding this constructor
	}


	/**
	 * Handles WooCommerce Blocks integrations in compatible plugins.
	 *
	 * @internal
	 *
	 * @since 5.12.0
	 *
	 * @return void
	 */
	public function handle_blocks_integration() {

		if ( ! class_exists( PaymentMethodRegistry::class ) ) {
			return;
		}

		// @TODO a payment gateway could have multiple integration per supported payment method and each should be registered separately
		if ( $this->is_checkout_block_compatible() && ( $checkout_integration = $this->get_checkout_block_integration_instance() ) ) {

			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function( PaymentMethodRegistry $payment_method_registry ) use ( $checkout_integration ) {
					$payment_method_registry->register( $checkout_integration );
				}
			);
		}
	}


}

endif;
