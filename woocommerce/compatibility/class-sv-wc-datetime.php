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
 * @package   SkyVerge/WooCommerce/Compatibility
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2022, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_12;

use DateTimeZone;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_12\\SV_WC_DateTime' ) ) :


/**
 * Extends the DateTime object for backwards compatibility.
 *
 * @since 4.6.0
 * @deprecated 5.5.0
 */
class SV_WC_DateTime extends \DateTime {


	/**
	 * SV_WC_DateTime constructor.
	 *
	 * @since 5.5.0
	 * @deprecated 5.5.0
	 *
	 * @param string $time
	 * @param \DateTimeZone|null $timezone
	 * @throws \Exception
	 */
	public function __construct( $time = 'now', \DateTimeZone $timezone = null ) {

		wc_deprecated_function( 'SV_WC_DateTime', '5.5.0', \DateTime::class );

		parent::__construct( $time, $timezone );
	}


	/**
	 * Outputs an ISO 8601 date string in local timezone.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @return string
	 */
	public function __toString() {

		wc_deprecated_function( __METHOD__, '5.5.0', 'DateTime::format( DATE_ATOM )' );

		return $this->format( DATE_ATOM );
	}


	/**
	 * Gets the UTC timestamp.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @return int
	 */
	public function getTimestamp() {

		wc_deprecated_function( __METHOD__, '5.5.0', 'DateTime::getTimestamp()' );

		return parent::getTimestamp();
	}


	/**
	 * Gets the timestamp with the WordPress timezone offset added or subtracted.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @return int
	 */
	public function getOffsetTimestamp() {

		wc_deprecated_function( __METHOD__, '5.5.0', 'DateTime::getOffset()' );

		return $this->getTimestamp() + $this->getOffset();
	}


	/**
	 * Gets a date based on the offset timestamp.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param string $format date format
	 * @return string
	 */
	public function date( $format ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'gmdate()' );

		return gmdate( $format, $this->getOffsetTimestamp() );
	}


	/**
	 * Gets a localised date based on offset timestamp.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param string $format date format
	 * @return string
	 */
	public function date_i18n( $format = 'Y-m-d' ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'date_i18n()' );

		return date_i18n( $format, $this->getOffsetTimestamp() );
	}


}


endif;
