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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/Payment-Tokens
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_0;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\SV_WC_Payment_Gateway_Payment_Tokens_Handler' ) ) :



/**
 * Handle the payment tokenization related functionality.
 *
 * @since 4.3.0
 */
class SV_WC_Payment_Gateway_Payment_Tokens_Handler {


	/** @var string the gateway environment ID */
	protected $environment_id;

	/** @var array|SV_WC_Payment_Gateway_Payment_Token[] array of cached user id to array of SV_WC_Payment_Gateway_Payment_Token token objects */
	protected $tokens;

	/** @var array cached legacy tokens, by user ID and environment */
	protected $legacy_tokens;

	/** @var SV_WC_Payment_Gateway gateway instance */
	protected $gateway;


	/**
	 * Build the class.
	 *
	 * @since 4.3.0
	 *
	 * @param SV_WC_Payment_Gateway $gateway payment gateway instance
	 */
	public function __construct( SV_WC_Payment_Gateway $gateway ) {

		$this->gateway = $gateway;

		$this->environment_id = $gateway->get_environment();

		$this->add_payment_token_deleted_action();
	}


	/**
	 * Builds the token object.
	 *
	 * A factory method to build and return a payment token object for the gateway.
	 * Child implementations can override this method to return a custom payment token.
	 *
	 * From version 5.8.0, this method can accept a core \WC_Payment_Token type as the second argument to read data from.
	 *
	 * @since 4.3.0
	 *
	 * @param string $token payment token
	 * @param \WC_Payment_Token|array $data {
	 *     Payment token data.
	 *
	 *     @type bool   $default   Optional. Indicates this is the default payment token
	 *     @type string $type      Payment type. Either 'credit_card' or 'check'
	 *     @type string $last_four Last four digits of account number
	 *     @type string $card_type Credit card type (`visa`, `mc`, `amex`, `disc`, `diners`, `jcb`) or `echeck`
	 *     @type string $exp_month Optional. Expiration month (credit card only)
	 *     @type string $exp_year  Optional. Expiration year (credit card only)
	 * }
	 * @return SV_WC_Payment_Gateway_Payment_Token payment token
	 */
	public function build_token( $token, $data ) {

		return new SV_WC_Payment_Gateway_Payment_Token( $token, $data );
	}


	/** Handle single tokens **********************************************************************/


	/**
	 * Tokenizes the current payment method and adds the standard transaction
	 * data to the order post record.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Order $order order object
	 * @param SV_WC_Payment_Gateway_API_Create_Payment_Token_Response|null $response payment token API response, or null if the request should be made
	 * @param string $environment_id optional environment ID, defaults to the current environment
	 * @return \WC_Order order object
	 * @throws SV_WC_Plugin_Exception on transaction failure
	 */
	public function create_token( \WC_Order $order, $response = null, $environment_id = null ) {

		$gateway = $this->get_gateway();

		// default to current environment
		if ( is_null( $environment_id ) ) {
			$environment_id = $this->get_environment_id();
		}

		// perform the API request to tokenize the payment method if needed
		if ( ! $response || $this->get_gateway()->tokenize_after_sale() ) {
			$response = $gateway->get_api()->tokenize_payment_method( $order );
		}

		if ( $response->transaction_approved() ) {

			// add the token to the order object for processing
			$token   = $response->get_payment_token();
			$address = new Addresses\Customer_Address();

			// generate an address from the order
			$address->set_from_order( $order );

			// store the billing hash on the token for later use in case it needs to be updated
			$token->set_billing_hash( $address->get_hash() );

			// set the resulting token on the order
			$order->payment->token = $token->get_id();

			// for credit card transactions add the card type, if known (some gateways return the credit card type as part of the response, others may require it as part of the request, and still others it may never be known)
			if ( $gateway->is_credit_card_gateway() && $token->get_card_type() ) {
				$order->payment->card_type = $token->get_card_type();
			}

			// checking/savings, if known
			if ( $gateway->is_echeck_gateway() && $token->get_account_type() ) {
				$order->payment->account_type = $token->get_account_type();
			}

			// set the token to the user account
			if ( $order->get_user_id() ) {
				$this->add_token( $order->get_user_id(), $token, $environment_id );
			}

			$order->add_order_note( $this->get_order_note( $token ) );

			// add the standard transaction data
			$gateway->add_transaction_data( $order, $response );

			// clear any cached tokens
			if ( $transient_key = $this->get_transient_key( $order->get_user_id() ) ) {
				delete_transient( $transient_key );
			}

		} else {

			if ( $response->get_status_code() && $response->get_status_message() ) {
				/* translators: Placeholders: %1$s - payment request response status code, %2$s - payment request response status message */
				$message = sprintf( esc_html__( 'Status code %1$s: %2$s', 'woocommerce-plugin-framework' ), $response->get_status_code(), $response->get_status_message() );
			} elseif ( $response->get_status_code() ) {
				/* translators: Placeholders: %s - payment request response status code */
				$message = sprintf( esc_html__( 'Status code: %s', 'woocommerce-plugin-framework' ), $response->get_status_code() );
			} elseif ( $response->get_status_message() ) {
				/* translators: Placeholders: %s - payment request response status message */
				$message = sprintf( esc_html__( 'Status message: %s', 'woocommerce-plugin-framework' ), $response->get_status_message() );
			} else {
				$message = esc_html__( 'Unknown Error', 'woocommerce-plugin-framework' );
			}

			// add transaction id if there is one
			if ( $response->get_transaction_id() ) {
				$message .= ' ' . sprintf( esc_html__( 'Transaction ID %s', 'woocommerce-plugin-framework' ), $response->get_transaction_id() );
			}

			throw new SV_WC_Payment_Gateway_Exception( $message );
		}

		return $order;
	}


