<?php
/**
 * WooCommerce Plugin Framework
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the plugin to newer
 * versions in the future. If you wish to customize the plugin for your
 * needs please refer to http://www.skyverge.com
 *
 * @package   SkyVerge/WooCommerce/Plugin/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2023, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_12_1;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use stdClass;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_12_1\\SV_WC_Plugin' ) ) :


/**
 * # WooCommerce Plugin Framework
 *
 * This framework class provides a base level of configurable and overrideable
 * functionality and features suitable for the implementation of a WooCommerce
 * plugin.  This class handles all the "non-feature" support tasks such
 * as verifying dependencies are met, loading the text domain, etc.
 *
 * @version 5.8.0
 */
#[\AllowDynamicProperties]
abstract class SV_WC_Plugin {


	/** Plugin Framework Version */
	const VERSION = '5.12.1';

	/** @var object single instance of plugin */
	protected static $instance;

	/** @var string plugin id */
	private $id;

	/** @var string version number */
	private $version;

	/** @var string plugin path, without trailing slash */
	private $plugin_path;

	/** @var string plugin URL */
	private $plugin_url;

	/** @var string template path, without trailing slash */
	private $template_path;

	/** @var \WC_Logger instance */
	private $logger;

	/** @var  SV_WP_Admin_Message_Handler instance */
	private $message_handler;

	/** @var string the plugin text domain */
	private $text_domain;

	/** @var array{ hpos?: bool, blocks?: array{ cart?: bool, checkout?: bool }} plugin compatibility flags */
	private $supported_features;

	/** @var array memoized list of active plugins */
	private $active_plugins = [];

	/** @var SV_WC_Plugin_Dependencies dependency handler instance */
	private $dependency_handler;

	/** @var SV_WC_Hook_Deprecator hook deprecator instance */
	private $hook_deprecator;

	/** @var Plugin\Lifecycle lifecycle handler instance */
	protected $lifecycle_handler;

	/** @var REST_API REST API handler instance */
	protected $rest_api_handler;

	/** @var Blocks\Blocks_Handler blocks handler instance */
	protected Blocks\Blocks_Handler $blocks_handler;

	/** @var Admin\Setup_Wizard handler instance */
	protected $setup_wizard_handler;

	/** @var SV_WC_Admin_Notice_Handler the admin notice handler class */
	private $admin_notice_handler;


	/**
	 * Initialize the plugin.
	 *
	 * Child plugin classes may add their own optional arguments.
	 *
	 * @since 2.0.0
	 *
	 * @param string $id plugin id
	 * @param string $version plugin version number
	 * @param array{
	 *     latest_wc_versions?: int|float,
	 *     text_domain?: string,
	 *     supported_features?: array{
	 *          hpos?: bool,
	 *          blocks?: array{
	 *               cart?: bool,
	 *               checkout?: bool
	 *          }
	 *     },
	 *     dependencies?: array{
	 *          php_extensions?: array<string, mixed>,
	 *          php_functions?: array<string, mixed>,
	 *          php_settings?: array<string, mixed>
	 *     }
	 *  } $args
	 */
	public function __construct( string $id, string $version, array $args = [] ) {

		// required params
		$this->id      = $id;
		$this->version = $version;

		$args = wp_parse_args( $args, [
			'text_domain'        => '',
			'dependencies'       => [],
			'supported_features' => [
				'hpos'   => false,
				'blocks' => [
					'cart'     => false,
					'checkout' => false,
				],
			],
		] );

		$this->text_domain        = $args['text_domain'];
		$this->supported_features = $args['supported_features'];

		// includes that are required to be available at all times
		$this->includes();

		// initialize the dependencies manager
		$this->init_dependencies( $args['dependencies'] );

		// build the admin message handler instance
		$this->init_admin_message_handler();

		// build the admin notice handler instance
		$this->init_admin_notice_handler();

		// build the hook deprecator instance
		$this->init_hook_deprecator();

		// build the lifecycle handler instance
		$this->init_lifecycle_handler();

		// build the REST API handler instance
		$this->init_rest_api_handler();

		// build the blocks handler instance
		$this->init_blocks_handler();

		// build the setup handler instance
		$this->init_setup_wizard_handler();

		// add the action & filter hooks
		$this->add_hooks();
	}


	/** Init methods **********************************************************/


	/**
	 * Initializes the plugin dependency handler.
	 *
	 * @since 5.2.0
	 *
	 * @param array $dependencies {
	 *     PHP extension, function, and settings dependencies
	 *
	 *     @type array $php_extensions PHP extension dependencies
	 *     @type array $php_functions  PHP function dependencies
	 *     @type array $php_settings   PHP settings dependencies
	 * }
	 */
	protected function init_dependencies( $dependencies ) {

		$this->dependency_handler = new SV_WC_Plugin_Dependencies( $this, $dependencies );
	}


