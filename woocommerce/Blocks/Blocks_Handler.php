<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_11_10\Blocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use SkyVerge\WooCommerce\PluginFramework\v5_11_10\Payment_Gateway\Blocks\Gateway_Checkout_Block_Integration;
use SkyVerge\WooCommerce\PluginFramework\v5_11_10\SV_WC_Payment_Gateway;
use SkyVerge\WooCommerce\PluginFramework\v5_11_10\SV_WC_Plugin;
use WP_Error;

if ( ! class_exists( '\SkyVerge\WooCommerce\PluginFramework\v5_11_10\Blocks\Blocks_Handler' ) ) :

/**
 * WooCommerce Blocks handler.
 *
 * This handler is responsible for loading and registering WooCommerce Block integration handlers in supported plugins.
 *
 * Individual plugins should override this class to load their own block integrations classes.
 *
 * @since 5.12.0
 */
class Blocks_Handler {


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
	public static function is_cart_block_in_use() : bool
	{
		if ( ! class_exists( CartCheckoutUtils::class ) ) {
			return false;
		}

		return CartCheckoutUtils::is_cart_block_default();
	}


	/**
	 * This utility method will create a new shortcode-based Cart page if the checkout block is in use, and set it as default.
	 *
	 * This should be used when the plugin is not compatible with the Cart block and the merchant wants to revert to shortcode.
	 *
	 * @since 5.12.0
	 *
	 * @return bool success
	 */
	public function restore_cart_shortcode() : bool {

		if ( ! static::is_cart_block_in_use() ) {
			return false;
		}

		/** @var array<mixed> $cart_page */
		$cart_page = get_post( wc_get_page_id( 'cart' ), ARRAY_A );

		if ( ! $cart_page || ! wp_delete_post( $cart_page['ID'] ?? 0 ) ) {
			return false;
		}

		$new_cart_page_id = wp_insert_post( array_merge( $cart_page, [
			'post_content' => '[woocommerce_cart]',
		] ) );

		if ( ! $new_cart_page_id || $new_cart_page_id instanceof WP_Error ) {
			return false;
		}

		update_option( 'woocommerce_cart_page_id', $new_cart_page_id );

		return true;
	}


	/**
	 * This utility method will create a new shortcode-based Checkout page if the checkout block is in use, and set it as default.
	 *
	 * This should be used when the plugin is not compatible with the Checkout block and the merchant wants to revert to shortcode.
	 *
	 * @since 5.12.0
	 *
	 * @return bool success
	 */
	public function restore_checkout_shortcode() : bool {

		if ( ! static::is_checkout_block_in_use() ) {
			return false;
		}

		/** @var array<mixed> $checkout_page */
		$checkout_page = get_post( wc_get_page_id( 'checkout' ), ARRAY_A );

		if ( ! $checkout_page || ! wp_delete_post( $checkout_page['ID'] ?? 0 ) ) {
			return false;
		}

		$new_checkout_page_id = wp_insert_post( array_merge( $checkout_page, [
			'post_content' => '[woocommerce_checkout]',
		] ) );

		if ( ! $new_checkout_page_id || $new_checkout_page_id instanceof WP_Error ) {
			return false;
		}

		update_option( 'woocommerce_checkout_page_id', $new_checkout_page_id );

		return true;
	}


}

endif;
