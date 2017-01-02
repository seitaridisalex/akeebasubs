<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Site\Model\Subscribe\Validation;

use Akeeba\Subscriptions\Tests\Stubs\ValidatorTestCase;

/**
 * Test the Name validator
 *
 * @covers Akeeba\Subscriptions\Site\Model\Subscribe\Validation\Name
 */
class NameTest extends ValidatorTestCase
{
	public static function setUpBeforeClass()
	{
		self::$validatorType = 'Name';

		parent::setUpBeforeClass();
	}

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
	 * Test the validator
	 *
	 * @dataProvider getTestData
	 */
	public function testGetValidationResult($state, $expected, $message)
	{
		parent::testGetValidationResult($state, $expected, $message);
	}


}