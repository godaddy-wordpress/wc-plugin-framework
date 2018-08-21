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
 * @copyright Copyright (c) 2013-2018, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_2_1;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_2_1\\SV_WC_Payment_Gateway_Admin_Order' ) ) :

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

			// auto-capture on order status change if enabled
			add_action( 'woocommerce_order_status_changed', array( $this, 'maybe_capture_paid_order' ), 10, 3 );
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

		if ( 'shop_order' === get_post_type() && 'post.php' === $hook_suffix ) {

			wp_enqueue_script( 'sv-wc-payment-gateway-admin-order', $this->get_plugin()->get_payment_gateway_framework_assets_url() . '/js/admin/sv-wc-payment-gateway-admin-order.min.js', array( 'jquery' ), SV_WC_Plugin::VERSION, true );

			$order = wc_get_order( SV_WC_Helper::get_request( 'post' ) );

			if ( ! $order ) {
				return;
			}

			wp_localize_script( 'sv-wc-payment-gateway-admin-order', 'sv_wc_payment_gateway_admin_order', array(
				'ajax_url'       => admin_url( 'admin-ajax.php' ),
				'gateway_id'     => SV_WC_Order_Compatibility::get_prop( $order, 'payment_method' ),
				'order_id'       => SV_WC_Order_Compatibility::get_prop( $order, 'id' ),
				'capture_ays'    => __( 'Are you sure you wish to process this capture? The action cannot be undone.', 'woocommerce-plugin-framework' ),
				'capture_action' => 'wc_' . $this->get_plugin()->get_id() . '_capture_charge',
				'capture_nonce'  => wp_create_nonce( 'wc_' . $this->get_plugin()->get_id() . '_capture_charge' ),
				'capture_error'  => __( 'Something went wrong, and the capture could no be completed. Please try again.', 'woocommerce-plugin-framework' ),
			) );

			wp_enqueue_style( 'sv-wc-payment-gateway-admin-order', $this->get_plugin()->get_payment_gateway_framework_assets_url() . '/css/admin/sv-wc-payment-gateway-admin-order.min.css', SV_WC_Plugin::VERSION );
		}
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

				$this->maybe_capture_charge( $order );
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

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		if ( ! $this->is_order_ready_for_capture( $order ) ) {
			return;
		}

		$gateway = $this->get_order_gateway( $order );

		if ( ! $gateway ) {
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
		} elseif ( $gateway->authorization_valid_for_capture( $order ) ) {
			$classes[] = 'button-primary';
		}

		// ensure that the authorization is still valid for capture
		if ( ! $gateway->authorization_valid_for_capture( $order ) ) {

			$classes[] = 'tips disabled';

			// add some tooltip wording explaining why this cannot be captured
			if ( 'yes' === $gateway->get_order_meta( $order, 'charge_captured' ) ) {
				$tooltip = __( 'This charge has been fully captured.', 'woocommerce-plugin-framework' );
			} elseif ( $gateway->get_order_meta( $order, 'trans_date' ) && $gateway->has_authorization_expired( $order ) ) {
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

		$authorization_total = $gateway->get_order_authorization_amount( $order );
		$total_captured      = $gateway->get_order_meta( $order, 'capture_total' );
		$remaining_total       = SV_WC_Helper::number_format( (float) $order->get_total() - (float) $total_captured );

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

		$gateway_id = SV_WC_Helper::get_request( 'gateway_id' );

		if ( ! $this->get_plugin()->has_gateway( $gateway_id ) ) {
			die();
		}

		$gateway = $this->get_plugin()->get_gateway( $gateway_id );

		try {

			$order_id = SV_WC_Helper::get_request( 'order_id' );
			$order    = wc_get_order( $order_id );

			if ( ! $order ) {
				throw new SV_WC_Payment_Gateway_Exception( 'Invalid order ID' );
			}

			if ( ! current_user_can( 'edit_shop_order', $order_id ) ) {
				throw new SV_WC_Payment_Gateway_Exception( 'Invalid permissions' );
			}

			if ( SV_WC_Order_Compatibility::get_prop( $order, 'payment_method' ) !== $gateway->get_id() ) {
				throw new SV_WC_Payment_Gateway_Exception( 'Invalid payment method' );
			}

			$amount_captured = (float) $gateway->get_order_meta( $order, 'capture_total' );

			if ( SV_WC_Helper::get_request( 'amount' ) ) {
				$amount = (float) SV_WC_Helper::get_request( 'amount' );
			} else {
				$amount = $order->get_total();
			}

			$result = $this->maybe_capture_charge( $order, $amount );

			if ( 'success' !== $result['result'] ) {
				throw new SV_WC_Payment_Gateway_Exception( $result['message'] );
			}

			wp_send_json_success( array(
				'message' => html_entity_decode( $result['message'] ),
			) );

		} catch ( SV_WC_Payment_Gateway_Exception $e ) {

			wp_send_json_error( array(
				'message' => $e->getMessage(),
			) );
		}
	}


	/**
	 * Captures an order on status change to a "paid" status.
	 *
	 * @since 5.0.1-dev
	 *
	 * @param int $order_id order ID
	 * @param string $old_status status being changed
	 * @param string $new_status new order status
	 */
	public function maybe_capture_paid_order( $order_id, $old_status, $new_status ) {

		$paid_statuses = SV_WC_Plugin_Compatibility::wc_get_is_paid_statuses();

		// bail if changing to a non-paid status or from a paid status
		if ( ! in_array( $new_status, $paid_statuses, true ) || in_array( $old_status, $paid_statuses ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$gateway = $this->get_order_gateway( $order );

		if ( ! $gateway ) {
			return;
		}

		if ( $gateway->is_paid_capture_enabled() ) {
			$this->maybe_capture_charge( $order );
		}
	}


	/**
	 * Capture a credit card charge for a prior authorization if this payment
	 * method was used for the given order, the charge hasn't already been
	 * captured, and the gateway supports issuing a capture request
	 *
	 * @since 5.0.0
	 *
	 * @param \WC_Order|int $order the order identifier or order object
	 */
	protected function maybe_capture_charge( $order, $amount = null ) {

		if ( ! is_object( $order ) ) {
			$order = wc_get_order( $order );
		}

		// don't try to capture cancelled/fully refunded transactions
		if ( ! $this->is_order_ready_for_capture( $order ) ) {
			return;
		}

		$gateway = $this->get_order_gateway( $order );

		if ( ! $gateway ) {
			return;
		}

		// ensure the authorization is still valid for capture
		if ( ! $gateway->authorization_valid_for_capture( $order ) ) {
			return;
		}

		// if no amount is specified, and the authorization has already been captured for the original amount, bail
		if ( ! $amount && $gateway->get_order_meta( $order, 'capture_total' ) >= $gateway->get_order_authorization_amount( $order ) ) {
			return;
		}

		// remove order status change actions, otherwise we get a whole bunch of capture calls and errors
		remove_action( 'woocommerce_order_action_wc_' . $this->get_plugin()->get_id() . '_capture_charge', array( $this, 'maybe_capture_charge' ) );

		// since a capture results in an update to the post object (by updating
		// the paid date) we need to unhook the meta box save action, otherwise we
		// can get boomeranged and change the status back to on-hold
		remove_action( 'woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 40 );

		// perform the capture
		return $gateway->do_credit_card_capture( $order, $amount );
	}


	/**
	 * Determines if an order is ready for capture.
	 *
	 * @since 5.0.0
	 *
	 * @param \WC_Order $order order object
	 * @return bool
	 */
	protected function is_order_ready_for_capture( \WC_Order $order ) {

		return ! in_array( $order->get_status(), array( 'cancelled', 'refunded' ), true );
	}


	/**
	 * Gets the gateway object from an order.
	 *
	 * @since 5.0.0
	 *
	 * @param \WC_Order $order order object
	 * @return \SV_WC_Payment_Gateway
	 */
	protected function get_order_gateway( \WC_Order $order ) {

		$capture_gateway = null;

		$payment_method = SV_WC_Order_Compatibility::get_prop( $order, 'payment_method' );

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


}

endif;