	/**
	 * Adds a payment method and token as user meta.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id user identifier
	 * @param SV_WC_Payment_Gateway_Payment_Token $token the token
	 * @param string|null $environment_id optional environment id, defaults to plugin current environment
	 * @return int
	 */
	public function add_token( $user_id, $token, $environment_id = null ) {

		// default to current environment
		if ( is_null( $environment_id ) ) {
			$environment_id = $this->get_environment_id();
		}

		// if this token is set as active, mark all others as false
		if ( $token->is_default() ) {

			$existing_tokens = $this->get_tokens( $user_id, array( 'environment_id' => $environment_id ) );

			foreach ( $existing_tokens as $existing_token ) {

				$existing_token->set_default( false );

				try {
					$existing_token->save();
				} catch ( SV_WC_Payment_Gateway_Exception $e ) {
					$this->get_gateway()->get_plugin()->log( $e->getMessage() );
				}
			}

			$this->tokens[ $environment_id ][ $user_id ] = $existing_tokens;
		}

		$token->set_gateway_id( $this->get_gateway()->get_id() );
		$token->set_user_id( $user_id );
		$token->set_environment( $environment_id );

		try {
			$saved = $token->save();
		} catch ( SV_WC_Payment_Gateway_Exception $e ) {
			$saved = false;
			$this->get_gateway()->get_plugin()->log( $e->getMessage() );
		}

		// if saved, update the local cache
		if ( $saved ) {

			$this->tokens[ $environment_id ][ $user_id ][ $token->get_id() ] = $token;

			$this->clear_transient( $user_id );
		}

		return $saved;
	}


	/**
	 * Returns the payment token object identified by $token from the user
	 * identified by $user_id
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id WordPress user identifier, or 0 for guest
	 * @param string $token payment token
	 * @param string|null $environment_id optional environment id, defaults to plugin current environment
	 * @return SV_WC_Payment_Gateway_Payment_Token payment token object or null
	 */
	public function get_token( $user_id, $token, $environment_id = null ) {

		// default to current environment
		if ( is_null( $environment_id ) ) {
			$environment_id = $this->get_environment_id();
		}

		$tokens = $this->get_tokens( $user_id, array( 'environment_id' => $environment_id ) );

		if ( isset( $tokens[ $token ] ) ) return $tokens[ $token ];

		return null;
	}


	/**
	 * Updates a single token by persisting its data as a core token.
	 *
	 * @since since 4.0.0
	 *
	 * @param int $user_id WP user ID
	 * @param SV_WC_Payment_Gateway_Payment_Token $token token to update
	 * @param string|null $environment_id optional environment ID, defaults to plugin current environment
	 * @return string|int updated user meta ID
	 */
	public function update_token( $user_id, $token, $environment_id = null ) {

		// default to current environment
		if ( null === $environment_id ) {
			$environment_id = $this->get_environment_id();
		}

		$token->set_gateway_id( $this->get_gateway()->get_id() );
		$token->set_user_id( $user_id );
		$token->set_environment( $environment_id );

		try {
			$saved = $token->save();
		} catch ( SV_WC_Payment_Gateway_Exception $e ) {
			$saved = false;
			$this->get_gateway()->get_plugin()->log( $e->getMessage() );
		}

		// if saved, update the local cache
		if ( $saved ) {

			$this->tokens[ $environment_id ][ $user_id ][ $token->get_id() ] = $token;

			$this->clear_transient( $user_id );
		}

		return $saved;
	}


	/**
	 * Deletes a credit card token from user meta
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id user identifier
	 * @param SV_WC_Payment_Gateway_Payment_Token|string $token the payment token to delete
	 * @param string|null $environment_id optional environment id, defaults to plugin current environment
	 * @return bool|int false if not deleted, updated user meta ID if deleted
	 */
	public function remove_token( $user_id, $token, $environment_id = null ) {

		// default to current environment
		if ( is_null( $environment_id ) ) {
			$environment_id = $this->get_environment_id();
		}

		// unknown token?
		if ( ! $this->user_has_token( $user_id, $token, $environment_id ) ) {
			return false;
		}

		// get the payment token object as needed
		if ( ! is_object( $token ) ) {
			$token = $this->get_token( $user_id, $token, $environment_id );
		}

		// for direct gateways that allow it, attempt to delete the token from the endpoint
		if ( $this->get_gateway()->get_api()->supports_remove_tokenized_payment_method() ) {

			if ( ! $this->remove_token_from_gateway( $user_id, $environment_id, $token ) ) {
				return false;
			}
		}

		return $this->delete_token( $user_id, $token );
	}


