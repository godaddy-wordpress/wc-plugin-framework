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

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'SV_WC_Payment_Gateway_Helper' ) ) :

/**
 * SkyVerge Payment Gateway Helper Class
 *
 * The purpose of this class is to centralize common utility functions that
 * are commonly used in SkyVerge payment gateway plugins
 *
 * @since 3.0.0
 */
class SV_WC_Payment_Gateway_Helper {


	/** @var string the Visa card type ID **/
	public static $card_type_visa = 'visa';

	/** @var string the MasterCard card type ID **/
	public static $card_type_mastercard = 'mc'; // TODO: switch to 'mastercard' once all plugins are using this variable

	/** @var string the American Express card type ID **/
	public static $card_type_amex = 'amex';

	/** @var string the Diners Club card type ID **/
	public static $card_type_dinersclub = 'diners'; // TODO: switch to 'dinersclub' once all plugins are using this variable

	/** @var string the Discover card type ID **/
	public static $card_type_discover = 'discover';

	/** @var string the JCB card type ID **/
	public static $card_type_jcb = 'jcb';

	/** @var string the CarteBleue card type ID **/
	public static $card_type_cartebleue = 'cartebleue';

	/** @var string the Maestro card type ID **/
	public static $card_type_maestro = 'maestro';

	/** @var string the Laser card type ID **/
	public static $card_type_laser = 'laser';


	/**
	 * Perform standard luhn check.  Algorithm:
	 *
	 * 1. Double the value of every second digit beginning with the second-last right-hand digit.
	 * 2. Add the individual digits comprising the products obtained in step 1 to each of the other digits in the original number.
	 * 3. Subtract the total obtained in step 2 from the next higher number ending in 0.
	 * 4. This number should be the same as the last digit (the check digit). If the total obtained in step 2 is a number ending in zero (30, 40 etc.), the check digit is 0.
	 *
	 * @since 3.0.0
	 * @param string $account_number the credit card number to check
	 * @return bool true if $account_number passes the check, false otherwise
	 */
	public static function luhn_check( $account_number ) {

		for ( $sum = 0, $i = 0, $ix = strlen( $account_number ); $i < $ix - 1; $i++) {

			$weight = substr( $account_number, $ix - ( $i + 2 ), 1 ) * ( 2 - ( $i % 2 ) );
			$sum += $weight < 10 ? $weight : $weight - 9;

		}

		return substr( $account_number, $ix - 1 ) == ( ( 10 - $sum % 10 ) % 10 );
	}


	/**
	 * Normalize a card type to a standard type ID and account for variations.
	 *
	 * @since 4.4.2-1
	 * @param string $card_type the card type to normalize
	 * @return string
	 */
	public static function normalize_card_type( $card_type ) {

		$card_types = self::get_card_types();

		$card_type = strtolower( $card_type );

		// stop here if the provided card type is already normalized
		if ( in_array( $card_type, array_keys( $card_types ) ) ) {
			return $card_type;
		}

		$variations = wp_list_pluck( $card_types, 'variations' );

		// if the provided card type matches a known variation, return the normalized card type
		foreach ( $variations as $valid_type => $vars ) {

			if ( in_array( $card_type, $vars ) ) {
				$card_type = $valid_type;
				break;
			}
		}

		// otherwise, let it through unaltered
		return $card_type;
	}


	/**
	 * Determine the credit card type from a given account number (only first 4
	 * required)
	 *
	 * @since 4.0.0
	 * @param string $account_number the credit card account number
	 * @return string the credit card type
	 */
	public static function card_type_from_account_number( $account_number ) {

		// card type regex patterns from https://github.com/stripe/jquery.payment/blob/master/src/jquery.payment.coffee
		$types = array(
			self::$card_type_visa       => '/^4/',
			self::$card_type_mastercard => '/^5[1-5]/',
			self::$card_type_amex       => '/^3[47]/',
			self::$card_type_discover   => '/^(6011|65|64[4-9]|622)/',
			self::$card_type_dinersclub => '/^(36|38|30[0-5])/',
			self::$card_type_jcb        => '/^35/',
			self::$card_type_maestro    => '/^(5018|5020|5038|6304|6759|676[1-3])/',
			self::$card_type_laser      => '/^(6706|6771|6709)/',
		);

		foreach ( $types as $type => $pattern ) {

			if ( 1 === preg_match( $pattern, $account_number ) ) {
				return $type;
			}
		}

		return null;
	}


