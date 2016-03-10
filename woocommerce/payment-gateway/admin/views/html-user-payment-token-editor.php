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
 * @copyright Copyright (c) 2013-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */
?>

<tr>

	<th><?php echo esc_html( $title ); ?></th>

	<td class="forminp">

		<table class="sv_wc_payment_gateway_token_editor widefat">

			<thead>
				<tr>

					<?php // Display a column for each token field
					foreach ( $fields as $column_id => $column_name ) : ?>
						<th class="token-<?php echo esc_attr( $column_id ); ?>"><?php echo esc_html( $column_name ); ?></th>
					<?php endforeach; ?>

					<th class="token-default"><?php esc_html_e( 'Default', 'woocommerce-plugin-framework' ); ?></th>

					<th class="token-actions"></th>

				</tr>
			</thead>

			<?php $count = 0; ?>

			<?php foreach ( $tokens as $token_id => $token ) : ?>

				<?php $token_input_name = $input_name . '[' . $count . ']'; ?>

				<tr class="token">

					<?php foreach ( $fields as $field_id => $field_label ) : ?>

						<td class="token-<?php echo esc_attr( $field_id ); ?> token-attribute">

							<?php if ( 'card_type' === $field_id ) : ?>

								<select name="<?php echo esc_attr( $token_input_name ); ?>[<?php echo esc_attr( $field_id ); ?>]">

									<option value=""><?php esc_html_e( '-- Select an option --', 'woocommerce-plugin-framework' ); ?></option>

									<?php $token_card_type = ( isset( $token['card_type'] ) ) ? $token['card_type'] : ''; ?>

									<?php foreach ( $card_types as $card_type ) : ?>
										<option value="<?php echo esc_attr( strtolower( $card_type ) ); ?>" <?php selected( strtolower( $card_type ), $token_card_type ); ?>><?php echo esc_html( SV_WC_Payment_Gateway_Helper::payment_type_to_name( $card_type ) ); ?></option>
									<?php endforeach; ?>

								</select>

							<?php elseif ( 'account_type' === $field_id ) : ?>

								<select name="<?php echo esc_attr( $token_input_name ); ?>[<?php echo esc_attr( $field_id ); ?>]">
									<option value=""><?php esc_html_e( '-- Select an option --', 'woocommerce-plugin-framework' ); ?></option>
									<option value="checking" <?php selected( 'checking', $token[ $field_id ] ); ?>>
										<?php echo esc_html_x( 'Checking', 'account type', 'woocommerce-plugin-framework' ); ?>
									</option>
									<option value="savings" <?php selected( 'savings', $token[ $field_id ] ); ?>>
										<?php echo esc_html_x( 'Savings', 'account type', 'woocommerce-plugin-framework' ); ?>
									</option>
								</select>

							<?php else : ?>

								<input name="<?php echo esc_attr( $token_input_name ); ?>[<?php echo esc_attr( $field_id ); ?>]" value="<?php echo ( isset( $token[ $field_id ] ) ) ? esc_attr( $token[ $field_id ] ) : ''; ?>" />

							<?php endif; ?>

						</td>

					<?php endforeach; ?>

					<input name="<?php echo esc_attr( $token_input_name ); ?>[type]" value="<?php echo ( isset( $token['type'] ) ) ? esc_attr( $token['type'] ) : ''; ?>"type="hidden" />

					<?php if ( isset( $token['default'] ) ) : ?>
						<td class="token-default token-attribute"><span class="status-enabled">Yes</span></td>
					<?php else : ?>
						<td class="token-default token-attribute">-</td>
					<?php endif; ?>

					<td class="token-actions">
						<button class="sv-wc-payment-gateway-token-action-button button" data-action="remove" data-token-id="<?php echo esc_attr( $token_id ); ?>" data-user-id="<?php echo esc_attr( $user_id ); ?>"><?php esc_html_e( 'Remove', 'woocommerce-plugin-framework' ); ?></button>
					</td>

				</tr>

				<?php $count++; ?>

			<?php endforeach; ?>

		</table>

		<!-- <button class="button"><?php esc_html_e( 'Add New', 'woocommerce-plugin-framework' ); ?></button> -->

	</td>

</tr>