	/**
	 * Removes a tokenized payment method using the gateway's API.
	 *
	 * Returns true if the token's local data should be removed.
	 *
	 * @since 5.8.0
	 *
	 * @param int $user_id user identifier
	 * @param string $environment_id environment id
	 * @param SV_WC_Payment_Gateway_Payment_Token $token the payment token to remove
	 * @return bool
	 */
	private function remove_token_from_gateway( $user_id, $environment_id, $token ) {

		// remove a token's local data unless an exception occurs or we choose to keep loca data based on the API response
		$remove_local_data = true;

		try {

			$response = $this->get_gateway()->get_api()->remove_tokenized_payment_method( $token->get_id(), $this->get_gateway()->get_customer_id( $user_id, array( 'environment_id' => $environment_id ) ) );

			if ( ! $response->transaction_approved() && ! $this->should_delete_token( $token, $response ) ) {
				$remove_local_data = false;
			}

		} catch( SV_WC_Plugin_Exception $e ) {

			if ( $this->get_gateway()->debug_log() ) {
				$this->get_gateway()->get_plugin()->log( $e->getMessage(), $this->get_gateway()->get_id() );
			}

			$remove_local_data = false;
		}

		return $remove_local_data;
	}


	/**
	 * Determines if a token's local meta should be deleted based on an API response.
	 *
	 * @since 5.1.0
	 *
	 * @param SV_WC_Payment_Gateway_Payment_Token $token payment token object
	 * @param SV_WC_Payment_Gateway_API_Response $response API response object
	 * @return bool
	 */
	public function should_delete_token( SV_WC_Payment_Gateway_Payment_Token $token, SV_WC_Payment_Gateway_API_Response $response ) {

		return false;
	}


	/**
	 * Deletes a payment token from user meta.
	 *
	 * @since 5.1.0
	 *
	 * @param int $user_id WordPress user ID
	 * @param SV_WC_Payment_Gateway_Payment_Token $token payment token object
	 * @param string|null $environment_id gateway environment ID
	 * @return bool
	 */
	public function delete_token( $user_id, SV_WC_Payment_Gateway_Payment_Token $token, $environment_id = null ) {

		// default to current environment
		if ( null === $environment_id ) {
			$environment_id = $this->get_environment_id();
		}

		// always clear the transient
		$this->clear_transient( $user_id );

		$is_default = $token->is_default();

		// no need to respond to woocommerce_payment_token_deleted, we will remove remote and legacy data here
		$this->remove_payment_token_deleted_action();

		$deleted = $token->delete();

		// restore action callback for woocommerce_payment_token_deleted
		$this->add_payment_token_deleted_action();

		if ( $deleted ) {

			// delete token from local cache if successful
			unset( $this->tokens[ $environment_id ][ $user_id ][ $token->get_id() ] );

			// if the deleted token was the default token, make another one the new default
			if ( $is_default ) {

				foreach ( $this->get_tokens( $user_id, array( 'environment_id' => $environment_id ) ) as $existing_token ) {

					if ( $existing_token->get_id() === $token->get_id() ) {
						continue;
					}

					// set the first as default and bail
					$existing_token->set_default( true );

					try {
						$existing_token->save();
					} catch ( SV_WC_Payment_Gateway_Exception $e ) {
						$this->get_gateway()->get_plugin()->log( $e->getMessage() );
					}

					// update the local cache
					$this->tokens[ $environment_id ][ $user_id ][ $existing_token->get_id() ] = $existing_token;

					break;
				}
			}

			// delete the legacy token data now that the token has been removed
			$this->delete_legacy_token( $user_id, $token, $environment_id );
		}

		return $deleted;
	}


	/**
	 * Deletes a legacy payment token from user meta.
	 *
	 * @see SV_WC_Payment_Gateway_Payment_Token::delete()
	 *
	 * @since 5.8.0
	 *
	 * @param int $user_id WordPress user ID
	 * @param SV_WC_Payment_Gateway_Payment_Token $token payment token object
	 * @param string|null $environment_id gateway environment ID
	 * @return bool whether the token was deleted from the user meta
	 */
	public function delete_legacy_token( $user_id, SV_WC_Payment_Gateway_Payment_Token $token, $environment_id = null ) {

		$deleted = false;

		// default to current environment
		if ( null === $environment_id ) {
			$environment_id = $this->get_environment_id();
		}

		$legacy_tokens = get_user_meta( $user_id, $this->get_user_meta_name( $environment_id ), true );

		if ( is_array( $legacy_tokens ) && isset( $legacy_tokens[ $token->get_id() ] ) ) {

			unset( $this->legacy_tokens[ $environment_id ][ $user_id ][ $token->get_id() ] );

			unset( $legacy_tokens[ $token->get_id() ] );

			$deleted = (bool) update_user_meta( $user_id, $this->get_user_meta_name( $environment_id ), $legacy_tokens );
		}

		return $deleted;
	}


