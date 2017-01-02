<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Admin\Helper;

use FOF30\Container\Container;
use Akeeba\Subscriptions\Admin\Helper\Forex;

class ForexTest extends \PHPUnit_Framework_TestCase
{
	/** @var   Container  The container of the component */
	public static $container = null;

	/**
	 * Set up the static objects before the class is created
	 */
	public static function setUpBeforeClass()
	{
		if (is_null(static::$container))
		{
			static::$container = Container::getInstance('com_akeebasubs', [
				'platformClass' => 'Akeeba\\Subscriptions\\Tests\\Stubs\\CustomPlatform'
			]);
		}

		// Prime the component parameters
		static::$container->params->get('currency');
	}

	/**
	 * @covers Akeeba\Subscriptions\Admin\Helper\Forex::reloadCurrencyData
	 */
	public function testReloadCurrencyData_FirstRun()
	{
		// Reset the current cache
		$reflectionClass = new \ReflectionClass('Akeeba\Subscriptions\Admin\Helper\Forex');
		$refCountryCurrency = $reflectionClass->getProperty('countryCurrency');
		$refCountryCurrency->setAccessible(true);
		$refCountryCurrency->setValue([]);
		$refCurrencySymbols = $reflectionClass->getProperty('currencySymbols');
		$refCurrencySymbols->setAccessible(true);
		$refCurrencySymbols->setValue([]);

		Forex::reloadCurrencyData(false, self::$container);

		$actual = $refCountryCurrency->getValue();
		$this->assertNotEmpty($actual, 'Forex::$countryCurrency should not be empty on first load');
		$this->assertNotEmpty($refCurrencySymbols->getValue(), 'Forex::$currencySymbols should not be empty  on first load');
	}

	/**
	 * @covers Akeeba\Subscriptions\Admin\Helper\Forex::reloadCurrencyData
	 * @depends testReloadCurrencyData_FirstRun
	 */
	public function testReloadCurrencyData_NoReload()
	{
		$reflectionClass = new \ReflectionClass('Akeeba\Subscriptions\Admin\Helper\Forex');
		$refCountryCurrency = $reflectionClass->getProperty('countryCurrency');
		$refCountryCurrency->setAccessible(true);
		$refCurrencySymbols = $reflectionClass->getProperty('currencySymbols');
		$refCurrencySymbols->setAccessible(true);

		// Get current values
		$valCountry = $refCountryCurrency->getValue();
		$valSymbols = $refCurrencySymbols->getValue();

		// Make sure they're not empty
		$this->assertNotEmpty($valCountry, 'Test Self-check: Forex::$countryCurrency should not be empty');
		$this->assertNotEmpty($valSymbols, 'Test Self-check: Forex::$currencySymbols should not be empty');

		// Set special test data
		$valCountry['XX'] = 'TEST';
		$refCountryCurrency->setValue($valCountry);

		$valSymbols['XXX'] = 'TEST';
		$refCurrencySymbols->setValue($valSymbols);

		Forex::reloadCurrencyData(false, self::$container);

		$valCountry = $refCountryCurrency->getValue();
		$valSymbols = $refCurrencySymbols->getValue();

		$this->assertNotEmpty($valCountry, 'Forex::$countryCurrency should not be empty');
		$this->assertNotEmpty($valSymbols, 'Forex::$currencySymbols should not be empty');

		$this->assertArrayHasKey('XX', $valCountry, 'Forex::$countryCurrency must not be reloaded when it is already loaded');
		$this->assertArrayHasKey('XXX', $valSymbols, 'Forex::$currencySymbols must not be reloaded when it is already loaded');
	}

