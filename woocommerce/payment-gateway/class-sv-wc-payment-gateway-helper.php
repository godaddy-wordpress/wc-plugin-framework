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
			'visa'     => '/^4/',
			'mc'       => '/^5[1-5]/',
			'amex'     => '/^3[47]/',
			'discover' => '/^(6011|65|64[4-9]|622)/',
			'diners'   => '/^(36|38|30[0-5])/',
			'jcb'      => '/^35/',
			'maestro'  => '/^(5018|5020|5038|6304|6759|676[1-3])/',
			'laser'    => '/^(6706|6771|6709)/',
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
	 * e.g. 'mc' => 'MasterCard' or 'savings' => 'eCheck'
	 *
	 * @since 4.0.0
	 * @param string $payment_type the card or bank type, ie 'mc', 'amex', 'checking'
	 * @return string the card or bank account name, ie 'MasterCard', 'American Express', 'Checking Account'
	 */
	public static function payment_type_to_name( $payment_type ) {

		$name = '';
		$type = strtolower( $payment_type );

		// special cases
		switch ( $type ) {

			case 'mc':         $name = esc_html_x( 'MasterCard', 'credit card type', 'woocommerce-plugin-framework' );          break;
			case 'mastercard': $name = esc_html_x( 'MasterCard', 'credit card type', 'woocommerce-plugin-framework' );          break;
			case 'amex':       $name = esc_html_x( 'American Express', 'credit card type', 'woocommerce-plugin-framework' );    break;
			case 'disc':       $name = esc_html_x( 'Discover', 'credit card type', 'woocommerce-plugin-framework' );            break;
			case 'discover':   $name = esc_html_x( 'Discover', 'credit card type', 'woocommerce-plugin-framework' );            break;
			case 'jcb':        $name = esc_html_x( 'JCB', 'credit card type', 'woocommerce-plugin-framework' );                 break;
			case 'cartebleue': $name = esc_html_x( 'CarteBleue', 'credit card type', 'woocommerce-plugin-framework' );          break;
			case 'paypal':     $name = esc_html__( 'PayPal', 'woocommerce-plugin-framework' );                                  break;
			case 'checking':   $name = esc_html__( 'Checking Account', 'woocommerce-plugin-framework' );                        break;
			case 'savings':    $name = esc_html__( 'Savings Account', 'woocommerce-plugin-framework' );                         break;
			case 'card':       $name = esc_html__( 'Credit / Debit Card', 'woocommerce-plugin-framework' );                     break;
			case 'bank':       $name = esc_html__( 'Bank Account', 'woocommerce-plugin-framework' );                            break;
			case '':           $name = esc_html_x( 'Account', 'payment method type', 'woocommerce-plugin-framework' );          break;
		}

		// default: replace dashes with spaces and uppercase all words
		if ( ! $name ) {
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


}

endif; // Class exists check
