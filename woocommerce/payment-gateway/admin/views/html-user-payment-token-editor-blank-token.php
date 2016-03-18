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

<tr class="new-token token">

	<?php foreach ( $fields as $field_id => $field ) : ?>

		<td class="token-<?php echo esc_attr( $field_id ); ?>">

			<?php if ( isset( $field['is_editable'] ) && ! $field['is_editable'] ) : ?>

			<?php elseif ( isset( $field['type'] ) && 'select' === $field['type'] ) : ?>

				<select name="<?php echo esc_attr( $input_name ); ?>[<?php echo absint( $index ); ?>][<?php echo esc_attr( $field_id ); ?>]">

					<option value=""><?php esc_html_e( '-- Select an option --', 'woocommerce-plugin-framework' ); ?></option>

					<?php foreach ( $field['options'] as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>

				</select>

			<?php else : ?>

				<?php $attributes = array();

				if ( isset( $field['attributes'] ) ) {

					foreach ( $field['attributes'] as $name => $value ) {
						$attributes[] = esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
					}
				}?>

				<input
					name="<?php echo esc_attr( $input_name ); ?>[<?php echo absint( $index ); ?>][<?php echo esc_attr( $field_id ); ?>]"
					value=""
					type="text"
					<?php echo implode( ' ', $attributes ); ?>
					<?php echo isset( $field['required'] ) ? 'required' : ''; ?>
				/>

			<?php endif; ?>

		</td>

	<?php endforeach; ?>

	<input name="<?php echo esc_attr( $input_name ); ?>[<?php echo absint( $index ); ?>][type]" value="<?php echo esc_attr( $type ); ?>" type="hidden" />

	<td class="token-default token-attribute">-</td>

	<td class="token-actions">
		<button class="sv-wc-payment-gateway-token-action-button button" data-action="remove">
			<?php esc_html_e( 'Remove', 'woocommerce-plugin-framework' ); ?>
		</button>
	</td>

</tr>
