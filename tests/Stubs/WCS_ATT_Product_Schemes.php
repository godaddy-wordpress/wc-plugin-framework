<?php

/**
 * Minimal WCS_ATT_Product_Schemes stub for unit tests.
 *
 * The real class is provided by WooCommerce Subscriptions' bundled ("All Products for Subscriptions")
 * scheme mechanism, which is not available in the WP_Mock test environment. Tests configure the public
 * static properties directly to control the return values of each method for a given scenario.
 */
class WCS_ATT_Product_Schemes {

	public static $hasSchemes = false;
	public static $activeScheme = null;
	public static $defaultScheme = null;
	public static $baseScheme = null;

	public static function has_subscription_schemes( $product, $context = 'any' ) {
		return self::$hasSchemes;
	}

	public static function get_subscription_scheme( $product, $return = 'key', $scheme_key = '' ) {
		return self::$activeScheme;
	}

	public static function get_default_subscription_scheme( $product, $return = 'key' ) {
		return self::$defaultScheme;
	}

	public static function get_base_subscription_scheme( $product ) {
		return self::$baseScheme;
	}

	/** Resets all configurable state back to its default (no schemes) values. */
	public static function reset() {
		self::$hasSchemes    = false;
		self::$activeScheme  = null;
		self::$defaultScheme = null;
		self::$baseScheme    = null;
	}
}
