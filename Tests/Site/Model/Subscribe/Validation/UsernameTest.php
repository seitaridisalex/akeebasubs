<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Site\Model\Subscribe\Validation;

use Akeeba\Subscriptions\Tests\Stubs\ValidatorTestCase;

/**
 * Test the Username validator
 *
 * @covers Akeeba\Subscriptions\Site\Model\Subscribe\Validation\Username
 */
class UsernameTest extends ValidatorTestCase
{
	public static function setUpBeforeClass()
	{
		self::$validatorType = 'Username';

		parent::setUpBeforeClass();
	}

	public function getTestData()
	{
		return [
			[
				'loggedIn' => 'guest',
				'state'    => [
					'username' => ''
				],
				'expected' => false,
				'message'  => 'Empty username: invalid'
			],
			[
				'loggedIn' => 'guest',
				'state'    => [
					'username' => 'user1'
				],
				'expected' => false,
				'message'  => 'Existing username, not blocked user: invalid'
			],
			[
				'loggedIn' => 'guest',
				'state'    => [
					'username' => 'user2'
				],
				'expected' => false,
				'message'  => 'Existing username, blocked but activated user: invalid'
			],
			[
				'loggedIn' => 'guest',
				'state'    => [
					'username' => 'user3'
				],
				'expected' => true,
				'message'  => 'Existing username, blocked but not activated user: valid'
			],
			[
				'loggedIn' => 'guest',
				'state'    => [
					'username' => 'newuser'
				],
				'expected' => true,
				'message'  => 'Not existing username: valid'
			],
			[
				'loggedIn' => 'guest',
				'state'    => [
					'username' => 'new user'
				],
				'expected' => true,
				'message'  => 'Not existing username with spaces: valid'
			],
			[
				'loggedIn' => 'guest',
				'state'    => [
					'username' => 'new@user.com'
				],
				'expected' => true,
				'message'  => 'Not existing username looking like an email address: valid'
			],
			[
				'loggedIn' => 'guest',
				'state'    => [
					'username' => 'Παπαδόπουλος'
				],
				'expected' => true,
				'message'  => 'Not existing username with UTF characters: valid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'username' => ''
				],
				'expected' => false,
				'message'  => 'Logged in user, empty username: invalid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'username' => 'user1'
				],
				'expected' => true,
				'message'  => 'Logged in user, own username: valid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'username' => 'user2'
				],
				'expected' => false,
				'message'  => 'Logged in user, other existing username: invalid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'username' => 'newuser'
				],
				'expected' => false,
				'message'  => 'Logged in user, other non-existing username: invalid'
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
		self::$jUser = self::$users[ $loggedIn ];

		parent::testGetValidationResult($state, $expected, $message);
	}

}