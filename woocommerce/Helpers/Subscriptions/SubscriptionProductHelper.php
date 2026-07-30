<?php
/**
 * Helper class for detecting and reading subscription details from a product.
 *
 * Supports both the legacy WC Subscriptions product types (`subscription`, `variable-subscription`)
 * and the "subscription scheme" mechanism introduced by "All Products for Subscriptions" (APFS), which
 * WooCommerce Subscriptions 9.0 merged into core. Detection is gated by `is_callable()` checks against the
 * relevant classes/methods rather than a Subscriptions version check, since those classes are present whenever
 * the corresponding mechanism is available - either because WC Subscriptions 9.0+ bundles the scheme mechanism,
 * or because an older WC Subscriptions (7/8) has the standalone APFS plugin active alongside it.
 *
 * @package   SkyVerge/WooCommerce/Helpers
 * @since     6.4.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v6_3_0\Helpers\Subscriptions;

/**
 * SubscriptionProductHelper class
 *
 * @since 6.4.0
 */
class SubscriptionProductHelper {

	/**
	 * Determines whether a product has a subscription attached to it.
	 *
	 * True for legacy subscription product types, for cart/order items already contextualized by
	 * WC Subscriptions or APFS, and for products carrying one or more APFS subscription schemes.
	 *
	 * @since 6.4.0
	 *
	 * @param int|\WC_Product $product product or product ID
	 * @return bool
	 */
	public static function isSubscriptionProduct( $product ) : bool {

		$product = self::resolveProduct( $product );

		if ( ! $product ) {
			return false;
		}

		if ( is_callable( [ 'WC_Subscriptions_Product', 'is_subscription' ] ) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
			return true;
		}

		if ( is_callable( [ 'WCS_ATT_Product_Schemes', 'has_subscription_schemes' ] ) ) {
			return (bool) \WCS_ATT_Product_Schemes::has_subscription_schemes( $product );
		}

		return false;
	}


	/**
	 * Gets the subscription period for a product (e.g. `day`, `week`, `month`, `year`).
	 *
	 * @since 6.4.0
	 *
	 * @param int|\WC_Product $product product or product ID
	 * @return string
	 */
	public static function getSubscriptionPeriod( $product ) : string {

		$product = self::resolveProduct( $product );

		if ( ! $product ) {
			return '';
		}

		if ( is_callable( [ 'WC_Subscriptions_Product', 'is_subscription' ] ) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
			return (string) \WC_Subscriptions_Product::get_period( $product );
		}

		$scheme = self::resolveEffectiveScheme( $product );

		return $scheme ? (string) $scheme->get_period() : '';
	}


	/**
	 * Gets the subscription period interval for a product.
	 *
	 * @since 6.4.0
	 *
	 * @param int|\WC_Product $product product or product ID
	 * @return int
	 */
	public static function getSubscriptionPeriodInterval( $product ) : int {

		$product = self::resolveProduct( $product );

		if ( ! $product ) {
			return 1;
		}

		if ( is_callable( [ 'WC_Subscriptions_Product', 'is_subscription' ] ) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
			return (int) \WC_Subscriptions_Product::get_interval( $product );
		}

		$scheme = self::resolveEffectiveScheme( $product );

		return $scheme ? (int) $scheme->get_interval() : 1;
	}


	/**
	 * Gets the subscription length for a product, or 0 if it continues for perpetuity (or isn't a subscription).
	 *
	 * @since 6.4.0
	 *
	 * @param int|\WC_Product $product product or product ID
	 * @return int
	 */
	public static function getSubscriptionLength( $product ) : int {

		$product = self::resolveProduct( $product );

		if ( ! $product ) {
			return 0;
		}

		if ( is_callable( [ 'WC_Subscriptions_Product', 'is_subscription' ] ) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
			return (int) \WC_Subscriptions_Product::get_length( $product );
		}

		$scheme = self::resolveEffectiveScheme( $product );

		return $scheme ? (int) $scheme->get_length() : 0;
	}


	/**
	 * Gets the subscription trial length for a product, or 0 if there is no trial.
	 *
	 * @since 6.4.0
	 *
	 * @param int|\WC_Product $product product or product ID
	 * @return int
	 */
	public static function getSubscriptionTrialLength( $product ) : int {

		$product = self::resolveProduct( $product );

		if ( ! $product ) {
			return 0;
		}

		if ( is_callable( [ 'WC_Subscriptions_Product', 'is_subscription' ] ) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
			return (int) \WC_Subscriptions_Product::get_trial_length( $product );
		}

		$scheme = self::resolveEffectiveScheme( $product );

		return $scheme ? (int) $scheme->get_trial_length() : 0;
	}


	/**
	 * Gets the subscription trial period for a product (e.g. `day`, `week`, `month`, `year`).
	 *
	 * @since 6.4.0
	 *
	 * @param int|\WC_Product $product product or product ID
	 * @return string
	 */
	public static function getSubscriptionTrialPeriod( $product ) : string {

		$product = self::resolveProduct( $product );

		if ( ! $product ) {
			return '';
		}

		if ( is_callable( [ 'WC_Subscriptions_Product', 'is_subscription' ] ) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
			return (string) \WC_Subscriptions_Product::get_trial_period( $product );
		}

		$scheme = self::resolveEffectiveScheme( $product );

		return $scheme ? (string) $scheme->get_trial_period() : '';
	}


