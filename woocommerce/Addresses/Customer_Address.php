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
 * @copyright Copyright (c) 2013-2022, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_12\Addresses;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_12\\Addresses\\Customer_Address' ) ) :


/**
 * The customer address data class.
 *
 * Adds customer-specific data to a base address, as used for a billing or shipping address that can include first and last name.
 *
 * @since 5.3.0
 */
class Customer_Address extends Address {


	/** @var string customer first name */
	protected $first_name = '';

	/** @var string customer last name */
	protected $last_name = '';


	/** Getter Methods ************************************************************************************************/


	/**
	 * Gets the customer first name.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_first_name() {

		return $this->first_name;
	}


	/**
	 * Gets the customer first name.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_last_name() {

		return $this->last_name;
	}


	/**
	 * Gets the data used to generate a hash for the address.
	 *
	 * @see Address::get_hash_data()
	 *
	 * @since 5.3.0
	 *
	 * @return string[]
	 */
	protected function get_hash_data() {

		// add the first & last name to data used to generate the hash
		return array_merge( [
			$this->get_first_name(),
			$this->get_last_name(),
		], parent::get_hash_data() );
	}


	/** Setter Methods ************************************************************************************************/


	/**
	 * Sets the customer first name.
	 *
	 * @since 5.3.0
	 *
	 * @param string $value first name value
	 */
	public function set_first_name( $value ) {

		$this->first_name = $value;
	}


	/**
	 * Sets the customer last name.
	 *
	 * @since 5.3.0
	 *
	 * @param string $value first name value
	 */
	public function set_last_name( $value ) {

		$this->last_name = $value;
	}


	/**
	 * Sets the full address based on a WooCommerce order.
	 *
	 * @since 5.3.0
	 *
	 * @param \WC_Order $order WooCommerce order object
	 * @param string $type address type, like billing or shipping
	 */
	public function set_from_order( \WC_Order $order, $type = 'billing' ) {

		$this->set_first_name( $order->{"get_{$type}_first_name"}() );
		$this->set_last_name( $order->{"get_{$type}_last_name"}() );
		$this->set_line_1( $order->{"get_{$type}_address_1"}() );
		$this->set_line_2( $order->{"get_{$type}_address_2"}() );
		$this->set_locality( $order->{"get_{$type}_city"}() );
		$this->set_region( $order->{"get_{$type}_state"}() );
		$this->set_country( $order->{"get_{$type}_country"}() );
		$this->set_postcode( $order->{"get_{$type}_postcode"}() );
	}


}


endif;
