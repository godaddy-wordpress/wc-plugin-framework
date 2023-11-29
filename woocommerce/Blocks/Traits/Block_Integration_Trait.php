<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_12_0\Blocks\Traits;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use SkyVerge\WooCommerce\PluginFramework\v5_12_0\Blocks\Block_Integration;
use SkyVerge\WooCommerce\PluginFramework\v5_12_0\Payment_Gateway\Blocks\Gateway_Checkout_Block_Integration;
use SkyVerge\WooCommerce\PluginFramework\v5_12_0\SV_WC_Payment_Gateway;
use SkyVerge\WooCommerce\PluginFramework\v5_12_0\SV_WC_Payment_Gateway_Plugin;
use SkyVerge\WooCommerce\PluginFramework\v5_12_0\SV_WC_Plugin;

if ( ! class_exists( '\\SkyVerge\WooCommerce\PluginFramework\v5_12_0\Blocks\Traits\Block_Integration_Trait' ) ) :

/**
 * A trait for block integrations.
 *
 * Since WooCommerce does not provide a base class for non-gateways, but any integration needs to implement {@see IntegrationInterface},
 * we can reuse this trait in both {@see Block_Integration} and {@see Gateway_Checkout_Block_Integration} base classes.
 *
 * @since 5.12.0
 *
 * @property SV_WC_Plugin|SV_WC_Payment_Gateway_Plugin $plugin
 * @property SV_WC_Payment_Gateway|null $gateway only in payment gateway integrations
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
	 * Implements {@see IntegrationInterface::get_name()}.
	 *
	 * @since 5.12.0
	 *
	 * @return string
	 */
	public function get_name() : string {

		return $this->get_id();
	}


	/**
	 * Gets the integration ID.
	 *
	 * @since 5.12.0
	 *
	 * @return string
	 */
	protected function get_id() : string {

		return isset( $this->gateway ) ? $this->gateway->get_id() : $this->plugin->get_id();
	}


	/**
	 * Gets the integration ID (dasherized).
	 *
	 * @since 5.12.0
	 *
	 * @return string
	 */
	protected function get_id_dasherized() : string {

		return isset( $this->gateway ) ? $this->gateway->get_id_dasherized() : $this->plugin->get_id_dasherized();
	}


	/**
	 * Initializes the block integration.
	 *
	 * Individual implementations may need to override this if they need to handle scripts and styles differently.
	 *
	 * @since 5.12.0
	 *
	 * @return void
	 */
	public function initialize() : void {

		$version = isset( $this->gateway ) ? $this->plugin->get_assets_version( $this->gateway->get_id() ) : $this->plugin->get_assets_version();

		wp_register_script(
			$this->get_main_script_handle(),
			$this->get_main_script_url(),
			$this->get_main_script_dependencies(),
			$version,
			[ 'in_footer' => true ]
		);

		wp_set_script_translations(
			$this->get_main_script_handle(),
			$this->plugin->get_textdomain()
		);

		/**
		 * @NOTE Normally {@see wp_enqueue_block_style()} should suffice for block purposes,
		 * however we noticed that in some themes the block stylesheet is not loaded unless we enqueue the stylesheet
		 * via {@see wp_enqueue_style()}.
		 *
		 * Probably the reason is that if the theme has opted-in to separate-styles loading, then the stylesheet will be
		 * enqueued on-render, otherwise when the block inits.
		 */

		wp_register_style(
			$this->get_main_script_handle(),
			$this->get_main_script_stylesheet_url(),
			$this->get_main_script_stylesheet_dependencies(),
			$version,
		);

		wp_enqueue_block_style(
			$this->block_name,
			[
				'handle' => $this->get_main_script_handle(),
				'src'    => $this->get_main_script_stylesheet_url(),
				'deps'   => $this->get_main_script_stylesheet_dependencies(),
				'ver'    => $version,
			]
		);

		wp_enqueue_style( $this->get_main_script_handle() );
	}


	/**
	 * Gets the main script handle.
	 *
	 * The default is `wc-{plugin_id}-{block_name}-block`.
	 * If a gateway plugin includes different gateways supporting a block, it is assumed they will share the same script,
	 * used for a single script and stylesheet. If this is not the case, and a specific gateway needs to use a different script,
	 * then that gateway should override this method or filter out the handle.
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
		return (string) apply_filters( 'wc_' . $this->get_id() . '_'. $this->block_name . '_block_handle', sprintf(
			'wc-%s-%s-block',
			$this->plugin->get_id_dasherized(),
			$this->block_name
		), $this );
	}


	/**
	 * Gets the main script URL.
	 *
	 * The default is `{plugin_root}/assets/js/blocks/wc-{plugin_id}-{block_name}-block.js`.
	 * This may introduce the same script name if a gateway plugin includes multiple gateways.
	 * However, it is assumed that gateways supporting a block will use a single script for all the included gateways.
	 * If this is not the case, then any outlier gateway should override this method or filter out the URL.
	 *
	 * @return string
	 *@since 5.12.0
	 *
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
		return (string) apply_filters( 'wc_' . $this->get_id() . '_' . $this->block_name . '_block_script_url', sprintf(
			'%s/wc-%s-%s-block.js',
			$this->plugin->get_plugin_url() . '/assets/js/blocks',
			$this->plugin->get_id_dasherized(),
			$this->block_name
		), $this );
	}


	/**
	 * Gets the main script stylesheet URL.
	 *
	 * The default is `{plugin_root}/assets/css/blocks/wc-{plugin_id}-{block_name}-block.css`.
	 * This may introduce the same stylesheet name if a gateway plugin includes multiple gateways.
	 * However, it is assumed that gateways supporting a block will use a single stylesheet for all the included gateways.
	 * If this is not the case, then any outlier gateway should override this method or filter out the URL.
	 *
	 * @return string
	 *@since 5.12.0
	 *
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
		return (string) apply_filters( 'wc_' . $this->get_id() . '_' . $this->block_name . '_block_stylesheet_url', sprintf(
			'%s/wc-%s-%s-block.css',
			$this->plugin->get_plugin_url() . '/assets/css/blocks',
			$this->plugin->get_id_dasherized(),
			$this->block_name
		), $this );
	}


	/**
	 * Adds a main script dependency.
	 *
	 * @since 5.12.0
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
		return (array) apply_filters( 'wc_' . $this->get_id() . '_' . $this->block_name . '_block_script_dependencies', $this->script_dependencies, $this );
	}


	/**
	 * Adds a main stylesheet dependency.
	 *
	 * @since 5.12.0
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
		return (array) apply_filters( 'wc_' . $this->get_id() . '_' . $this->block_name . '_block_stylesheet_dependencies', $this->stylesheet_dependencies, $this );
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
	 * @see Block_Integration_Trait::prepare_log_data()
	 *
	 * @since 5.12.0
	 *
	 * @return void
	 */
	public function ajax_log() : void {

		// classes implementing this trait should override this method to provide AJAX logging
	}


	/**
	 * Prepares message data for logging.
	 *
	 * Implementations can override this method, for example to strip any sensitive data from an API response that shouldn't be logged, or to format specific log entries.
	 *
	 * @see Block_Integration_Trait::ajax_log()
	 *
	 * @since 5.12.0
	 *
	 * @param string|array<mixed $log_message
	 * @return string|array<mixed>
	 */
	protected function prepare_log_message( $log_message ) {

		return $log_message;
	}


}

endif;
