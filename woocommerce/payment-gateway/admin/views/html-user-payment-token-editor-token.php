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
 * @copyright Copyright (c) 2013-2024, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

/**
 * @type string $index token index while adding tokens in AJAX requests
 * @type string $input_name HTML name for the token fields
 * @type array $fields token editor fields
 * @type array $token default payment token
 * @type string $type payment type
 * @type array $actions payment token actions
 */
?>

<?php $token_input_name = $input_name . '[' . $index . ']'; ?>

<tr class="token <?php echo ! $token['id'] ? 'new-token' : ''; ?>">

	<?php foreach ( $fields as $field_id => $field ) : ?>

		<?php $is_select = isset( $field['type'], $field['options'] ) && 'select' === $field['type'] && ! empty( $field['options'] ); ?>

		<td class="token-<?php echo esc_attr( $field_id ); ?>">

			<?php if ( ! $field['editable'] ) : ?>

				<?php $display_value = $is_select && ! empty( $field['options'][ $token[ $field_id ] ] ) ? $field['options'][ $token[ $field_id ] ] : $token[ $field_id ]; ?>

				<span class="token-<?php echo esc_attr( $field_id ); ?> token-attribute"><?php echo esc_attr( $display_value ); ?></span>

				<input name="<?php echo esc_attr( $token_input_name ); ?>[<?php echo esc_attr( $field_id ); ?>]" value="<?php echo esc_attr( $token[ $field_id ] ); ?>" type="hidden" />

			<?php elseif ( $is_select ) : ?>

				<select name="<?php echo esc_attr( $token_input_name ); ?>[<?php echo esc_attr( $field_id ); ?>]">

					<option value=""><?php esc_html_e( '-- Select an option --', 'woocommerce-plugin-framework' ); ?></option>

					<?php foreach ( $field['options'] as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $token[ $field_id ] ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>

				</select>

			<?php else : ?>

				<?php // Build the input attributes
				$attributes = array();

				foreach ( $field['attributes'] as $name => $value ) {
					$attributes[] = esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
				} ?>

				<input
					name="<?php echo esc_attr( $token_input_name ); ?>[<?php echo esc_attr( $field_id ); ?>]"
					value="<?php echo esc_attr( $token[ $field_id ] ); ?>"
					type="text"
					<?php echo implode( ' ', $attributes ); ?>
					<?php echo $field['required'] ? 'required' : ''; ?>
				/>

			<?php endif; ?>

		</td>

	<?php endforeach; ?>

	<input name="<?php echo esc_attr( $token_input_name ); ?>[original_id]" value="<?php echo esc_attr( $token['id'] ); ?>" type="hidden" />
	<input name="<?php echo esc_attr( $token_input_name ); ?>[type]" value="<?php echo esc_attr( $type ); ?>" type="hidden" />

	<td class="token-default token-attribute">
		<input name="<?php echo esc_attr( $input_name ); ?>_default" value="<?php echo esc_attr( $token['id'] ); ?>" type="radio" <?php checked( true, $token['default'] ); ?>/>
	</td>

	<?php // Token actions ?>
	<td class="token-actions">

		<?php foreach ( $actions as $action => $label ) : ?>
				<button class="sv-wc-payment-gateway-token-action-button button" data-action="<?php echo esc_attr( $action ); ?>" data-token-id="<?php echo esc_attr( $token['id'] ); ?>" data-user-id="<?php echo esc_attr( $user_id ); ?>">
					<?php echo esc_attr( $label ); ?>
				</button>
		<?php endforeach; ?>

	</td>

</tr>
