<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Site\Model\Subscribe\Validation;

use Akeeba\Subscriptions\Tests\Stubs\ValidatorWithSubsTestCase;

/**
 * Test the UpgradeDiscount validator
 *
 * @covers Akeeba\Subscriptions\Site\Model\Subscribe\Validation\UpgradeExpiredDiscount
 */
class UpgradeExpiredDiscountTest extends ValidatorWithSubsTestCase
{
	public static function setUpBeforeClass()
	{
		// Set the validator type
		self::$validatorType = 'UpgradeExpiredDiscount';

		// Create the base objects
		parent::setUpBeforeClass();
	}

	public function getTestData()
	{
		$jNow = \JFactory::getDate();

		$jLastYear = clone $jNow;
		$jLastYear->sub(new \DateInterval('P1Y1D'));

		$jLastHalfYear = clone($jNow);
		$jLastHalfYear->sub(new \DateInterval('P181D'));

		$j370DaysAgo = clone($jNow);
		$j370DaysAgo->sub(new \DateInterval('P370D'));

		return [
			[
				'loggedIn' => 'guineapig',
				'subs'     => [],
				'state'    => [
					'id' => '1',
				],
				'expected' => [
					'upgrade_id' => null,
					'value'      => 0.0,
				    'combine'    => false
				],
				'message'  => 'No upgrade'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
						'publish_up' => $jLastYear->toSql(),
						'enabled' => 1,
					]
				],
				'state'    => [
					'id' => '1',
				],
				'expected' => [
					'upgrade_id' => 0,
					'value'      => 0.0
					,
					'combine'    => false
				],
				'message'  => 'LEVEL1 subscription renewal (not applied, the subscription is not expired)'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
					    'publish_up' => $jLastYear->toSql(),
					    'enabled' => 0,
					]
				],
				'state'    => [
					'id' => '1',
				],
				'expected' => [
					'upgrade_id' => 4,
					'value'      => 30.0
					,
					'combine'    => false
				],
				'message'  => 'LEVEL1 expired subscription renewal'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
					    'publish_up' => $jNow->toSql(),
					]
				],
				'state'    => [
					'id' => '1',
				],
				'expected' => [
					'upgrade_id' => 0,
					'value'      => 0.0,
					'combine'    => false
				],
				'message'  => 'LEVEL1 renewal, first six months (not applied, subscription active)'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
					    'publish_up' => $jLastHalfYear->toSql(),
					]
				],
				'state'    => [
					'id' => '1',
				],
				'expected' => [
					'upgrade_id' => 0,
					'value'      => 0.0,
					'combine'    => false
				],
				'message'  => 'LEVEL1 renewal, last six months (not applied, subscription active)'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
					    'publish_up' => $jLastHalfYear->toSql(),
						'net_amount' => 80,
					]
				],
				'state'    => [
					'id' => '1',
				],
				'expected' => [
					'upgrade_id' => 0,
					'value'      => 0.0,
					'combine'    => false
				],
				'message'  => 'LEVEL1 renewal, last six months, different price for lastpercent (not applied, subscription active)'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
					    'publish_up' => $jLastHalfYear->toSql(),
					]
				],
				'state'    => [
					'id' => '2',
				],
				'expected' => [
					'upgrade_id' => 0,
					'value'      => 0.00,
					'combine'    => false
				],
				'message'  => 'LEVEL1 to LEVEL2, fixed price (not applied, subscription active)'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [],
				'state'    => [
					'id' => '2',
				],
				'expected' => [
					'upgrade_id' => 0,
					'value'      => 0.00,
					'combine'    => false
				],
				'message'  => 'LEVEL1 to LEVEL2, no subscription (rule not applied)'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
					    'publish_up' => $j370DaysAgo->toSql(),
					    'enabled' => 0
					]
				],
				'state'    => [
					'id' => '2',
				],
				'expected' => [
					'upgrade_id' => 5,
					'value'      => 12.34,
					'combine'    => false
				],
				'message'  => 'Expired LEVEL1 up to 10 days to LEVEL2, fixed to 12.34'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
						'publish_up' => $jLastHalfYear->toSql(),
					]
				],
				'state'    => [
					'id' => '4',
				],
				'expected' => [
					'upgrade_id' => 0,
					'value'      => 0.00,
					'combine'    => false
				],
				'message'  => 'LEVEL1 to FOREVER, 10% (not applied, subscription active)'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 2,
						'publish_up' => $jLastHalfYear->toSql(),
					]
				],
				'state'    => [
					'id' => '4',
				],
				'expected' => [
					'upgrade_id' => 0,
					'value'      => 0.00,
					'combine'    => false
				],
				'message'  => 'LEVEL2 to FOREVER, 10% (not applied, subscription active)'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
						'publish_up' => $jLastHalfYear->toSql(),
					],
					[
						'level' => 2,
						'publish_up' => $jLastHalfYear->toSql(),
					]
				],
				'state'    => [
					'id' => '4',
				],
				'expected' => [
					'upgrade_id' => 0,
					'value'      => 0.00,
					'combine'    => false
				],
				'message'  => 'LEVEL1 and LEVEL2 to FOREVER, combined 10% each  (not applied, subscription active)'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 3,
						'publish_up' => $jLastHalfYear->toSql(),
					],
				],
				'state'    => [
					'id' => '5',
				],
				'expected' => [
					'upgrade_id' => 0,
					'value'      => 0.00,
					'combine'    => false
				],
				'message'  => 'RECURRING to FIXED, active, combined 10% (not applied, subscription active)'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 3,
						'publish_up' => $jLastYear->toSql(),
					    'enabled' => 0
					],
				],
				'state'    => [
					'id' => '5',
				],
				'expected' => [
					'upgrade_id' => 9,
					'value'      => 10.00,
					'combine'    => true
				],
				'message'  => 'RECURRING to FIXED, expired, combined 10%'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 6,
						'publish_up' => $jLastYear->toSql(),
					],
				],
				'state'    => [
					'id' => '5',
				],
				'expected' => [
					'upgrade_id' => 0,
					'value'      => 0.00,
					'combine'    => false
				],
				'message'  => 'FREE to FIXED: the rule is unpublished'
			],
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
}