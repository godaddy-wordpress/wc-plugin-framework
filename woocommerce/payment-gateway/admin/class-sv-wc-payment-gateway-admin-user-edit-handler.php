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
 * @copyright Copyright (c) 2013-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'SV_WC_Payment_Gateway_Admin_User_Edit_Handler' ) ) :

/**
 * SkyVerge Admin User Edit Handler Class
 *
 * The purpose of this class is to add support for editing the following fields
 * from the Admin User Edit screen:
 *
 * * Customer ID (if supported by gateway)
 * * Payment tokens (if supported by gateway, and gateway does not support a
 *   payment token retrieval API query; meaning they are locally stored)
 *
 * TODO: handle echeck tokens better
 *
 * @since 3.0.0
 */
class SV_WC_Payment_Gateway_Admin_User_Edit_Handler {


	/** @var SV_WC_Plugin the plugin */
	private $plugin;

	/** @var boolean used to limit the Admin User Edit handler javascript to one instance no matter how many active framework gateways */
	private static $user_profile_tokenization_js_rendered = false;


	/**
	 * Initialize and setup the Admin User Edit Handler
	 *
	 * @param \SV_WC_Payment_Gateway_Plugin $plugin
	 * @since 3.0.0
	 */
	public function __construct( $plugin ) {

		$this->plugin = $plugin;

		// Admin
		if ( is_admin() && ! is_ajax() ) {

			// show the editable customer profile fields
			add_action( 'show_user_profile', array( $this, 'add_user_profile_fields' ) );
			add_action( 'edit_user_profile', array( $this, 'add_user_profile_fields' ) );

			// save the editable customer profile fields
			add_action( 'personal_options_update',  array( $this, 'save_user_profile_fields' ) );
			add_action( 'edit_user_profile_update', array( $this, 'save_user_profile_fields' ) );
		}
	}


