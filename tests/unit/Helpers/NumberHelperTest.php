<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\Unit\Helpers;

use Exception;
use Generator;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Helpers\NumberHelper;
use SkyVerge\WooCommerce\PluginFramework\v5_15_11\Tests\TestCase;
use stdClass;
use WP_Mock;

/**
 * @coversDefaultClass \SkyVerge\WooCommerce\PluginFramework\v5_15_11\Helpers\NumberHelper
 */
final class NumberHelperTest extends TestCase
{
	/**
	 * @covers ::format()
	 * @dataProvider providerCanFormat
	 */
	public function testCanFormat($number, string $expected) : void
	{
		$this->assertSame($expected, NumberHelper::format($number));
	}

	/** @see testCanFormat */
	public function providerCanFormat(): Generator
	{
		yield '5' => [
			'number' => 5,
			'expected' => '5.00',
		];

		yield '10.5' => [
			'number' => 10.5,
			'expected' => '10.50',
		];

		yield '1,350.66' => [
			'number' => 1350.66,
			'expected' => '1350.66',
		];
	}

	/**
	 * @covers ::isCommaDecimalSeparatedNumber()
	 * @dataProvider providerCanDetermineIsCommaDecimalSeparatedValue
	 *
	 * @param string $value
	 * @param string $wcDecimalSeparator
	 * @param bool $expectedReturnValue
	 * @return void
	 */
	public function testCanDetermineIsCommaDecimalSeparatedValue(string $value, string $wcDecimalSeparator, bool $expectedReturnValue): void
	{
		WP_Mock::userFunction('wc_get_price_decimal_separator')
			->once()
			->andReturn($wcDecimalSeparator);

		$this->assertSame($expectedReturnValue, NumberHelper::isCommaDecimalSeparatedNumber($value));
	}

	/** @see testCanDetermineIsCommaDecimalSeparatedValue */
	public function providerCanDetermineIsCommaDecimalSeparatedValue(): Generator
	{
		yield 'USD style with decimal separator' => [
			'value' => '10.50',
			'wcDecimalSeparator' => '.',
			'expectedReturnValue' => false,
		];

		yield 'USD style with decimal separator, with thousands' => [
			'value' => '1,100.50',
			'wcDecimalSeparator' => '.',
			'expectedReturnValue' => false,
		];

		yield 'USD style with comma separator' => [
			'value' => '10.50',
			'wcDecimalSeparator' => ',',
			'expectedReturnValue' => false,
		];

		yield 'EUR style with decimal separator' => [
			'value' => '10,50',
			'wcDecimalSeparator' => '.',
			'expectedReturnValue' => false,
		];

		yield 'EUR style with comma separator' => [
			'value' => '10,50',
			'wcDecimalSeparator' => ',',
			'expectedReturnValue' => true,
		];

		yield 'EUR style with comma separator, less than 1' => [
			'value' => '0,50',
			'wcDecimalSeparator' => ',',
			'expectedReturnValue' => true,
		];

		yield 'EUR style with comma separator, with thousands' => [
			'value' => '3.500,60',
			'wcDecimalSeparator' => ',',
			'expectedReturnValue' => true,
		];

		yield 'EUR style with comma separator, with extra decimals' => [
			'value' => '3.500,633333',
			'wcDecimalSeparator' => ',',
			'expectedReturnValue' => true,
		];
	}

	/**
	 * @covers ::isPeriodDecimalSeparatedNumber()
	 * @dataProvider providerCanDetermineIsPeriodDecimalSeparatedValue
	 *
	 * @param string $value
	 * @param string $wcDecimalSeparator
	 * @param bool $expectedReturnValue
	 * @return void
	 * @throws Exception
	 */
	public function testCanDetermineIsPeriodDecimalSeparatedValue(string $value, string $wcDecimalSeparator, bool $expectedReturnValue): void
	{
		WP_Mock::userFunction('wc_get_price_decimal_separator')
			->once()
			->andReturn($wcDecimalSeparator);

		$this->assertSame($expectedReturnValue, NumberHelper::isPeriodDecimalSeparatedNumber($value));
	}

