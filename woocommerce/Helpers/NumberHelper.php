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
 * @copyright Copyright (c) 2013-2024, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_9\Helpers;

class NumberHelper
{
	/**
	 * Format a number with 2 decimal points, using a period for the decimal
	 * separator and no thousands separator.
	 *
	 * Commonly used for payment gateways which require amounts in this format.
	 *
	 * @since 5.15.0
	 *
	 * @param float|mixed $number
	 * @return string
	 */
	public static function format($number) : string
	{
		return number_format((float) $number, 2, '.', '');
	}

	/**
	 * Determines whether or not the provided value is formatted with a comma as the decimal.
	 *
	 * This will only return `true` if the store settings are also configured to use a comma as the separator.
	 *
	 * @since 5.15.0
	 *
	 * @param mixed $number
	 * @return bool
	 */
	public static function isCommaDecimalSeparatedNumber($number) : bool
	{
		if (!is_string($number)) {
			return false;
		}

		/*
		 * Regex format:
		 *  - optional: any number of digits, followed by a period;
		 *  - Then any number of digits, followed by a comma;
		 *  - Followed by any number of digits (these are the decimals)
		 *
		 * Valid examples:
		 * 0,60
		 * 51,60
		 * 51,63333
		 * 3500,60
		 * 3.500,60
		 *
		 * We want to use the regex here because it's possible someone might have their settings configured with comma
		 * decimal separators, but still enter values using a period decimal separator.
		 */
		return ',' === wc_get_price_decimal_separator() &&
			preg_match('/(\d+\.?)?\d+(,\d+)$/', $number);
	}

	/**
	 * Determines whether or not the provided value is formatted with a period as the decimal.
	 *
	 * This will only return `true` if store settings are also configured to use a decimal as the separator.
	 *
	 * @since 5.15.0
	 *
	 * @param int|float|string $number
	 * @return bool
	 */
	public static function isPeriodDecimalSeparatedNumber($number) : bool
	{
		/*
		 * Regex format:
		 *  - Any number of digits, followed by a comma;
		 *  - Then 3 digits;
		 *  - Then optional: a period and any number of digits (these are the decimals)
		 *
		 * Valid examples:
		 * 1,000
		 * 12,000.50
		 * 12,000.5666666
		 *
		 * We're targeting US-style currency that still has the thousands separator inside.
		 */
		return '.' === wc_get_price_decimal_separator() &&
			preg_match('/(\d+,?)\d{3}(\.\d+)?$/', $number);
	}

	/**
	 * Accepts a number input and converts it to a float, for use in database storage or mathematical operations.
	 *
	 * For example: putting in a string `5,50` (number formatted European style) will be converted to float `5.50`.
	 *
	 * @since 5.15.0
	 *
	 * @param string|int|float $number
	 * @return float
	 */
	public static function convertNumberToFloatValue($number) : float
	{
		if (is_string($number)) {
			if (static::isCommaDecimalSeparatedNumber($number)) {

				$numberWithThousandsStripped = str_replace(wc_get_price_thousand_separator(), '', $number);

				// replace the comma decimal separator with a period
				$number = (float) str_replace(',', '.', $numberWithThousandsStripped);

			} else if (static::isPeriodDecimalSeparatedNumber($number)) {

				// just strip the thousands
				$number = str_replace(wc_get_price_thousand_separator(), '', $number);
			}
		}

		return is_numeric($number) ? (float) $number : 0.0;
	}

	/**
	 * Formats a given price for front-end display. This is the same as {@see wc_price()} but without the surrounding
	 * HTML markup.
	 *
	 * @since 5.15.0
	 *
	 * @param int|string|float $price
	 * @return string
	 */
	public static function wcPrice($price) : string
	{
		$price = static::convertNumberToFloatValue($price);

		if (0.00 === $price) {
			return __('Free!', 'woocommerce-plugin-framework');
		}

		return strip_tags(wc_price($price));
	}
}
