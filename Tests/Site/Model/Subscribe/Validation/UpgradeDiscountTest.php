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
 * @covers Akeeba\Subscriptions\Site\Model\Subscribe\Validation\UpgradeDiscount
 */
class UpgradeDiscountTest extends ValidatorWithSubsTestCase
{
	public static function setUpBeforeClass()
	{
		// Set the validator type
		self::$validatorType = 'UpgradeDiscount';

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
					    'enabled' => 0,
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
				'message'  => 'LEVEL1 renewal but no rule catches it (expired subscription) – Only validates in UpgradeExpiredDiscount'
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
					'upgrade_id' => 1,
					'value'      => 10.0,
					'combine'    => false
				],
				'message'  => 'LEVEL1 renewal, first six months'
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
					'upgrade_id' => 3,
					'value'      => 20.0,
					'combine'    => false
				],
				'message'  => 'LEVEL1 renewal, last six months'
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
					'upgrade_id' => 3,
					'value'      => 16.0,
					'combine'    => false
				],
				'message'  => 'LEVEL1 renewal, last six months, different price for lastpercent'
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
					'upgrade_id' => 2,
					'value'      => 5.00,
					'combine'    => false
				],
				'message'  => 'LEVEL1 to LEVEL2, fixed price'
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
					'upgrade_id' => 0,
					'value'      => 0.00,
					'combine'    => false
				],
				'message'  => 'Expired LEVEL1 up to 10 days to LEVEL2 (rule only validates in UpgradeExpiredDiscount)'
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
					'upgrade_id' => 6,
					'value'      => 10.00,
					'combine'    => true
				],
				'message'  => 'LEVEL1 to FOREVER, 10%'
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
					'upgrade_id' => 7,
					'value'      => 10.00,
					'combine'    => true
				],
				'message'  => 'LEVEL2 to FOREVER, 10%'
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
					'upgrade_id' => 7,
					'value'      => 20.00,
					'combine'    => true
				],
				'message'  => 'LEVEL1 and LEVEL2 to FOREVER, combined 10% each (the second rule is reported as active)'
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
					'upgrade_id' => 8,
					'value'      => 10.00,
					'combine'    => true
				],
				'message'  => 'RECURRING to FIXED, active, combined 10%'
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
					'upgrade_id' => 0,
					'value'      => 0.00,
					'combine'    => false
				],
				'message'  => 'RECURRING to FIXED, expired, combined 10% (only validates in UpgradeExpiredDiscount)'
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