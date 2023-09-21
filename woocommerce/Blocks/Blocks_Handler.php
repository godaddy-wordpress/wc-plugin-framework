<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_11_8\Blocks;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use SkyVerge\WooCommerce\PluginFramework\v5_11_8\SV_WC_Payment_Gateway;
use SkyVerge\WooCommerce\PluginFramework\v5_11_8\SV_WC_Plugin;

if ( ! class_exists( '\SkyVerge\WooCommerce\PluginFramework\v5_11_8\Blocks\Blocks_Handler' ) ) :

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

	/** @var Block_Integration|null */
	protected ?Block_Integration $cart_block_integration;

	/** @var Block_Integration|null */
	protected ?Block_Integration $checkout_block_integration;


	/**
	 * Blocks handler constructor.
	 *
	 * @since 5.12.0
	 *
	 * @param SV_WC_Plugin $plugin
	 */
	public function __construct( SV_WC_Plugin $plugin ) {

		$this->plugin = $plugin;

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
	public function get_cart_block_integration_instance() : ?Block_Integration {

		return $this->cart_block_integration;
	}


	/**
	 * Gets the checkout block integration instance.
	 *
	 * @since 5.12.0
	 *
	 * @return Block_Integration|null
	 */
	public function get_checkout_block_integration_instance() : ?Block_Integration {

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
	public function handle_blocks_integration() {

		// @TODO investigate how to register integrations for non-gateways
	}


}

endif;
