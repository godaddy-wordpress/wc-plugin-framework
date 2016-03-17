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

<?php $count = 0; ?>

<?php foreach ( $tokens as $token_id => $token ) : ?>

	<?php $token_input_name = $input_name . '[' . $count . ']'; ?>

	<tr class="token">

		<?php foreach ( $fields as $field_id => $field ) : ?>

			<td class="token-<?php echo esc_attr( $field_id ); ?>">

				<?php if ( isset( $field['is_editable'] ) && ! $field['is_editable'] ) : ?>

					<span class="token-<?php echo esc_attr( $field_id ); ?> token-attribute"><?php echo esc_attr( ( isset( $token[ $field_id ] ) ) ? $token[ $field_id ] : '' ); ?></span>
					<input name="<?php echo esc_attr( $token_input_name ); ?>[<?php echo esc_attr( $field_id ); ?>]" value="<?php echo ( isset( $token[ $field_id ] ) ) ? esc_attr( $token[ $field_id ] ) : ''; ?>" type="hidden" />

				<?php elseif ( isset( $field['type'] ) && 'select' === $field['type'] ) : ?>

					<select name="<?php echo esc_attr( $token_input_name ); ?>[<?php echo esc_attr( $field_id ); ?>]">

						<option value=""><?php esc_html_e( '-- Select an option --', 'woocommerce-plugin-framework' ); ?></option>

						<?php $selected = ( isset( $token[ $field_id ] ) ) ? $token[ $field_id ] : ''; ?>

						<?php foreach ( $field['options'] as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $selected ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>

					</select>

				<?php else : ?>

					<input name="<?php echo esc_attr( $token_input_name ); ?>[<?php echo esc_attr( $field_id ); ?>]" value="<?php echo ( isset( $token[ $field_id ] ) ) ? esc_attr( $token[ $field_id ] ) : ''; ?>" type="text" />

				<?php endif; ?>

			</td>

		<?php endforeach; ?>

		<?php $token_type = isset( $token['type'] ) ? $token['type'] : ''; ?>

		<input name="<?php echo esc_attr( $token_input_name ); ?>[type]" value="<?php echo esc_attr( isset( $token['type'] ) ? $token['type'] : '' ); ?>" type="hidden" />

		<?php if ( isset( $token['default'] ) ) : ?>
			<td class="token-default token-attribute">
				<span class="status-enabled">Yes</span>
				<input name="<?php echo esc_attr( $token_input_name ); ?>[default]" value="1" type="hidden" />
			</td>
		<?php else : ?>
			<td class="token-default token-attribute">-</td>
		<?php endif; ?>

		<?php // Token actions ?>
		<td class="token-actions">

			<?php foreach ( $token_actions as $action => $label ) : ?>
					<button class="sv-wc-payment-gateway-token-action-button button" data-action="<?php echo esc_attr( $action ); ?>" data-token-id="<?php echo esc_attr( $token_id ); ?>" data-user-id="<?php echo esc_attr( $user_id ); ?>">
						<?php echo esc_attr( $label ); ?>
					</button>
			<?php endforeach; ?>

		</td>

	</tr>

	<?php $count++; ?>

<?php endforeach; ?>
