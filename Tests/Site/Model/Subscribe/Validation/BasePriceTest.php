<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Site\Model\Subscribe\Validation;

use Akeeba\Subscriptions\Tests\Stubs\ValidatorTestCase;

/**
 * Test the BasePrice validator
 *
 * @covers Akeeba\Subscriptions\Site\Model\Subscribe\Validation\BasePrice
 */
class BasePriceTest extends ValidatorTestCase
{
	public static function setUpBeforeClass()
	{
		// Set the validator type
		self::$validatorType = 'BasePrice';

		// Create the base objects
		parent::setUpBeforeClass();

		// Fake the EU VAT checks
		$reflector     = new \ReflectionClass('Akeeba\Subscriptions\Admin\Helper\EUVATInfo');
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
				'loggedIn'        => 'guest',
				'state'           => [
					'id' => 99999999
				],
				'expected'        => [
					'levelNet'    => 0.0,
					'basePrice'   => 0.0, // Base price, including sign-up and surcharges
					'signUp'      => 0.0, // Sign-up fee applied
					'isRecurring' => false
				],
				'message'         => 'Invalid level ID'
			],
			[
				'loggedIn'        => 'guest',
				'state'           => [
					'id' => 1
				],
				'expected'        => [
					'levelNet'    => 100.0,
					'basePrice'   => 110.0, // Base price, including sign-up and surcharges
					'signUp'      => 10.0, // Sign-up fee applied
					'isRecurring' => false
				],
				'message'         => 'Level with sign-up, guest user'
			],
			[
				'loggedIn'        => 'forcedvat',
				'state'           => [
					'id' => 1
				],
				'expected'        => [
					'levelNet'    => 100.0,
					'basePrice'   => 110.0, // Base price, including sign-up and surcharges
					'signUp'      => 10.0, // Sign-up fee applied
					'isRecurring' => false
				],
				'message'         => 'Level with sign-up, user without subscription'
			],
			[
				'loggedIn'        => 'user1',
				'state'           => [
					'id' => 1
				],
				'expected'        => [
					'levelNet'    => 100.0,
					'basePrice'   => 100.0, // Base price, including sign-up and surcharges
					'signUp'      => 0.0, // Sign-up fee applied
					'isRecurring' => false
				],
				'message'         => 'Level with sign-up, user with expired subscription'
			],
			[
				'loggedIn'        => 'business',
				'state'           => [
					'id' => 1
				],
				'expected'        => [
					'levelNet'    => 100.0,
					'basePrice'   => 100.0, // Base price, including sign-up and surcharges
					'signUp'      => 0.0, // Sign-up fee applied
					'isRecurring' => false
				],
				'message'         => 'Level with sign-up, user with active subscription'
			],
			[
				'loggedIn'        => 'guest',
				'state'           => [
					'id' => 3
				],
				'expected'        => [
					'levelNet'    => 100.0,
					'basePrice'   => 110.0, // Base price, including sign-up and surcharges
					'signUp'      => 10.0, // Sign-up fee applied
					'isRecurring' => true
				],
				'message'         => 'Recurring subscription with signup fee'
			],
			[
				'loggedIn'        => 'guest',
				'state'           => [
					'id' => 6
				],
				'expected'        => [
					'levelNet'    => 0.0,
					'basePrice'   => 0.0, // Base price, including sign-up and surcharges
					'signUp'      => 0.0, // Sign-up fee applied
					'isRecurring' => false
				],
				'message'         => 'Free subscription without signup fee'
			],
			[
				'loggedIn'        => 'guest',
				'state'           => [
					'id' => 7
				],
				'expected'        => [
					'levelNet'    => 0.0,
					'basePrice'   => 10.0, // Base price, including sign-up and surcharges
					'signUp'      => 10.0, // Sign-up fee applied
					'isRecurring' => false
				],
				'message'         => 'Free subscription with signup fee'
			]
		];
	}

	/**
	 * Test the validator
	 *
	 * @dataProvider getTestData
	 */
	public function testGetValidationResult($loggedIn, $state, $expected, $message)
	{
		self::$jUser = self::$users[ $loggedIn ];
		static::$factory->reset();

		parent::testGetValidationResult($state, $expected, $message);
	}
}