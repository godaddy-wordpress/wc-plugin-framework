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
		add_action( 'wp_ajax_' . $this->plugin->get_id() . '_restore_cart_checkout_shortcode', [ $this, 'restore_cart_or_checkout_shortcode'] );

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
	 * @internal
	 *
	 * @return void
	 */
	public function add_admin_notices() : void {

		$admin_notice_handler = $this->plugin->get_admin_notice_handler();

		if ( static::is_checkout_block_in_use() ) {

			if ( ! $this->is_checkout_block_compatible() ) {

				$cta = '<button id="' . esc_attr( sprintf( '%s-restore-cart-shortcode', $this->plugin->get_id() ) ) . '" class="button button-primary">' . _x( 'Restore Checkout Page Shortcode', 'Button label', 'woocommerce-plugin-framework' ) . '</button>';

				$admin_notice_handler->add_admin_notice(
					sprintf(
						/* translators: Placeholders: %1$s - Plugin name, %2$s - opening HTML <a> tag, %3$s - closing HTML </a> tag, %4$s - opening HTML <a> tag, %5$s - `[woocommerce_checkout]` shortcode tag, %6$s - closing HTML </a> tag, %7$s opening HTML <a> tag, %8$s - closing HTML </a> tag */
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

				$this->enqueue_restore_shortcode_script( 'checkout', __( 'The Checkout page contents will be replaced with a checkout shortcode.', 'woocommerce-plugin-framework' ) );

			} else {

				$admin_notice_handler->dismiss_notice( sprintf( '%s-checkout-block-not-compatible', $this->plugin->get_id_dasherized() ), );
			}
		}

		if ( static::is_cart_block_in_use() ) {

			if ( ! $this->is_cart_block_compatible() ) {

				$cta = '<button id="' . esc_attr( sprintf( '%s-restore-cart-shortcode', $this->plugin->get_id() ) ) . '" class="button button-primary">' . _x( 'Restore Cart Page Shortcode', 'Button label', 'woocommerce-plugin-framework' ) . '</button>';

				$admin_notice_handler->add_admin_notice(
					sprintf(
						/* translators: Placeholders: %1$s - Plugin name, %2$s - opening HTML <a> tag, %3$s - closing HTML </a> tag, %4$s - opening HTML <a> tag, %5$s - `[woocommerce_cart]` shortcode tag, %6$s - closing HTML </a> tag, %7$s opening HTML <a> tag, %8$s - closing HTML </a> tag */
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

				$this->enqueue_restore_shortcode_script( 'cart', __( 'The Cart page contents will be replaced with a cart shortcode.', 'woocommerce-plugin-framework' ) );

			} else {

				$admin_notice_handler->dismiss_notice( sprintf( '%s-cart-block-not-compatible', $this->plugin->get_id_dasherized() ) );
			}
		}
	}


	/**
	 * Enqueues a script used in {@see Blocks_Handler::add_admin_notices()} to restore the Cart or Checkout page shortcodes.
	 *
	 * @since 5.12.0
	 *
	 * @param string $page either 'cart' or 'checkout
	 * @param string $confirmation_message
	 * @return void
	 */
	protected function enqueue_restore_shortcode_script( string $page, string $confirmation_message ) : void {

		if ( ! in_array( $page, [ 'cart', 'checkout' ], true ) ) {
			return;
		}

		wc_enqueue_js( "
			jQuery( document ).on( 'click', '#" . esc_js( sprintf( '%s-restore-%s-shortcode', $this->plugin->get_id(), $page ) ) . "', function( e ) {
				e.preventDefault();

				if ( ! confirm( '" . esc_js( $confirmation_message  ) . "' ) ) {
					return;
				}

				jQuery.ajax( {
					url:  '" . esc_js( admin_url( 'admin-ajax.php' ) ) . "',
					type: 'POST',
					data: {
						action: '" . esc_js( sprintf( '%s_restore_cart_checkout_shortcode', $this->plugin->get_id() ) ) . "',
						page:   '" . esc_js( $page ) . "',
						nonce:  '" . esc_js( wp_create_nonce( sprintf( '%s_restore_cart_checkout_shortcode', $this->plugin->get_id() ) ) ) . "',
					},
					success: function( response ) {
						window.location.reload();
					}
				} );
			} );"
		);
	}


	/**
	 * Restores the contents of the Cart or Checkout page, replacing any block with a corresponding shortcode.
	 *
	 * This is only used as an AJAX callback for a CTA button in {@see Blocks_Handler::add_admin_notices()}.
	 *
	 * @since 5.12.0
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function restore_cart_or_checkout_shortcode() : void {

		wp_verify_nonce( $_POST['nonce'] ?? '', sprintf( '%s_restore_cart_checkout_shortcode', $this->plugin->get_id() ) );

		wp_send_json( [ 'success' => $this->restore_page_shortcode( $_POST['page'] ?? '' ) ] );
	}


	/**
	 * Replaces the contents of the Cart or Checkout page with a corresponding shortcode.
	 *
	 * This should only be used when the plugin is not compatible with either block type and the merchant wants to revert it to shortcode-based.
	 *
	 * @since 5.12.0
	 *
	 * @param string $page either 'cart' or 'checkout'
	 */
	protected function restore_page_shortcode( string $page ) : bool {

		if ( ! in_array( $page, [ 'cart', 'checkout' ], true ) || ( 'cart' === $page && ! static::is_cart_block_in_use() ) || ( 'checkout' === $page && ! static::is_checkout_block_in_use() ) ) {
			return false;
		}

		$page_id = wc_get_page_id( $page );

		if ( ! $page_id ) {
			return false;
		}

		$success = wp_update_post( $page_id, [ 'post_content' => '[woocommerce_cart]' ] );

		if ( ! $success || $success instanceof WP_Error ) {
			return false;
		}

		return true;
	}


}

endif;
