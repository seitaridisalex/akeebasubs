<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Site\Model\Subscribe\Validation;

use Akeeba\Subscriptions\Tests\Stubs\ValidatorTestCase;

/**
 * Test the Business validator
 *
 * @covers Akeeba\Subscriptions\Site\Model\Subscribe\Validation\Business
 */
class BusinessTest extends ValidatorTestCase
{
	public static function setUpBeforeClass()
	{
		// Set the validator type
		self::$validatorType = 'Business';

		// Create the base objects
		parent::setUpBeforeClass();

		// Fake the EU VAT checks
		$reflector = new \ReflectionClass('Akeeba\Subscriptions\Admin\Helper\EUVATInfo');
		$propReflector = $reflector->getProperty('cache');
		$propReflector->setAccessible(true);
		$propReflector->setValue([
			'vat' => [
				'EL123456789' => false,
				'EL070298898' => true,
				'EL666666666' => false,
				'CY123456789' => false,
				'CY999999999' => true,
			]
		]);
	}

	public function getTestData()
	{
		return [
			[
				'componentParams' => [
				],
				'loggedIn' => 'guest',
				'state'    => [
					'isbusiness' => 0,
					'country' => '',
					'vatnumber' => '',
					'businessname' => '',
					'occupation' => '',
				],
				'expected' => [
					'businessname' => true,
					'occupation'	=> true,
					'vatnumber'		=> false,
					'novatrequired' => false
				],
				'message'  => 'Not a business: TTFF'
			],

			[
				'componentParams' => [
				],
				'loggedIn' => 'guest',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'GR',
					'vatnumber' => '',
					'businessname' => '',
					'occupation' => '',
				],
				'expected' => [
					'businessname' => false,
					'occupation'	=> false,
					'vatnumber'		=> false,
					'novatrequired' => false
				],
				'message'  => 'Is business, EU, no information: FFFF'
			],

			[
				'componentParams' => [
				],
				'loggedIn' => 'guest',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'US',
					'vatnumber' => '',
					'businessname' => '',
					'occupation' => '',
				],
				'expected' => [
					'businessname' => false,
					'occupation'	=> false,
					'vatnumber'		=> false,
					'novatrequired' => true
				],
				'message'  => 'Is business, non-EU, no information: FFFT'
			],