	/**
	 * Translates a credit card type or bank account name to a full name,
	 * e.g. 'mastercard' => 'MasterCard' or 'savings' => 'eCheck'
	 *
	 * @since 4.0.0
	 * @param string $payment_type the credit card or bank type, ie 'mastercard', 'amex', 'checking'
	 * @return string the credit card or bank account name, ie 'MasterCard', 'American Express', 'Checking Account'
	 */
	public static function payment_type_to_name( $payment_type ) {

		$name = '';

		// normalize for backwards compatibility with gateways that pass the card type directly from \SV_WC_Payment_Gateway::get_card_types()
		$type = self::normalize_card_type( $payment_type );

		// known payment type names, excluding credit cards
		$payment_types = array(
			'paypal'   => esc_html__( 'PayPal', 'woocommerce-plugin-framework' ),
			'checking' => esc_html__( 'Checking Account', 'woocommerce-plugin-framework' ),
			'savings'  => esc_html__( 'Savings Account', 'woocommerce-plugin-framework' ),
			'card'     => esc_html__( 'Credit / Debit Card', 'woocommerce-plugin-framework' ),
			'bank'     => esc_html__( 'Bank Account', 'woocommerce-plugin-framework' ),
		);

		// add the credit card names
		$payment_types = array_merge( wp_list_pluck( self::get_card_types(), 'name' ), $payment_types );

		if ( isset( $payment_types[ $type ] ) ) {
			$name = $payment_types[ $type ];
		} elseif ( '' === $type ) {
			$name = esc_html_x( 'Account', 'payment method type', 'woocommerce-plugin-framework' );
		} else {
			$name = ucwords( str_replace( '-', ' ', $type ) );
		}

		/**
		 * Payment Gateway Type to Name Filter.
		 *
		 * Allow actors to modify the name returned given a payment type.
		 *
		 * @since 4.0.0
		 * @param string $name nice payment type name, e.g. American Express
		 * @param string $type payment type, e.g. amex
		 */
		return apply_filters( 'wc_payment_gateway_payment_type_to_name', $name, $type );
	}


	/**
	 * Get the known card types and their variations.
	 *
	 * Returns the card types in the format:
	 *
	 * 'mastercard' {
	 *     'name'      => 'MasterCard',
	 *     'varations' => array( 'mc' ),
	 * }
	 *
	 * @since 4.4.2-1
	 * @return array
	 */
	public static function get_card_types() {

		return array(
			self::$card_type_visa => array(
				'name'       => esc_html_x( 'Visa', 'credit card type', 'woocommerce-plugin-framework' ),
				'variations' => array(),
			),
			self::$card_type_mastercard => array(
				'name'       => esc_html_x( 'MasterCard', 'credit card type', 'woocommerce-plugin-framework' ),
				'variations' => array( 'mastercard' ), // TODO: switch to 'mc' once $card_type_mastercard is switched to 'mastercard'
			),
			self::$card_type_amex => array(
				'name'       => esc_html_x( 'American Express', 'credit card type', 'woocommerce-plugin-framework' ),
				'variations' => array(),
			),
			self::$card_type_dinersclub => array(
				'name'       => esc_html_x( 'Diners Club', 'credit card type', 'woocommerce-plugin-framework' ),
				'variations' => array( 'dinersclub' ), // TODO: switch to 'diners' once $card_type_dinersclub is switched to 'dinersclub'
			),
			self::$card_type_discover => array(
				'name'       => esc_html_x( 'Discover', 'credit card type', 'woocommerce-plugin-framework' ),
				'variations' => array( 'disc' ),
			),
			self::$card_type_jcb => array(
				'name'       => esc_html_x( 'JCB', 'credit card type', 'woocommerce-plugin-framework' ),
				'variations' => array(),
			),
			self::$card_type_cartebleue => array(
				'name'       => esc_html_x( 'CarteBleue', 'credit card type', 'woocommerce-plugin-framework' ),
				'variations' => array(),
			),
			self::$card_type_maestro => array(
				'name'       => esc_html_x( 'Maestro', 'credit card type', 'woocommerce-plugin-framework' ),
				'variations' => array(),
			),
			self::$card_type_laser => array(
				'name'       => esc_html_x( 'Laser', 'credit card type', 'woocommerce-plugin-framework' ),
				'variations' => array(),
			),
		);
	}


}

endif; // Class exists check
