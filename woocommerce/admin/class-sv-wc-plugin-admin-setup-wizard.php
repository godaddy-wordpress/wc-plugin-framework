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
 * @copyright Copyright (c) 2013-2018, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_1_4\Admin;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_1_4 as Framework;

class Setup_Wizard {


    /** the "finish" step ID */
    const ACTION_FINISH = 'finish';


    /** @var string the current step ID */
	private $current_step = '';

	/** @var array registered steps to be displayed */
	private $steps = array();

	/** @var string setup handler ID  */
	private $id;

	/** @var Framework\SV_WC_Plugin plugin instance */
	private $plugin;


	/**
	 * Constructs the class.
	 *
	 * @param Framework\SV_WC_Plugin $plugin plugin instance
	 */
	public function __construct( Framework\SV_WC_Plugin $plugin ) {

		$this->id     = $plugin->get_id();
		$this->plugin = $plugin;

		if ( $this->get_slug() === Framework\SV_WC_Helper::get_request( 'page' ) ) {

			$this->register_steps();
			$this->init();
		}
	}


	/**
	 * Registers the core steps.
	 *
	 * @since 5.3.0-dev
	 */
	protected function register_steps() {

		// stub
	}


	/**
	 * Initializes setup.
     *
     * @since 5.3.0-dev
	 */
	protected function init() {

		if ( ! empty( $this->steps ) ) {

			// get a step ID from $_GET
			$current_step = sanitize_key( Framework\SV_WC_Helper::get_request( 'step' ) );

			// if the step is valid and we aren't finished, set the internal pointer
			if ( $this->has_step( $current_step ) && ! $this->is_finished() ) {
				$this->current_step = $current_step;
			}

			// add the page to WP core
			add_action( 'admin_menu', array( $this, 'add_page' ) );

			// renders the entire setup page markup
			add_action( 'admin_init', array( $this, 'render_page' ) );
		}
    }


	/**
	 * Adds the page to WP core.
     *
     * While this doesn't output any markup/menu items, it is essential to officially register the page to avoid
     * permissions issues.
     *
     * @since 5.3.0-dev
	 */
    public function add_page() {

	    add_dashboard_page( '', '', 'manage_options', $this->get_slug(), '' );
    }

	/**
	 * Renders the entire setup page markup.
     *
     * @since 5.3.0-dev
	 */
	public function render_page() {

		$this->enqueue_scripts();

		ob_start();

		?>

		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
			<head>
				<meta name="viewport" content="width=device-width" />
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<title>
                    <?php echo esc_html( sprintf(
                        /** translators: Placeholders: %s - plugin name */
				        __( '%s &rsaquo; Setup', 'woocommerce-plugin-framework' ),
                        $this->get_plugin()->get_plugin_name()
                    ) ); ?>
                </title>
				<?php wp_print_scripts( 'wc-setup' ); ?>
				<?php do_action( 'admin_print_styles' ); ?>
				<?php do_action( 'admin_head' ); ?>
			</head>
			<body class="wc-setup wp-core-ui">
				<?php $this->render_header(); ?>
				<?php $this->render_steps(); ?>
				<?php $this->render_content(); ?>
				<?php $this->render_footer(); ?>
			</body>
		</html>

		<?php
		exit;
	}


	/**
	 * Enqueues the scripts and styles.
     *
     * @since 5.3.0-dev
	 */
	protected function enqueue_scripts() {

		wp_register_script( 'jquery-blockui', WC()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI.min.js', array( 'jquery' ), '2.70', true );
		wp_register_script( 'select2', WC()->plugin_url() . '/assets/js/select2/select2.full.min.js', array( 'jquery' ), '4.0.3' );
		wp_register_script( 'wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select.min.js', array( 'jquery', 'select2' ), $this->get_plugin()->get_version() );

		wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), $this->get_plugin()->get_version() );
		wp_enqueue_style( 'wc-setup', WC()->plugin_url() . '/assets/css/wc-setup.css', array( 'dashicons', 'install' ), $this->get_plugin()->get_version() );