	/**
	 * Sets the default token for a user.
	 *
	 * This is shown as "Default Card" in the frontend and will be auto-selected during checkout.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id user identifier
	 * @param SV_WC_Payment_Gateway_Payment_Token|string $token the token to make default
	 * @param string|null $environment_id optional environment id, defaults to plugin current environment
	 * @return string|bool false if not set, updated user meta ID if set
	 */
	public function set_default_token( $user_id, $token, $environment_id = null ) {

		// default to current environment
		if ( is_null( $environment_id ) ) {
			$environment_id = $this->get_environment_id();
		}

		// unknown token?
		if ( ! $this->user_has_token( $user_id, $token ) )
			return false;

		// get the payment token object as needed
		if ( ! is_object( $token ) ) {
			$token = $this->get_token( $user_id, $token, $environment_id );
		}

		// get existing tokens
		$tokens = $this->get_tokens( $user_id, array( 'environment_id' => $environment_id ) );

		// mark $token as the only active
		foreach ( $tokens as $key => $_token ) {

			if ( $token->get_id() == $_token->get_id() ) {
				$tokens[ $key ]->set_default( true );
			} else {
				$tokens[ $key ]->set_default( false );
			}

		}

		// persist the updated tokens
		return $this->update_tokens( $user_id, $tokens, $environment_id );

	}


	/** Handle all tokens *************************************************************************/


