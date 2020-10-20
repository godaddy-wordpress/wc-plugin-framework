<?php
/**
 * WooCommerce Payment Gateway Framework
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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/Admin
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_0;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\SV_WC_Payment_Gateway_Admin_Order' ) ) :


/**
 * Handle the admin order screens.
 *
 * @since 5.0.0
 */
class SV_WC_Payment_Gateway_Admin_Order {


	/** @var SV_WC_Payment_Gateway_Plugin the plugin instance **/
	protected $plugin;


	/**
	 * Constructs the class.
	 *
	 * @since 5.0.0
	 *
	 * @param SV_WC_Payment_Gateway_Plugin The plugin instance
	 */
	public function __construct( SV_WC_Payment_Gateway_Plugin $plugin ) {

		$this->plugin = $plugin;

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// capture feature
		if ( $this->get_plugin()->supports_capture_charge() ) {

			add_action( 'woocommerce_order_item_add_action_buttons', array( $this, 'add_capture_button' ) );

			add_action( 'wp_ajax_wc_' . $this->get_plugin()->get_id() . '_capture_charge', array( $this, 'ajax_process_capture' ) );

			// bulk capture order action
			add_action( 'admin_footer-edit.php', array( $this, 'maybe_add_capture_charge_bulk_order_action' ) );
			add_action( 'load-edit.php',         array( $this, 'process_capture_charge_bulk_order_action' ) );
		}
	}


	/**
	 * Enqueues the scripts and styles.
	 *
	 * @internal
	 *
	 * @since 5.0.0
	 *
	 * @param string $hook_suffix page hook suffix
	 */
	public function enqueue_scripts( $hook_suffix ) {

		// Order screen assets
		if ( 'shop_order' === get_post_type() ) {

			// Edit Order screen assets
			if ( 'post.php' === $hook_suffix ) {

				$order = wc_get_order( SV_WC_Helper::get_requested_value( 'post' ) );

				if ( ! $order ) {
					return;
				}

				// bail if the order payment method doesn't belong to this plugin
				if ( ! $this->get_order_gateway( $order ) ) {
					return;
				}

				$this->enqueue_edit_order_assets( $order );
			}
		}
	}


	/**
	 * Enqueues the assets for the Edit Order screen.
	 *
	 * @since 5.3.0
	 *
	 * @param \WC_Order $order order object
	 */
	protected function enqueue_edit_order_assets( \WC_Order $order ) {

		wp_enqueue_script( 'sv-wc-payment-gateway-admin-order', $this->get_plugin()->get_payment_gateway_framework_assets_url() . '/dist/admin/sv-wc-payment-gateway-admin-order.js', array( 'jquery' ), SV_WC_Plugin::VERSION, true );

		wp_localize_script( 'sv-wc-payment-gateway-admin-order', 'sv_wc_payment_gateway_admin_order', array(
			'ajax_url'       => admin_url( 'admin-ajax.php' ),
			'gateway_id'     => $order->get_payment_method( 'edit' ),
			'order_id'       => $order->get_id(),
			'capture_ays'    => __( 'Are you sure you wish to process this capture? The action cannot be undone.', 'woocommerce-plugin-framework' ),
			'capture_action' => 'wc_' . $this->get_plugin()->get_id() . '_capture_charge',
			'capture_nonce'  => wp_create_nonce( 'wc_' . $this->get_plugin()->get_id() . '_capture_charge' ),
			'capture_error'  => __( 'Something went wrong, and the capture could no be completed. Please try again.', 'woocommerce-plugin-framework' ),
		) );

		wp_enqueue_style( 'sv-wc-payment-gateway-admin-order', $this->get_plugin()->get_payment_gateway_framework_assets_url() . '/css/admin/sv-wc-payment-gateway-admin-order.min.css', SV_WC_Plugin::VERSION );
	}


	/** Capture Charge Feature ******************************************************/


	/**
	 * Adds 'Capture charge' to the Orders screen bulk action select.
	 *
	 * @since 5.0.0
	 */
	public function maybe_add_capture_charge_bulk_order_action() {
		global $post_type, $post_status;

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}

		if ( $post_type === 'shop_order' && $post_status !== 'trash' ) {

			$can_capture_charge = false;

			// ensure at least one gateway supports capturing charge
			foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

				// ensure that it supports captures
				if ( $gateway->supports_credit_card_capture() ) {

					$can_capture_charge = true;
					break;
				}
			}

