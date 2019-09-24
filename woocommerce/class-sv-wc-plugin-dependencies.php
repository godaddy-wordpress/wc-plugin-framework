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
 * @copyright Copyright (c) 2013-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_5_0;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_5_0\\SV_WC_Plugin_Dependencies' ) ) :


/**
 * Plugin dependencies handler.
 *
 * @since 5.2.0
 */
class SV_WC_Plugin_Dependencies {


	/** @var array required PHP extensions */
	protected $php_extensions = array();

	/** @var array required PHP functions */
	protected $php_functions = array();

	/** @var array required PHP settings */
	protected $php_settings = array();

	/** @var SV_WC_Plugin plugin instance */
	protected $plugin;


	/**
	 * Constructs the class.
	 *
	 * @since 5.2.0
	 *
	 * @param SV_WC_Plugin $plugin plugin instance
	 * @param array $args {
	 *     PHP extension, function, and settings dependencies
	 *
	 *     @type array $php_extensions PHP extension dependencies
	 *     @type array $php_functions  PHP function dependencies
	 *     @type array $php_settings   PHP settings dependencies
	 * }
	 */
	public function __construct( SV_WC_Plugin $plugin, $args = array() ) {

		$this->plugin = $plugin;

		$dependencies = $this->parse_dependencies( $args );

		$this->php_extensions = (array) $dependencies['php_extensions'];
		$this->php_functions  = (array) $dependencies['php_functions'];
		$this->php_settings   = (array) $dependencies['php_settings'];

		// add the action & filter hooks
		$this->add_hooks();
	}


	/**
	 * Parses the dependency arguments and sets defaults.
	 *
	 * @since 5.2.0
	 *
	 * @param array $args dependency args
	 * @return array
	 */
	private function parse_dependencies( $args ) {

		$dependencies = wp_parse_args( $args, array(
			'php_extensions' => array(),
			'php_functions'  => array(),
			'php_settings'   => array(),
		) );

		$default_settings = array(
			'suhosin.post.max_array_index_length'    => 256,
			'suhosin.post.max_totalname_length'      => 65535,
			'suhosin.post.max_vars'                  => 1024,
			'suhosin.request.max_array_index_length' => 256,
			'suhosin.request.max_totalname_length'   => 65535,
			'suhosin.request.max_vars'               => 1024,
		);

		// override any default settings requirements if the plugin specifies them
		if ( ! empty( $dependencies['php_settings'] ) ) {
			$dependencies['php_settings'] = array_merge( $default_settings, $dependencies['php_settings'] );
		}

		return $dependencies;
	}


	/**
	 * Adds the action & filter hooks.
	 *
	 * @since 5.2.0
	 */
	protected function add_hooks() {

		// add the admin dependency notices
		add_action( 'admin_init', array( $this, 'add_admin_notices' ) );
	}


	/**
	 * Adds the admin dependency notices.
	 *
	 * @since 5.2.0
	 */
	public function add_admin_notices() {

		$this->add_php_extension_notices();
		$this->add_php_function_notices();
		$this->add_php_settings_notices();

		$this->add_deprecated_notices();
	}


	/**
	 * Adds notices for any missing PHP extensions.
	 *
	 * @since 5.2.0
	 */
	public function add_php_extension_notices() {

		$missing_extensions = $this->get_missing_php_extensions();

		if ( count( $missing_extensions ) > 0 ) {

			$message = sprintf(
				/* translators: Placeholders: %1$s - plugin name, %2$s - a PHP extension/comma-separated list of PHP extensions */
				_n(
					'%1$s requires the %2$s PHP extension to function. Contact your host or server administrator to install and configure the missing extension.',
					'%1$s requires the following PHP extensions to function: %2$s. Contact your host or server administrator to install and configure the missing extensions.',
					count( $missing_extensions ),
					'woocommerce-plugin-framework'
				),
				esc_html( $this->get_plugin()->get_plugin_name() ),
				'<strong>' . implode( ', ', $missing_extensions ) . '</strong>'
			);

			$this->add_admin_notice( 'wc-' . $this->get_plugin()->get_id_dasherized() . '-missing-extensions', $message, 'error' );
		}
	}


	/**
	 * Adds notices for any missing PHP functions.
	 *
	 * @since 5.2.0
	 */
	public function add_php_function_notices() {

		$missing_functions = $this->get_missing_php_functions();

		if ( count( $missing_functions ) > 0 ) {

			$message = sprintf(
				/* translators: Placeholders: %1$s - plugin name, %2$s - a PHP function/comma-separated list of PHP functions */
				_n(
					'%1$s requires the %2$s PHP function to exist.  Contact your host or server administrator to install and configure the missing function.',
					'%1$s requires the following PHP functions to exist: %2$s.  Contact your host or server administrator to install and configure the missing functions.',
					count( $missing_functions ),
					'woocommerce-plugin-framework'
				),
				esc_html( $this->get_plugin()->get_plugin_name() ),
				'<strong>' . implode( ', ', $missing_functions ) . '</strong>'
			);

			$this->add_admin_notice( 'wc-' . $this->get_plugin()->get_id_dasherized() . '-missing-functions', $message, 'error' );
		}
	}