	/**
	 * @covers Akeeba\Subscriptions\Admin\Helper\Forex::reloadCurrencyData
	 * @depends testReloadCurrencyData_NoReload
	 */
	public function testReloadCurrencyData_DoReload()
	{
		$reflectionClass = new \ReflectionClass('Akeeba\Subscriptions\Admin\Helper\Forex');
		$refCountryCurrency = $reflectionClass->getProperty('countryCurrency');
		$refCountryCurrency->setAccessible(true);
		$refCurrencySymbols = $reflectionClass->getProperty('currencySymbols');
		$refCurrencySymbols->setAccessible(true);

		// Get current values
		$valCountry = $refCountryCurrency->getValue();
		$valSymbols = $refCurrencySymbols->getValue();

		// Make sure they're not empty
		$this->assertNotEmpty($valCountry, 'Test Self-check: Forex::$countryCurrency should not be empty');
		$this->assertNotEmpty($valSymbols, 'Test Self-check: Forex::$currencySymbols should not be empty');

		// Set special test data
		$valCountry['XX'] = 'TEST';
		$refCountryCurrency->setValue($valCountry);

		$valSymbols['XXX'] = 'TEST';
		$refCurrencySymbols->setValue($valSymbols);

		Forex::reloadCurrencyData(true, self::$container);

		$valCountry = $refCountryCurrency->getValue();
		$valSymbols = $refCurrencySymbols->getValue();

		$this->assertNotEmpty($valCountry, 'Forex::$countryCurrency should not be empty');
		$this->assertNotEmpty($valSymbols, 'Forex::$currencySymbols should not be empty');

		$this->assertArrayNotHasKey('XX', $valCountry, 'Forex::$countryCurrency must be reloaded when forced');
		$this->assertArrayNotHasKey('XXX', $valSymbols, 'Forex::$currencySymbols must be reloaded when forced');
	}

	/**
	 * @covers Akeeba\Subscriptions\Admin\Helper\Forex::testUpdateRates
	 * @covers Akeeba\Subscriptions\Admin\Helper\Forex::shouldUpdate
	 * @covers Akeeba\Subscriptions\Admin\Helper\Forex::setLastUpdateTimestamp
	 * @covers Akeeba\Subscriptions\Admin\Helper\Forex::getLastUpdateTimestamp
	 */
	public function testUpdateRates()
	{
		$db = self::$container->db;

		// Delete the last update timestamp key
		$query = $db->getQuery(true)
			->delete('#__akeeba_common')
			->where($db->qn('key') . ' = ' . $db->q('akeebasubs_forex_update_timestamp'));
		$db->setQuery($query)->execute();

		// Delete the contents of the table
		$db->truncateTable('#__akeebasubs_forexrates');

		// Reset the current cache
		$reflectionClass = new \ReflectionClass('Akeeba\Subscriptions\Admin\Helper\Forex');

		$refRates = $reflectionClass->getProperty('rates');
		$refRates->setAccessible(true);
		$refRates->setValue([]);

		// Set a local source
		$refSourceUrl = $reflectionClass->getProperty('rateSourceUrl');
		$refSourceUrl->setAccessible(true);
		$refSourceUrl->setValue('file://' . realpath(__DIR__ . '/eurofxref-daily.xml'));

		// ===== Test first load
		Forex::updateRates(false, self::$container);

		$rates = $refRates->getValue();
		$this->assertNotEmpty($rates, 'The rates must be loaded from the source');
		$this->assertEquals('1.1096', $rates['USD']['rate'], 'The rates must be read correctly from the source');

		// ===== Test no reload
		$rates['USD'] = array('rate' => 12.345, 'currency' => 'USD');
		$refRates->setValue($rates);

		Forex::updateRates(false, self::$container);

		$rates = $refRates->getValue();
		$this->assertEquals('12.345', $rates['USD']['rate'], 'The rates must not be reloaded if not forced and not expired');

		// ===== Test force reload

		Forex::updateRates(true, self::$container);

		$rates = $refRates->getValue();
		$this->assertNotEquals('12.345', $rates['USD']['rate'], 'The rates must be reloaded if forced');

		// ===== Test no reload when up to date
		$rates['USD'] = array('rate' => 12.345, 'currency' => 'USD');
		$refRates->setValue($rates);

		$o = (object)[
			'key' => 'akeebasubs_forex_update_timestamp',
			'value' => time() - 3600
		];
		$db->updateObject('#__akeeba_common', $o, 'key');

		Forex::updateRates(false, self::$container);

		$rates = $refRates->getValue();
		$this->assertEquals('12.345', $rates['USD']['rate'], 'The rates must not be reloaded if not expired');

		// ===== Test reload on expired
		$o = (object)[
			'key' => 'akeebasubs_forex_update_timestamp',
			'value' => time() - 100000
		];
		$db->updateObject('#__akeeba_common', $o, 'key');

		Forex::updateRates(true, self::$container);

		$rates = $refRates->getValue();
		$this->assertNotEquals('12.345', $rates['USD']['rate'], 'The rates must be reloaded if expired');
	}

