<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Site\Model\Subscribe\Validation;

use Akeeba\Subscriptions\Tests\Stubs\ValidatorTestCase;

/**
 * Test the Password validator
 *
 * @covers Akeeba\Subscriptions\Site\Model\Subscribe\Validation\Password
 */
class PasswordTest extends ValidatorTestCase
{
	public static function setUpBeforeClass()
	{
		self::$validatorType = 'Password';

		parent::setUpBeforeClass();
	}

	public function getTestData()
	{
		return [
			[
				'loggedIn' => 'guest',
				'state' => [
					'password' => '',
					'password2' => ''
				],
				'expected' => false,
				'message' => 'Guest, Empty password: invalid'
			],
			[
				'loggedIn' => 'guest',
				'state' => [
					'password' => 'test',
					'password2' => ''
				],
				'expected' => false,
				'message' => 'Guest, Empty password2: invalid'
			],
			[
				'loggedIn' => 'guest',
				'state' => [
					'password' => 'test',
					'password2' => 'test2'
				],
				'expected' => false,
				'message' => 'Guest, not matching passwords: invalid'
			],
			[
				'loggedIn' => 'guest',
				'state' => [
					'password' => 'test',
					'password2' => 'test'
				],
				'expected' => true,
				'message' => 'Guest, matching passwords: valid'
			],

			[
				'loggedIn' => 'user1',
				'state' => [
					'password' => '',
					'password2' => ''
				],
				'expected' => true,
				'message' => 'Logged in user, Empty password: always valid'
			],
			[
				'loggedIn' => 'user1',
				'state' => [
					'password' => 'test',
					'password2' => ''
				],
				'expected' => true,
				'message' => 'Logged in user, Empty password2: always valid'
			],
			[
				'loggedIn' => 'user1',
				'state' => [
					'password' => 'test',
					'password2' => 'test2'
				],
				'expected' => true,
				'message' => 'Logged in user, not matching passwords: always valid'
			],
			[
				'loggedIn' => 'user1',
				'state' => [
					'password' => 'test',
					'password2' => 'test'
				],
				'expected' => true,
				'message' => 'Logged in user, matching passwords: always valid'
			],

		];
	}

	/**
	 * Test the validator
	 *
	 * @dataProvider getTestData
	 */
	public function testGetValidationResult($loggedIn, $state, $expected, $message)
	{
		self::$jUser = self::$users[$loggedIn];

		parent::testGetValidationResult($state, $expected, $message);
	}

}