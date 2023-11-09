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


	/** @var string[] main script dependencies */
	protected array $script_dependencies = [
		'wc-blocks-registry',
		'wc-settings',
		'wp-element',
		'wp-components',
		'wp-html-entities',
		'wp-i18n',
	];

	/** @var string[] main stylesheet dependencies */
	protected array $stylesheet_dependencies = [];


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
		return (string) apply_filters( 'wc_' . $this->gateway->get_id() . '_'. $this->block_name . '_block_handle', sprintf(
			'%s-%s-block',
			$this->gateway->get_id_dasherized(),
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
		return (string) apply_filters( 'wc_' . $this->gateway->get_id() . '_' . $this->block_name . '_block_script_url', sprintf(
			'wc-%s-%s-%s-block.js',
			$this->plugin->get_plugin_url() . '/assets/js/blocks/',
			$this->gateway->get_id_dasherized(),
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
		return (string) apply_filters( 'wc_' . $this->gateway->get_id() . '_' . $this->block_name . '_block_stylesheet_url', sprintf(
			'wc-%s-%s-%s-block.css',
			$this->plugin->get_plugin_url() . '/assets/css/blocks/',
			$this->gateway->get_id_dasherized(),
			$this->block_name
		), $this );
	}


	/**
	 * Adds a main script dependency.
	 *
	 * @param string|string[] $dependency one or more dependencies defined by their identifiers
	 * @return void
	 */
	protected function add_main_script_dependency( $dependency ) : void {

		$this->script_dependencies = array_merge( $this->script_dependencies, (array) $dependency );
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
		return (array) apply_filters( 'wc_' . $this->gateway->get_id() . '_' . $this->block_name . '_block_script_dependencies', $this->script_dependencies, $this );
	}


	/**
	 * Adds a main stylesheet dependency.
	 *
	 * @param string|string[] $dependency one or more dependencies defined by their identifiers
	 * @return void
	 */
	protected function add_main_script_stylesheet_dependency( $dependency ) : void {

		$this->stylesheet_dependencies = array_merge( $this->stylesheet_dependencies, (array) $dependency );
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
		return (array) apply_filters( 'wc_' . $this->gateway->get_id() . '_' . $this->block_name . '_block_stylesheet_dependencies', $this->stylesheet_dependencies, $this );
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


	/**
	 * Logs a message to the plugin or gateway log via AJAX.
	 *
	 * @since 5.12.0
	 *
	 * @return void
	 */
	public function ajax_log() : void {

		// classes implementing this trait should override this method to provide AJAX logging
	}


}

endif;
