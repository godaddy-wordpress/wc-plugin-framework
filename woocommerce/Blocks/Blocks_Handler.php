<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_11_12\Blocks;

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use SkyVerge\WooCommerce\PluginFramework\v5_11_12 as Framework;

if ( ! class_exists( '\SkyVerge\WooCommerce\PluginFramework\v5_11_12\Blocks\Blocks_Handler' ) ) :

/**
 * WooCommerce Blocks handler.
 *
 * This handler is responsible for loading and registering WooCommerce Block integration handlers in supported plugins.
 *
 * Individual plugins should override this class to load their own block integrations classes.
 *
 * @since 5.11.11
 */
class Blocks_Handler {


	/** @var Framework\SV_WC_Plugin|Framework\SV_WC_Payment_Gateway current plugin instance */
	protected Framework\SV_WC_Plugin $plugin;


	/**
	 * Blocks handler constructor.
	 *
	 * Individual plugins should initialize their block integrations classes by overriding this constructor and calling the parent.
	 *
	 * @since 5.11.11
	 *
	 * @param Framework\SV_WC_Plugin $plugin
	 */
	public function __construct( Framework\SV_WC_Plugin $plugin ) {

		$this->plugin = $plugin;

		// blocks-related notices and call-to-actions
		add_action( 'admin_notices', [ $this, 'add_admin_notices' ] );
	}


	/**
	 * Determines if the plugin is compatible with the WooCommerce Cart block.
	 *
	 * @since 5.11.11
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
	 * @since 5.11.11
	 *
	 * @return bool
	 */
	public function is_checkout_block_compatible() : bool {

		$supports = $this->plugin->get_supported_features();

		return isset( $supports['blocks']['checkout'] ) && true === $supports['blocks']['checkout'];
	}


	/**
	 * Determines if the checkout page is using the checkout block.
	 *
	 * @since 5.11.11
	 *
	 * @return bool false when using the legacy checkout shortcode
	 */
	public static function is_checkout_block_in_use() : bool {

		if ( ! class_exists( CartCheckoutUtils::class ) ) {
			return false;
		}

		return CartCheckoutUtils::is_checkout_block_default();
	}


	/**
	 * Determines if the cart page is using the cart block.
	 *
	 * @since 5.11.11
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
	 * @since 5.11.11
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
						/* translators: Context: WordPress blocks and shortcodes. Placeholders: %1$s - Plugin name, %2$s - opening HTML <a> tag, %3$s - closing HTML </a> tag, %4$s - opening HTML <a> tag, %5$s - `[woocommerce_checkout]` shortcode tag, %6$s - closing HTML </a> tag */
						__( '%1$s is not yet compatible with the Checkout block. We recommend %2$sfollowing this guide%3$s to revert to the %4$s%5$s shortcode%6$s.', 'woocommerce-plugin-framework' ),
						'<strong>' . $this->plugin->get_plugin_name() . '</strong>',
						'<a href="https://woo.com/document/cart-checkout-blocks-status/#section-6" target="_blank">',
						'</a>',
						'<a href="https://woo.com/document/woocommerce-shortcodes/#checkout" target="_blank">',
						'<code>[woocommerce_checkout]</code>',
						'</a>',
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
						/* translators: Context: WordPress blocks and shortcodes. Placeholders: %1$s - Plugin name, %2$s - opening HTML <a> tag, %3$s - closing HTML </a> tag, %4$s - opening HTML <a> tag, %5$s - `[woocommerce_cart]` shortcode tag, %6$s - closing HTML </a> tag */
						__( '%1$s is not yet compatible with the Cart block. We recommend %2$sfollowing this guide%3$s to revert to the %4$s%5$s shortcode%6$s.', 'woocommerce-plugin-framework' ),
						'<strong>' . $this->plugin->get_plugin_name() . '</strong>',
						'<a href="https://woo.com/document/cart-checkout-blocks-status/#section-6" target="_blank">',
						'</a>',
						'<a href="https://woo.com/document/woocommerce-shortcodes/#cart" target="_blank">',
						'<code>[woocommerce_cart]</code>',
						'</a>',
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