	/**
	 * Builds the admin message handler instance.
	 *
	 * Plugins can override this with their own handler.
	 *
	 * @since 5.2.0
	 */
	protected function init_admin_message_handler() {

		$this->message_handler = new SV_WP_Admin_Message_Handler( $this->get_id() );
	}


	/**
	 * Builds the admin notice handler instance.
	 *
	 * Plugins can override this with their own handler.
	 *
	 * @since 5.2.0
	 */
	protected function init_admin_notice_handler() {

		$this->admin_notice_handler = new SV_WC_Admin_Notice_Handler( $this );
	}


	/**
	 * Builds the hook deprecator instance.
	 *
	 * Plugins can override this with their own handler.
	 *
	 * @since 5.2.0
	 */
	protected function init_hook_deprecator() {

		$this->hook_deprecator = new SV_WC_Hook_Deprecator( $this->get_plugin_name(), array_merge( $this->get_framework_deprecated_hooks(), $this->get_deprecated_hooks() ) );
	}


	/**
	 * Builds the lifecycle handler instance.
	 *
	 * Plugins can override this with their own handler to perform install and
	 * upgrade routines.
	 *
	 * @since 5.2.0
	 */
	protected function init_lifecycle_handler() {

		$this->lifecycle_handler = new Plugin\Lifecycle( $this );
	}


	/**
	 * Builds the REST API handler instance.
	 *
	 * Plugins can override this to add their own data and/or routes.
	 *
	 * @since 5.2.0
	 */
	protected function init_rest_api_handler() {

		$this->rest_api_handler = new REST_API( $this );
	}


	/**
	 * Builds the blocks handler instance.
	 *
	 * @since 5.11.11
	 *
	 * @return void
	 */
	protected function init_blocks_handler() : void {

		require_once( $this->get_framework_path() . '/Blocks/Blocks_Handler.php' );

		// individual plugins should initialize their block integrations handler by overriding this method
		$this->blocks_handler = new Blocks\Blocks_Handler( $this );
	}


	/**
	 * Builds the Setup Wizard handler instance.
	 *
	 * Plugins can override and extend this method to add their own setup wizard.
	 *
	 * @since 5.3.0
	 */
	protected function init_setup_wizard_handler() {

		require_once( $this->get_framework_path() . '/admin/abstract-sv-wc-plugin-admin-setup-wizard.php' );
	}


	/**
	 * Adds the action & filter hooks.
	 *
	 * @since 5.2.0
	 */
	private function add_hooks() {

		// initialize the plugin
		add_action( 'plugins_loaded', array( $this, 'init_plugin' ), 15 );

		// initialize the plugin admin
		add_action( 'admin_init', array( $this, 'init_admin' ), 0 );

		// hook for translations separately to ensure they're loaded
		add_action( 'init', array( $this, 'load_translations' ) );

		// handle WooCommerce features compatibility (such as HPOS, WC Cart & Checkout Blocks support...)
		add_action( 'before_woocommerce_init', [ $this, 'handle_features_compatibility' ] );

		// add the admin notices
		add_action( 'admin_notices', array( $this, 'add_admin_notices' ) );
		add_action( 'admin_footer',  array( $this, 'add_delayed_admin_notices' ) );

		// add a 'Configure' link to the plugin action links
		add_filter( 'plugin_action_links_' . plugin_basename( $this->get_plugin_file() ), array( $this, 'plugin_action_links' ) );

		// automatically log HTTP requests from SV_WC_API_Base
		$this->add_api_request_logging();

		// add any PHP incompatibilities to the system status report
		add_filter( 'woocommerce_system_status_environment_rows', array( $this, 'add_system_status_php_information' ) );
	}


