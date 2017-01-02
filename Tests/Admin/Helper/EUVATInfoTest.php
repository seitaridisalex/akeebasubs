<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Admin\Helper;

use FOF30\Container\Container;
use Akeeba\Subscriptions\Admin\Helper\EUVATInfo;

class EUVATInfoTest extends \PHPUnit_Framework_TestCase
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
	public function testLiveVIESValidation()
	{
		$country ='GR';
		$vat = '070298898';
		$expected = true;

		// Reset the current cache...
		$reflectionClass = new \ReflectionClass('Akeeba\Subscriptions\Admin\Helper\EUVATInfo');
		$refCache = $reflectionClass->getProperty('cache');
		$refCache->setAccessible(true);
		$refCache->setValue([]);

		// ...and the session cache
		$session = \JFactory::getSession();
		$session->set('vat_validation_cache_data', null, 'com_akeebasubs');

		// Run the VIES check
		$actual = EUVATInfo::isVIESValidVATNumber($country, $vat);

		// Assert results
		$message = $expected ? "Could not validate " : "False positive validation of ";

		$this->assertEquals($expected, $actual, "$message VAT number $country $vat");
	}


}