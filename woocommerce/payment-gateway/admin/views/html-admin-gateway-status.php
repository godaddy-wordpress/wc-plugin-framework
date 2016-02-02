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

<table class="wc_status_table widefat" cellspacing="0">

	<thead>
		<tr>
			<th colspan="3" data-export-label="">
				<?php echo esc_html( $gateway->get_method_title() ); ?>
				<?php echo wc_help_tip( __( 'Describe this', 'woocommerce-plugin-framework' ) ); // TODO ?>
			</th>
		</tr>
	</thead>

	<tbody>

		<tr>
			<td data-export-label="Environment"><?php _e( 'Environment', 'woocommerce-plugin-framework' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'The transaction environment for this gateway.', 'woocommerce-plugin-framework' ) ); ?></td>
			<td><?php echo esc_html( $environment ); ?></td>
		</tr>

		<?php if ( $gateway->supports_tokenization() ) : ?>

			<tr>
				<td data-export-label="Tokenization Enabled"><?php _e( 'Tokenization Enabled', 'woocommerce-plugin-framework' ); ?>:</td>
				<td class="help"><?php echo wc_help_tip( __( 'Displays whether or not tokenization is enabled for this gateway.', 'woocommerce-plugin-framework' ) ); ?></td>
				<td>
					<?php if ( $gateway->tokenization_enabled() ) : ?>
						<mark class="yes">&#10004;</mark>
					<?php else : ?>
						<mark class="no">&ndash;</mark>
					<?php endif; ?>
				</td>
			</tr>

		<?php endif; ?>

	</tbody>

</table>
