<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Site\Model\Subscribe\Validation;

use Akeeba\Subscriptions\Tests\Stubs\ValidatorWithSubsTestCase;
use FOF30\Date\Date;

/**
 * Test the Price validator
 *
 * @covers Akeeba\Subscriptions\Site\Model\Subscribe\Validation\Price
 */
class PriceTest extends ValidatorWithSubsTestCase
{

	public static function setUpBeforeClass()
	{
		// Set the validator type
		self::$validatorType = 'Price';

		// Create the base objects
		parent::setUpBeforeClass();
	}

	public function getTestData()
	{
		$jNow = new Date();

		return [
			[
				'loggedIn' => 'guest',
				'subs'     => [],
				'state'    => [
					'id'           => '1',
					'coupon'       => '',
					'country'      => 'US',
					'state'        => '',
					'isbusiness'   => '',
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
				],
				'expected' => [
					'net'        => 110.0,
					'realnet'    => 100.0,
					'signup'     => 10.0,
					'discount'   => 0.0,
					'taxrate'    => 0.0,
					'tax'        => 0.0,
					'gross'      => 110.0,
					'recurring'  => 0.0,
					'usecoupon'  => 0,
					'useauto'    => 0,
					'couponid'   => 0,
					'upgradeid'  => 0,
					'oldsub'     => null,
					'allsubs'    => [],
					'expiration' => 'overlap',
				],
				'message'  => 'With sign-up, Guest, no discount, Extra EU (no VAT)'
			],
			[
				'loggedIn' => 'guest',
				'subs'     => [],
				'state'    => [
					'id'           => '1',
					'coupon'       => '',
					'country'      => 'GR',
					'state'        => '',
					'isbusiness'   => '',
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
				],
				'expected' => [
					'net'        => 110.0,
					'realnet'    => 100.0,
					'signup'     => 10.0,
					'discount'   => 0.0,
					'taxrate'    => 23.0,
					'tax'        => 25.30,
					'gross'      => 135.30,
					'recurring'  => 0.0,
					'usecoupon'  => 0,
					'useauto'    => 0,
					'couponid'   => 0,
					'upgradeid'  => 0,
					'oldsub'     => null,
					'allsubs'    => [],
					'expiration' => 'overlap',
				],
				'message'  => 'With sign-up, Guest, no discount, EU, non-business user (VAT)',
			],
			[
				'loggedIn' => 'guest',
				'subs'     => [],
				'state'    => [
					'id'           => '1',
					'coupon'       => '',
					'country'      => 'GR',
					'state'        => '',
					'isbusiness'   => 1,
					'businessname' => 'Something',
					'occupation'   => 'Something',
					'vatnumber'    => '070298898',
				],
				'expected' => [
					'net'        => 110.0,
					'realnet'    => 100.0,
					'signup'     => 10.0,
					'discount'   => 0.0,
					'taxrate'    => 0.0,
					'tax'        => 0.0,
					'gross'      => 110.00,
					'recurring'  => 0.0,
					'usecoupon'  => 0,
					'useauto'    => 0,
					'couponid'   => 0,
					'upgradeid'  => 0,
					'oldsub'     => null,
					'allsubs'    => [],
					'expiration' => 'overlap',
				],
				'message'  => 'With sign-up, Guest, no discount, EU, business, VIES registered (no VAT)',
			],
			[
				'loggedIn' => 'guest',
				'subs'     => [],
				'state'    => [
					'id'           => '1',
					'coupon'       => '',
					'country'      => 'GR',
					'state'        => '',
					'isbusiness'   => 1,
					'businessname' => 'Something',
					'occupation'   => 'Something',
					'vatnumber'    => '123456789',
				],
				'expected' => [
					'net'        => 110.0,
					'realnet'    => 100.0,
					'signup'     => 10.0,
					'discount'   => 0.0,
					'taxrate'    => 23.0,
					'tax'        => 25.30,
					'gross'      => 135.30,
					'recurring'  => 0.0,
					'usecoupon'  => 0,
					'useauto'    => 0,
					'couponid'   => 0,
					'upgradeid'  => 0,
					'oldsub'     => null,
					'allsubs'    => [],
					'expiration' => 'overlap',
				],
				'message'  => 'With sign-up, Guest, no discount, EU, business, not VIES registered (VAT)',
			],
			[
				'loggedIn' => 'guest',
				'subs'     => [],
				'state'    => [
					'id'           => '1',
					'coupon'       => '',
					'country'      => 'ES',
					'state'        => 'ES-TF',
					'isbusiness'   => 0,
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
				],
				'expected' => [
					'net'        => 110.0,
					'realnet'    => 100.0,
					'signup'     => 10.0,
					'discount'   => 0.0,
					'taxrate'    => 0.0,
					'tax'        => 0.0,
					'gross'      => 110.00,
					'recurring'  => 0.0,
					'usecoupon'  => 0,
					'useauto'    => 0,
					'couponid'   => 0,
					'upgradeid'  => 0,
					'oldsub'     => null,
					'allsubs'    => [],
					'expiration' => 'overlap',
				],
				'message'  => 'With sign-up, Guest, no discount, EU, Canary Islands (special rule, no VAT)',
			],
			[
				'loggedIn' => 'guest',
				'subs'     => [],
				'state'    => [
					'id'           => '1',
					'coupon'       => 'VALIDALL',
					'country'      => 'US',
					'state'        => 'TX',
					'isbusiness'   => 0,
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
				],
				'expected' => [
					'net'        => 110.0, // Prediscount net + signup
					'realnet'    => 100.0, // Prediscount net, without signup
					'signup'     => 10.0, // Prediscount signup
					'discount'   => 55.0, // Discount
					'taxrate'    => 0.0, // Tax rate in % points
					'tax'        => 0.0, // Tax amount = taxrate * (net - discount)
					'gross'      => 55.00, // net - discount
					'recurring'  => 0.0, // Recurring amount
					'usecoupon'  => true,
					'useauto'    => 0,
					'couponid'   => 1,
					'upgradeid'  => 0,
					'oldsub'     => null,
					'allsubs'    => [],
					'expiration' => 'overlap',
				],
				'message'  => 'With sign-up, Guest, coupon discount, Extra EU (no VAT)',
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [],
				'state'    => [
					'id'           => '1',
					'coupon'       => '',
					'country'      => 'US',
					'state'        => '',
					'isbusiness'   => '',
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
				],
				'expected' => [
					'net'        => 110.0,
					'realnet'    => 100.0,
					'signup'     => 10.0,
					'discount'   => 0.0,
					'taxrate'    => 0.0,
					'tax'        => 0.0,
					'gross'      => 110.0,
					'recurring'  => 0.0,
					'usecoupon'  => 0,
					'useauto'    => 0,
					'couponid'   => 0,
					'upgradeid'  => 0,
					'oldsub'     => null,
					'allsubs'    => [],
					'expiration' => 'overlap',
				],
				'message'  => 'With sign-up, Logged in, no discount, Extra EU (no VAT)'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [],
				'state'    => [
					'id'           => '1',
					'coupon'       => '',
					'country'      => 'GR',
					'state'        => '',
					'isbusiness'   => '',
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
				],
				'expected' => [
					'net'        => 110.0,
					'realnet'    => 100.0,
					'signup'     => 10.0,
					'discount'   => 0.0,
					'taxrate'    => 23.0,
					'tax'        => 25.30,
					'gross'      => 135.30,
					'recurring'  => 0.0,
					'usecoupon'  => 0,
					'useauto'    => 0,
					'couponid'   => 0,
					'upgradeid'  => 0,
					'oldsub'     => null,
					'allsubs'    => [],
					'expiration' => 'overlap',
				],
				'message'  => 'With sign-up, Logged in, no discount, EU, non-business user (VAT)',
			],

			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
					    'publish_up' => $jNow->toSql()
					]
				],
				'state'    => [
					'id'           => '1',
					'coupon'       => 'VALIDALL',
					'country'      => 'US',
					'state'        => '',
					'isbusiness'   => '',
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
				],
				'expected' => [
					'net'        => 100.0,
					'realnet'    => 100.0,
					'signup'     => 0.0,
					'discount'   => 50.0,
					'taxrate'    => 0.0,
					'tax'        => 0.0,
					'gross'      => 50.0,
					'recurring'  => 0.0,
					'usecoupon'  => true,
					'useauto'    => 0,
					'couponid'   => 1,
					'upgradeid'  => 0,
					'oldsub'     => 'S1',
					'allsubs'    => ['S1'],
					'expiration' => 'replace',
				],
				'message'  => 'With sign-up,Logged in, coupon + best upgrade, coupon wins, Extra EU (no VAT)'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
						'publish_up' => $jNow->toSql()
					]
				],
				'state'    => [
					'id'           => '1',
					'coupon'       => 'VALIDALL',
					'country'      => 'GR',
					'state'        => '',
					'isbusiness'   => '',
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
				],
				'expected' => [
					'net'        => 100.0,
					'realnet'    => 100.0,
					'signup'     => 0.0,
					'discount'   => 50.0,
					'taxrate'    => 23.0,
					'tax'        => 11.50,
					'gross'      => 61.50,
					'recurring'  => 0.0,
					'usecoupon'  => true,
					'useauto'    => 0,
					'couponid'   => 1,
					'upgradeid'  => 0,
					'oldsub'     => 'S1',
					'allsubs'    => ['S1'],
					'expiration' => 'replace',
				],
				'message'  => 'With sign-up,Logged in, coupon + best upgrade, coupon wins, EU, non-business user (VAT)'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
						'publish_up' => $jNow->toSql()
					]
				],
				'state'    => [
					'id'           => '1',
					'coupon'       => 'VALIDALL',
					'country'      => 'GR',
					'state'        => 'GR-ATT',
					'isbusiness'   => 1,
					'businessname' => 'Something',
					'occupation'   => 'Something',
					'vatnumber'    => '070298898',
				],
				'expected' => [
					'net'        => 100.0,
					'realnet'    => 100.0,
					'signup'     => 0.0,
					'discount'   => 50.0,
					'taxrate'    => 0.0,
					'tax'        => 0.0,
					'gross'      => 50.0,
					'recurring'  => 0.0,
					'usecoupon'  => true,
					'useauto'    => 0,
					'couponid'   => 1,
					'upgradeid'  => 0,
					'oldsub'     => 'S1',
					'allsubs'    => ['S1'],
					'expiration' => 'replace',
				],
				'message'  => 'With sign-up,Logged in, coupon + best upgrade, coupon wins, EU, business, VIES registered (no VAT)'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
						'publish_up' => $jNow->toSql()
					]
				],
				'state'    => [
					'id'           => '1',
					'coupon'       => 'VALIDALL',
					'country'      => 'GR',
					'state'        => 'GR-ATT',
					'isbusiness'   => 1,
					'businessname' => 'Something',
					'occupation'   => 'Something',
					'vatnumber'    => '123456789',
				],
				'expected' => [
					'net'        => 100.0,
					'realnet'    => 100.0,
					'signup'     => 0.0,
					'discount'   => 50.0,
					'taxrate'    => 23.0,
					'tax'        => 11.50,
					'gross'      => 61.50,
					'recurring'  => 0.0,
					'usecoupon'  => true,
					'useauto'    => 0,
					'couponid'   => 1,
					'upgradeid'  => 0,
					'oldsub'     => 'S1',
					'allsubs'    => ['S1'],
					'expiration' => 'replace',
				],
				'message'  => 'With sign-up,Logged in, coupon + best upgrade, coupon wins, EU, business, not VIES registered (VAT)'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
						'publish_up' => $jNow->toSql()
					]
				],
				'state'    => [
					'id'           => '1',
					'coupon'       => 'VALIDALL',
					'country'      => 'ES',
					'state'        => 'ES-TF',
					'isbusiness'   => 0,
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
				],
				'expected' => [
					'net'        => 100.0,
					'realnet'    => 100.0,
					'signup'     => 0.0,
					'discount'   => 50.0,
					'taxrate'    => 0.0,
					'tax'        => 0.0,
					'gross'      => 50.00,
					'recurring'  => 0.0,
					'usecoupon'  => true,
					'useauto'    => 0,
					'couponid'   => 1,
					'upgradeid'  => 0,
					'oldsub'     => 'S1',
					'allsubs'    => ['S1'],
					'expiration' => 'replace',
				],
				'message'  => 'With sign-up,Logged in, coupon + best upgrade, coupon wins, EU, Canary Islands (special rule, no VAT)'
			],
			[
				'loggedIn' => 'guest',
				'subs'     => [],
				'state'    => [
					'id'           => '3',
					'coupon'       => '',
					'country'      => 'US',
					'state'        => 'TX',
					'isbusiness'   => 0,
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
				],
				'expected' => [
					'net'        => 110.0,
					'realnet'    => 100.0,
					'signup'     => 10.0,
					'discount'   => 0.0,
					'taxrate'    => 0.0,
					'tax'        => 0.0,
					'gross'      => 110.00,
					'recurring'  => 100.00,
					'usecoupon'  => false,
					'useauto'    => 0,
					'couponid'   => 0,
					'upgradeid'  => 0,
					'oldsub'     => null,
					'allsubs'    => [],
					'expiration' => 'overlap',
				],
				'message'  => 'Recurring, Guest, no discount, Extra EU (no VAT)'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [],
				'state'    => [
					'id'           => '3',
					'coupon'       => '',
					'country'      => 'US',
					'state'        => 'TX',
					'isbusiness'   => 0,
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
				],
				'expected' => [
					'net'        => 110.0,
					'realnet'    => 100.0,
					'signup'     => 10.0,
					'discount'   => 0.0,
					'taxrate'    => 0.0,
					'tax'        => 0.0,
					'gross'      => 110.00,
					'recurring'  => 100.00,
					'usecoupon'  => false,
					'useauto'    => 0,
					'couponid'   => 0,
					'upgradeid'  => 0,
					'oldsub'     => null,
					'allsubs'    => [],
					'expiration' => 'overlap',
				],
				'message'  => 'Recurring, Logged in, no discount, Extra EU (no VAT)'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
					    'publish_up' => $jNow->toSql()
					]
				],
				'state'    => [
					'id'           => '3',
					'coupon'       => 'VALIDALL',
					'country'      => 'US',
					'state'        => 'TX',
					'isbusiness'   => 0,
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
				],
				'expected' => [
					'net'        => 110.0,
					'realnet'    => 100.0,
					'signup'     => 10.0,
					'discount'   => 55.0,
					'taxrate'    => 0.0,
					'tax'        => 0.0,
					'gross'      => 55.00,
					'recurring'  => 50.00,
					'usecoupon'  => true,
					'useauto'    => 0,
					'couponid'   => 1,
					'upgradeid'  => 0,
					'oldsub'     => 'S1',
					'allsubs'    => ['S1'],
					'expiration' => 'replace',
				],
				'message'  => 'Recurring, Logged in, coupon & SLL discount, coupon wins, Extra EU (no VAT)'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
						'publish_up' => $jNow->toSql()
					]
				],
				'state'    => [
					'id'           => '3',
					'coupon'       => 'VALIDALL',
					'country'      => 'GR',
					'state'        => 'GR-ATT',
					'isbusiness'   => 0,
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
				],
				'expected' => [
					'net'        => 110.0,
					'realnet'    => 100.0,
					'signup'     => 10.0,
					'discount'   => 55.0,
					'taxrate'    => 23.0,
					'tax'        => 12.65,
					'gross'      => 67.65,
					'recurring'  => 61.50,
					'usecoupon'  => true,
					'useauto'    => 0,
					'couponid'   => 1,
					'upgradeid'  => 0,
					'oldsub'     => 'S1',
					'allsubs'    => ['S1'],
					'expiration' => 'replace',
				],
				'message'  => 'Recurring, Logged in, coupon & SLL discount, coupon wins, EU, non-business user (VAT)'
			]
		];
	}

	/**
	 * Test the validator
	 *
	 * @dataProvider getTestData
	 */
	public function testGetValidationResult($loggedIn, $subs, $state, $expected, $message)
	{
		$this->createSubscriptions($subs);

		self::$jUser = self::$users[ $loggedIn ];
		self::$factory->reset();

		parent::testGetValidationResult($state, $expected, $message);
	}

	/**
	 * Perform the assertion(s) required for this test
	 *
	 * @param   mixed  $expected Expected value
	 * @param   mixed  $actual   Actual validator result
	 * @param   string $message  Message to show on failure
	 *
	 * @return  void
	 */
	public function performAssertion($expected, $actual, $message)
	{
		$expected['oldsub']  = self::translateSubToId($expected['oldsub']);
		$expected['allsubs'] = self::translateSubToId($expected['allsubs']);

		unset($actual['taxrule_id']);
		unset($actual['tax_match']);
		unset($actual['tax_fuzzy']);

		parent::performAssertion($expected, $actual, $message);
	}
}