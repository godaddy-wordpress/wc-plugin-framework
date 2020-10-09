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
 * @package   SkyVerge/WooCommerce/Plugin/Gateway/Admin/Views
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */
?>

<div class="wc-order-data-row wc-order-data-row-toggle sv-wc-payment-gateway-partial-capture wc-<?php echo esc_attr( $gateway->get_id_dasherized() ); ?>-partial-capture" style="display:none;">
	<table class="wc-order-totals">

		<tr>
			<td class="label"><?php esc_html_e( 'Authorization total', 'woocommerce-plugin-framework' ); ?>:</td>
			<td class="total"><?php echo wc_price( $authorization_total, array( 'currency' => $order->get_currency() ) ); ?></td>
		</tr>
		<tr>
			<td class="label"><?php esc_html_e( 'Amount already captured', 'woocommerce-plugin-framework' ); ?>:</td>
			<td class="total"><?php echo wc_price( $total_captured, array( 'currency' => $order->get_currency() ) ); ?></td>
		</tr>

		<?php if ( $remaining_total > 0 ) : ?>
			<tr>
				<td class="label"><?php esc_html_e( 'Remaining order total', 'woocommerce-plugin-framework' ); ?>:</td>
				<td class="total"><?php echo wc_price( $remaining_total, array( 'currency' => $order->get_currency() ) ); ?></td>
			</tr>
		<?php endif; ?>

		<tr>
			<td class="label"><label for="capture_amount"><?php esc_html_e( 'Capture amount', 'woocommerce-plugin-framework' ); ?>:</label></td>
			<td class="total">
				<input type="text" class="text" id="capture_amount" name="capture_amount" class="wc_input_price" />
				<div class="clear"></div>
			</td>
		</tr>
		<tr>
			<td class="label"><label for="capture_comment"><?php esc_html_e( 'Comment (optional):', 'woocommerce-plugin-framework' ); ?></label></td>
			<td class="total">
				<input type="text" class="text" id="capture_comment" name="capture_comment" />
				<div class="clear"></div>
			</td>
		</tr>
	</table>
	<div class="clear"></div>
	<div class="capture-actions">

		<?php $amount = '<span class="capture-amount">' . wc_price( 0, array( 'currency' => $order->get_currency() ) ) . '</span>'; ?>

		<button type="button" class="button button-primary capture-action" disabled="disabled"><?php printf( esc_html__( 'Capture %s', 'woocommerce-plugin-framework' ), $amount ); ?></button>
		<button type="button" class="button cancel-action"><?php _e( 'Cancel', 'woocommerce-plugin-framework' ); ?></button>

		<div class="clear"></div>
	</div>
</div>
<script type="text/javascript">
	if ( window.sv_wc_payment_gateway_admin_order_add_capture_events ) {
		window.sv_wc_payment_gateway_admin_order_add_capture_events();
	}
</script>
