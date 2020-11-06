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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/API
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_0;

defined( 'ABSPATH' ) or exit;

if ( ! interface_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_0\\SV_WC_Payment_Gateway_Payment_Notification_Tokenization_Response' ) ) :


/**
 * WooCommerce Payment Gateway API Payment Credit Card Notification Response
 *
 * Represents an IPN or redirect-back credit card request response
 *
 * @since 2.2.0
 */
interface SV_WC_Payment_Gateway_Payment_Notification_Tokenization_Response extends SV_WC_Payment_Gateway_API_Create_Payment_Token_Response {


	/** Response Message Methods **********************************************/


	/**
	 * Gets the overall result message for a new payment method tokenization
	 * and/or customer creation.
	 *
	 * @since 5.0.0
	 *
	 * @return string
	 */
	public function get_tokenization_message();


	/**
	 * Gets the result message for a new customer creation.
	 *
	 * @since 5.0.0
	 *
	 * @return string
	 */
	public function get_customer_created_message();


	/**
	 * Gets the result message for a new payment method tokenization.
	 *
	 * @since 5.0.0
	 *
	 * @return string
	 */
	public function get_payment_method_tokenized_message();


	/** Response Code Methods *************************************************/


	/**
	 * Gets the result code for a new customer creation.
	 *
	 * @since 5.0.0
	 *
	 * @return string
	 */
	public function get_customer_created_code();


	/**
	 * Gets the result code for a new payment method tokenization.
	 *
	 * @since 5.0.0
	 *
	 * @return string
	 */
	public function get_payment_method_tokenized_code();


	/**
	 * Determines whether a new customer was created.
	 *
	 * @since 5.0.0
	 *
	 * @return bool
	 */
	public function customer_created();


	/**
	 * Determines whether a new payment method was tokenized.
	 *
	 * @since 5.0.0
	 *
	 * @return bool
	 */
	public function payment_method_tokenized();


	/**
	 * Determines whether the overall payment tokenization was successful.
	 *
	 * Gateways can check that the payment method was tokenized, and if a new
	 * customer was created, that was successful.
	 *
	 * @since 5.0.0
	 *
	 * @return bool
	 */
	public function tokenization_successful();


	/**
	 * Determines whether the customer was successfully created.
	 *
	 * @since 5.0.0
	 *
	 * @return bool
	 */
	public function customer_creation_successful();


	/**
	 * Determines whether the payment method was successfully tokenized.
	 *
	 * @since 5.0.0
	 *
	 * @return bool
	 */
	public function payment_method_tokenization_successful();


	/**
	 * Gets any payment tokens that were edited on the hosted pay page.
	 *
	 * @since 5.0.0
	 *
	 * @return array|SV_WC_Payment_Gateway_Payment_Token[]
	 */
	public function get_edited_payment_tokens();


	/**
	 * Gets any payment tokens that were deleted on the hosted pay page.
	 *
	 * @since 5.0.0
	 *
	 * @return array|SV_WC_Payment_Gateway_Payment_Token[]
	 */
	public function get_deleted_payment_tokens();


}


endif;
