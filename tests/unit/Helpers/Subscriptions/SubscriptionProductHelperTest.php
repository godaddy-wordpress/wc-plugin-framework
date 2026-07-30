<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_3_0\Tests\Unit\Helpers\Subscriptions;

use Mockery;
use SkyVerge\WooCommerce\PluginFramework\v6_3_0\Helpers\Subscriptions\SubscriptionProductHelper;
use SkyVerge\WooCommerce\PluginFramework\v6_3_0\Tests\TestCase;
use WC_Subscriptions_Product;
use WCS_ATT_Product_Schemes;
use WCS_ATT_Scheme;
use WP_Mock;

/**
 * @coversDefaultClass \SkyVerge\WooCommerce\PluginFramework\v6_3_0\Helpers\Subscriptions\SubscriptionProductHelper
 */
final class SubscriptionProductHelperTest extends TestCase
{
	private const SECONDS_PER_DAY   = 86400;
	private const SECONDS_PER_WEEK  = 604800;
	private const SECONDS_PER_MONTH = 2592000;
	private const SECONDS_PER_YEAR  = 31536000;

	public function setUp() : void
	{
		parent::setUp();

		WC_Subscriptions_Product::reset();
		WCS_ATT_Product_Schemes::reset();
	}

	private function getProduct() : \WC_Product
	{
		return Mockery::mock('WC_Product');
	}

	private function mockTimeFunctions() : void
	{
		WP_Mock::userFunction('wcs_date_to_time')
			->andReturnUsing(static function (string $date) {
				return strtotime($date);
			});

		WP_Mock::userFunction('wcs_add_time')
			->andReturnUsing(static function (int $number, string $period, int $timestamp) {
				$secondsByPeriod = [
					'day'   => self::SECONDS_PER_DAY,
					'week'  => self::SECONDS_PER_WEEK,
					'month' => self::SECONDS_PER_MONTH,
					'year'  => self::SECONDS_PER_YEAR,
				];

				return $timestamp + ($number * ($secondsByPeriod[$period] ?? self::SECONDS_PER_DAY));
			});
	}

	/** @covers ::isSubscriptionProduct() */
	public function testIsNotSubscriptionProductWhenNeitherMechanismApplies() : void
	{
		$this->assertFalse(SubscriptionProductHelper::isSubscriptionProduct($this->getProduct()));
	}

	/** @covers ::isSubscriptionProduct() */
	public function testIsSubscriptionProductForLegacySubscriptionType() : void
	{
		WC_Subscriptions_Product::$isSubscription = true;

		$this->assertTrue(SubscriptionProductHelper::isSubscriptionProduct($this->getProduct()));
	}

	/** @covers ::isSubscriptionProduct() */
	public function testIsSubscriptionProductForApfsScheme() : void
	{
		WCS_ATT_Product_Schemes::$hasSchemes = true;

		$this->assertTrue(SubscriptionProductHelper::isSubscriptionProduct($this->getProduct()));
	}

	/** @covers ::getSubscriptionPeriod() */
	/** @covers ::getSubscriptionPeriodInterval() */
	/** @covers ::getSubscriptionLength() */
	/** @covers ::getSubscriptionTrialLength() */
	/** @covers ::getSubscriptionTrialPeriod() */
	/** @covers ::getSubscriptionSignUpFee() */
	public function testGettersReturnDefaultsWhenNotASubscription() : void
	{
		$product = $this->getProduct();

		$this->assertSame('', SubscriptionProductHelper::getSubscriptionPeriod($product));
		$this->assertSame(1, SubscriptionProductHelper::getSubscriptionPeriodInterval($product));
		$this->assertSame(0, SubscriptionProductHelper::getSubscriptionLength($product));
		$this->assertSame(0, SubscriptionProductHelper::getSubscriptionTrialLength($product));
		$this->assertSame('', SubscriptionProductHelper::getSubscriptionTrialPeriod($product));
		$this->assertSame(0.0, SubscriptionProductHelper::getSubscriptionSignUpFee($product));
	}

	/** @covers ::getSubscriptionPeriod() */
	/** @covers ::getSubscriptionPeriodInterval() */
	/** @covers ::getSubscriptionLength() */
	/** @covers ::getSubscriptionTrialLength() */
	/** @covers ::getSubscriptionTrialPeriod() */
	/** @covers ::getSubscriptionSignUpFee() */
	public function testGettersDelegateToLegacySubscriptionsProductForLegacyType() : void
	{
		WC_Subscriptions_Product::$isSubscription = true;
		WC_Subscriptions_Product::$period         = 'month';
		WC_Subscriptions_Product::$interval       = 2;
		WC_Subscriptions_Product::$length         = 12;
		WC_Subscriptions_Product::$trialLength    = 1;
		WC_Subscriptions_Product::$trialPeriod    = 'week';
		WC_Subscriptions_Product::$signUpFee      = 9.99;

		// APFS schemes should be entirely ignored once the legacy type is detected.
		WCS_ATT_Product_Schemes::$hasSchemes   = true;
		WCS_ATT_Product_Schemes::$activeScheme = new WCS_ATT_Scheme(['period' => 'year', 'length' => 99]);

		$product = $this->getProduct();

		$this->assertSame('month', SubscriptionProductHelper::getSubscriptionPeriod($product));
		$this->assertSame(2, SubscriptionProductHelper::getSubscriptionPeriodInterval($product));
		$this->assertSame(12, SubscriptionProductHelper::getSubscriptionLength($product));
		$this->assertSame(1, SubscriptionProductHelper::getSubscriptionTrialLength($product));
		$this->assertSame('week', SubscriptionProductHelper::getSubscriptionTrialPeriod($product));
		$this->assertSame(9.99, SubscriptionProductHelper::getSubscriptionSignUpFee($product));
	}