	/**
	 * @covers Akeeba\Subscriptions\Admin\Helper\Forex::reloadRates
	 */
	public function testReloadRates()
	{
		// Reset the current cache
		$reflectionClass = new \ReflectionClass('Akeeba\Subscriptions\Admin\Helper\Forex');
		$refRates = $reflectionClass->getProperty('rates');
		$refRates->setAccessible(true);
		$refRates->setValue([]);

		// ==== Test first load
		Forex::reloadRates(false, self::$container);

		$rates = $refRates->getValue();
		$this->assertNotEmpty($rates, 'Forex::$rates should not be empty on first load');

		// ==== Test no reload when not forced
		$rates['USD'] = array('rate' => 12.345, 'currency' => 'USD');
		$refRates->setValue($rates);

		Forex::reloadRates(false, self::$container);

		$rates = $refRates->getValue();
		$this->assertNotEquals('1.1096', $rates['USD']['rate'], 'The rates must not be reloaded when not empty');

		// ==== Test reload when forced
		Forex::reloadRates(true, self::$container);

		$rates = $refRates->getValue();
		$this->assertEquals('1.1096', $rates['USD']['rate'], 'The rates must be reloaded when forced');
	}

	/**
	 * @covers Akeeba\Subscriptions\Admin\Helper\Forex::getCurrencySymbol
	 * @depends testReloadRates
	 *
	 * @dataProvider Akeeba\Subscriptions\Tests\Admin\Helper\ForexTest::getTestGetCurrencySymbol
	 */
	public function testGetCurrencySymbol($currency, $symbol)
	{
		$actual = Forex::getCurrencySymbol($currency);

		$this->assertEquals($symbol, $actual, "Symbol for $currency must be $symbol");
	}

	/**
	 * @covers Akeeba\Subscriptions\Admin\Helper\Forex::convertFromEuro
	 * @depends testReloadRates
	 *
	 * @dataProvider Akeeba\Subscriptions\Tests\Admin\Helper\ForexTest::getTestConvertFromEuro
	 */
	public function testConvertFromEuro($currency, $value, $expected)
	{
		$actual = Forex::convertFromEuro($currency, $value, self::$container);

		$this->assertEquals($expected, $actual, "$value EUR to $currency must be $expected, not $actual");
	}

	/**
	 * @covers Akeeba\Subscriptions\Admin\Helper\Forex::convertFromEuro
	 * @depends testReloadRates
	 *
	 * @dataProvider Akeeba\Subscriptions\Tests\Admin\Helper\ForexTest::getTestConvertFromEuro
	 */
	public function testConvertToEuro($currency, $expected, $value)
	{
		$actual = Forex::convertToEuro($currency, $value, self::$container);

		$this->assertEquals($expected, $actual, "$value EUR to $currency must be $expected, not $actual");
	}

	/**
	 * @covers Akeeba\Subscriptions\Admin\Helper\Forex::testExhangeRate
	 * @covers Akeeba\Subscriptions\Admin\Helper\Forex::convertCurrency
	 * @depends testReloadRates
	 *
	 * @dataProvider Akeeba\Subscriptions\Tests\Admin\Helper\ForexTest::getTestExhangeRate
	 */
	public function testExhangeRate($from, $to, $expected, $exception)
	{
		try
		{
			$actual = Forex::exhangeRate($from, $to, self::$container);
		}
		catch (\InvalidArgumentException $e)
		{
			$actual = 0.0;

			if (!$exception)
			{
				$this->fail('Unexpected exception');
			}
		}

		$this->assertEquals($expected, $actual, "Exchange rate of $from to $to must be $expected, not $actual");
	}

