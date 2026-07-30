<?php

/**
 * Minimal WC_Subscriptions_Product stub for unit tests.
 *
 * The real class is provided by the WooCommerce Subscriptions plugin, which is not available in the
 * WP_Mock test environment. Tests configure the public static properties directly to control the
 * return values of each method for a given scenario.
 */
class WC_Subscriptions_Product {

	public static $isSubscription = false;
	public static $period = '';
	public static $interval = 1;
	public static $length = 0;
	public static $trialLength = 0;
	public static $trialPeriod = '';
	public static $signUpFee = 0;
	public static $expirationDate = 0;

	public static function is_subscription( $product ) {
		return self::$isSubscription;
	}

	public static function get_period( $product ) {
		return self::$period;
	}

	public static function get_interval( $product ) {
		return self::$interval;
	}

	public static function get_length( $product ) {
		return self::$length;
	}

	public static function get_trial_length( $product ) {
		return self::$trialLength;
	}

	public static function get_trial_period( $product ) {
		return self::$trialPeriod;
	}

	public static function get_sign_up_fee( $product ) {
		return self::$signUpFee;
	}

	public static function get_expiration_date( $product, $from_date = '' ) {
		return self::$expirationDate;
	}

	/** Resets all configurable state back to its default (non-subscription) values. */
	public static function reset() {
		self::$isSubscription = false;
		self::$period         = '';
		self::$interval       = 1;
		self::$length         = 0;
		self::$trialLength    = 0;
		self::$trialPeriod    = '';
		self::$signUpFee      = 0;
		self::$expirationDate = 0;
	}
}