	/** @covers ::getSubscriptionPeriod() */
	/** @covers ::getSubscriptionLength() */
	public function testGettersReadFromActiveApfsScheme() : void
	{
		WCS_ATT_Product_Schemes::$hasSchemes    = true;
		WCS_ATT_Product_Schemes::$activeScheme  = new WCS_ATT_Scheme(['period' => 'month', 'length' => 6]);
		WCS_ATT_Product_Schemes::$defaultScheme = new WCS_ATT_Scheme(['period' => 'year', 'length' => 1]);
		WCS_ATT_Product_Schemes::$baseScheme    = new WCS_ATT_Scheme(['period' => 'week', 'length' => 52]);

		$product = $this->getProduct();

		$this->assertSame('month', SubscriptionProductHelper::getSubscriptionPeriod($product));
		$this->assertSame(6, SubscriptionProductHelper::getSubscriptionLength($product));
	}

	/** @covers ::getSubscriptionPeriod() */
	/** @covers ::getSubscriptionLength() */
	public function testGettersFallBackToDefaultApfsSchemeWhenNoneIsActive() : void
	{
		WCS_ATT_Product_Schemes::$hasSchemes    = true;
		WCS_ATT_Product_Schemes::$activeScheme  = null;
		WCS_ATT_Product_Schemes::$defaultScheme = new WCS_ATT_Scheme(['period' => 'year', 'length' => 1]);
		WCS_ATT_Product_Schemes::$baseScheme    = new WCS_ATT_Scheme(['period' => 'week', 'length' => 52]);

		$product = $this->getProduct();

		$this->assertSame('year', SubscriptionProductHelper::getSubscriptionPeriod($product));
		$this->assertSame(1, SubscriptionProductHelper::getSubscriptionLength($product));
	}

	/** @covers ::getSubscriptionPeriod() */
	/** @covers ::getSubscriptionLength() */
	public function testGettersFallBackToBaseApfsSchemeWhenNoActiveOrDefault() : void
	{
		WCS_ATT_Product_Schemes::$hasSchemes    = true;
		WCS_ATT_Product_Schemes::$activeScheme  = null;
		WCS_ATT_Product_Schemes::$defaultScheme = null;
		WCS_ATT_Product_Schemes::$baseScheme    = new WCS_ATT_Scheme(['period' => 'week', 'length' => 52]);

		$product = $this->getProduct();

		$this->assertSame('week', SubscriptionProductHelper::getSubscriptionPeriod($product));
		$this->assertSame(52, SubscriptionProductHelper::getSubscriptionLength($product));
	}

	/** @covers ::getSubscriptionExpirationDate() */
	public function testExpirationDateDelegatesToLegacySubscriptionsProductForLegacyType() : void
	{
		WC_Subscriptions_Product::$isSubscription = true;
		WC_Subscriptions_Product::$expirationDate = '2027-01-01 00:00:00';

		$this->assertSame(
			'2027-01-01 00:00:00',
			SubscriptionProductHelper::getSubscriptionExpirationDate($this->getProduct())
		);
	}

	/** @covers ::getSubscriptionExpirationDate() */
	public function testExpirationDateIsZeroWhenApfsSchemeHasNoLength() : void
	{
		WCS_ATT_Product_Schemes::$hasSchemes   = true;
		WCS_ATT_Product_Schemes::$activeScheme = new WCS_ATT_Scheme(['period' => 'month', 'length' => 0]);

		$this->assertSame(0, SubscriptionProductHelper::getSubscriptionExpirationDate($this->getProduct()));
	}

	/** @covers ::getSubscriptionExpirationDate() */
	public function testExpirationDateIsCalculatedFromApfsSchemeLength() : void
	{
		$this->mockTimeFunctions();

		WCS_ATT_Product_Schemes::$hasSchemes   = true;
		WCS_ATT_Product_Schemes::$activeScheme = new WCS_ATT_Scheme(['period' => 'month', 'length' => 3]);

		$fromDate = '2027-01-01 00:00:00';
		$expected = gmdate('Y-m-d H:i:s', strtotime($fromDate) + (3 * self::SECONDS_PER_MONTH));

		$this->assertSame(
			$expected,
			SubscriptionProductHelper::getSubscriptionExpirationDate($this->getProduct(), $fromDate)
		);
	}

	/** @covers ::getSubscriptionExpirationDate() */
	public function testExpirationDateAccountsForApfsSchemeTrialBeforeLength() : void
	{
		$this->mockTimeFunctions();

		WCS_ATT_Product_Schemes::$hasSchemes   = true;
		WCS_ATT_Product_Schemes::$activeScheme = new WCS_ATT_Scheme([
			'period'       => 'month',
			'length'       => 3,
			'trial_length' => 2,
			'trial_period' => 'week',
		]);

		$fromDate         = '2027-01-01 00:00:00';
		$trialExpiration  = strtotime($fromDate) + (2 * self::SECONDS_PER_WEEK);
		$expected         = gmdate('Y-m-d H:i:s', $trialExpiration + (3 * self::SECONDS_PER_MONTH));

		$this->assertSame(
			$expected,
			SubscriptionProductHelper::getSubscriptionExpirationDate($this->getProduct(), $fromDate)
		);
	}
}