	/**
	 * @covers Akeeba\Subscriptions\Admin\Helper\Forex::convertToLocal
	 * @depends testExhangeRate
	 *
	 * @dataProvider Akeeba\Subscriptions\Tests\Admin\Helper\ForexTest::getTestConvertToLocal
	 */
	public function testConvertToLocal($options, $country, $value, $expected, $message)
	{
		// Apply component options
		/** @var array $params */
		$params = static::$container->params->getParams();

		foreach ($options as $k => $v)
		{
			static::$container->params->set($k, $v);
		}

		static::$container->params->save();

		// Get the result
		$actual = Forex::convertToLocal($country, $value, self::$container);

		// Assert equality
		$this->assertEquals($expected, $actual, $message);
	}

	public static function getTestConvertToLocal()
	{
		return [
			[
				'options' => [
					'currency' => 'EUR',
					'currencysymbol' => '€'
				],
				'country' => 'GR',
				'value' => 10.00,
				'expected' => [
					'currency' => 'EUR',
					'symbol' => '€',
					'value' => 10.00,
					'rate' => 1.00
				],
				'message' => 'Inside Eurozone (canonical)'
			],
			[
				'options' => [
					'currency' => 'eur',
					'currencysymbol' => 'e'
				],
				'country' => 'GR',
				'value' => 10.00,
				'expected' => [
					'currency' => 'EUR',
					'symbol' => 'e',
					'value' => 10.00,
					'rate' => 1.00
				],
				'message' => 'Inside Eurozone (lowercase currency code and alt symbol)'
			],
			[
				'options' => [
					'currency' => 'APL',
					'currencysymbol' => ''
				],
				'country' => 'US',
				'value' => 10.00,
				'expected' => [
					'currency' => 'APL',
					'symbol' => '',
					'value' => 10.00,
					'rate' => 1.00
				],
				'message' => 'Unknown currency'
			],
			[
				'options' => [
					'currency' => 'EUR',
					'currencysymbol' => '€'
				],
				'country' => 'XX',
				'value' => 10.00,
				'expected' => [
					'currency' => 'EUR',
					'symbol' => '€',
					'value' => 10.00,
					'rate' => 1.00
				],
				'message' => 'Invalid country'
			],
			[
				'options' => [
					'currency' => 'EUR',
					'currencysymbol' => '€'
				],
				'country' => 'AD',
				'value' => 10.00,
				'expected' => [
					'currency' => 'EUR',
					'symbol' => '€',
					'value' => 10.00,
					'rate' => 1.00
				],
				'message' => 'Country with no currency preference'
			],
			[
				'options' => [
					'currency' => 'EUR',
					'currencysymbol' => '€'
				],
				'country' => 'US',
				'value' => 10.00,
				'expected' => [
					'currency' => 'USD',
					'symbol' => '$',
					'value' => 11.096,
					'rate' => 1.1096
				],
				'message' => 'EUR to USD with USA country preference'
			],
			[
				'options' => [
					'currency' => 'USD',
					'currencysymbol' => '$'
				],
				'country' => 'DE',
				'value' => 10.00,
				'expected' => [
					'currency' => 'EUR',
					'symbol' => '€',
					'value' => 9.0122566690699,
					'rate' => 0.90122566690699
				],
				'message' => 'USD to EUR with Germany country preference'
			],
			[
				'options' => [
					'currency' => 'USD',
					'currencysymbol' => '$'
				],
				'country' => 'AU',
				'value' => 10.00,
				'expected' => [
					'currency' => 'AUD',
					'symbol' => 'A$',
					'value' => 13.2903749098774,
					'rate' => 1.32903749098774
				],
				'message' => 'USD to AUD with Australia country preference'
			],
		];
	}

	public static function getTestExhangeRate()
	{
		return [
			['EUR', 'USD', 1.1096, false],
			['USD', 'EUR', 0.90122566690699, false],
			['USD', 'AUD', 1.32903749098774, false],
			['', 'USD', 0, true],
			['USD', '', 0, true],
			['', '', 0, true],
		];
	}

	public static function getTestConvertFromEuro()
	{
		return [
			['USD', 10, 11.096],
			['BGN', 10, 19.558],
			['NOK', 12.85, 113.7225],
		];
	}

	public static function getTestGetCurrencySymbol()
	{
		return [
			['EUR', '€'],
			['USD', '$'],
			['BGN', 'лв.'],
		];
	}
}