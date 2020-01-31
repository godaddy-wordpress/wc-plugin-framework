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
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_5_4\Addresses;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_5_4\\Addresses\\Address' ) ) :


/**
 * The base address data class.
 *
 * This serves as a standard address object to be passed around by plugins whenever dealing with address data.
 * Eliminates the need to rely on WooCommerce's address arrays.
 *
 * @since 5.3.0
 */
class Address {


	/** @var string line 1 of the street address */
	protected $line_1 = '';

	/** @var string line 2 of the street address */
	protected $line_2 = '';

	/** @var string line 3 of the street address */
	protected $line_3 = '';

	/** @var string address locality (city) */
	protected $locality = '';

	/** @var string address region (state) */
	protected $region = '';

	/** @var string address country */
	protected $country = '';

	/** @var string address postcode */
	protected $postcode = '';


	/** Getter methods ************************************************************************************************/


	/**
	 * Gets line 1 of the street address.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_line_1() {

		return $this->line_1;
	}


	/**
	 * Gets line 2 of the street address.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_line_2() {

		return $this->line_2;
	}


	/**
	 * Gets line 3 of the street address.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_line_3() {

		return $this->line_3;
	}


	/**
	 * Gets the locality or city.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_locality() {

		return $this->locality;
	}


	/**
	 * Gets the region or state.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_region() {

		return $this->region;
	}


	/**
	 * Gets the country.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_country() {

		return $this->country;
	}


	/**
	 * Gets the postcode.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_postcode() {

		return $this->postcode;
	}


	/**
	 * Gets the hash representation of this address.
	 *
	 * @see Address::get_hash_data()
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_hash() {

		return md5( json_encode( $this->get_hash_data() ) );
	}


	/**
	 * Gets the data used to generate a hash for the address.
	 *
	 * @since 5.3.0
	 *
	 * @return string[]
	 */
	protected function get_hash_data() {

		return [
			$this->get_line_1(),
			$this->get_line_2(),
			$this->get_line_3(),
			$this->get_locality(),
			$this->get_region(),
			$this->get_country(),
			$this->get_postcode(),
		];
	}


	/** Setter methods ************************************************************************************************/


	/**
	 * Sets line 1 of the street address.
	 *
	 * @since 5.3.0
	 *
	 * @param string $value line 1 value
	 */
	public function set_line_1( $value ) {

		$this->line_1 = $value;
	}


	/**
	 * Sets line 2 of the street address.
	 *
	 * @since 5.3.0
	 *
	 * @param string $value line 2 value
	 */
	public function set_line_2( $value ) {

		$this->line_2 = $value;
	}


	/**
	 * Gets line 3 of the street address.
	 *
	 * @since 5.3.0
	 *
	 * @param string $value line 3 value
	 */
	public function set_line_3( $value ) {

		$this->line_3 = $value;
	}


	/**
	 * Gets the locality or city.
	 *
	 * @since 5.3.0
	 *
	 * @param string $value locality value
	 */
	public function set_locality( $value ) {

		$this->locality = $value;
	}


	/**
	 * Gets the region or state.
	 *
	 * @since 5.3.0
	 *
	 * @param string $value region value
	 */
	public function set_region( $value ) {

		$this->region = $value;
	}


	/**
	 * Sets the country.
	 *
	 * @since 5.3.0
	 *
	 * @param string $value country value
	 */
	public function set_country( $value ) {

		$this->country = $value;
	}


	/**
	 * Sets the postcode.
	 *
	 * @since 5.3.0
	 *
	 * @param string $value postcode value
	 */
	public function set_postcode( $value ) {

		$this->postcode = $value;
	}


}


endif;