	/**
	 * Cloning instances is forbidden due to singleton pattern.
	 *
	 * @since 3.1.0
	 */
	public function __clone() {
		/* translators: Placeholders: %s - plugin name */
		_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'You cannot clone instances of %s.', 'woocommerce-plugin-framework' ), esc_html( $this->get_plugin_name() ) ), '3.1.0' );
	}


	/**
	 * Unserializing instances is forbidden due to singleton pattern.
	 *
	 * @since 3.1.0
	 */
	public function __wakeup() {
		/* translators: Placeholders: %s - plugin name */
		_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'You cannot unserialize instances of %s.', 'woocommerce-plugin-framework' ), esc_html( $this->get_plugin_name() ) ), '3.1.0' );
	}


	/**
	 * Load plugin & framework text domains.
	 *
	 * @internal
	 *
	 * @since 4.2.0
	 */
	public function load_translations() {

		$this->load_framework_textdomain();

		// if this plugin passes along its text domain, load its translation files
		if ( $this->text_domain ) {
			$this->load_plugin_textdomain();
		}
	}


	/**
	 * Loads the framework textdomain.
	 *
	 * @since 4.5.0
	 */
	protected function load_framework_textdomain() {
		$this->load_textdomain( 'woocommerce-plugin-framework', dirname( plugin_basename( $this->get_framework_file() ) ) );
	}


	/**
	 * Loads the plugin textdomain.
	 *
	 * @since 4.5.0
	 */
	protected function load_plugin_textdomain() {
		$this->load_textdomain( $this->text_domain, dirname( plugin_basename( $this->get_plugin_file() ) ) );
	}


	/**
	 * Loads the plugin textdomain.
	 *
	 * @since 4.5.0
	 * @param string $textdomain the plugin textdomain
	 * @param string $path the i18n path
	 */
	protected function load_textdomain( $textdomain, $path ) {

		// user's locale if in the admin for WP 4.7+, or the site locale otherwise
		$locale = is_admin() && is_callable( 'get_user_locale' ) ? get_user_locale() : get_locale();

		$locale = apply_filters( 'plugin_locale', $locale, $textdomain );

		load_textdomain( $textdomain, WP_LANG_DIR . '/' . $textdomain . '/' . $textdomain . '-' . $locale . '.mo' );

		load_plugin_textdomain( $textdomain, false, untrailingslashit( $path ) . '/i18n/languages' );
	}


	/**
	 * Initializes the plugin.
	 *
	 * Plugins can override this to set up any handlers after WordPress is ready.
	 *
	 * @since 5.2.0
	 */
	public function init_plugin() {

		// stub
	}


	/**
	 * Initializes the plugin admin.
	 *
	 * Plugins can override this to set up any handlers after the WordPress admin is ready.
	 *
	 * @since 5.2.0
	 */
	public function init_admin() {

		// stub
	}


	/**
	 * Include any critical files which must be available as early as possible,
	 *
	 * @since 2.0.0
	 */
	private function includes() {

		$framework_path = $this->get_framework_path();

		// common exception class
		require_once(  $framework_path . '/class-sv-wc-plugin-exception.php' );

		// addresses
		require_once(  $framework_path . '/Addresses/Address.php' );
		require_once(  $framework_path . '/Addresses/Customer_Address.php' );

		// Settings API
		require_once( $framework_path . '/Settings_API/Abstract_Settings.php' );
		require_once( $framework_path . '/Settings_API/Setting.php' );
		require_once( $framework_path . '/Settings_API/Control.php' );

		// common utility methods
		require_once( $framework_path . '/class-sv-wc-helper.php' );
		require_once( $framework_path . '/Country_Helper.php' );
		require_once( $framework_path . '/admin/Notes_Helper.php' );

		// backwards compatibility for older WC versions
		require_once( $framework_path . '/class-sv-wc-plugin-compatibility.php' );
		require_once( $framework_path . '/compatibility/abstract-sv-wc-data-compatibility.php' );
		require_once( $framework_path . '/compatibility/class-sv-wc-order-compatibility.php' );
		require_once( $framework_path . '/compatibility/class-sv-wc-subscription-compatibility.php' );

		// generic API base
		require_once( $framework_path . '/api/class-sv-wc-api-exception.php' );
		require_once( $framework_path . '/api/class-sv-wc-api-base.php' );
		require_once( $framework_path . '/api/interface-sv-wc-api-request.php' );
		require_once( $framework_path . '/api/interface-sv-wc-api-response.php' );

		// XML API base
		require_once( $framework_path . '/api/abstract-sv-wc-api-xml-request.php' );
		require_once( $framework_path . '/api/abstract-sv-wc-api-xml-response.php' );

		// JSON API base
		require_once( $framework_path . '/api/abstract-sv-wc-api-json-request.php' );
		require_once( $framework_path . '/api/abstract-sv-wc-api-json-response.php' );

		// Cacheable API
		require_once( $framework_path . '/api/traits/Cacheable_Request_Trait.php' );
		require_once( $framework_path . '/api/Abstract_Cacheable_API_Base.php' );

		// REST API Controllers
		require_once( $framework_path . '/rest-api/Controllers/Settings.php' );

		// Handlers
		require_once( $framework_path . '/Handlers/Script_Handler.php' );
		require_once( $framework_path . '/class-sv-wc-plugin-dependencies.php' );
		require_once( $framework_path . '/class-sv-wc-hook-deprecator.php' );
		require_once( $framework_path . '/class-sv-wp-admin-message-handler.php' );
		require_once( $framework_path . '/class-sv-wc-admin-notice-handler.php' );
		require_once( $framework_path . '/Lifecycle.php' );
		require_once( $framework_path . '/rest-api/class-sv-wc-plugin-rest-api.php' );
	}


	/**
	 * Gets a list of framework deprecated/removed hooks.
	 *
	 * @see SV_WC_Plugin::init_hook_deprecator()
	 * @see SV_WC_Plugin::get_deprecated_hooks()
	 *
	 * @since 5.8.0
	 *
	 * @return array associative array
	 */
	private function get_framework_deprecated_hooks() {

		$plugin_id          = $this->get_id();
		$deprecated_hooks   = [];
		$deprecated_filters = [
			/** @see SV_WC_Payment_Gateway_My_Payment_Methods handler - once migrated to WC core tokens UI, we removed these and have no replacement */
			// TODO: remove deprecated hooks handling by version 6.0.0 or by 2021-02-25 {FN 2020-02-25}
			"wc_{$plugin_id}_my_payment_methods_table_html",
			"wc_{$plugin_id}_my_payment_methods_table_head_html",
			"wc_{$plugin_id}_my_payment_methods_table_title",
			"wc_{$plugin_id}_my_payment_methods_table_title_html",
			"wc_{$plugin_id}_my_payment_methods_table_row_html",
			"wc_{$plugin_id}_my_payment_methods_table_body_html",
			"wc_{$plugin_id}_my_payment_methods_table_body_row_data",
			"wc_{$plugin_id}_my_payment_methods_table_method_expiry_html",
			"wc_{$plugin_id}_my_payment_methods_table_actions_html",
		];

		foreach ( $deprecated_filters as $deprecated_filter ) {
			$deprecated_hooks[ $deprecated_filter ] = [
				'removed'     => true,
				'replacement' => false,
				'version'     => '5.8.1'
			];
		}

		return $deprecated_hooks;
	}


	/**
	 * Gets a list of the plugin's deprecated/removed hooks.
	 *
	 * Implementing classes should override this and return an array of deprecated/removed hooks in the following format:
	 *
	 * $old_hook_name = array {
	 *   @type string $version version the hook was deprecated/removed in
	 *   @type bool $removed if present and true, the message will indicate the hook was removed instead of deprecated
	 *   @type string|bool $replacement if present and a string, the message will indicate the replacement hook to use,
	 *     otherwise (if bool and false) the message will indicate there is no replacement available.
	 * }
	 *
	 * @since 4.3.0
	 *
	 * @return array
	 */
	protected function get_deprecated_hooks() {

		// stub method
		return [];
	}


	/** Admin methods ******************************************************/


	/**
	 * Returns true if on the admin plugin settings page, if any
	 *
	 * @since 2.0.0
	 * @return boolean true if on the admin plugin settings page
	 */
	public function is_plugin_settings() {
		// optional method, not all plugins *have* a settings page
		return false;
	}


	/**
	 * Adds admin notices upon initialization.
	 *
	 * @since 3.0.0
	 */
	public function add_admin_notices() {

		// stub method
	}


	/**
	 * Convenience method to add delayed admin notices, which may depend upon
	 * some setting being saved prior to determining whether to render
	 *
	 * @since 3.0.0
	 */
	public function add_delayed_admin_notices() {
		// stub method
	}


	/**
	 * Return the plugin action links.  This will only be called if the plugin
	 * is active.
	 *
	 * @since 2.0.0
	 * @param array $actions associative array of action names to anchor tags
	 * @return array associative array of plugin action links
	 */
	public function plugin_action_links( $actions ) {

		$custom_actions = array();

		// settings url(s)
		if ( $this->get_settings_link( $this->get_id() ) ) {
			$custom_actions['configure'] = $this->get_settings_link( $this->get_id() );
		}

		// documentation url if any
		if ( $this->get_documentation_url() ) {
			/* translators: Docs as in Documentation */
			$custom_actions['docs'] = sprintf( '<a href="%s" target="_blank">%s</a>', $this->get_documentation_url(), esc_html__( 'Docs', 'woocommerce-plugin-framework' ) );
		}

		// support url if any
		if ( $this->get_support_url() ) {
			$custom_actions['support'] = sprintf( '<a href="%s">%s</a>', $this->get_support_url(), esc_html_x( 'Support', 'noun', 'woocommerce-plugin-framework' ) );
		}

		// review url if any
		if ( $this->get_reviews_url() ) {
			$custom_actions['review'] = sprintf( '<a href="%s">%s</a>', $this->get_reviews_url(), esc_html_x( 'Review', 'verb', 'woocommerce-plugin-framework' ) );
		}

		// add the links to the front of the actions list
		return array_merge( $custom_actions, $actions );
	}


	/**
	 * Declares HPOS compatibility if the plugin is compatible with HPOS.
	 *
	 * @internal
	 *
	 * @since 5.11.0
	 * @deprecated since 5.11.11
	 * @see SV_WC_Plugin::handle_features_compatibility()
	 *
	 * @return void
	 */
	public function handle_hpos_compatibility() : void {

		wc_deprecated_function( 'SV_WC_Plugin::handle_hpos_compatibility', '5.11.11', 'SV_WC_Plugin::handle_features_compatibility' );

		$this->handle_features_compatibility();
	}


	/**
	 * Declares compatibility with specific WooCommerce features.
	 *
	 * @internal
	 *
	 * @since 5.12.0
	 *
	 * @return void
	 */
	public function handle_features_compatibility() : void {

		if ( ! class_exists( FeaturesUtil::class ) ) {
			return;
		}

		FeaturesUtil::declare_compatibility( 'custom_order_tables', $this->get_plugin_file(), $this->is_hpos_compatible() );
		FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', $this->get_plugin_file(), $this->get_blocks_handler()->is_cart_block_compatible() || $this->get_blocks_handler()->is_checkout_block_compatible() );
	}


	/** Helper methods ******************************************************/


	/**
	 * Automatically log API requests/responses when using SV_WC_API_Base
	 *
	 * @since 2.2.0
	 * @see SV_WC_API_Base::broadcast_request()
	 */
	public function add_api_request_logging() {

		if ( ! has_action( 'wc_' . $this->get_id() . '_api_request_performed' ) ) {
			add_action( 'wc_' . $this->get_id() . '_api_request_performed', array( $this, 'log_api_request' ), 10, 2 );
		}
	}


	/**
	 * Logs API requests/responses.
	 *
	 * @since 2.2.0
	 *
	 * @param array<mixed>|stdClass $request request data, see SV_WC_API_Base::broadcast_request() for format
	 * @param array<mixed>|stdClass $response response data
	 * @param string|null $log_id log to write data to
	 */
	public function log_api_request( $request, $response, ?string $log_id = null ) : void {

		if ( ! empty( $request ) ) {
			$this->log( "Request\n" . $this->get_api_log_message( $request, 'request' ), $log_id );
		}

		if ( ! empty( $response ) ) {
			$this->log( "Response\n" . $this->get_api_log_message( $response, 'response' ), $log_id );
		}
	}


	/**
	 * Transform the API request/response data into a string suitable for logging.
	 *
	 * @since 2.2.0
	 *
	 * @param array<string, mixed>|scalar $data
	 * @param string|null $type optional type of data, either 'request' or 'response'
	 * @return string
	 */
	public function get_api_log_message( $data, ?string $type = null ) : string {

		$messages = [];

		if ( ! empty( $type ) )  {
			$messages[] = ucfirst( $type );
		} else {
			$messages[] = isset( $data['uri'] ) && $data['uri'] ? 'Request' : 'Response';
		}

		foreach ( (array) $data as $key => $value ) {

			if ( is_array( $value ) || ( is_object( $value ) && 'stdClass' === get_class( $value ) ) ) {
				$value = print_r( (array) $value, true );
			} elseif ( is_bool( $value ) ) {
				$value = wc_bool_to_string( $value );
			}

			$messages[] = trim( sprintf( '%s: %s', $key, $value ) );
		}

		return implode( "\n", $messages ) . "\n";
	}


	/**
	 * Adds any PHP incompatibilities to the system status report.
	 *
	 * @since 4.5.0
	 *
	 * @param array $rows WooCommerce system status rows
	 * @return array
	 */
	public function add_system_status_php_information( $rows ) {

		foreach ( $this->get_dependency_handler()->get_incompatible_php_settings() as $setting => $values ) {

			if ( isset( $values['type'] ) && 'min' === $values['type'] ) {

				// if this setting already has a higher minimum from another plugin, skip it
				if ( isset( $rows[ $setting ]['expected'] ) && $values['expected'] < $rows[ $setting ]['expected'] ) {
					continue;
				}

				/* translators: Placeholders: %1$s - PHP setting value, %2$s - version or value required */
				$note = __( '%1$s - A minimum of %2$s is required.', 'woocommerce-plugin-framework' );

			} else {

				// if this requirement is already listed, skip it
				if ( isset( $rows[ $setting ] ) ) {
					continue;
				}

				/* translators: Context: As in "Value has been set as [foo], but [bar] is required". Placeholders: %1$s - current value for a PHP setting, %2$s - required value for the PHP setting */
				$note = __( 'Set as %1$s - %2$s is required.', 'woocommerce-plugin-framework' );
			}

			$note = sprintf( $note, $values['actual'], $values['expected'] );

			$rows[ $setting ] = array(
				'name'     => $setting,
				'note'     => $note,
				'success'  => false,
				'expected' => $values['expected'], // WC doesn't use this, but it's useful for us
			);
		}

		return $rows;
	}


	/**
	 * Saves errors or messages to WooCommerce Log (woocommerce/logs/plugin-id-xxx.txt)
	 *
	 * @since 2.0.0
	 * @param string $message error or message to save to log
	 * @param string $log_id optional log id to segment the files by, defaults to plugin id
	 */
	public function log( $message, $log_id = null ) {

		if ( is_null( $log_id ) ) {
			$log_id = $this->get_id();
		}

		if ( ! is_object( $this->logger ) ) {
			$this->logger = new \WC_Logger();
		}

		$this->logger->add( $log_id, $message );
	}


	/**
	 * Require and instantiate a class
	 *
	 * @since 4.2.0
	 * @param string $local_path path to class file in plugin, e.g. '/includes/class-wc-foo.php'
	 * @param string $class_name class to instantiate
	 * @return object instantiated class instance
	 */
	public function load_class( $local_path, $class_name ) {

		require_once( $this->get_plugin_path() . $local_path );

		return new $class_name;
	}


	/**
	 * Determines if TLS v1.2 is required for API requests.
	 *
	 * Subclasses should override this to return true if TLS v1.2 is required.
	 *
	 * @since 5.5.2
	 *
	 * @return bool
	 */
	public function require_tls_1_2() {

		return false;
	}


	/**
	 * Determines if TLS 1.2 is available.
	 *
	 * @since 5.5.2
	 *
	 * @return bool
	 */
	public function is_tls_1_2_available() {

		// assume availability to avoid notices for unknown SSL types
		$is_available = true;

		// check the cURL version if installed
		if ( is_callable( 'curl_version' ) ) {

			$versions = curl_version();

			// cURL 7.34.0 is considered the minimum version that supports TLS 1.2
			if ( version_compare( $versions['version'], '7.34.0', '<' ) ) {
				$is_available = false;
			}
		}

		return $is_available;
	}


	/**
	 * Gets a list of the plugin's compatibility flags.
	 *
	 * @since 5.11.11
	 *
	 * @return array{ hpos?: bool, blocks?: array{ cart?: bool, checkout?: bool }}
	 */
	public function get_supported_features() : array {

		return $this->supported_features ?? [];
	}


	/**
	 * Determines if the plugin supports HPOS.
	 *
	 * @since 5.11.0
	 *
	 * @return bool
	 */
	public function is_hpos_compatible() : bool {

		return isset( $this->supported_features['hpos'] )
			&& true === $this->supported_features['hpos']
			&& SV_WC_Plugin_Compatibility::is_wc_version_gte( '7.6' );
	}


	/** Getter methods ******************************************************/


	/**
	 * Gets the main plugin file.
	 *
	 * @since 5.0.0
	 *
	 * @return string
	 */
	public function get_plugin_file() {

		$slug = dirname( plugin_basename( $this->get_file() ) );

		return trailingslashit( $slug ) . $slug . '.php';
	}


	/**
	 * The implementation for this abstract method should simply be:
	 *
	 * return __FILE__;
	 *
	 * @since 2.0.0
	 * @return string the full path and filename of the plugin file
	 */
	abstract protected function get_file();


	/**
	 * Returns the plugin id
	 *
	 * @since 2.0.0
	 * @return string plugin id
	 */
	public function get_id() {
		return $this->id;
	}


	/**
	 * Returns the plugin id with dashes in place of underscores, and
	 * appropriate for use in frontend element names, classes and ids
	 *
	 * @since 2.0.0
	 * @return string plugin id with dashes in place of underscores
	 */
	public function get_id_dasherized() {
		return str_replace( '_', '-', $this->get_id() );
	}


	/**
	 * Returns the plugin full name including "WooCommerce", ie
	 * "WooCommerce X".  This method is defined abstract for localization purposes
	 *
	 * @since 2.0.0
	 * @return string plugin name
	 */
	abstract public function get_plugin_name();


	/** Handler methods *******************************************************/


	/**
	 * Gets the dependency handler.
	 *
	 * @since 5.2.0.1
	 *
	 * @return SV_WC_Plugin_Dependencies
	 */
	public function get_dependency_handler() {

		return $this->dependency_handler;
	}


	/**
	 * Gets the lifecycle handler instance.
	 *
	 * @since 5.1.0
	 *
	 * @return Plugin\Lifecycle
	 */
	public function get_lifecycle_handler() {

		return $this->lifecycle_handler;
	}


	/**
	 * Gets the blocks handler instance.
	 *
	 * @since 5.11.11
	 *
	 * @return Blocks\Blocks_Handler
	 */
	public function get_blocks_handler() : Blocks\Blocks_Handler {

		return $this->blocks_handler;
	}


	/**
	 * Gets the Setup Wizard handler instance.
	 *
	 * @since 5.3.0
	 *
	 * @return null|Admin\Setup_Wizard
	 */
	public function get_setup_wizard_handler() {

		return $this->setup_wizard_handler;
	}


	/**
	 * Gets the admin message handler.
	 *
	 * @since 2.0.0
	 *
	 * @return SV_WP_Admin_Message_Handler
	 */
	public function get_message_handler() {

		return $this->message_handler;
	}


	/**
	 * Gets the admin notice handler instance.
	 *
	 * @since 3.0.0
	 *
	 * @return SV_WC_Admin_Notice_Handler
	 */
	public function get_admin_notice_handler() {

		return $this->admin_notice_handler;
	}


	/**
	 * Gets the settings API handler instance.
	 *
	 * Plugins can use this to init the settings API handler.
	 *
	 * @since 5.7.0
	 *
	 * @return void|Settings_API\Abstract_Settings
	 */
	public function get_settings_handler() {

		return;
	}


	/**
	 * Returns the plugin version name.  Defaults to wc_{plugin id}_version
	 *
	 * @since 2.0.0
	 *
	 * @return string the plugin version name
	 */
	public function get_plugin_version_name() {

		return 'wc_' . $this->get_id() . '_version';
	}


	/**
	 * Returns the current version of the plugin
	 *
	 * @since 2.0.0
	 *
	 * @return string plugin version
	 */
	public function get_version() {

		return $this->version;
	}


	/**
	 * Gets the plugin version to be used by any internal scripts.
	 *
	 * This normally returns the plugin version, but will return `time()` if debug is enabled, to burst assets caches.
	 *
	 * @since 5.12.0
	 *
	 * @return string
	 */
	public function get_assets_version() : string {

		if ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG || defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
			return (string) time();
		}

		return $this->version;
	}


	/**
	 * Gets the plugin's textdomain.
	 *
	 * @since 5.12.0
	 *
	 * @return string
	 */
	public function get_textdomain() : string {

		return $this->text_domain;
	}


	/**
	 * Returns the "Configure" plugin action link to go directly to the plugin
	 * settings page (if any)
	 *
	 * @since 2.0.0
	 * @see SV_WC_Plugin::get_settings_url()
	 * @param string $plugin_id optional plugin identifier.  Note that this can be a
	 *        sub-identifier for plugins with multiple parallel settings pages
	 *        (ie a gateway that supports both credit cards and echecks)
	 * @return string plugin configure link
	 */
	public function get_settings_link( $plugin_id = null ) {

		$settings_url = $this->get_settings_url( $plugin_id );

		if ( $settings_url ) {
			return sprintf( '<a href="%s">%s</a>', $settings_url, esc_html__( 'Configure', 'woocommerce-plugin-framework' ) );
		}

		// no settings
		return '';
	}


	/**
	 * Gets the plugin configuration URL
	 *
	 * @since 2.0.0
	 * @see SV_WC_Plugin::get_settings_link()
	 * @param string $plugin_id optional plugin identifier.  Note that this can be a
	 *        sub-identifier for plugins with multiple parallel settings pages
	 *        (ie a gateway that supports both credit cards and echecks)
	 * @return string plugin settings URL
	 */
	public function get_settings_url( $plugin_id = null ) {

		// stub method
		return '';
	}


	/**
	 * Returns true if the current page is the admin general configuration page
	 *
	 * @since 3.0.0
	 * @return boolean true if the current page is the admin general configuration page
	 */
	public function is_general_configuration_page() {

		return isset( $_GET['page'] ) && 'wc-settings' === $_GET['page'] && ( ! isset( $_GET['tab'] ) || 'general' === $_GET['tab'] );
	}


	/**
	 * Returns the admin configuration url for the admin general configuration page
	 *
	 * @since 3.0.0
	 * @return string admin configuration url for the admin general configuration page
	 */
	public function get_general_configuration_url() {

		return admin_url( 'admin.php?page=wc-settings&tab=general' );
	}


	/**
	 * Gets the plugin documentation url, used for the 'Docs' plugin action
	 *
	 * @since 2.0.0
	 * @return string documentation URL
	 */
	public function get_documentation_url() {

		return null;
	}


	/**
	 * Gets the support URL, used for the 'Support' plugin action link
	 *
	 * @since 4.0.0
	 * @return string support url
	 */
	public function get_support_url() {

		return null;
	}


	/**
	 * Gets the plugin sales page URL.
	 *
	 * @since 5.1.0
	 *
	 * @return string
	 */
	public function get_sales_page_url() {

		return '';
	}


	/**
	 * Gets the plugin reviews page URL.
	 *
	 * Used for the 'Reviews' plugin action and review prompts.
	 *
	 * @since 5.1.0
	 *
	 * @return string
	 */
	public function get_reviews_url() {

		return $this->get_sales_page_url() ? $this->get_sales_page_url() . '#comments' : '';
	}


	/**
	 * Gets the plugin's path without a trailing slash.
	 *
	 * e.g. /path/to/wp-content/plugins/plugin-directory
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_plugin_path() {

		if ( null === $this->plugin_path ) {
			$this->plugin_path = untrailingslashit( plugin_dir_path( $this->get_file() ) );
		}

		return $this->plugin_path;
	}


	/**
	 * Gets the plugin's URL without a trailing slash.
	 *
	 * E.g. http://skyverge.com/wp-content/plugins/plugin-directory
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_plugin_url() {

		if ( null === $this->plugin_url ) {
			$this->plugin_url = untrailingslashit( plugins_url( '/', $this->get_file() ) );
		}

		return $this->plugin_url;
	}


	/**
	 * Gets the woocommerce uploads path, without trailing slash.
	 *
	 * Oddly WooCommerce core does not provide a way to get this.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public static function get_woocommerce_uploads_path() {

		$upload_dir = wp_upload_dir();

		return $upload_dir['basedir'] . '/woocommerce_uploads';
	}


	/**
	 * Returns the loaded framework __FILE__
	 *
	 * @since 4.0.0
	 * @return string
	 */
	public function get_framework_file() {

		return __FILE__;
	}


	/**
	 * Gets the loaded framework path, without trailing slash.
	 *
	 * This matches the path to the highest version of the framework currently loaded.
	 *
	 * @since 4.0.0
	 * @return string
	 */
	public function get_framework_path() {

		return untrailingslashit( plugin_dir_path( $this->get_framework_file() ) );
	}


	/**
	 * Gets the absolute path to the loaded framework image directory, without a trailing slash.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function get_framework_assets_path() {

		return $this->get_framework_path() . '/assets';
	}


	/**
	 * Gets the loaded framework assets URL without a trailing slash.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function get_framework_assets_url() {

		return untrailingslashit( plugins_url( '/assets', $this->get_framework_file() ) );
	}


	/**
	 * Gets the plugin default template path, without a trailing slash.
	 *
	 * @since 5.5.0
	 *
	 * @return string
	 */
	public function get_template_path() {

		if ( null === $this->template_path ) {
			$this->template_path = $this->get_plugin_path() . '/templates';
		}

		return $this->template_path;
	}


	/**
	 * Loads and outputs a template file HTML.
	 *
	 * @see \wc_get_template() except we define automatically the default path
	 *
	 * @since 5.5.0
	 *
	 * @param string $template template name/part
	 * @param array $args associative array of optional template arguments
	 * @param string $path optional template path, can be empty, as themes can override this
	 * @param string $default_path optional default template path, will normally use the plugin's own template path unless overridden
	 */
	public function load_template( $template, array $args = [], $path = '', $default_path = '' ) {

		if ( '' === $default_path || ! is_string( $default_path ) ) {
			$default_path = trailingslashit( $this->get_template_path() );
		}

		wc_get_template( $template, $args, $path, $default_path );
	}


	/**
	 * Determines whether a plugin is active.
	 *
	 * @since 2.0.0
	 *
	 * @param string $plugin_name plugin name, as the plugin-filename.php
	 * @return boolean true if the named plugin is installed and active
	 */
	public function is_plugin_active( $plugin_name ) {

		$is_active = false;

		if ( is_string( $plugin_name ) ) {

			if ( ! array_key_exists( $plugin_name, $this->active_plugins ) ) {

				$active_plugins = (array) get_option( 'active_plugins', array() );

				if ( is_multisite() ) {
					$active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) );
				}

				$plugin_filenames = array();

				foreach ( $active_plugins as $plugin ) {

					if ( SV_WC_Helper::str_exists( $plugin, '/' ) ) {

						// normal plugin name (plugin-dir/plugin-filename.php)
						[ , $filename ] = explode( '/', $plugin );

					} else {

						// no directory, just plugin file
						$filename = $plugin;
					}

					$plugin_filenames[] = $filename;
				}

				$this->active_plugins[ $plugin_name ] = in_array( $plugin_name, $plugin_filenames, true );
			}

			$is_active = (bool) $this->active_plugins[ $plugin_name ];
		}

		return $is_active;
	}


}


endif;