	/**
	 * Gets the available payment tokens for a user as an associative array of
	 * payment token to SV_WC_Payment_Gateway_Payment_Token
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id WordPress user identifier, or 0 for guest
	 * @param array $args optional arguments, can include
	 *  	`customer_id` - if not provided, this will be looked up based on $user_id
	 *  	`environment_id` - defaults to plugin current environment
	 * @return array|SV_WC_Payment_Gateway_Payment_Token[] associative array of string token to SV_WC_Payment_Gateway_Payment_Token object
	 */
	public function get_tokens( $user_id, $args = array() ) {

		// default to current environment
		if ( ! isset( $args['environment_id'] ) ) {
			$args['environment_id'] = $this->get_environment_id();
		}

		if ( ! isset( $args['customer_id'] ) ) {
			$args['customer_id'] = $this->get_gateway()->get_customer_id( $user_id, array( 'environment_id' => $args['environment_id'] ) );
		}

		$environment_id = $args['environment_id'];
		$customer_id    = $args['customer_id'];
		$transient_key  = $this->get_transient_key( $user_id );

		// return tokens cached during a single request
		if ( isset( $this->tokens[ $environment_id ][ $user_id ] ) ) {
			return $this->tokens[ $environment_id ][ $user_id ];
		}

		// return tokens cached in transient
		if ( $transient_key && ( false !== ( $this->tokens[ $environment_id ][ $user_id ] = get_transient( $transient_key ) ) ) ) {
			return $this->tokens[ $environment_id ][ $user_id ];
		}

		$this->tokens[ $environment_id ][ $user_id ] = array();
		$tokens = array();

		// retrieve the datastore persisted tokens first, so we have them for
		// gateways that don't support fetching them over an API, as well as the
		// default token for those that do
		if ( $user_id ) {

			$core_tokens = \WC_Payment_Tokens::get_customer_tokens( $user_id, $this->get_gateway()->get_id() );

			if ( is_array( $core_tokens ) ) {

				foreach ( $core_tokens as $core_token ) {

					if ( $environment_id === $core_token->get_meta( 'environment' ) ) {
						$tokens[ $core_token->get_token() ] = $this->build_token( $core_token->get_token(), $core_token );
					}
				}
			}

			// migrate legacy tokens if necessary
			if ( ! $this->user_legacy_tokens_migrated( $user_id ) ) {

				$legacy_tokens   = $this->get_legacy_tokens( $user_id );
				$migrated_tokens = 0;

				// migrate any legacy tokens that haven't already been migrated
				foreach ( $legacy_tokens as $legacy_token ) {

					if ( ! isset( $tokens[ $legacy_token->get_id() ] ) && ! $legacy_token->is_migrated() ) {

						$legacy_token->set_gateway_id( $this->get_gateway()->get_id() );
						$legacy_token->set_user_id( $user_id );
						$legacy_token->set_environment( $environment_id );

						try {

							if ( $legacy_token->save() ) {

								$tokens[ $legacy_token->get_id() ] = $legacy_token;

								$migrated_tokens++;

								$legacy_token->set_migrated( true );

								$this->update_legacy_token( $user_id, $legacy_token );
							}

						} catch ( SV_WC_Payment_Gateway_Exception $e ) {

							$this->get_gateway()->get_plugin()->log( $e->getMessage() );
						}

					}
				}

				// if all of the tokens were successfully migrated, flag the user for no further migrations
				if ( count( $legacy_tokens ) === $migrated_tokens ) {
					$this->set_user_legacy_tokens_migrated( $user_id );
				}
			}

			$this->tokens[ $environment_id ][ $user_id ] = $tokens;
		}

		// if the payment gateway API supports retrieving tokens directly, do so as it's easier to stay synchronized
		if ( $this->get_gateway()->get_api()->supports_get_tokenized_payment_methods() && $customer_id ) {

			try {

				// retrieve the payment method tokes from the remote API
				$response = $this->get_gateway()->get_api()->get_tokenized_payment_methods( $customer_id );
				$this->tokens[ $environment_id ][ $user_id ] = $response->get_payment_tokens();

				// check for a default from the persisted set, if any
				$default_token = null;
				foreach ( $tokens as $default_token ) {
					if ( $default_token->is_default() ) {
						break;
					}
				}

				// mark the corresponding token from the API as the default one
				if ( $default_token && $default_token->is_default() && isset( $this->tokens[ $environment_id ][ $user_id ][ $default_token->get_id() ] ) ) {
					$this->tokens[ $environment_id ][ $user_id ][ $default_token->get_id() ]->set_default( true );
				}

				// merge local token data with remote data, sometimes local data is more robust
				$this->tokens[ $environment_id ][ $user_id ] = $this->merge_token_data( $tokens, $this->tokens[ $environment_id ][ $user_id ] );

				// persist locally after merging
				$this->update_tokens( $user_id, $this->tokens[ $environment_id ][ $user_id ], $environment_id );

				// remove local tokens not present in remote data
				foreach ( array_diff_key( $tokens, $this->tokens[ $environment_id ][ $user_id ] ) as $key => $token ) {
					$this->delete_token( $user_id, $token, $environment_id );
				}

			} catch( SV_WC_Plugin_Exception $e ) {

				// communication or other error

				$this->get_gateway()->add_debug_message( $e->getMessage(), 'error' );

				$this->tokens[ $environment_id ][ $user_id ] = $tokens;
			}

		}

		// set the payment type image url, if any, for convenience
		foreach ( $this->tokens[ $environment_id ][ $user_id ] as $key => $token ) {
			$this->tokens[ $environment_id ][ $user_id ][ $key ]->set_image_url( $this->get_gateway()->get_payment_method_image_url( $token->is_credit_card() ? $token->get_card_type() : 'echeck' ) );
		}

		if ( $transient_key ) {
			set_transient( $transient_key, $this->tokens[ $environment_id ][ $user_id ], 60 );
		}

		/**
		 * Direct Payment Gateway Payment Tokens Loaded Action.
		 *
		 * Fired when payment tokens have been completely loaded.
		 *
		 * @since 4.0.0
		 *
		 * @param array $tokens array of SV_WC_Payment_Gateway_Payment_Tokens
		 * @param SV_WC_Payment_Gateway gateway class instance
		 */
		do_action( 'wc_payment_gateway_' . $this->get_gateway()->get_id() . '_payment_tokens_loaded', $this->tokens[ $environment_id ][ $user_id ], $this );

		return $this->tokens[ $environment_id ][ $user_id ];
	}


	/**
	 * Gets token objects from the legacy user meta data store.
	 *
	 * @since 5.8.0
	 *
	 * @param int $user_id WordPress user ID
	 * @param null|string $environment_id desired environment ID
	 * @return SV_WC_Payment_Gateway_Payment_Token[]
	 */
	public function get_legacy_tokens( $user_id, $environment_id = null ) {

		if ( null === $environment_id ) {
			$environment_id = $this->get_environment_id();
		}

		$this->legacy_tokens[ $environment_id ][ $user_id ] = [];

		$token_data = get_user_meta( $user_id, $this->get_user_meta_name( $environment_id ), true );

		// from database format
		if ( is_array( $token_data ) ) {

			foreach ( $token_data as $token => $data ) {
				$this->legacy_tokens[ $environment_id ][ $user_id ][ $token ] = $this->build_token( $token, $data );
			}
		}

		return $this->legacy_tokens[ $environment_id ][ $user_id ];
	}