	/** @see testCanDetermineIsPeriodDecimalSeparatedValue */
	public function providerCanDetermineIsPeriodDecimalSeparatedValue(): Generator
	{
		yield 'USD style with decimal separator, no decimals' => [
			'value' => '1,000',
			'wcDecimalSeparator' => '.',
			'expectedReturnValue' => true,
		];

		yield 'USD style with decimal separator, with decimals' => [
			'value' => '1000.50',
			'wcDecimalSeparator' => '.',
			'expectedReturnValue' => true,
		];

		yield 'USD style with decimal separator, with extra decimals' => [
			'value' => '1.33333333333',
			'wcDecimalSeparator' => '.',
			'expectedReturnValue' => true,
		];

		yield 'USD style with comma separator' => [
			'value' => '1.50',
			'wcDecimalSeparator' => ',',
			'expectedReturnValue' => false,
		];

		yield 'EUR style with comma separator' => [
			'value' => '1,50',
			'wcDecimalSeparator' => ',',
			'expectedReturnValue' => false,
		];

		yield 'EUR style with comma separator, with thousands' => [
			'value' => '5.100,50',
			'wcDecimalSeparator' => ',',
			'expectedReturnValue' => false,
		];
	}

	/**
	 * @covers ::convertNumberToFloatValue()
	 * @dataProvider providerCanNormalizeFloatValues
	 *
	 * @param mixed $inputValue
	 * @param string $wcDecimalSeparator
	 * @param string $wcThousandSeparator
	 * @param float $expectedReturnValue
	 * @return void
	 */
	public function testCanNormalizeFloatValues($inputValue, string $wcDecimalSeparator, string $wcThousandSeparator, float $expectedReturnValue): void
	{
		WP_Mock::userFunction('wc_get_price_decimal_separator')
			->zeroOrMoreTimes()
			->andReturn($wcDecimalSeparator);

		WP_Mock::userFunction('wc_get_price_thousand_separator')
			->zeroOrMoreTimes()
			->andReturn($wcThousandSeparator);

		$this->assertSame(
			$expectedReturnValue,
			NumberHelper::convertNumberToFloatValue($inputValue)
		);
	}

	/** @see testCanNormalizeFloatValues */
	public function providerCanNormalizeFloatValues(): Generator
	{
		yield 'value is a float' => [
			'inputValue' => 50.99,
			'wcDecimalSeparator' => '.',
			'wcThousandSeparator' => ',',
			'expectedReturnValue' => 50.99,
		];

		yield 'value is a USD string, USD settings' => [
			'inputValue' => '50.99',
			'wcDecimalSeparator' => '.',
			'wcThousandSeparator' => ',',
			'expectedReturnValue' => 50.99,
		];

		yield 'value is a USD string, less than 1, USD settings' => [
			'inputValue' => '0.99',
			'wcDecimalSeparator' => '.',
			'wcThousandSeparator' => ',',
			'expectedReturnValue' => 0.99,
		];

		yield 'value is a USD string, with thousands, USD settings' => [
			'inputValue' => '1,500.99',
			'wcDecimalSeparator' => '.',
			'wcThousandSeparator' => ',',
			'expectedReturnValue' => 1500.99,
		];

		yield 'value is a USD string, EUR settings' => [
			'inputValue' => '50.99',
			'wcDecimalSeparator' => ',',
			'wcThousandSeparator' => '.',
			'expectedReturnValue' => 50.99,
		];

		yield 'value is a EUR string, EUR settings' => [
			'inputValue' => '50,99',
			'wcDecimalSeparator' => ',',
			'wcThousandSeparator' => '.',
			'expectedReturnValue' => 50.99,
		];

		yield 'value is a EUR string, less than 1, EUR settings' => [
			'inputValue' => '0,99',
			'wcDecimalSeparator' => ',',
			'wcThousandSeparator' => '.',
			'expectedReturnValue' => .99,
		];

		yield 'value is a EUR string, with thousands, EUR settings' => [
			'inputValue' => '1.500,99',
			'wcDecimalSeparator' => ',',
			'wcThousandSeparator' => '.',
			'expectedReturnValue' => 1500.99,
		];

		yield 'value is completely non-numeric' => [
			'inputValue' => new stdClass(),
			'wcDecimalSeparator' => ',',
			'wcThousandSeparator' => '.',
			'expectedReturnValue' => 0.0,
		];
	}

	/**
	 * @covers ::wcPrice()
	 * @throws Exception
	 */
	public function testWcPrice(): void
	{
		$inputPrice = 5.50;

		$this->mockStaticMethod(NumberHelper::class, 'convertNumberToFloatValue')
			->once()
			->with($inputPrice)
			->andReturnArg(0);

		WP_Mock::userFunction('wc_price')
			->once()
			->with($inputPrice)
			->andReturn('<div>$5.50</div>');

		$this->assertSame('$5.50', NumberHelper::wcPrice($inputPrice));
	}

	/**
	 * @covers ::wcPrice()
	 * @throws Exception
	 */
	public function testWcPriceWhenFree(): void
	{
		$this->mockStaticMethod(NumberHelper::class, 'convertNumberToFloatValue')
			->once()
			->with(0.00)
			->andReturnArg(0);

		$this->assertSame('Free!', NumberHelper::wcPrice(0.00));
	}
}