	/**
	 * Gets the subscription sign-up fee for a product, or 0.0 if there is none.
	 *
	 * @since 6.4.0
	 *
	 * @param int|\WC_Product $product product or product ID
	 * @return float
	 */
	public static function getSubscriptionSignUpFee( $product ) : float {

		$product = self::resolveProduct( $product );

		if ( ! $product ) {
			return 0.0;
		}

		if ( is_callable( [ 'WC_Subscriptions_Product', 'is_subscription' ] ) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
			return (float) \WC_Subscriptions_Product::get_sign_up_fee( $product );
		}

		$scheme = self::resolveEffectiveScheme( $product );

		return $scheme ? (float) $scheme->get_signup_fee() : 0.0;
	}


	/**
	 * Gets the date a product's subscription will expire, calculated from either the given date or now.
	 *
	 * For legacy subscription products this delegates to {@see \WC_Subscriptions_Product::get_expiration_date()}
	 * unchanged. For APFS scheme products, the same two-stage calculation (trial, then length) is replicated
	 * here using this helper's own scheme-aware getters, since the legacy method only ever reads flat legacy
	 * product meta and returns nothing useful for scheme-based products.
	 *
	 * @since 6.4.0
	 *
	 * @param int|\WC_Product $product product or product ID
	 * @param string $fromDate MySQL formatted date/time string to calculate from, or empty to use now
	 * @return string|int MySQL formatted date/time string, or 0 if there is no expiration
	 */
	public static function getSubscriptionExpirationDate( $product, string $fromDate = '' ) {

		$resolvedProduct = self::resolveProduct( $product );

		if ( ! $resolvedProduct ) {
			return 0;
		}

		if ( is_callable( [ 'WC_Subscriptions_Product', 'is_subscription' ] ) && \WC_Subscriptions_Product::is_subscription( $resolvedProduct ) ) {
			return \WC_Subscriptions_Product::get_expiration_date( $resolvedProduct, $fromDate );
		}

		if ( ! function_exists( 'wcs_add_time' ) || ! function_exists( 'wcs_date_to_time' ) ) {
			return 0;
		}

		$length = self::getSubscriptionLength( $resolvedProduct );

		if ( $length <= 0 ) {
			return 0;
		}

		if ( empty( $fromDate ) ) {
			$fromDate = gmdate( 'Y-m-d H:i:s' );
		}

		$trialLength = self::getSubscriptionTrialLength( $resolvedProduct );

		if ( $trialLength > 0 ) {
			$fromDate = gmdate( 'Y-m-d H:i:s', wcs_add_time( $trialLength, self::getSubscriptionTrialPeriod( $resolvedProduct ), wcs_date_to_time( $fromDate ) ) );
		}

		return gmdate( 'Y-m-d H:i:s', wcs_add_time( $length, self::getSubscriptionPeriod( $resolvedProduct ), wcs_date_to_time( $fromDate ) ) );
	}


	/**
	 * Normalizes a product ID or object into a product instance.
	 *
	 * @since 6.4.0
	 *
	 * @param int|\WC_Product $product product or product ID
	 * @return \WC_Product|null
	 */
	private static function resolveProduct( $product ) : ?\WC_Product {

		if ( $product instanceof \WC_Product ) {
			return $product;
		}

		$resolved = wc_get_product( $product );

		return $resolved instanceof \WC_Product ? $resolved : null;
	}


	/**
	 * Resolves the APFS subscription scheme that should be used to read period/length/trial/fee details from.
	 *
	 * Prefers the scheme currently active on the product object (e.g. set via cart/order context), falls back
	 * to the product's default scheme, and finally to its "base" (lowest-price) scheme. Returns null if the
	 * product has no schemes, or if APFS isn't available.
	 *
	 * No return type hint is declared here, in order to avoid a hard reference to the optional `WCS_ATT_Scheme`
	 * class - callers only ever receive an instance when `WCS_ATT_Product_Schemes` has already been confirmed
	 * to exist.
	 *
	 * @since 6.4.0
	 *
	 * @param \WC_Product $product product
	 * @return object|null a `WCS_ATT_Scheme` instance, or null
	 */
	private static function resolveEffectiveScheme( \WC_Product $product ) {

		if ( ! is_callable( [ 'WCS_ATT_Product_Schemes', 'has_subscription_schemes' ] ) || ! \WCS_ATT_Product_Schemes::has_subscription_schemes( $product ) ) {
			return null;
		}

		$scheme = \WCS_ATT_Product_Schemes::get_subscription_scheme( $product, 'object' );

		if ( is_object( $scheme ) ) {
			return $scheme;
		}

		$scheme = \WCS_ATT_Product_Schemes::get_default_subscription_scheme( $product, 'object' );

		if ( is_object( $scheme ) ) {
			return $scheme;
		}

		$scheme = \WCS_ATT_Product_Schemes::get_base_subscription_scheme( $product );

		return is_object( $scheme ) ? $scheme : null;
	}

}
