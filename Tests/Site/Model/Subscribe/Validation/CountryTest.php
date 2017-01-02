<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Site\Model\Subscribe\Validation;

use Akeeba\Subscriptions\Admin\Helper\Select;
use Akeeba\Subscriptions\Tests\Stubs\ValidatorTestCase;

/**
 * Test the Country validator
 *
 * @covers Akeeba\Subscriptions\Site\Model\Subscribe\Validation\Country
 */
class CountryTest extends ValidatorTestCase
{
	public static function setUpBeforeClass()
	{
		self::$validatorType = 'Country';

		parent::setUpBeforeClass();
	}

	public function getTestData()
	{
		return [
			// ========== Collect personal info

			[
				'componentParams' => [
				],
				'state' => [
					'country' => ''
				],
				'expected' => false,
				'message' => 'Empty country: invalid'
			],
			[
				'componentParams' => [
				],
				'state' => [
					'country' => 'GR'
				],
				'expected' => true,
				'message' => 'Valid country: valid'
			],
			[
				'componentParams' => [
				],
				'state' => [
					'country' => 'XO'
				],
				'expected' => false,
				'message' => 'Invalid country: invalid'
			],
		];
	}

	/**
	 * Test the validator
	 *
	 * @dataProvider getTestData
	 */
	public function testGetValidationResult($componentParams, $state, $expected, $message)
	{
		foreach ($componentParams as $k => $v)
		{
			if (static::$container->params->get($k) != $v)
			{
				static::$container->params->set($k, $v);
				static::$container->params->save();
			}
		}

		Select::getFilteredCountries(true);

		parent::testGetValidationResult($state, $expected, $message);
	}


}