			if ( $can_capture_charge ) {

				?>
					<script type="text/javascript">
						jQuery( document ).ready( function ( $ ) {
							if ( 0 == $( 'select[name^=action] option[value=wc_capture_charge]' ).size() ) {
								$( 'select[name^=action]' ).append(
									$( '<option>' ).val( '<?php echo esc_js( 'wc_capture_charge' ); ?>' ).text( '<?php _e( 'Capture Charge', 'woocommerce-plugin-framework' ); ?>' )
								);
							}
						});
					</script>
				<?php
			}
		}
	}


	/**
	 * Processes the 'Capture Charge' custom bulk action.
	 *
	 * @since 5.0.0
	 */
	public function process_capture_charge_bulk_order_action() {
		global $typenow;

		if ( 'shop_order' === $typenow ) {

			// get the action
			$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
			$action        = $wp_list_table->current_action();

			// bail if not processing a capture
			if ( 'wc_capture_charge' !== $action ) {
				return;
			}

			if ( ! current_user_can( 'edit_shop_orders' ) ) {
				return;
			}

			// security check
			check_admin_referer( 'bulk-posts' );

			// make sure order IDs are submitted
			if ( isset( $_REQUEST['post'] ) ) {
				$order_ids = array_map( 'absint', $_REQUEST['post'] );
			}

			// return if there are no orders to export
			if ( empty( $order_ids ) ) {
				return;
			}

			// give ourselves an unlimited timeout if possible
			@set_time_limit( 0 );

			foreach ( $order_ids as $order_id ) {

				$order = wc_get_order( $order_id );

				if ( $order && ( $gateway = $this->get_order_gateway( $order ) ) ) {
					$gateway->get_capture_handler()->maybe_perform_capture( $order );
				}
			}
		}
	}


	/**
	 * Adds a "Capture Charge" action to the admin Order Edit screen
	 *
	 * @since 5.0.0
	 *
	 * @param array $actions available order actions
	 * @return array
	 */
	public function add_order_action_charge_action( $actions ) {

		/* translators: verb, as in "Capture credit card charge".
		 Used when an amount has been pre-authorized before, but funds have not yet been captured (taken) from the card.
		 Capturing the charge will take the money from the credit card and put it in the merchant's pockets. */
		$actions[ 'wc_' . $this->get_plugin()->get_id() . '_capture_charge' ] = esc_html__( 'Capture Charge', 'woocommerce-plugin-framework' );

		return $actions;
	}


	/**
	 * Adds the capture charge button to the order UI.
	 *
	 * @internal
	 *
	 * @since 5.0.0
	 *
	 * @param \WC_Order $order order object
	 */
	public function add_capture_button( $order ) {

		// only display the button for core orders
		if ( ! $order instanceof \WC_Order || 'shop_order' !== get_post_type( $order->get_id() ) ) {
			return;
		}

		$gateway = $this->get_order_gateway( $order );

		if ( ! $gateway ) {
			return;
		}

		if ( ! $gateway->get_capture_handler()->is_order_ready_for_capture( $order ) ) {
			return;
		}

		$tooltip = '';
		$classes = array(
			'button',
			'sv-wc-payment-gateway-capture',
			'wc-' . $gateway->get_id_dasherized() . '-capture',
		);

		// indicate if the partial-capture UI can be shown
		if ( $gateway->supports_credit_card_partial_capture() && $gateway->is_partial_capture_enabled() ) {
			$classes[] = 'partial-capture';
		} elseif ( $gateway->get_capture_handler()->order_can_be_captured( $order ) ) {
			$classes[] = 'button-primary';
		}

		// ensure that the authorization is still valid for capture
		if ( ! $gateway->get_capture_handler()->order_can_be_captured( $order ) ) {

			$classes[] = 'tips disabled';

			// add some tooltip wording explaining why this cannot be captured
			if ( $gateway->get_capture_handler()->is_order_fully_captured( $order ) ) {
				$tooltip = __( 'This charge has been fully captured.', 'woocommerce-plugin-framework' );
			} elseif ( $gateway->get_order_meta( $order, 'trans_date' ) && $gateway->get_capture_handler()->has_order_authorization_expired( $order ) ) {
				$tooltip = __( 'This charge can no longer be captured.', 'woocommerce-plugin-framework' );
			} else {
				$tooltip = __( 'This charge cannot be captured.', 'woocommerce-plugin-framework' );
			}
		}

		?>

		<button type="button" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" <?php echo ( $tooltip ) ? 'data-tip="' . esc_html( $tooltip ) . '"' : ''; ?>><?php _e( 'Capture Charge', 'woocommerce-plugin-framework' ); ?></button>

		<?php

		// add the partial capture UI HTML
		if ( $gateway->supports_credit_card_partial_capture() && $gateway->is_partial_capture_enabled() ) {
			$this->output_partial_capture_html( $order, $gateway );
		}
	}


	/**
	 * Outputs the partial capture UI HTML.
	 *
	 * @since 5.0.0
	 *
	 * @param \WC_Order $order order object
	 * @param SV_WC_Payment_Gateway $gateway gateway instance
	 */
	protected function output_partial_capture_html( \WC_Order $order, SV_WC_Payment_Gateway $gateway ) {

		$authorization_total = $gateway->get_capture_handler()->get_order_authorization_amount( $order );
		$total_captured      = $gateway->get_order_meta( $order, 'capture_total' );
		$remaining_total     = SV_WC_Helper::number_format( (float) $order->get_total() - (float) $total_captured );

		include( $this->get_plugin()->get_payment_gateway_framework_path() . '/admin/views/html-order-partial-capture.php' );
	}


	/**
	 * Processes a capture via AJAX.
	 *
	 * @internal
	 *
	 * @since 5.0.0
	 */
	public function ajax_process_capture() {

		check_ajax_referer( 'wc_' . $this->get_plugin()->get_id() . '_capture_charge', 'nonce' );

		$gateway_id = SV_WC_Helper::get_requested_value( 'gateway_id' );

		if ( ! $this->get_plugin()->has_gateway( $gateway_id ) ) {
			die();
		}

		$gateway = $this->get_plugin()->get_gateway( $gateway_id );

		try {

			$order_id = SV_WC_Helper::get_requested_value( 'order_id' );
			$order    = wc_get_order( $order_id );

			if ( ! $order ) {
				throw new SV_WC_Payment_Gateway_Exception( 'Invalid order ID' );
			}

			if ( ! current_user_can( 'edit_shop_order', $order_id ) ) {
				throw new SV_WC_Payment_Gateway_Exception( 'Invalid permissions' );
			}

			if ( $order->get_payment_method( 'edit' ) !== $gateway->get_id() ) {
				throw new SV_WC_Payment_Gateway_Exception( 'Invalid payment method' );
			}

			$amount_captured = (float) $gateway->get_order_meta( $order, 'capture_total' );

			if ( $request_amount = SV_WC_Helper::get_requested_value( 'amount' ) ) {
				$amount = (float) $request_amount;
			} else {
				$amount = $order->get_total();
			}

			$result = $gateway->get_capture_handler()->perform_capture( $order, $amount );

			if ( empty( $result['success'] ) ) {
				throw new SV_WC_Payment_Gateway_Exception( $result['message'] );
			}

			wp_send_json_success( [
				'message' => html_entity_decode( wp_strip_all_tags( $result['message'] ) ), // ensure any HTML tags are removed and the currency symbol entity is decoded
			] );

		} catch ( SV_WC_Payment_Gateway_Exception $e ) {

			wp_send_json_error( [
				'message' => $e->getMessage(),
			] );
		}
	}


	/**
	 * Gets the gateway object from an order.
	 *
	 * @since 5.0.0
	 *
	 * @param \WC_Order $order order object
	 * @return SV_WC_Payment_Gateway
	 */
	protected function get_order_gateway( \WC_Order $order ) {

		$capture_gateway = null;
		$payment_method  = $order->get_payment_method( 'edit' );

		if ( $this->get_plugin()->has_gateway( $payment_method ) ) {

			$gateway = $this->get_plugin()->get_gateway( $payment_method );

			// ensure that it supports captures
			if ( $gateway->supports_credit_card_capture() ) {
				$capture_gateway = $gateway;
			}
		}

		return $capture_gateway;
	}


	/**
	 * Gets the plugin instance.
	 *
	 * @since 5.0.0
	 *
	 * @return SV_WC_Payment_Gateway_Plugin the plugin instance
	 */
	protected function get_plugin() {

		return $this->plugin;
	}


	/** Deprecated Methods ********************************************************************************************/


	/**
	 * Capture a credit card charge for a prior authorization if this payment
	 * method was used for the given order, the charge hasn't already been
	 * captured, and the gateway supports issuing a capture request
	 *
	 * @since 5.0.0
	 * @deprecated 5.3.0
	 *
	 * @param \WC_Order|int $order the order identifier or order object
	 * @param float|null $amount capture amount
	 */
	protected function maybe_capture_charge( $order, $amount = null ) {

		wc_deprecated_function( __METHOD__, '5.3.0' );

		if ( ! is_object( $order ) ) {
			$order = wc_get_order( $order );
		}

		$gateway = $this->get_order_gateway( $order );

		if ( ! $gateway ) {
			return;
		}

		// don't try to capture cancelled/fully refunded transactions
		if ( ! $gateway->get_capture_handler()->is_order_ready_for_capture( $order ) ) {
			return;
		}

		// since a capture results in an update to the post object (by updating
		// the paid date) we need to unhook the meta box save action, otherwise we
		// can get boomeranged and change the status back to on-hold
		remove_action( 'woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 40 );

		// perform the capture
		$gateway->get_capture_handler()->maybe_perform_capture( $order, $amount );
	}


	/**
	 * Captures an order on status change to a "paid" status.
	 *
	 * @internal
	 *
	 * @since 5.0.1
	 * @deprecated 5.3.0
	 *
	 * @param int $order_id order ID
	 * @param string $old_status status being changed
	 * @param string $new_status new order status
	 */
	public function maybe_capture_paid_order( $order_id, $old_status, $new_status ) {

		wc_deprecated_function( __METHOD__, '5.3.0' );
	}


	/**
	 * Determines if an order is ready for capture.
	 *
	 * @since 5.0.0
	 * @deprecated 5.3.0
	 *
	 * @param \WC_Order $order order object
	 * @return bool
	 */
	protected function is_order_ready_for_capture( \WC_Order $order ) {

		wc_deprecated_function( __METHOD__, '5.3.0' );

		$gateway = $this->get_order_gateway( $order );

		return $gateway && $gateway->get_capture_handler()->is_order_ready_for_capture( $order );
	}


}


endif;