			[
				'componentParams' => [
				],
				'loggedIn' => 'guest',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'US',
					'vatnumber' => '123456789',
					'businessname' => '',
					'occupation' => '',
				],
				'expected' => [
					'businessname' => false,
					'occupation'	=> false,
					'vatnumber'		=> false,
					'novatrequired' => true
				],
				'message'  => 'Is business, non-EU, VAT number: FFFT'
			],

			[
				'componentParams' => [
				],
				'loggedIn' => 'guest',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'US',
					'vatnumber' => '',
					'businessname' => 'Fake Corp.',
					'occupation' => '',
				],
				'expected' => [
					'businessname' => true,
					'occupation'	=> false,
					'vatnumber'		=> false,
					'novatrequired' => true
				],
				'message'  => 'Is business, non-EU, only business name: TFFT'
			],

			[
				'componentParams' => [
				],
				'loggedIn' => 'guest',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'US',
					'vatnumber' => '',
					'businessname' => '',
					'occupation' => 'World Domination',
				],
				'expected' => [
					'businessname' => false,
					'occupation'	=> true,
					'vatnumber'		=> false,
					'novatrequired' => true
				],
				'message'  => 'Is business, non-EU, only business activity: FTFT'
			],

			[
				'componentParams' => [
				],
				'loggedIn' => 'guest',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'US',
					'vatnumber' => '',
					'businessname' => 'Fake Corp',
					'occupation' => 'World Domination',
				],
				'expected' => [
					'businessname' => true,
					'occupation'	=> true,
					'vatnumber'		=> false,
					'novatrequired' => true
				],
				'message'  => 'Is business, non-EU, business name and activity: TTFT'
			],

			[
				'componentParams' => [
				],
				'loggedIn' => 'guest',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'US',
					'vatnumber' => '123456789',
					'businessname' => 'Fake Corp',
					'occupation' => 'World Domination',
				],
				'expected' => [
					'businessname' => true,
					'occupation'	=> true,
					'vatnumber'		=> false,
					'novatrequired' => true
				],
				'message'  => 'Is business, non-EU, business name and activity and VAT: TTFT'
			],


			[
				'componentParams' => [
				],
				'loggedIn' => 'guest',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'GR',
					'vatnumber' => '',
					'businessname' => '',
					'occupation' => '',
				],
				'expected' => [
					'businessname' => false,
					'occupation'	=> false,
					'vatnumber'		=> false,
					'novatrequired' => false
				],
				'message'  => 'Is business, EU, no information'
			],

			[
				'componentParams' => [
				],
				'loggedIn' => 'guest',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'GR',
					'vatnumber' => '123456789',
					'businessname' => '',
					'occupation' => '',
				],
				'expected' => [
					'businessname' => false,
					'occupation'	=> false,
					'vatnumber'		=> false,
					'novatrequired' => false
				],
				'message'  => 'Is business, EU, bad VAT number'
			],

			[
				'componentParams' => [
				],
				'loggedIn' => 'guest',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'GR',
					'vatnumber' => '123456789',
					'businessname' => '',
					'occupation' => '',
				],
				'expected' => [
					'businessname' => false,
					'occupation'	=> false,
					'vatnumber'		=> false,
					'novatrequired' => false
				],
				'message'  => 'Is business, EU, bad VAT number'
			],

			[
				'componentParams' => [
				],
				'loggedIn' => 'guest',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'GR',
					'vatnumber' => '070298898',
					'businessname' => '',
					'occupation' => '',
				],
				'expected' => [
					'businessname' => false,
					'occupation'	=> false,
					'vatnumber'		=> true,
					'novatrequired' => false
				],
				'message'  => 'Is business, EU, good VAT number'
			],

			[
				'componentParams' => [
				],
				'loggedIn' => 'guest',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'GR',
					'vatnumber' => '',
					'businessname' => 'Fake Corp.',
					'occupation' => '',
				],
				'expected' => [
					'businessname' => true,
					'occupation'	=> false,
					'vatnumber'		=> false,
					'novatrequired' => false
				],
				'message'  => 'Is business, EU, only business name'
			],

			[
				'componentParams' => [
				],
				'loggedIn' => 'guest',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'GR',
					'vatnumber' => '',
					'businessname' => '',
					'occupation' => 'World Domination',
				],
				'expected' => [
					'businessname' => false,
					'occupation'	=> true,
					'vatnumber'		=> false,
					'novatrequired' => false
				],
				'message'  => 'Is business, EU, only business activity'
			],

			[
				'componentParams' => [
				],
				'loggedIn' => 'guest',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'GR',
					'vatnumber' => '',
					'businessname' => 'Fake Corp',
					'occupation' => 'World Domination',
				],
				'expected' => [
					'businessname' => true,
					'occupation'	=> true,
					'vatnumber'		=> false,
					'novatrequired' => false
				],
				'message'  => 'Is business, EU, business name and activity'
			],

			[
				'componentParams' => [
				],
				'loggedIn' => 'guest',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'GR',
					'vatnumber' => '123456789',
					'businessname' => 'Fake Corp',
					'occupation' => 'World Domination',
				],
				'expected' => [
					'businessname' => true,
					'occupation'	=> true,
					'vatnumber'		=> false,
					'novatrequired' => false
				],
				'message'  => 'Is business, EU, business name and activity and bad VAT'
			],

			[
				'componentParams' => [
				],
				'loggedIn' => 'guest',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'GR',
					'vatnumber' => '070298898',
					'businessname' => 'Fake Corp',
					'occupation' => 'World Domination',
				],
				'expected' => [
					'businessname' => true,
					'occupation'	=> true,
					'vatnumber'		=> true,
					'novatrequired' => false
				],
				'message'  => 'Is business, EU, business name and activity and good VAT'
			],

			[
				'componentParams' => [
				],
				'loggedIn' => 'guest',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'CY',
					'vatnumber' => '123456789',
					'businessname' => 'Fake Corp',
					'occupation' => 'World Domination',
				],
				'expected' => [
					'businessname' => true,
					'occupation'	=> true,
					'vatnumber'		=> false,
					'novatrequired' => true
				],
				'message'  => 'Is business, own EU country, business name and activity and bad VAT'
			],

			[
				'componentParams' => [
				],
				'loggedIn' => 'guest',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'CY',
					'vatnumber' => '999999999',
					'businessname' => 'Fake Corp',
					'occupation' => 'World Domination',
				],
				'expected' => [
					'businessname' => true,
					'occupation'	=> true,
					'vatnumber'		=> false,
					'novatrequired' => true
				],
				'message'  => 'Is business, own EU country, business name and activity and good VAT (the VAT validation is ignored)'
			],

			[
				'componentParams' => [
				],
				'loggedIn' => 'business',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'GR',
					'vatnumber' => '070298898',
					'businessname' => 'Fake Corp',
					'occupation' => 'World Domination',
				],
				'expected' => [
					'businessname' => true,
					'occupation'	=> true,
					'vatnumber'		=> true,
					'novatrequired' => false
				],
				'message'  => 'Logged in business user without VAT preference, valid VAT'
			],

			[
				'componentParams' => [
				],
				'loggedIn' => 'business',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'GR',
					'vatnumber' => '123456789',
					'businessname' => 'Fake Corp',
					'occupation' => 'World Domination',
				],
				'expected' => [
					'businessname' => true,
					'occupation'	=> true,
					'vatnumber'		=> false,
					'novatrequired' => false
				],
				'message'  => 'Logged in business user without VAT preference, bad VAT'
			],

			[
				'componentParams' => [
				],
				'loggedIn' => 'forcedvat',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'GR',
					'vatnumber' => '123456789',
					'businessname' => 'Fake Corp',
					'occupation' => 'World Domination',
				],
				'expected' => [
					'businessname' => true,
					'occupation'	=> true,
					'vatnumber'		=> true,
					'novatrequired' => false
				],
				'message'  => 'Logged in business user WITH VAT preference, bad VAT (same as user record)'
			],

			[
				'componentParams' => [
				],
				'loggedIn' => 'forcedvat',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'GR',
					'vatnumber' => '666666666',
					'businessname' => 'Fake Corp',
					'occupation' => 'World Domination',
				],
				'expected' => [
					'businessname' => true,
					'occupation'	=> true,
					'vatnumber'		=> false,
					'novatrequired' => false
				],
				'message'  => 'Logged in business user WITH VAT preference, bad VAT (different than user record)'
			],

			[
				'componentParams' => [
				],
				'loggedIn' => 'forcedvat',
				'state'    => [
					'isbusiness' => 1,
					'country' => 'GR',
					'vatnumber' => '070298898',
					'businessname' => 'Fake Corp',
					'occupation' => 'World Domination',
				],
				'expected' => [
					'businessname' => true,
					'occupation'	=> true,
					'vatnumber'		=> true,
					'novatrequired' => false
				],
				'message'  => 'Logged in business user WITH VAT preference, good VAT (different than user record)'
			],
		];
	}

	/**
	 * Test the validator
	 *
	 * @dataProvider getTestData
	 */
	public function testGetValidationResult($componentParams, $loggedIn, $state, $expected, $message)
	{
		foreach ($componentParams as $k => $v)
		{
			if (static::$container->params->get($k) != $v)
			{
				static::$container->params->set($k, $v);
				static::$container->params->save();
			}
		}

		self::$jUser = self::$users[$loggedIn];

		parent::testGetValidationResult($state, $expected, $message);
	}


}