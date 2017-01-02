<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Site\Model\Subscribe\Validation;

use Akeeba\Subscriptions\Site\Model\Subscribe\StateData;
use Akeeba\Subscriptions\Tests\Stubs\CustomPlatform;
use Akeeba\Subscriptions\Tests\Stubs\ValidatorTestCase;

/**
 * Test the SubscriptionCustomFields validator
 *
 * @covers Akeeba\Subscriptions\Site\Model\Subscribe\Validation\SubscriptionCustomFields
 */
class SubscriptionCustomFieldsTest extends ValidatorTestCase
{
	public static function setUpBeforeClass()
	{
		// Set the validator type
		self::$validatorType = 'SubscriptionCustomFields';

		// Create the base objects
		parent::setUpBeforeClass();
	}

	public function getTestData()
	{
		return [
			[
				'plugins' => [],
				'state'    => [
					'subcustom' => [],
				],
				'expected' => [
					'subscription_custom_validation' => [],
					'subscription_custom_valid' => true
				],
				'message'  => 'No plugins defined: valid (default)'
			],
			[
				'plugins' => ['plgWrongFormat1'],
				'state'    => [
					'subcustom' => [
						'test1' => 42,
						'test2' => 42,
						'test_none' => 'whatever'
					],
				],
				'expected' => [
					'subscription_custom_validation' => [],
					'subscription_custom_valid' => true
				],
				'message'  => 'Ignore plugin which doesn\'t have a \'valid\' key in its return array'
			],
			[
				'plugins' => ['plgWrongFormat2'],
				'state'    => [
					'subcustom' => [
						'test1' => 42,
						'test2' => 42,
						'test_none' => 'whatever'
					],
				],
				'expected' => [
					'subscription_custom_validation' => [],
					'subscription_custom_valid' => true
				],
				'message'  => 'Ignore plugin which doesn\'t have a \'subscription_custom_validation\' key in its return array'
			],
			[
				'plugins' => ['plgScalarReturn'],
				'state'    => [
					'subcustom' => [
						'test1' => 42,
						'test2' => 42,
						'test_none' => 'whatever'
					],
				],
				'expected' => [
					'subscription_custom_validation' => [],
					'subscription_custom_valid' => true
				],
				'message'  => 'Ignore plugin which doesn\'t return an array'
			],
			[
				'plugins' => ['plgInvalidCustomValidation'],
				'state'    => [
					'subcustom' => [
						'test1' => 42,
						'test2' => 42,
						'test_none' => 'whatever'
					],
				],
				'expected' => [
					'subscription_custom_validation' => [],
					'subscription_custom_valid' => true
				],
				'message'  => 'Ignore plugin which doesn\'t return an array under key \'subscription_custom_validation\''
			],
			[
				'plugins' => ['plgTest1'],
				'state'    => [
					'subcustom' => [
						'test1' => 42,
					],
				],
				'expected' => [
					'subscription_custom_validation' => [
						'test1' => 42
					],
					'subscription_custom_valid' => true
				],
				'message'  => 'Valid plugin = valid'
			],
			[
				'plugins' => ['plgTest1', 'plgTest2'],
				'state'    => [
					'subcustom' => [
						'test1' => 42,
						'test2' => 'bork!'
					],
				],
				'expected' => [
					'subscription_custom_validation' => [
						'test1' => 42,
						'test2' => 'bork!'
					],
					'subscription_custom_valid' => false
				],
				'message'  => 'Valid plugin + invalid plugin = invalid'
			],
			[
				'plugins' => ['plgTest1', 'plgTest2'],
				'state'    => [
					'subcustom' => [
						'test1' => 42,
						'test2' => 42
					],
				],
				'expected' => [
					'subscription_custom_validation' => [
						'test1' => 42,
						'test2' => 42
					],
					'subscription_custom_valid' => true
				],
				'message'  => 'Valid plugin + valid plugin = valid'
			],
			[
				'plugins' => ['plgInvalidCustomValidation', 'plgTest1'],
				'state'    => [
					'subcustom' => [
						'test1' => 42,
					],
				],
				'expected' => [
					'subscription_custom_validation' => [
						'test1' => 42
					],
					'subscription_custom_valid' => true
				],
				'message'  => 'Valid plugin + ignored plugin = valid'
			],
			[
				'plugins' => ['plgInvalidCustomValidation', 'plgTest1', 'plgTest2'],
				'state'    => [
					'subcustom' => [
						'test1' => 42,
						'test2' => 'bork!',
					],
				],
				'expected' => [
					'subscription_custom_validation' => [
						'test1' => 42,
						'test2' => 'bork!',
					],
					'subscription_custom_valid' => false
				],
				'message'  => 'Valid plugin + invalid plugin + ignored plugin = invalid'
			],
			[
				'plugins' => ['plgInvalidCustomValidation', 'plgTest1', 'plgTest2'],
				'state'    => [
					'subcustom' => [
						'test1' => 42,
						'test2' => 42,
					],
				],
				'expected' => [
					'subscription_custom_validation' => [
						'test1' => 42,
						'test2' => 42,
					],
					'subscription_custom_valid' => true
				],
				'message'  => 'Valid plugin + valid plugin + ignored plugin = valid'
			],
		];
	}

	/**
	 * Test the validator
	 *
	 * @dataProvider getTestData
	 */
	public function testGetValidationResult($plugins, $state, $expected, $message)
	{
		CustomPlatform::resetEventHandlers();

		if (count($plugins))
		{
			foreach ($plugins as $method)
			{
				CustomPlatform::addEventHandler('onValidatePerSubscription', array($this, $method));
			}
		}

		parent::testGetValidationResult($state, $expected, $message);
	}

	public function performAssertion($expected, $actual, $message)
	{
		$expected = (object)$expected;

		parent::performAssertion($expected, $actual, $message);
	}

	public static function plgScalarReturn(StateData $state)
	{
		return false;
	}

	public static function plgWrongFormat1(StateData $state)
	{
		return [
			'not_valid' => 'true',
			'subscription_custom_validation' => [
				'a' => 'b'
			]
		];
	}

	public static function plgWrongFormat2(StateData $state)
	{
		return [
			'valid' => 'true',
			'subscription_custom_validation_almost' => [
				'a' => 'b'
			]
		];
	}

	public static function plgInvalidCustomValidation(StateData $state)
	{
		return [
			'valid' => 'true',
			'subscription_custom_validation' => false
		];
	}

	public static function plgTest1(StateData $state, $key = 'test1')
	{
		$ret = [
			'valid' => false,
			'subscription_custom_validation' => [
				$key => null
			]
		];

		$customData = $state->subcustom;

		if (empty($customData))
		{
			return $ret;
		}

		if (!isset($customData[$key]))
		{
			return $ret;
		}

		$ret['subscription_custom_validation'][$key] = $customData[$key];

		if ($customData[$key] != 42)
		{
			return $ret;
		}

		$ret['valid'] = true;

		return $ret;
	}

	public static function plgTest2(StateData $state)
	{
		return self::plgTest1($state, 'test2');
	}
}