	/**
	 * Renders any available customer profile fields on the Admin Edit User
	 * screen
	 *
	 * @since 3.0.0
	 * @see SV_WC_Payment_Gateway_Admin_User_Edit_Handler::save_user_profile_fields()
	 * @param WP_User $user user object for the current edit page
	 */
	public function add_user_profile_fields( $user ) {

		// bail if the current user is not allowed to manage woocommerce
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$customer_id_user_meta_names = array();

		// one section per gateway
		foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

			if ( ! $gateway->is_enabled() ) {
				continue;
			}

			ob_start();

			if ( $this->get_plugin()->supports( SV_WC_Payment_Gateway_Plugin::FEATURE_CUSTOMER_ID ) ) {
				$customer_id_user_meta_name = $gateway->get_customer_id_user_meta_name();

				// jump through all these hoops because the gateway expects a single customer id per plugin, even when it includes multiple gateways
				if ( $customer_id_user_meta_name && ! in_array( $customer_id_user_meta_name, $customer_id_user_meta_names ) ) {
					$this->maybe_add_user_profile_customer_id_fields( $gateway, $user );
					$customer_id_user_meta_names[] = $customer_id_user_meta_name;
				}
			}

			if ( $gateway->supports_tokenization() ) {
				$this->maybe_add_user_profile_tokenization_fields( $gateway, $user );
			}

			// allow concrete gateways to add their own fields
			$this->add_custom_user_profile_fields( $gateway, $user );

			$fields = ob_get_clean();

			if ( $fields ) {
				/* translators: Placeholders: %s - payment gateway title */
				echo '<h3>' . sprintf( esc_html__( '%s Customer Details', 'woocommerce-plugin-framework' ), $gateway->get_method_title() ) . '</h3>';
				echo $fields;
			}
		}
	}


	/**
	 * Saves any editable customer profile fields from the Admin Edit user
	 * screen
	 *
	 * @since 3.0.0
	 * @see SV_WC_Payment_Gateway_Admin_User_Edit_Handler::add_user_profile_fields()
	 * @param int $user_id identifies the user to save the settings for
	 */
	public function save_user_profile_fields( $user_id ) {

		// bail if the current user is not allowed to manage woocommerce
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$customer_id_user_meta_names = array();

		// one section per gateway
		foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

			if ( $this->get_plugin()->supports( SV_WC_Payment_Gateway_Plugin::FEATURE_CUSTOMER_ID ) ) {
				$customer_id_user_meta_name = $gateway->get_customer_id_user_meta_name();

				// jump through all these hoops because the gateway expects a single customer id per plugin, even when it includes multiple gateways
				if ( $customer_id_user_meta_name && ! in_array( $customer_id_user_meta_name, $customer_id_user_meta_names ) ) {
					$this->save_user_profile_customer_id_fields( $gateway, $user_id );
					$customer_id_user_meta_names[] = $customer_id_user_meta_name;
				}
			}

			if ( $gateway->supports_tokenization() ) {
				$this->save_user_profile_tokenization_fields( $gateway, $user_id );
			}

			// allow concrete gateways to save their own fields
			$this->save_custom_user_profile_fields( $gateway, $user_id );

		}
	}


	/**
	 * Stub method that can be overridden by concrete gateway implementations
	 * to add their specific user profile fields.  These fields will be displayed
	 * on the Admin User edit page, and should be echoed.
	 *
	 * @since 3.0.0
	 * @see SV_WC_Payment_Gateway_Admin_User_Edit_Handler::add_user_profile_fields()
	 * @see SV_WC_Payment_Gateway_Admin_User_Edit_Handler::save_custom_user_profile_fields()
	 * @param SV_WC_Payment_Gateway $gateway the gateway instance
	 * @param WP_User $user user object for the current edit page
	 */
	protected function add_custom_user_profile_fields( $gateway, $user ) {
		// stub
	}


	/**
	 * Stub method that can be overridden by concrete gateway implementations
	 * to save their specific user profile fields.
	 *
	 * @since 3.0.0
	 * @see SV_WC_Payment_Gateway_Admin_User_Edit_Handler::add_custom_user_profile_fields()
	 * @see SV_WC_Payment_Gateway_Admin_User_Edit_Handler::save_user_profile_fields()
	 * @param SV_WC_Payment_Gateway $gateway the gateway instance
	 * @param WP_User $user user object for the current edit page
	 */
	protected function save_custom_user_profile_fields( $gateway, $user_id ) {
		// stub
	}


	/**
	 * Display fields for the Customer ID meta for each and every environment,
	 * on the view/edit user page, if this gateway uses Customer ID's
	 * (ie SV_WC_Payment_Gateway::get_customer_id_user_meta_name() does not
	 * return false).
	 *
	 * If only a single environment is defined, the field will be named
	 * "Customer ID".  If more than one environment is defined the field will
	 * be named like "Customer ID (Production)", etc to distinguish them.
	 *
	 * NOTE: the plugin id, rather than gateway id, is used here, because it's
	 * assumed that in the case of a plugin having multiple gateways (ie credit
	 * card and eCheck) the customer id will be the same between them.
	 *
	 * @since 3.0.0
	 * @see SV_WC_Payment_Gateway::get_customer_id_user_meta_name()
	 * @see SV_WC_Payment_Gateway_Admin_User_Edit_Handler::save_user_profile_customer_id_fields()
	 * @param SV_WC_Payment_Gateway $gateway the gateway instance
	 * @param WP_User $user user object for the current edit page
	 */
	protected function maybe_add_user_profile_customer_id_fields( $gateway, $user ) {

		$environments = $gateway->get_environments();

		?>
		<table class="form-table">
		<?php

		foreach ( $environments as $environment_id => $environment_name ) :

			?>
				<tr>
					<th><label for="<?php printf( '_wc_%s_customer_id_%s', $gateway->get_id(), $environment_id ); ?>"><?php echo count( $environments ) > 1 ? /* translators: %s - environment name (production/test) */sprintf( esc_html__( 'Customer ID (%s)', 'woocommerce-plugin-framework' ), $environment_name ) : esc_html__( 'Customer ID', 'woocommerce-plugin-framework' ); ?></label></th>
					<td>
						<input type="text" name="<?php printf( '_wc_%s_customer_id_%s', $gateway->get_id(), $environment_id ); ?>" value="<?php echo esc_attr( $gateway->get_customer_id( $user->ID, array( 'environment_id' => $environment_id, 'autocreate' => false ) ) ); ?>" class="regular-text" /><br/>
						<span class="description"><?php echo count( $environments ) > 1 ? /* translators: %s - environment name (production/test - https://www.skyverge.com/for-translators-environments/) */ sprintf( esc_html__( 'The gateway customer ID for the user in the %s environment. Only edit this if necessary.', 'woocommerce-plugin-framework' ), $environment_name ) : esc_html__( 'The gateway customer ID for the user. Only edit this if necessary.', 'woocommerce-plugin-framework' ); ?></span>
					</td>
				</tr>
			<?php

		endforeach;

		?>
		</table>
		<?php
	}


	/**
	 * Persist the user gateway Customer ID for each defined environment, if
	 * the gateway uses Customer ID's
	 *
	 * NOTE: the plugin id, rather than gateway id, is used here, because it's
	 * assumed that in the case of a plugin having multiple gateways (ie credit
	 * card and eCheck) the customer id will be the same between them.
	 *
	 * @since 3.0.0
	 * @see SV_WC_Payment_Gateway_Admin_User_Edit_Handler::maybe_add_user_profile_customer_id_fields()
	 * @param SV_WC_Payment_Gateway $gateway the gateway instance
	 * @param int $user_id identifies the user to save the settings for
	 */
	protected function save_user_profile_customer_id_fields( $gateway, $user_id ) {

		$environments = $gateway->get_environments();

		// valid environments only
		foreach ( array_keys( $environments ) as $environment_id ) {

			// update (or blank out) customer id for the given environment
			if ( isset( $_POST[ '_wc_' . $gateway->get_id() . '_customer_id_' . $environment_id ] ) ) {
				$gateway->update_customer_id( $user_id, trim( $_POST[ '_wc_' . $gateway->get_id() . '_customer_id_' . $environment_id ] ), $environment_id );
			}

		}
	}


	/**
	 * Conditionally adds the payment token fields to the Admin User Edit
	 * screen, if tokenization is enabled on the gateway, and the gateway
	 * API does not support payment token retrieval (meaning the tokens
	 * are stored only locally)
	 *
	 * @see SV_WC_Payment_Gateway_Admin_User_Edit_Handler::save_user_profile_tokenization_fields()
	 * @param SV_WC_Payment_Gateway $gateway the gateway instance
	 * @param WP_User $user user object for the current edit page
	 */
	protected function maybe_add_user_profile_tokenization_fields( $gateway, $user ) {

		// ensure that it supports tokenization, but doesn't have a "get tokens" request, meaning that the tokens are stored and accessed locally
		if ( $gateway->tokenization_enabled() && ! $gateway->get_api()->supports_get_tokenized_payment_methods() ) {

			$environments = $gateway->get_environments();

			foreach ( $environments as $environment_id => $environment_name ) :

				// get any payment tokens
				$payment_tokens = $gateway->get_payment_tokens( $user->ID, array( 'environment_id' => $environment_id ) );

				?>

				<table class="form-table">
					<tr>
						<th style="padding-bottom: 0;"><?php echo ( count( $environments ) > 1 ? /* translators: %s - environment name (production/test). Payment Token - as in a specific entity used to make payments, such as a specific credit card, e-check account, bank account, etc. */ sprintf( esc_html__( '%s Payment Tokens', 'woocommerce-plugin-framework' ), $environment_name ) : esc_html__( 'Payment Tokens', 'woocommerce-plugin-framework' ) ); ?></th>
						<td style="padding-bottom: 0;">
							<?php
							if ( empty( $payment_tokens ) ):
								/* translators: Payment Token as in a specific entity used to make payments, such as a specific credit card, e-check account, bank account, etc. */
								echo "<p>" . esc_html__( 'This customer has no saved payment tokens', 'woocommerce-plugin-framework' ) . "</p>";
							else:
								?>
								<ul style="margin:0;">
									<?php
									foreach ( $payment_tokens as $token ) :

										?>
											<li>
												<?php echo esc_html( $token->get_id() ); ?> (<?php /* translators: %1$s - credit card type (mastercard, visa, ...), %2$s - last 4 numbers of the card, %3$s - card expiry date */ printf( '%1$s ending in %2$s expiring %3$s', $token->get_type_full(), $token->get_last_four(), $token->get_exp_month() . '/' . $token->get_exp_year() ); echo ( $token->is_default() ? ' <strong>' . esc_html__( 'Default card', 'woocommerce-plugin-framework' ) . '</strong>' : '' ); ?>)
												<a href="#" class="js-sv-wc-payment-token-delete" data-payment_token="<?php echo esc_attr( $token->get_id() ); ?>"><?php esc_html_e( 'Delete', 'woocommerce-plugin-framework' ); ?></a>
											</li>
										<?php

									endforeach; ?>
								</ul>
								<input type="hidden" class="js-sv-wc-payment-tokens-deleted" name="wc_<?php echo $gateway->get_id(); ?>_payment_tokens_deleted_<?php echo $environment_id; ?>" value="" />
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<?php /* translators: Payment Token as in a specific entity used to make payments, such as a specific credit card, e-check account, bank account, etc.  */ ?>
						<th style="padding-top: 0;"><?php esc_html_e( 'Add a Payment Token', 'woocommerce-plugin-framework' ); ?></th>
						<td style="padding-top: 0;">
							<input type="text" name="wc_<?php echo $gateway->get_id(); ?>_payment_token_<?php echo $environment_id; ?>" placeholder="<?php esc_attr_e( 'Token', 'woocommerce-plugin-framework' ); ?>" style="width:145px;" />
							<?php if ( $gateway->supports( SV_WC_Payment_Gateway::FEATURE_CARD_TYPES ) ) : ?>
								<select name="wc_<?php echo $gateway->get_id(); ?>_payment_token_type_<?php echo $environment_id; ?>">
									<option value=""><?php esc_html_e( 'Card Type', 'woocommerce-plugin-framework' ); ?></option>
									<?php
									foreach ( $gateway->get_card_types() as $card_type ) :
										$card_type = strtolower( $card_type );
										?>
										<option value="<?php echo esc_attr( $card_type ); ?>"><?php echo esc_html( SV_WC_Payment_Gateway_Helper::payment_type_to_name( $card_type ) ); ?></option>
										<?php
									endforeach;
									?>
								</select>
							<?php endif; ?>
							<input type="text" name="wc_<?php echo $gateway->get_id(); ?>_payment_token_last_four_<?php echo $environment_id; ?>" placeholder="<?php printf( esc_attr__( 'Last Four', 'woocommerce-plugin-framework' ), substr( date( 'Y' ) + 1, -2 ) ); ?>" style="width:75px;" />
							<input type="text" name="wc_<?php echo $gateway->get_id(); ?>_payment_token_exp_date_<?php echo $environment_id; ?>" placeholder="<?php printf( esc_attr__( 'Expiry Date (01/%s)', 'woocommerce-plugin-framework' ), date( 'Y' ) + 1 ); ?>" style="width:155px;" />
							<br/>
							<span class="description"><?php echo apply_filters( 'wc_payment_gateway_' . $gateway->get_id() . '_user_profile_add_token_description', '', $gateway, $user ); ?></span>
						</td>
					</tr>
				</table>
				<?php
			endforeach;

			$this->maybe_add_user_profile_tokenization_fields_js();
		}

	}


	/**
	 * Conditionally adds the javascript to handle the Admin User Edit payment
	 * token fields
	 *
	 * @see SV_WC_Payment_Gateway_Plugin::maybe_add_user_profile_tokenization_fields()
	 */
	protected function maybe_add_user_profile_tokenization_fields_js() {

		if ( ! self::$user_profile_tokenization_js_rendered ) : ?>
			<script type="text/javascript">
				jQuery( document ).ready( function( $ ) {
					$( '.js-sv-wc-payment-token-delete' ).click( function() {

						if ( ! confirm( '<?php esc_html_e( 'Are you sure you wish to do this? Change will not be finalized until you click "Update"', 'woocommerce-plugin-framework' ); ?>' ) ) {
							return false;
						}

						var $deleted_tokens = $( this ).closest( 'table' ).find( '.js-sv-wc-payment-tokens-deleted' );
						$deleted_tokens.val( $( this ).data( 'payment_token' ) + ',' + $deleted_tokens.val() );
						$( this ).closest( 'li' ).remove();

						return false;
					} );
				} );
			</script>
			<?php
			self::$user_profile_tokenization_js_rendered = true;
		endif;
	}


	/**
	 * Save the Admin User Edit screen payment token fields, if any
	 *
	 * @see SV_WC_Payment_Gateway_Plugin::maybe_add_user_profile_tokenization_fields()
	 * @param SV_WC_Payment_Gateway $gateway the gateway instance
	 * @param int $user_id identifies the user to save the settings for
	 */
	protected function save_user_profile_tokenization_fields( $gateway, $user_id ) {

		foreach ( array_keys( $gateway->get_environments() ) as $environment_id ) {

			// deleting any payment tokens?
			$payment_tokens_deleted_name = 'wc_' . $gateway->get_id() . '_payment_tokens_deleted_' . $environment_id;
			$delete_payment_tokens = SV_WC_Helper::get_post( $payment_tokens_deleted_name ) ? explode( ',', trim( SV_WC_Helper::get_post( $payment_tokens_deleted_name ), ',' ) ) : array();

			// see whether we're deleting any
			foreach ( $delete_payment_tokens as $token ) {
				$gateway->remove_payment_token( $user_id, $token, $environment_id );
			}

			// adding a new payment token?
			$payment_token_name = 'wc_' . $gateway->get_id() . '_payment_token_' . $environment_id;

			if ( SV_WC_Helper::get_post( $payment_token_name ) ) {

				$exp_date = explode( '/', SV_WC_Helper::get_post( 'wc_' . $gateway->get_id() . '_payment_token_exp_date_' . $environment_id ) );

				// add the new payment token, making it active if this is the first card
				$gateway->add_payment_token(
					$user_id,
					$gateway->build_payment_token(
						SV_WC_Helper::get_post( $payment_token_name ),
						array(
							'type'      => $gateway->is_credit_card_gateway() ? 'credit_card' : 'check',
							'card_type' => SV_WC_Helper::get_post( 'wc_' . $gateway->get_id() . '_payment_token_type_' . $environment_id ),
							'last_four' => SV_WC_Helper::get_post( 'wc_' . $gateway->get_id() . '_payment_token_last_four_' . $environment_id ),
							'exp_month' => count( $exp_date ) > 1 ? sprintf( '%02s', $exp_date[0] ) : null,
							'exp_year'  => count( $exp_date ) > 1 ? $exp_date[1] : null,
						)
					)
				);
			}
		}

	}


	/** Getter methods ******************************************************/


	/**
	 * Get the plugin
	 *
	 * @since 3.0.0
	 * @return SV_WC_Plugin returns the plugin instance
	 */
	protected function get_plugin() {
		return $this->plugin;
	}

}

endif; // Class exists check