	/**
	 * Adds notices for any incompatible PHP settings.
	 *
	 * @since 5.2.0
	 */
	public function add_php_settings_notices() {

		if ( isset( $_GET['page'] ) && 'wc-settings' === $_GET['page'] ) {

			$bad_settings = $this->get_incompatible_php_settings();

			if ( count( $bad_settings ) > 0 ) {

				$message = sprintf(
					/* translators: Placeholders: %s - plugin name */
					__( '%s may behave unexpectedly because the following PHP configuration settings are required:' ),
					'<strong>' . esc_html( $this->get_plugin()->get_plugin_name() ) . '</strong>'
				);

				$message .= '<ul>';

					foreach ( $bad_settings as $setting => $values ) {

						$setting_message = '<code>' . $setting . ' = ' . $values['expected'] . '</code>';

						if ( ! empty( $values['type'] ) && 'min' === $values['type'] ) {

							$setting_message = sprintf(
								/** translators: Placeholders: %s - a PHP setting value */
								__( '%s or higher', 'woocommerce-plugin-framework' ),
								$setting_message
							);
						}

						$message .= '<li>' . $setting_message . '</li>';
					}

				$message .= '</ul>';

				$message .= __( 'Please contact your hosting provider or server administrator to configure these settings.', 'woocommerce-plugin-framework' );

				$this->add_admin_notice( 'wc-' . $this->get_plugin()->get_id_dasherized() . '-incompatibile-php-settings', $message, 'warning' );
			}
		}
	}


	/**
	 * Gets any deprecated warning notices.
	 *
	 * @since 5.2.0
	 */
	protected function add_deprecated_notices() {

		// add a notice for PHP < 5.6
		if ( version_compare( PHP_VERSION, '5.6.0', '<' ) ) {

			$message = '<p>';

			$message .= sprintf(
				/* translators: Placeholders: %1$s - <strong>, %2$s - </strong> */
				__( 'Hey there! We\'ve noticed that your server is running %1$san outdated version of PHP%2$s, which is the programming language that WooCommerce and its extensions are built on.
					The PHP version that is currently used for your site is no longer maintained, nor %1$sreceives security updates%2$s; newer versions are faster and more secure.
					As a result, %3$s no longer supports this version and you should upgrade PHP as soon as possible.
					Your hosting provider can do this for you. %4$sHere are some resources to help you upgrade%5$s and to explain PHP versions further.', 'woocommerce-plugin-framework' ),
				'<strong>', '</strong>',
				esc_html( $this->get_plugin()->get_plugin_name() ),
				'<a href="http://skyver.ge/upgradephp">', '</a>'
			);

			$message .= '</p>';

			$this->add_admin_notice( 'sv-wc-deprecated-php-version', $message, 'error' );
		}
	}


	/**
	 * Adds an admin notice.
	 *
	 * @since 5.2.0
	 *
	 * @param string $id notice ID
	 * @param string $message notice message
	 * @param string $type notice type
	 */
	protected function add_admin_notice( $id, $message, $type = 'info' ) {

		$notice_class = $type;

		// translate the types into WP notice classes
		switch ( $type ) {

			case 'error':
				$notice_class = 'notice-error';
			break;

			case 'warning':
				$notice_class = 'notice-warning';
			break;

			case 'info':
				$notice_class = 'notice-info';
			break;

			case 'success':
				$notice_class = 'notice-success';
			break;
		}

		$this->get_plugin()->get_admin_notice_handler()->add_admin_notice( $message, $id, array(
			'notice_class' => $notice_class,
		) );
	}


	/** Getter methods ********************************************************/


	/**
	 * Gets any missing PHP extensions.
	 *
	 * @since 5.2.0
	 *
	 * @return array
	 */
	public function get_missing_php_extensions() {

		$missing_extensions = [];

		foreach ( $this->get_php_extensions() as $extension ) {

			if ( ! extension_loaded( $extension ) ) {
				$missing_extensions[] = $extension;
			}
		}

		return $missing_extensions;
	}


	/**
	 * Gets the required PHP extensions.
	 *
	 * @since 5.2.0
	 *
	 * @return array
	 */
	public function get_php_extensions() {

		return $this->php_extensions;
	}


	/**
	 * Gets any missing PHP functions.
	 *
	 * @since 5.2.0
	 *
	 * @return array
	 */
	public function get_missing_php_functions() {

		$missing_functions = [];

		foreach ( $this->get_php_functions() as $function ) {

			if ( ! extension_loaded( $function ) ) {
				$missing_functions[] = $function;
			}
		}

		return $missing_functions;
	}


	/**
	 * Gets the required PHP functions.
	 *
	 * @since 5.2.0
	 *
	 * @return array
	 */
	public function get_php_functions() {

		return $this->php_functions;
	}


	/**
	 * Gets any incompatible PHP settings.
	 *
	 * @since 5.2.0
	 *
	 * @return array
	 */
	public function get_incompatible_php_settings() {

		$incompatible_settings = [];

		if ( function_exists( 'ini_get' ) ) {

			foreach ( $this->get_php_settings() as $setting => $expected ) {

				$actual = ini_get( $setting );

				if ( ! $actual ) {
					continue;
				}

				if ( is_int( $expected ) ) {

					// determine if this is a size string, like "10MB"
					$is_size = ! is_numeric( substr( $actual, -1 ) );

					$actual_num = $is_size ? wc_let_to_num( $actual ) : $actual;

					if ( $actual_num < $expected ) {

						$incompatible_settings[ $setting ] = [
							'expected' => $is_size ? size_format( $expected ) : $expected,
							'actual'   => $is_size ? size_format( $actual_num ) : $actual,
							'type'     => 'min',
						];
					}

				} elseif ( $actual !== $expected ) {

					$incompatible_settings[ $setting ] = [
						'expected' => $expected,
						'actual'   => $actual,
					];
				}
			}
		}

		return $incompatible_settings;
	}


	/**
	 * Gets the required PHP settings.
	 *
	 * @since 5.2.0
	 *
	 * @return array
	 */
	public function get_php_settings() {

		return $this->php_settings;
	}


	/**
	 * Gets the plugin instance.
	 *
	 * @since 5.2.0
	 *
	 * @return SV_WC_Plugin
	 */
	protected function get_plugin() {

		return $this->plugin;
	}


}


endif;
