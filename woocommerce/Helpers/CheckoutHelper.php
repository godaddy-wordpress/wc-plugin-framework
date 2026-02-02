<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_0_0\Helpers;

class CheckoutHelper
{
	/**
	 * Determines whether the provided country code is allowed to place an order.
	 *
	 * @since 6.0.1
	 *
	 * @param string $countryCode recommended to pass through the *billing* address
	 * @return bool
	 */
	public static function isCountryAllowedToOrder(string $countryCode): bool
	{
		if (empty($countryCode)) {
			return false;
		}

		if (WC() && WC()->countries) {
			$allowed_countries = WC()->countries->get_allowed_countries();

			return array_key_exists($countryCode, $allowed_countries);
		}

		return true;
	}

	/**
	 * Determines whether the provided country code is allowed for shipping.
	 *
	 * @since 6.0.1
	 *
	 * @param string $countryCode recommended to pass through the *shipping* address
	 * @return bool
	 */
	public static function isCountryAllowedForShipping(string $countryCode): bool
	{
		if (empty($countryCode)) {
			return false;
		}

		if (WC() && WC()->countries) {
			$shipping_countries = WC()->countries->get_shipping_countries();

			return array_key_exists($countryCode, $shipping_countries);
		}

		return true;
	}
}
