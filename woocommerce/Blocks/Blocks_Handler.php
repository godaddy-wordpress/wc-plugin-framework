<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_11_9\Blocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use SkyVerge\WooCommerce\PluginFramework\v5_11_9\Payment_Gateway\Blocks\Gateway_Checkout_Block_Integration;
use SkyVerge\WooCommerce\PluginFramework\v5_11_9\SV_WC_Payment_Gateway;
use SkyVerge\WooCommerce\PluginFramework\v5_11_9\SV_WC_Plugin;

if ( ! class_exists( '\SkyVerge\WooCommerce\PluginFramework\v5_11_9\Blocks\Blocks_Handler' ) ) :

/**
 * WooCommerce Blocks handler.
 *
 * This handler is responsible for loading and registering WooCommerce Block integration handlers in supported plugins.
 *
 * Individual plugins should override this class to load their own block integrations classes.
 *
 * @since 5.12.0
 */
class Blocks_Handler
{


	/** @var SV_WC_Plugin|SV_WC_Payment_Gateway current plugin instance */
	protected SV_WC_Plugin $plugin;

	/** @var IntegrationInterface|null */
	protected ?IntegrationInterface $cart_block_integration;

	/** @var IntegrationInterface|null */
	protected ?IntegrationInterface $checkout_block_integration;


	/**
	 * Blocks handler constructor.
	 *
	 * @since 5.12.0
	 *
	 * @param SV_WC_Plugin $plugin
	 */
	public function __construct( SV_WC_Plugin $plugin ) {

		$this->plugin = $plugin;

		$framework_path = $this->plugin->get_framework_path();

		require_once( $framework_path . '/Blocks/Traits/Block_Integration_Trait.php' );
		require_once( $framework_path . '/Blocks/Block_Integration.php' );

		// handle WooCommerce Blocks integrations in compatible plugins
		add_action( 'woocommerce_blocks_loaded', [ $this, 'handle_blocks_integration' ] );

		// individual plugins should initialize their block integrations classes by overriding this constructor
	}


	/**
	 * Determines if the plugin is compatible with the WooCommerce Cart block.
	 *
	 * @since 5.12.0
	 *
	 * @return bool
	 */
	public function is_cart_block_compatible() : bool {

		$supports = $this->plugin->get_supported_features();

		return isset( $supports['blocks']['cart'] ) && true === $supports['blocks']['cart'];
	}


	/**
	 * Determines if the plugin is compatible with the WooCommerce Checkout block.
	 *
	 * @since 5.12.0
	 *
	 * @return bool
	 */
	public function is_checkout_block_compatible() : bool {

		$supports = $this->plugin->get_supported_features();

		return isset( $supports['blocks']['checkout'] ) && true === $supports['blocks']['checkout'];
	}


	/**
	 * Gets the cart block integration instance.
	 *
	 * @since 5.12.0
	 *
	 * @return Block_Integration|null
	 */
	public function get_cart_block_integration_instance() : ?IntegrationInterface {

		return $this->cart_block_integration;
	}


	/**
	 * Gets the checkout block integration instance.
	 *
	 * @since 5.12.0
	 *
	 * @return Block_Integration|Gateway_Checkout_Block_Integration|null
	 */
	public function get_checkout_block_integration_instance() : ?IntegrationInterface {

		return $this->checkout_block_integration;
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
	public function handle_blocks_integration() : void {

		// @TODO investigate how to register integrations for non-gateways
	}


	/**
	 * Determines if the checkout page is using the checkout block.
	 *
	 * @since 5.12.0
	 *
	 * @return bool false when using the legacy checkout shortcode
	 */
	public static function is_checkout_block_in_use() : bool
	{
		if ( ! class_exists( CartCheckoutUtils::class ) ) {
			return false;
		}

		return CartCheckoutUtils::is_checkout_block_default();
	}


	/**
	 * Determines if the cart page is using the cart block.
	 *
	 * @since 5.12.0
	 *
	 * @return bool false if using the legacy cart shortcode
	 */
	public function is_cart_block_in_use() : bool
	{
		if ( ! class_exists( CartCheckoutUtils::class ) ) {
			return false;
		}

		return CartCheckoutUtils::is_cart_block_default();
	}


}

endif;
