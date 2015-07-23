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
 * @copyright Copyright (c) 2013-2015, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

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
	 * Returns the admin configuration url for the gateway with class name
	 * $gateway_class_name
	 *
	 * Temporary home for this function, until all payment gateways are brought into the frameworked fold
	 *
	 * @since 3.0.0
	 * @param string $gateway_class_name the gateway class name
	 * @return string admin configuration url for the gateway
	 */
	public static function get_payment_gateway_configuration_url( $gateway_class_name ) {

		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . strtolower( $gateway_class_name ) );
	}


	/**
	 * Returns true if the current page is the admin configuration page for the
	 * gateway with class name $gateway_class_name
	 *
	 * Temporary home for this function, until all payment gateways are brought into the frameworked fold
	 *
	 * @since 3.0.0
	 * @param string $gateway_class_name the gateway class name
	 * @return boolean true if the current page is the admin configuration page for the gateway
	 */
	public static function is_payment_gateway_configuration_page( $gateway_class_name ) {

		return 'wc-settings' == SV_WC_Helper::get_request( 'page' ) &&
			'checkout' == SV_WC_Helper::get_request( 'tab' ) &&
			strtolower( $gateway_class_name ) == SV_WC_Helper::get_request( 'section' );
	}


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
	 * @since 4.0.0-beta
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
	 * @since 4.0.0-beta
	 * @param string $payment_type the card or bank type, ie 'mc', 'amex', 'checking'
	 * @return string the card or bank account name, ie 'MasterCard', 'American Express', 'Checking Account'
	 */
	public static function payment_type_to_name( $payment_type ) {

		$name = '';
		$type = strtolower( $payment_type );

		// special cases
		switch ( $type ) {

			case 'mc':         $name = _x( 'MasterCard', 'credit card type', 'sv-wc-plugin-framework' );          break;
			case 'amex':       $name = _x( 'American Express', 'credit card type', 'sv-wc-plugin-framework' );    break;
			case 'disc':       $name = _x( 'Discover', 'credit card type', 'sv-wc-plugin-framework' );            break;
			case 'jcb':        $name = _x( 'JCB', 'credit card type', 'sv-wc-plugin-framework' );                 break;
			case 'cartebleue': $name = _x( 'CarteBleue', 'credit card type', 'sv-wc-plugin-framework' );          break;
			case 'paypal':     $name = __( 'PayPal', 'sv-wc-plugin-framework' );                                  break;
			case 'checking':   $name = __( 'Checking Account', 'sv-wc-plugin-framework' );                        break;
			case 'savings':    $name = __( 'Savings Account', 'sv-wc-plugin-framework' );                         break;
			case 'card':       $name = __( 'Credit / Debit Card', 'sv-wc-plugin-framework' );                     break;
			case 'bank':       $name = __( 'Bank Account', 'sv-wc-plugin-framework' );                            break;
			case '':           $name = _x( 'Account', 'payment method type', 'sv-wc-plugin-framework' );          break;
		}

		// default: replace dashes with spaces and uppercase all words
		if ( ! $name ) {
			$name = ucwords( str_replace( '-', ' ', $type ) );
		}

		return apply_filters( 'wc_payment_gateway_payment_type_to_name', $name, $type );
	}


}

endif; // Class exists check