	/**
	 * Updates the given payment tokens for the identified user, in the database.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id WP user ID
	 * @param SV_WC_Payment_Gateway_Payment_Token[] $tokens array of tokens
	 * @param string|null $environment_id optional environment id, defaults to plugin current environment
	 * @return bool
	 */
	public function update_tokens( $user_id, $tokens, $environment_id = null ) {

		// default to current environment
		if ( is_null( $environment_id ) ) {
			$environment_id = $this->get_environment_id();
		}

		// update the local cache
		$this->tokens[ $environment_id ][ $user_id ] = $tokens;

		// clear the transient
		$this->clear_transient( $user_id );

		$updated_tokens = [];

		foreach ( $tokens as $token ) {

			// skip invalid objects
			if ( ! $token instanceof SV_WC_Payment_Gateway_Payment_Token ) {
				continue;
			}

			// ensure the vital properties are set
			$token->set_user_id( $user_id );
			$token->set_gateway_id( $this->get_gateway()->get_id() );
			$token->set_environment( $environment_id );

			try {
				$token_id = $token->save();
			} catch ( SV_WC_Payment_Gateway_Exception $e ) {
				$token_id = 0;
				$this->get_gateway()->get_plugin()->log( $e->getMessage() );
			}

			if ( $token_id ) {
				$updated_tokens[] = $token_id;
			}
		}

		return count( $tokens ) === count( $updated_tokens );
	}


	/**
	 * Updates a single legacy token in user meta.
	 *
	 * @see SV_WC_Payment_Gateway_Payment_Token::save()
	 *
	 * @since 5.8.0
	 *
	 * @param int $user_id WP user ID
	 * @param SV_WC_Payment_Gateway_Payment_Token $token token to update
	 * @param string|null $environment_id optional environment ID, defaults to plugin current environment
	 * @param bool $migrated whether the token was migrated to the new datastore
	 * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure
	 */
	public function update_legacy_token( $user_id, $token, $environment_id = null, $migrated = false ) {

		$updated = false;

		// default to current environment
		if ( null === $environment_id ) {
			$environment_id = $this->get_environment_id();
		}

		$legacy_tokens = get_user_meta( $user_id, $this->get_user_meta_name( $environment_id ), true );

		if ( is_array( $legacy_tokens ) && isset( $legacy_tokens[ $token->get_id() ] ) ) {

			// update the cached token
			$this->legacy_tokens[ $environment_id ][ $user_id ][ $token->get_id() ] = $token;

			$legacy_tokens[ $token->get_id() ] = $token->to_datastore_format();

			if ( $migrated ) {
				$legacy_tokens[ $token->get_id() ]['migrated'] = true;
			}

			$updated = update_user_meta( $user_id, $this->get_user_meta_name( $environment_id ), $legacy_tokens );
		}

		return $updated;
	}



	/** Admin methods *****************************************************************************/


	/**
	 * Get the admin token editor instance.
	 *
	 * @since 4.3.0
	 *
	 * @return SV_WC_Payment_Gateway_Admin_Payment_Token_Editor
	 */
	public function get_token_editor() {
		return new SV_WC_Payment_Gateway_Admin_Payment_Token_Editor( $this->get_gateway() );
	}


	/** Conditional methods ***********************************************************************/


	/**
	 * Determines if the identified user has the given payment token
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id WordPress user identifier, or 0 for guest
	 * @param string|SV_WC_Payment_Gateway_Payment_Token $token payment token
	 * @param string|null $environment_id optional environment id, defaults to plugin current environment
	 * @return bool
	 */
	public function user_has_token( $user_id, $token, $environment_id = null ) {

		// default to current environment
		if ( is_null( $environment_id ) ) {
			$environment_id = $this->get_environment_id();
		}

		if ( is_object( $token ) ) {
			$token = $token->get_id();
		}

		// token exists?
		return ! is_null( $this->get_token( $user_id, $token, $environment_id ) );
	}


