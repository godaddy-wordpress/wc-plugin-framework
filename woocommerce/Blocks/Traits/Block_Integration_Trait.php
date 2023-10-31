<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_11_10\Blocks\Traits;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use SkyVerge\WooCommerce\PluginFramework\v5_11_10\Blocks\Block_Integration;
use SkyVerge\WooCommerce\PluginFramework\v5_11_10\Payment_Gateway\Blocks\Gateway_Checkout_Block_Integration;
use SkyVerge\WooCommerce\PluginFramework\v5_11_10\SV_WC_Payment_Gateway;
use SkyVerge\WooCommerce\PluginFramework\v5_11_10\SV_WC_Payment_Gateway_Plugin;
use SkyVerge\WooCommerce\PluginFramework\v5_11_10\SV_WC_Plugin;

if ( ! class_exists( '\\SkyVerge\WooCommerce\PluginFramework\v5_11_10\Blocks\Traits\Block_Integration_Trait' ) ) :

/**
 * A trait for block integrations.
 *
 * Since WooCommerce does not provide a base class for non-gateways, but any integration needs to implement {@see IntegrationInterface},
 * we can reuse this trait in both {@see Block_Integration} and {@see Gateway_Checkout_Block_Integration} base classes.
 *
 * @since 5.12.0
 *
 * @property SV_WC_Plugin|SV_WC_Payment_Gateway_Plugin $plugin
 * @property SV_WC_Payment_Gateway $gateway only in payment gateway integrations
 * @property string $block_name the name of the block the integration is for, e.g. 'cart' or 'checkout
 */
trait Block_Integration_Trait {


	/**
	 * Gets the integration name.
	 *
	 * @since 5.12.0
	 *
	 * @return string
	 */
	public function get_name() : string {

		return $this->plugin->get_id();
	}


	/**
	 * Initializes the block integration.
	 *
	 * @since 5.12.0
	 *
	 * @return void
	 */
	public function initialize() : void {

		// @TODO: perhaps we can provide here a framework initialization of basic scripts or dynamically load the expected plugin assets
	}


	/**
	 * Gets the main script handle.
	 *
	 * @since 5.12.0
	 *
	 * @return string
	 */
	protected function get_main_script_handle() : string {

		/**
		 * Filters the block main script handle.
		 *
		 * @since 5.12.0
		 *
		 * @param string $handle
		 * @param Block_Integration $integration
		 */
		return (string) apply_filters( 'wc_' . $this->plugin->get_id() . '_'. $this->block_name . '_block_handle', sprintf(
			'%s-%s-block',
			$this->plugin->get_id_dasherized(),
			$this->block_name
		), $this );
	}


	/**
	 * Gets the main script URL.
	 *
	 * @since 5.12.0
	 *
	 * @return string
	 */
	protected function get_main_script_url() : string {

		/**
		 * Filters the block main script URL.
		 *
		 * @since 5.12.0
		 *
		 * @param string $url
		 * @param Block_Integration $integration
		 */
		return (string) apply_filters( 'wc_' . $this->plugin->get_id() . '_' . $this->block_name . '_script_url', sprintf(
			'%s-%s-%s-block',
			$this->plugin->get_plugin_url() . '/assets/js/blocks/',
			$this->plugin->get_id_dasherized(),
			$this->block_name
		), $this );
	}


	/**
	 * Gets the main script stylesheet URL.
	 *
	 * @since 5.12.0
	 *
	 * @return string
	 */
	protected function get_main_script_stylesheet_url() : string {

		/**
		 * Filters the block main script stylesheet URL.
		 *
		 * @since 5.12.0
		 *
		 * @param string $url
		 * @param Block_Integration $integration
		 */
		return (string) apply_filters( 'wc_' . $this->plugin->get_id() . '_' . $this->block_name . '_stylesheet_url', sprintf(
			'%s-%s-%s-block',
			$this->plugin->get_plugin_url() . '/assets/css/blocks/',
			$this->plugin->get_id_dasherized(),
			$this->block_name
		), $this );
	}


	/**
	 * Gets the main script dependencies.
	 *
	 * @since 5.12.0
	 *
	 * @return string[]
	 */
	protected function get_main_script_dependencies() : array {

		/**
		 * Filters the block main script dependencies.
		 *
		 * @since 5.12.0
		 *
		 * @param string[] $dependencies
		 * @param Block_Integration $integration
		 */
		return (array) apply_filters( 'wc_' . $this->plugin->get_id() . '_' . $this->block_name . '_dependencies', [
			'wc-blocks-registry',
			'wc-settings',
			'wp-element',
			'wp-components',
			'wp-html-entities',
			'wp-i18n',
		], $this );
	}


	/**
	 * Gets the main script stylesheet dependencies.
	 *
	 * @since 5.12.0
	 *
	 * @return string[]
	 */
	protected function get_main_script_stylesheet_dependencies() : array {

		/**
		 * Filters the block main script stylesheet dependencies.
		 *
		 * @since 5.12.0
		 *
		 * @param string[] $dependencies
		 * @param Block_Integration $integration
		 */
		return (array) apply_filters( 'wc_' . $this->plugin->get_id() . '_' . $this->block_name . '_stylesheet_dependencies', [], $this );
	}


	/**
	 * Gets an array of script handles to enqueue in the frontend context.
	 *
	 * @since 5.12.0
	 *
	 * @return string[]
	 */
	public function get_script_handles() {

		return [ $this->get_main_script_handle() ];
	}


	/**
	 * Gets an array of script handles to enqueue in the editor context.
	 *
	 * @since 5.12.0
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() : array {

		return [ $this->get_main_script_handle() ];
	}


	/**
	 * Gets array of key-value pairs of data made available to the block on the client side.
	 *
	 * @since 5.12.0
	 *
	 * @return array<string, mixed> (default empty)
	 */
	public function get_script_data() : array {

		return [];
	}


}

endif;