		wp_register_script( 'wc-setup', WC()->plugin_url() . '/assets/js/admin/wc-setup.min.js', array( 'jquery', 'wc-enhanced-select', 'jquery-blockui' ), $this->get_plugin()->get_version() );
	}


	/** Header Methods ************************************************************************************************/


	/**
	 * Renders the header markup.
     *
     * @since 5.3.0-dev
	 */
	protected function render_header() {

		$title     = $this->get_plugin()->get_plugin_name();
		$link_url  = $this->get_plugin()->get_sales_page_url(); // TODO: sales or docs page?
		$image_url = $this->get_header_image_url();

		$header_content = $image_url ? '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $title ) . '" />' : $title;
		?>

		<h1 id="wc-logo">

			<?php if ( $link_url ) : ?>
				<a href="<?php echo esc_url( $link_url ); ?>" target="_blank"><?php echo $header_content; ?></a>
			<?php else : ?>
				<?php echo esc_html( $header_content ); ?>
			<?php endif; ?>

		</h1>

		<?php
	}


	/**
     * Gets the header image URL.
     *
     * Plugins can override this to point to their own branding image URL.
     *
     * @since 5.3.0-dev
     *
	 * @return string
	 */
	protected function get_header_image_url() {

		return '';
	}


	/**
	 * Renders the step list.
     *
     * This displays a list of steps, marking them as complete or upcoming as sort of a progress bar.
     *
     * @since 5.3.0-dev
	 */
	protected function render_steps() {

		?>

		<ol class="wc-setup-steps">

			<?php foreach ( $this->steps as $id => $step ) : ?>

				<?php if ( $id === $this->current_step ) {
					$class = 'active';
				} elseif ( $this->is_step_complete( $id ) ) {
					$class = 'done';
				} else {
				    $class = '';
                } ?>

				<li class="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $step['name'] ); ?></li>

			<?php endforeach; ?>

		</ol>

		<?php
	}


	/** Content Methods ***********************************************************************************************/


	/**
	 * Renders the setup content.
     *
     * This will display the welcome screen, finished screen, or a specific step's markup.
     *
     * @since 5.3.0-dev
	 */
	protected function render_content() {

		?>

		<div class="wc-setup-content">

			<?php if ( $this->is_finished() ) : ?>

				<?php $this->render_finished(); ?>

            <?php elseif ( ! $this->is_started() ) : ?>

                <?php $this->render_welcome(); ?>

            <?php else : ?>

                <form method="post">

					<?php $this->render_step( $this->current_step ); ?>

                </form>

			<?php endif; ?>

		</div>

		<?php
	}


	/**
	 * Renders the welcome screen markup.
     *
     * This is what gets displayed before beginning the setup steps.
     *
     * @since 5.3.0-dev
	 */
	protected function render_welcome() {

		?>

        <h1><?php printf( esc_html__( 'Welcome to %s', 'woocommerce-plugin-framework' ), $this->get_plugin()->get_plugin_name() ); ?></h1>

        <p class="wc-setup-actions step">
            <a href="<?php echo esc_url( $this->get_next_step_url() ); ?>" class="button-primary button button-large button-next"><?php esc_html_e( 'Let\'s go!', 'woocommerce-plugin-framework' ); ?></a>
        </p>

		<?php
	}


	/**
	 * Renders the finished screen markup.
     *
     * This is what gets displayed after all of the steps have been completed or skipped.
     *
     * @since 5.3.0-dev
	 */
	protected function render_finished() {

		// TODO: determine what we want here, and what needs to be defined by the plugin

		?>

        <h1><?php printf( esc_html__( '%s is ready!', 'woocommerce-plugin-framework' ), esc_html( $this->get_plugin()->get_plugin_name() ) ); ?></h1>

		<?php
	}


	/**
     * Renders a given step's markup.
     *
     * @since 5.3.0-dev
     *
	 * @param string $step_id step ID to render
	 */
	protected function render_step( $step_id ) {

	    echo '<h1>' . esc_html( $this->steps[ $step_id ]['name'] ) . '</h1>';

		call_user_func( $this->steps[ $step_id ]['view'], $this );

		?>

        <p class="wc-setup-actions step">
            <button type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'woocommerce-plugin-framework' ); ?>" name="save_step"><?php esc_html_e( 'Continue', 'woocommerce-plugin-framework' ); ?></button>
        </p>

        <?php
	}


	/**
	 * Renders the setup footer.
     *
     * @since 5.3.0-dev
	 */
	protected function render_footer() {

		if ( $this->is_finished() ) : ?>
            <a class="wc-setup-footer-links" href="<?php echo esc_url( admin_url() ); ?>"><?php esc_html_e( 'Return to the WordPress Dashboard', 'woocommerce-plugin-framework' ); ?></a>
        <?php elseif ( ! $this->is_started() ) : ?>
            <a class="wc-setup-footer-links" href="<?php echo esc_url( admin_url() ); ?>"><?php esc_html_e( 'Not right now', 'woocommerce-plugin-framework' ); ?></a>
        <?php else : ?>
            <a class="wc-setup-footer-links" href="<?php echo esc_url( $this->get_next_step_url() ); ?>"><?php esc_html_e( 'Skip this step', 'woocommerce-plugin-framework' ); ?></a>
		<?php endif;
	}


	/** Defined Step Methods ******************************************************************************************/


	protected function step_test() {

		echo 'Test';
	}


	protected function step_test_2() {

		echo 'Test2';
	}


	/** Helper Methods ************************************************************************************************/


	/**
     * Registers a step.
     *
     * @since 5.3.0-dev
     *
	 * @param string $id unique step ID
	 * @param string $name step name for display
	 * @param string|array $view_callback callback to render the step's content HTML
	 * @param string|array|null $save_callback callback to save the step's form values
     * @return bool whether the step was successfully added
	 */
	public function register_step( $id, $name, $view_callback, $save_callback = null ) {

	    try {

		    // invalid ID
		    if ( ! is_string( $id ) || empty( $id ) || $this->has_step( $id ) ) {
			    throw new Framework\SV_WC_Plugin_Exception( 'Invalid step ID' );
		    }

		    // invalid name
		    if ( ! is_string( $name ) || empty( $name ) ) {
			    throw new Framework\SV_WC_Plugin_Exception( 'Invalid step name' );
		    }

		    // invalid view callback
		    if ( ! is_callable( $view_callback ) ) {
			    throw new Framework\SV_WC_Plugin_Exception( 'Invalid view callback' );
		    }

		    // invalid save callback
		    if ( null !== $save_callback && ! is_callable( $save_callback ) ) {
			    throw new Framework\SV_WC_Plugin_Exception( 'Invalid save callback' );
		    }

		    $this->steps[ $id ] = array(
			    'name' => $name,
			    'view' => $view_callback,
			    'save' => $save_callback,
		    );

		    return true;

        } catch ( Framework\SV_WC_Plugin_Exception $exception ) {

	        return false;
        }
	}


	/** Conditional Methods *******************************************************************************************/


	/**
     * Determines if setup has started.
     *
     * @since 5.3.0-dev
     *
	 * @return bool
	 */
	public function is_started() {

	    return (bool) $this->current_step;
    }


	/**
     * Determines if setup has completed all of the steps.
     *
     * @since 5.3.0-dev
     *
	 * @return bool
	 */
	public function is_finished() {

	    return self::ACTION_FINISH === Framework\SV_WC_Helper::get_request( 'action' ) && ! $this->current_step;
    }


	/**
	 * Determines if the given step has been completed.
	 *
	 * @since 5.3.0-dev
	 *
	 * @param string $step_id step ID to check
	 * @return bool
	 */
    public function is_step_complete( $step_id ) {

	    return array_search( $this->current_step, array_keys( $this->steps ), true ) > array_search( $step_id, array_keys( $this->steps ), true ) || $this->is_finished();
    }


	/**
     * Determines if this setup handler has a given step.
     *
     * @since 5.3.0-dev
     *
	 * @param string $step_id step ID to check
	 * @return bool
	 */
    public function has_step( $step_id ) {

	    return ! empty( $this->steps[ $step_id ] );
    }


    /** Getter Methods ************************************************************************************************/


	/**
     * Gets the URL for the next step based on a current step.
     *
     * @since 5.3.0-dev
     *
	 * @param string $step_id step ID to base "next" off of - defaults to this class's internal pointer
	 * @return string
	 */
	public function get_next_step_url( $step_id = '' ) {

		if ( ! $step_id ) {
			$step_id = $this->current_step;
		}

		$steps = array_keys( $this->steps );

		// if on the last step, next is the final finish step
		if ( end( $steps ) === $step_id ) {

			$url = $this->get_finish_url();

		} else {

			$step_index = array_search( $step_id, $steps, true );

			// if the current step is found, use the next in the array. otherwise, the first
			$step = false !== $step_index ? $steps[ $step_index + 1 ] : reset( $steps );

			$url = add_query_arg( 'step', $step );
		}

        return $url;
	}


	/**
     * Gets the "finish" action URL.
     *
     * @since 5.3.0-dev
     *
	 * @return string
	 */
	protected function get_finish_url() {

	    return add_query_arg( 'action', self::ACTION_FINISH, remove_query_arg( 'step' ) );
    }


	/**
     * Gets the setup setup handler's slug.
     *
     * @since 5.3.0-dev
     *
	 * @return string
	 */
	protected function get_slug() {

		return 'wc-' . $this->get_plugin()->get_id_dasherized() . '-setup';
	}


	/**
     * Gets the plugin instance.
     *
     * @since 5.3.0-dev
     *
	 * @return Framework\SV_WC_Plugin
	 */
	protected function get_plugin() {

		return $this->plugin;
	}


}
