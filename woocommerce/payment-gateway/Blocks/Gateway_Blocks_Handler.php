<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_11_8\Payment_Gateway\Blocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use SkyVerge\WooCommerce\PluginFramework\v5_11_8\Blocks\Blocks_Handler;
use SkyVerge\WooCommerce\PluginFramework\v5_11_8\SV_WC_Payment_Gateway_Plugin;

use function Patchwork\Redefinitions\LanguageConstructs\_require_once;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_11_8\\Payment_Gateway\Blocks\\Gateway_Blocks_Handler' ) ) :

/**
 * Extends the base {@see Blocks_Handler} for support o WooCommerce Blocks in payment gateways.
 *
 * Individual gateway plugins should override this class to load their own block integrations classes.
 *
 * @since 5.12.0
 *
 * @property Gateway_Checkout_Block_Integration $checkout_Block_Integration
 */
class Gateway_Blocks_Handler extends Blocks_Handler
{


	/** @var IntegrationInterface[] */
	protected array $checkout_block_integrations = [];

	/** @var IntegrationInterface[] */
	protected array $cart_block_integrations = [];

	/**
	 * Gateway blocks handler constructor.
	 *
	 * @since 5.12.0
	 *
	 * @param SV_WC_Payment_Gateway_Plugin $plugin
	 */
	public function __construct(SV_WC_Payment_Gateway_Plugin $plugin) {

		parent::__construct($plugin);

		require_once( $this->plugin->get_framework_path() . '/payment-gateway/Blocks/Gateway_Checkout_Block_Integration.php' );

		foreach ( $plugin->get_gateways() as $gateway ) {
			if ( $checkout = $gateway->get_checkout_block_integration_instance() ) {
				$this->checkout_block_integrations[ $gateway->get_id_dasherized() ] = $checkout;
			}

			if ( $cart = $gateway->get_cart_block_integration_instance() ) {
				$this->cart_block_integrations[ $gateway->get_id_dasherized() ] = $cart;
			}
		}
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

		if ( $this->is_checkout_block_compatible() ) {

			foreach ( $this->checkout_block_integrations as $checkout_integration ) {

				add_action(
					'woocommerce_blocks_payment_method_type_registration',
					function( PaymentMethodRegistry $payment_method_registry ) use ( $checkout_integration ) {
						$payment_method_registry->register( $checkout_integration );
					}
				);
			}
		}
	}


}

endif;
