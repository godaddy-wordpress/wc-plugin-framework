<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_9\Payment_Gateway\Blocks;

use Automattic\WooCommerce\Blocks\Package as WooCommerceBlocks;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use SkyVerge\WooCommerce\PluginFramework\v5_15_9\Blocks\Blocks_Handler;
use SkyVerge\WooCommerce\PluginFramework\v5_15_9\SV_WC_Payment_Gateway_Plugin;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_15_9\\Payment_Gateway\Blocks\\Gateway_Blocks_Handler' ) ) :

/**
 * Extends the base {@see Blocks_Handler} for supporting WooCommerce Blocks in payment gateways.
 *
 * Individual gateway plugins should override this class to load their own block integrations classes.
 *
 * @since 5.12.0
 *
 * @property Gateway_Checkout_Block_Integration $checkout_Block_Integration
 * @property SV_WC_Payment_Gateway_Plugin $plugin
 */
#[\AllowDynamicProperties]
class Gateway_Blocks_Handler extends Blocks_Handler {


	/**
	 * Gateway blocks handler constructor.
	 *
	 * @since 5.12.0
	 *
	 * @param SV_WC_Payment_Gateway_Plugin $plugin
	 */
	public function __construct( SV_WC_Payment_Gateway_Plugin $plugin ) {

		parent::__construct( $plugin );
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
	public function handle_blocks_integration() : void
	{
		if (
			! class_exists(PaymentMethodRegistry::class) ||
			! class_exists(WooCommerceBlocks::class) ||
			! version_compare(WooCommerceBlocks::get_version(), '4.4.0', '>')
		) {
			return;
		}

		if ($this->is_checkout_block_compatible()) {
			add_action('woocommerce_blocks_payment_method_type_registration', function (PaymentMethodRegistry $payment_method_registry) {
				if (! $this->plugin instanceof SV_WC_Payment_Gateway_Plugin) {
					return;
				}

				foreach ($this->plugin->get_gateways() as $gateway) {
					if ($checkout_integration = $gateway->get_checkout_block_integration_instance()) {
						$payment_method_registry->register($checkout_integration);
					}
				}
			});
		}
	}


}

endif;
