<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Site\Model\Subscribe\Validation;

use Akeeba\Subscriptions\Tests\Stubs\ValidatorTestCase;

/**
 * Test the Coupon validator
 *
 * @covers Akeeba\Subscriptions\Site\Model\Subscribe\Validation\Coupon
 */
class CouponTest extends ValidatorTestCase
{
	public static function setUpBeforeClass()
	{
		// Set the validator type
		self::$validatorType = 'Coupon';

		// Create the base objects
		parent::setUpBeforeClass();
	}

	public function getTestData()
	{
		return [
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => '',
				],
				'expected' => [
					'valid' => false,
					'couponFound' => false,
					'coupon' => null
				],
				'message'  => 'No coupon: invalid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => 'IAMNOTTHERE',
				],
				'expected' => [
					'valid' => false,
					'couponFound' => false,
					'coupon' => null
				],
				'message'  => 'Non-existent coupon code (IAMNOTTHERE): invalid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => 'VALIDALL',
				],
				'expected' => [
					'valid' => true,
					'couponFound' => true,
					'coupon' => 'VALIDALL'
				],
				'message'  => 'Valid coupon code, all caps (VALIDALL): valid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => 'validall',
				],
				'expected' => [
					'valid' => true,
					'couponFound' => true,
					'coupon' => 'VALIDALL'
				],
				'message'  => 'Valid coupon code, all lowercase (validall): valid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => 'ValidALL',
				],
				'expected' => [
					'valid' => true,
					'couponFound' => true,
					'coupon' => 'VALIDALL'
				],
				'message'  => 'Valid coupon code, mixed case (ValidALL): valid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => ' VALIDALL',
				],
				'expected' => [
					'valid' => true,
					'couponFound' => true,
					'coupon' => 'VALIDALL'
				],
				'message'  => 'Valid coupon code, spaces before (VALIDALL): valid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => 'VALIDALL ',
				],
				'expected' => [
					'valid' => true,
					'couponFound' => true,
					'coupon' => 'VALIDALL'
				],
				'message'  => 'Valid coupon code, spaces after (VALIDALL): valid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => "VALIDALL\n",
				],
				'expected' => [
					'valid' => true,
					'couponFound' => true,
					'coupon' => 'VALIDALL'
				],
				'message'  => 'Valid coupon code, newline after (VALIDALL): valid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => ' VALIDALL ',
				],
				'expected' => [
					'valid' => true,
					'couponFound' => true,
					'coupon' => 'VALIDALL'
				],
				'message'  => 'Valid coupon code, spaces around (VALIDALL): valid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => 'NOTYETACTIVE',
				],
				'expected' => [
					'valid' => false,
					'couponFound' => true,
					'coupon' => null
				],
				'message'  => 'Valid coupon code, not yet active (NOTYETACTIVE): invalid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => 'ALREADYEXPIRED',
				],
				'expected' => [
					'valid' => false,
					'couponFound' => true,
					'coupon' => null
				],
				'message'  => 'Valid coupon code, expired (ALREADYEXPIRED): invalid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => 'INSIDEDATERANGE',
				],
				'expected' => [
					'valid' => true,
					'couponFound' => true,
					'coupon' => 'INSIDEDATERANGE'
				],
				'message'  => 'Valid coupon code, inside the date range (INSIDEDATERANGE): valid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => 'FORLEVEL1',
				],
				'expected' => [
					'valid' => true,
					'couponFound' => true,
					'coupon' => 'FORLEVEL1'
				],
				'message'  => 'Valid coupon code, limited to this subscription level (FORLEVEL1): valid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => 'FORLEVEL2',
				],
				'expected' => [
					'valid' => false,
					'couponFound' => true,
					'coupon' => null
				],
				'message'  => 'Valid coupon code, limited to other subscription level (FORLEVEL2): invalid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => 'FORUSER1',
				],
				'expected' => [
					'valid' => true,
					'couponFound' => true,
					'coupon' => 'FORUSER1'
				],
				'message'  => 'Valid coupon code, limited to our user (FORUSER1): valid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => 'FORUSER2',
				],
				'expected' => [
					'valid' => false,
					'couponFound' => true,
					'coupon' => null
				],
				'message'  => 'Valid coupon code, limited to other user (FORUSER2): invalid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => 'FORUSER1EMAIL',
				],
				'expected' => [
					'valid' => true,
					'couponFound' => true,
					'coupon' => 'FORUSER1EMAIL'
				],
				'message'  => 'Valid coupon code, limited to our email address (FORUSER1EMAIL): valid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => 'FORUSER2EMAIL',
				],
				'expected' => [
					'valid' => false,
					'couponFound' => true,
					'coupon' => null
				],
				'message'  => 'Valid coupon code, limited to other email address (FORUSER2EMAIL): invalid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => 'FORSUBSCRIBERS',
				],
				'expected' => [
					'valid' => true,
					'couponFound' => true,
					'coupon' => 'FORSUBSCRIBERS'
				],
				'message'  => 'Valid coupon code, limited to our user group (FORSUBSCRIBERS): valid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => 'FORSUPERUSERS',
				],
				'expected' => [
					'valid' => false,
					'couponFound' => true,
					'coupon' => null
				],
				'message'  => 'Valid coupon code, limited to other user group (FORSUPERUSERS): invalid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => 'TENHITS',
				],
				'expected' => [
					'valid' => true,
					'couponFound' => true,
					'coupon' => 'TENHITS'
				],
				'message'  => 'Valid coupon code, hits limit not reached (TENHITS): valid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => 'ONEHIT',
				],
				'expected' => [
					'valid' => false,
					'couponFound' => true,
					'coupon' => null
				],
				'message'  => 'Valid coupon code, hits limit already reached (ONEHIT): invalid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => 'TENUSERHITS',
				],
				'expected' => [
					'valid' => true,
					'couponFound' => true,
					'coupon' => 'TENUSERHITS'
				],
				'message'  => 'Valid coupon code, logged in, user hits limit not reached (TENUSERHITS): valid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => 'ONEUSERHIT',
				],
				'expected' => [
					'valid' => false,
					'couponFound' => true,
					'coupon' => null
				],
				'message'  => 'Valid coupon code, logged in, user hits limit already reached (ONEUSERHIT): invalid'
			],
			[
				'loggedIn' => 'user1',
				'state'    => [
					'id' => '1',
					'email' => 'user1@test.web',
					'coupon' => 'TWOUSERHITS',
				],
				'expected' => [
					'valid' => true,
					'couponFound' => true,
					'coupon' => 'TWOUSERHITS'
				],
				'message'  => 'Valid coupon code, logged in, user hits limit already reached for a different user (TWOUSERHITS): valid'
			],
			[
				'loggedIn' => 'guest',
				'state'    => [
					'id' => '1',
					'email' => 'newuser@test.web',
					'coupon' => 'TENUSERHITS',
				],
				'expected' => [
					'valid' => true,
					'couponFound' => true,
					'coupon' => 'TENUSERHITS'
				],
				'message'  => 'Valid coupon code, guest, user hits limit (TENUSERHITS): valid'
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
		self::$jUser = self::$users[$loggedIn];

		parent::testGetValidationResult($state, $expected, $message);
	}

	public function performAssertion($expected, $actual, $message)
	{
		if (is_null($expected['coupon']))
		{
			$this->assertEquals($expected, $actual, $message);

			return;
		}

		$this->assertEquals($expected['valid'], $actual['valid'], $message . ' (valid)');
		$this->assertEquals($expected['couponFound'], $actual['couponFound'], $message . ' (couponFound)');

		$actualCouponCode = $actual['coupon']->coupon;
		$this->assertEquals($expected['coupon'], $actualCouponCode, $message . ' (coupon code)');
	}

}