	/**
	 * Determines if the current payment method should be tokenized.
	 *
	 * Whether requested by customer or otherwise forced. This parameter is passed from
	 * the checkout page/payment form.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function should_tokenize() {

		return SV_WC_Helper::get_posted_value( 'wc-' . $this->get_gateway()->get_id_dasherized() . '-tokenize-payment-method' ) && ! SV_WC_Helper::get_posted_value( 'wc-' . $this->get_gateway()->get_id_dasherized() . '-payment-token' );
	}


	/**
	 * Determines if tokenization should be forced on the checkout page.
	 *
	 * This is most useful to force tokenization for a subscription or pre-orders initial transaction.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function tokenization_forced() {

		/**
		 * Direct Gateway Tokenization Forced Filter.
		 *
		 * Allow actors to indicate that tokenization should be forced for the current
		 * checkout.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $force true to force tokenization, false otherwise
		 * @param SV_WC_Payment_Gateway $this instance
		 */
		return apply_filters( 'wc_payment_gateway_' . $this->get_gateway()->get_id() . '_tokenization_forced', false, $this->get_gateway() );
	}


	/** Utility methods ***************************************************************************/


	/**
	 * Merges remote token data with local tokens.
	 *
	 * Sometimes local tokens can provide additional detail that's not provided remotely.
	 *
	 * @since 4.0.0
	 *
	 * @param array $local_tokens local tokens
	 * @param array $remote_tokens remote tokens
	 * @return array associative array of string token to SV_WC_Payment_Gateway_Payment_Token objects
	 */
	protected function merge_token_data( $local_tokens, $remote_tokens ) {

		foreach ( $remote_tokens as &$remote_token ) {

			$remote_token_id = $remote_token->get_id();

			// bail if the remote token doesn't exist locally
			if ( ! isset( $local_tokens[ $remote_token_id ] ) ) {
				continue;
			}

			foreach ( $this->get_merge_attributes() as $attribute ) {

				$get_method = "get_{$attribute}";
				$set_method = "set_{$attribute}";

				// if the remote token is missing an attribute and the local token has it...
				if ( ! $remote_token->$get_method() && $local_tokens[ $remote_token_id ]->$get_method() ) {

					// set the attribute on the remote token
					$remote_token->$set_method( $local_tokens[ $remote_token_id ]->$get_method() );
				}
			}
		}

		return $remote_tokens;
	}


	/**
	 * Returns the attributes that should be used to merge local token data into
	 * a remote token.
	 *
	 * Gateways can override this method to add their own attributes, but must
	 * also include the associated get_*() & set_*() methods in the token class.
	 *
	 * See Authorize.net CIM for an example implementation.
	 *
	 * @since 4.0.0
	 *
	 * @return array associative array of string token to SV_WC_Payment_Gateway_Payment_Token objects
	 */
	protected function get_merge_attributes() {

		return array( 'last_four', 'card_type', 'account_type', 'exp_month', 'exp_year', 'nickname' );
	}


	/**
	 * Gets the payment token transient key for the given user, gateway and environment.
	 *
	 * Payment token transients can be disabled by using the filter below.
	 *
	 * @since 4.0.0
	 *
	 * @param string|int $user_id
	 * @return string transient key
	 */
	protected function get_transient_key( $user_id = null ) {

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		// ex: wc_sv_tokens_<md5 hash of gateway_id, user ID, and environment ID>
		$key = sprintf( 'wc_sv_tokens_%s', md5( $this->get_gateway()->get_id() . '_' . $user_id . '_' . $this->get_environment_id() ) );

		/**
		 * Filter payment tokens transient key
		 *
		 * Warning: this filter should generally only be used to disable token
		 * transients by returning false or an empty string. Setting an incorrect or invalid
		 * transient key (e.g. not keyed to the current user or environment) can
		 * result in unexpected and difficult to debug situations involving tokens.
		 *
		 * filter responsibly!
		 *
		 * @since 4.0.0
		 * @param string $key transient key (must be 45 chars or less)
		 * @param SV_WC_Payment_Gateway $this direct gateway class instance
		 */
		return apply_filters( 'wc_payment_gateway_' . $this->get_gateway()->get_id() . '_payment_tokens_transient_key', $key, $user_id, $this->get_gateway() );
	}


	/**
	 * Helper method to clear the tokens transient
	 *
	 * TODO: ideally the transient would make use of actions to clear itself
	 * as needed (e.g. when customer IDs are updated/removed), but for now it's
	 * only cleared when the tokens are updated. @MR July 2015
	 *
	 * @since 4.0.0
	 *
	 * @param int|string $user_id
	 */
	public function clear_transient( $user_id ) {

		delete_transient( $this->get_transient_key( $user_id ) );
	}


	/**
	 * Returns the payment token user meta name for persisting the payment tokens.
	 *
	 * Defaults to _wc_{gateway id}_payment_tokens for the production environment,
	 * and _wc_{gateway id}_payment_tokens_{environment} for any other environment.
	 *
	 * NOTE: the gateway id, rather than plugin id, is used by default to create
	 * the meta key for this setting, because it's assumed that in the case of a
	 * plugin having multiple gateways (ie credit card and eCheck) the payment
	 * tokens will be distinct between them
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $environment_id optional environment id, defaults to plugin current environment
	 * @return string payment token user meta name
	 */
	public function get_user_meta_name( $environment_id = null ) {

		// default to current environment
		if ( is_null( $environment_id ) ) {
			$environment_id = $this->get_environment_id();
		}

		// leading underscore since this will never be displayed to an admin user in its raw form
		return $this->get_gateway()->get_order_meta_prefix() . 'payment_tokens' . ( ! $this->get_gateway()->is_production_environment( $environment_id ) ? '_' . $environment_id : '' );
	}


	/**
	 * Gets the order note message when a customer saves their payment method
	 * to their account
	 *
	 * @since 4.1.2
	 *
	 * @param SV_WC_Payment_Gateway_Payment_Token $token the payment token being saved
	 * @return string
	 */
	protected function get_order_note( $token ) {

		$gateway = $this->get_gateway();

		$message = '';

		// order note based on gateway type
		if ( $gateway->is_credit_card_gateway() ) {

			/* translators: Placeholders: %1$s - payment gateway title (such as Authorize.net, Braintree, etc), %2$s - payment method name (mastercard, bank account, etc), %3$s - last four digits of the card/account, %4$s - card/account expiry date */
			$message = sprintf( __( '%1$s Payment Method Saved: %2$s ending in %3$s (expires %4$s)', 'woocommerce-plugin-framework' ),
				$gateway->get_method_title(),
				$token->get_type_full(),
				$token->get_last_four(),
				$token->get_exp_date()
			);

		} elseif ( $gateway->is_echeck_gateway() ) {

			// account type (checking/savings) may or may not be available, which is fine
			/* translators: Placeholders: %1$s - payment gateway title (such as CyberSouce, NETbilling, etc), %2$s - account type (checking/savings - may or may not be available), %3$s - last four digits of the account */
			$message = sprintf( __( '%1$s eCheck Payment Method Saved: %2$s account ending in %3$s', 'woocommerce-plugin-framework' ),
				$gateway->get_method_title(),
				$token->get_account_type(),
				$token->get_last_four()
			);
		}

		return $message;
	}


	/**
	 * Returns $tokens in a format suitable for data storage
	 *
	 * @since 1.0.0
	 *
	 * @param array $tokens array of SV_WC_Payment_Gateway_Payment_Token tokens
	 * @return array data storage version of $tokens
	 */
	protected function format_for_db( $tokens ) {

		$_tokens = array();

		// to database format
		foreach ( $tokens as $key => $token ) {
			$_tokens[ $key ] = $token->to_datastore_format();
		}

		return $_tokens;
	}


	/**
	 * Get the gateway environment ID.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	protected function get_environment_id() {
		return $this->environment_id;
	}


	/**
	 * Gets the gateway instance.
	 *
	 * @since 4.3.0
	 *
	 * @return SV_WC_Payment_Gateway gateway instance
	 */
	protected function get_gateway() {

		return $this->gateway;
	}


	/**
	 * Determines whether a user's tokens have been migrated.
	 *
	 * @since 5.8.0
	 *
	 * @param int $user_id WordPress user ID
	 * @param string|null $environment_id environment ID
	 * @return bool
	 */
	public function user_legacy_tokens_migrated( $user_id, $environment_id = null ) {

		if ( null === $environment_id ) {
			$environment_id = $this->get_environment_id();
		}

		$migrated = 'yes' === get_user_meta( $user_id, $this->get_user_meta_name( $environment_id ) . '_migrated', true );

		/**
		 * Filters whether a user's legacy tokens have been migrated.
		 *
		 * @since 5.8.0
		 *
		 * @param bool $migrated whether the tokens have been migrated
		 * @param int $user_id user ID
		 * @param string $environment_id the gateway environment the tokens are related to
		 */
		return (bool) apply_filters( 'wc_payment_gateway_' . $this->get_gateway()->get_id() . '_user_legacy_tokens_migrated', $migrated, $user_id, $environment_id );
	}


	/**
	 * Marks a user as having their tokens migrated.
	 *
	 * @since 5.8.0
	 *
	 * @param int $user_id WordPress user ID
	 * @param string|null $environment_id environment ID
	 */
	public function set_user_legacy_tokens_migrated( $user_id, $environment_id = null ) {

		if ( null === $environment_id ) {
			$environment_id = $this->get_environment_id();
		}

		update_user_meta( $user_id, $this->get_user_meta_name( $environment_id ) . '_migrated', 'yes' );
	}


	/**
	 * Adds the callback action for woocommerce_payment_token_deleted.
	 *
	 * @since 5.8.0
	 */
	private function add_payment_token_deleted_action() {

		add_action( 'woocommerce_payment_token_deleted', [ $this, 'payment_token_deleted' ], 10, 2 );
	}


	/**
	 * Removes the callback action for woocommerce_payment_token_deleted.
	 *
	 * @since 5.8.0
	 */
	private function remove_payment_token_deleted_action() {

		remove_action( 'woocommerce_payment_token_deleted', [ $this, 'payment_token_deleted' ], 10, 2 );
	}


	/**
	 * Deletes remote token data and legacy token data when the corresponding core token is deleted.
	 *
	 * @internal
	 *
	 * @since 5.8.0
	 *
	 * @param int $token_id the ID of a core token
	 * @param \WC_Payment_Token $core_token the core token object
	 */
	public function payment_token_deleted( $token_id, $core_token ) {

		if ( $this->get_gateway()->get_id() === $core_token->get_gateway_id() ) {

			$token = $this->build_token( $core_token->get_token(), $core_token );

			$user_id        = $token->get_user_id();
			$environment_id = $token->get_environment();

			// for direct gateways that allow it, attempt to delete the token from the endpoint
			if ( ! $this->get_gateway()->get_api()->supports_remove_tokenized_payment_method() || $this->remove_token_from_gateway( $user_id, $environment_id, $token ) ) {

				// clear tokens transient
				$this->clear_transient( $user_id );

				// delete token from local cache
				unset( $this->tokens[ $environment_id ][ $user_id ][ $token->get_id() ] );

				// delete the legacy token data now that the token has been removed
				$this->delete_legacy_token( $user_id, $token, $environment_id );
			}
		}
	}


}


endif;
