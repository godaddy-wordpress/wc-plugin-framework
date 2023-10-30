<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_11_10\Blocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use Exception;
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
	 * Individual plugins should initialize their block integrations classes by overriding this constructor and calling the parent.
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

		// blocks-related notices and call-to-actions
		add_action( 'admin_notices', [ $this, 'add_admin_notices' ] );

		// handle WooCommerce Blocks integrations in compatible plugins
		add_action( 'woocommerce_blocks_loaded', [ $this, 'handle_blocks_integration' ] );
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
	 * Adds admin notices pertaining the blocks integration.
	 *
	 * @since 5.12.0
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function add_admin_notices() : void {

		$admin_notice_handler = $this->plugin->get_admin_notice_handler();

		if ( static::is_checkout_block_in_use() ) {

			if ( ! $this->is_checkout_block_compatible() ) {

				$url = get_edit_post_link( wc_get_page_id( 'checkout' ) );
				$cta = '<a href="' . esc_url( $url ) .'" id="' . esc_attr( sprintf( '%s-restore-cart-shortcode', $this->plugin->get_id() ) ) . '" class="button button-primary">' . _x( 'Edit the Checkout Page', 'Button label', 'woocommerce-plugin-framework' ) . '</a>';

				$admin_notice_handler->add_admin_notice(
					sprintf(
						/* translators: Context: WordPress blocks and shortcodes. Placeholders: %1$s - Plugin name, %2$s - opening HTML <a> tag, %3$s - closing HTML </a> tag, %4$s - opening HTML <a> tag, %5$s - `[woocommerce_checkout]` shortcode tag, %6$s - closing HTML </a> tag, %7$s opening HTML <a> tag, %8$s - closing HTML </a> tag */
						__( 'The Checkout block is not compatible with %1$s. Please %2$sedit the Checkout page%3$s to use the %4$s%5$s shortcode%6$s instead. %7$sLearn more about using shortcodes here%8$s.', 'woocommerce-plugin-framework' ),
						'<strong>' . $this->plugin->get_plugin_name() . '</strong>',
						'<a href="' . esc_url( get_edit_post_link( wc_get_page_id( 'checkout' ) ) ) . '">', '</a>',
						'<a href="https://woocommerce.com/document/woocommerce-shortcodes/#checkout">',
						'<code>[woocommerce_checkout]</code>',
						'</a>',
						'<a href="https://woocommerce.com/document/cart-checkout-blocks-support-status/#reverting-to-shortcodes">', '</a>'
					) . '<br><br>' . $cta,
					sprintf( '%s-checkout-block-not-compatible', $this->plugin->get_id_dasherized() ),
					[
						'notice_class'            => 'notice-error',
						'always_show_on_settings' => false,
					]
				);

			} else {

				$admin_notice_handler->dismiss_notice( sprintf( '%s-checkout-block-not-compatible', $this->plugin->get_id_dasherized() ), );
			}
		}

		if ( static::is_cart_block_in_use() ) {

			if ( ! $this->is_cart_block_compatible() ) {

				$url = get_edit_post_link( wc_get_page_id( 'cart' ) );
				$cta = '<a href="' . esc_url( $url ) . '" id="' . esc_attr( sprintf( '%s-restore-cart-shortcode', $this->plugin->get_id() ) ) . '" class="button button-primary">' . _x( 'Edit the Cart Page', 'Button label', 'woocommerce-plugin-framework' ) . '</a>';

				$admin_notice_handler->add_admin_notice(
					sprintf(
						/* translators: Context: WordPress blocks and shortcodes. Placeholders: %1$s - Plugin name, %2$s - opening HTML <a> tag, %3$s - closing HTML </a> tag, %4$s - opening HTML <a> tag, %5$s - `[woocommerce_cart]` shortcode tag, %6$s - closing HTML </a> tag, %7$s opening HTML <a> tag, %8$s - closing HTML </a> tag */
						__( 'The Cart block is not compatible with %1$s. Please %2$sedit the Cart page%3$s to use the %4$s%5$s shortcode%6$s instead. %7$sLearn more about using shortcodes here%8$s.', 'woocommerce-plugin-framework' ),
						'<strong>' . $this->plugin->get_plugin_name() . '</strong>',
						'<a href="' . esc_url( get_edit_post_link( wc_get_page_id( 'cart' ) ) ) . '">', '</a>',
						'<a href="https://woocommerce.com/document/woocommerce-shortcodes/#cart">',
						'<code>[woocommerce_cart]</code>',
						'</a>',
						'<a href="https://woocommerce.com/document/cart-checkout-blocks-support-status/#reverting-to-shortcodes">', '</a>'
					) . '<br><br>' . $cta,
					sprintf( '%s-cart-block-not-compatible', $this->plugin->get_id_dasherized() ),
					[
						'notice_class'            => 'notice-error',
						'always_show_on_settings' => false,
					]
				);

			} else {

				$admin_notice_handler->dismiss_notice( sprintf( '%s-cart-block-not-compatible', $this->plugin->get_id_dasherized() ) );
			}
		}
	}


}

endif;
