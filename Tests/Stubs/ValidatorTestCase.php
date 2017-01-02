<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Stubs;

use Akeeba\Subscriptions\Admin\Helper\Select;
use Akeeba\Subscriptions\Site\Model\Subscribe\StateData;
use Akeeba\Subscriptions\Site\Model\Subscribe\ValidatorFactory;
use FOF30\Container\Container;
use JUser;
use JUserHelper;

abstract class ValidatorTestCase extends \PHPUnit_Framework_TestCase
{
	/** @var   ValidatorFactory  The validator factory for this class */
	public static $factory = null;

	/** @var   Container  The container of the component */
	public static $container = null;

	/** @var   StateData  The state data we're operating on */
	public static $state = null;

	/** @var   JUser  The currently active Joomla! user object */
	public static $jUser = null;

	/** @var   array  Known users we have already created */
	public static $users = [];

	/** @var   string  Which validator are we testing? */
	public static $validatorType = '';

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

		static::$jUser = new JUser();

		self::$users = CommonSetup::getUsers();

		// Reset the component configuration
		static::$container->params->setParams([
		    'reqcoupon' => 0,
		]);
		static::$container->params->save();

		// Force reset the filtered countries list (some tests change the showcountries / hidecountries)
		Select::getFilteredCountries(true);

		// Set up the StateData object
		$model = static::$container->factory->model('Subscribe');
		static::$state = new StateData($model);

		// Set up the ValidatorFactory object
		static::$factory = new ValidatorFactory(static::$container, static::$state, static::$jUser);
	}

	/**
	 * The data to set up and run tests.
	 *
	 * The return is an array of arrays. Each second level array has three keys:
	 * – state, array. The state variables to set up.
	 * - expected, mixed. The expected return value of the validator.
	 * - message. Message to show if the test fails.
	 *
	 * @return  array  See above
	 */
	public function getTestData()
	{
		return [
			[
				'state' => [
					'name' => 'Foobar'
				],
				'expected' => false,
				'message' => 'Single word names are not allowed'
			],
			[
				'state' => [
					'name' => 'Foo bar'
				],
				'expected' => true,
				'message' => 'Two word names are allowed'
			],
			[
				'state' => [
					'name' => 'Foo bar baz'
				],
				'expected' => true,
				'message' => 'Three word names are allowed'
			],
			[
				'state' => [
					'name' => 'a b'
				],
				'expected' => true,
				'message' => 'Single letter names with two parts are allowed'
			],
		];
	}

	/**
	 * Run the validator test
	 *
	 * @param   array   $state     State variables to set to the StateData instance
	 * @param   mixed   $expected  Expected validation result
	 * @param   string  $message   Message to show if the test fails
	 *
	 * @return  void
	 */
	public function testGetValidationResult($state, $expected, $message)
	{
		// Reset the state data
		static::$state->reset();

		// Apply the new state data
		foreach ($state as $k => $v)
		{
			static::$state->$k = $v;
		}

		// Replace the JUser object in the ValidatorFactory
		$factoryReflector = new \ReflectionObject(static::$factory);
		$jUserProperty = $factoryReflector->getProperty('jUser');
		$jUserProperty->setAccessible(true);
		$jUserProperty->setValue(static::$factory, static::$jUser);

		// Force create a new validator object
		static::$factory->setValidator(self::$validatorType, null);
		$validator = static::$factory->getValidator(self::$validatorType);

		// Forcibly execute the validator and get the actual value
		$actual = $validator->execute(true);

		// Assert the actual value matches the expected value
		$this->performAssertion($expected, $actual, $message);
	}

	/**
	 * Perform the assertion(s) required for this test
	 *
	 * @param   mixed   $expected  Expected value
	 * @param   mixed   $actual    Actual validator result
	 * @param   string  $message   Message to show on failure
	 *
	 * @return  void
	 */
	public function performAssertion($expected, $actual, $message)
	{
		$this->assertEquals($expected, $actual, $message);
	}
}