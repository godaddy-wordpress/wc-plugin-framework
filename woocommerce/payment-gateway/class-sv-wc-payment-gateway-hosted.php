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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2014, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'SV_WC_Payment_Gateway_Hosted' ) ) :

/**
 * # WooCommerce Payment Gateway Framework Hosted Gateway
 *
 * @since 1.0
 */
abstract class SV_WC_Payment_Gateway_Hosted extends SV_WC_Payment_Gateway {


	/**
	 * Display the payment fields on the checkout page
	 *
	 * @since 0.1
	 * @see WC_Payment_Gateway::payment_fields()
	 */
	public function payment_fields() {

		parent::payment_fields();
		?><style type="text/css">#payment ul.payment_methods li label[for='payment_method_<?php echo $this->get_id(); ?>'] img:nth-child(n+2) { margin-left:1px; }</style><?php
	}


	/**
	 * Process the payment by redirecting customer to the pay page
	 *
	 * @since 0.1
	 * @see WC_Payment_Gateway::process_payment()
	 * @param int $order_id the order to process
	 * @return array with keys 'result' and 'redirect'
	 */
	public function process_payment( $order_id ) {

		// setup order
		$order = new WC_Order( $order_id );

		SV_WC_Plugin_Compatibility::WC()->cart->empty_cart();

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}


	/** Getters ******************************************************/


	/**
	 * Returns true if this is a hosted type gateway
	 *
	 * @since 1.0
	 * @return boolean if this is a hosted payment gateway
	 */
	public function is_hosted_gateway() {
		return true;
	}

}

endif;  // class exists check
