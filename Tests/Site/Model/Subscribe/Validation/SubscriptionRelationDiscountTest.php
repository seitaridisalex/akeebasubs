<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Site\Model\Subscribe\Validation;

use Akeeba\Subscriptions\Admin\Model\Relations;
use Akeeba\Subscriptions\Tests\Stubs\ValidatorWithSubsTestCase;
use FOF30\Date\Date;

/**
 * Test the SubscriptionRelationDiscount validator
 *
 * @covers Akeeba\Subscriptions\Site\Model\Subscribe\Validation\SubscriptionRelationDiscount
 */
class SubscriptionRelationDiscountTest extends ValidatorWithSubsTestCase
{
	public static function setUpBeforeClass()
	{
		// Set the validator type
		self::$validatorType = 'SubscriptionRelationDiscount';

		// Create the base objects
		parent::setUpBeforeClass();
	}

	public function getTestData()
	{
		$jNow = new Date();

		$jLastYear = clone $jNow;
		$jLastYear->sub(new \DateInterval('P1Y1D'));

		$j13MonthsAgo = clone $jNow;
		$j13MonthsAgo->sub(new \DateInterval('P1Y1M'));

		$jNextYear = clone $jNow;
		$jNextYear->add(new \DateInterval('P1Y1D'));

		$jLastHalfYear = clone($jNow);
		$jLastHalfYear->sub(new \DateInterval('P181D'));

		$jLastMonth = clone($jNow);
		$jLastMonth->sub(new \DateInterval('P31D'));

		$jThreeMonthsAgo = clone($jNow);
		$jThreeMonthsAgo->sub(new \DateInterval('P92D'));

		$jElevenMonthsAgo = clone($jNow);
		$jElevenMonthsAgo->sub(new \DateInterval('P335D'));

		$j370DaysAgo = clone($jNow);
		$j370DaysAgo->sub(new \DateInterval('P370D'));

		return [
			[
				'loggedIn' => 'guest',
				'subs'     => [
					[
						'level'      => 1,
						'publish_up' => $jNow->toSql()
					]
				],
				'state'    => [
					'id' => '1',
				],
				'expected' => [
					'discount' => 0.0,
					'relation' => null,
					'oldsub'   => null,
					'allsubs'  => [],
				],
				'message'  => 'Not logged in, no SLL'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [],
				'state'    => [
					'id' => '1',
				],
				'expected' => [
					'discount' => 0.0,
					'relation' => null,
					'oldsub'   => null,
					'allsubs'  => [],
				],
				'message'  => 'No SLL'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 1,
						'publish_up' => $jNow->toSql()
					]
				],
				'state'    => [
					'id' => '1',
				],
				'expected' => [
					'discount' => 10.0,
					'relation' => 1,
					'oldsub'   => 'S1',
					'allsubs'  => ['S1'],
				],
				'message'  => 'SLL with upgrade rules, replace'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 1,
						'publish_up' => $jNow->toSql()
					]
				],
				'state'    => [
					'id' => '2',
				],
				'expected' => [
					'discount' => 5.0,
					'relation' => 2,
					'oldsub'   => 'S1',
					'allsubs'  => ['S1'],
				],
				'message'  => 'SLL with upgrade rules, extend'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 2,
						'publish_up' => $jNow->toSql()
					]
				],
				'state'    => [
					'id' => '4',
				],
				'expected' => [
					'discount' => 10.0,
					'relation' => 3,
					'oldsub'   => 'S1',
					'allsubs'  => ['S1'],
				],
				'message'  => 'SLL with upgrade rules, overlap'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 7,
						'publish_up' => $jNow->toSql()
					]
				],
				'state'    => [
					'id' => '1',
				],
				'expected' => [
					'discount' => 5.0,
					'relation' => 4,
					'oldsub'   => 'S1',
					'allsubs'  => ['S1'],
				],
				// FREEWITHSIGNUP to LEVEL1
				'message'  => 'SLL with fixed discount, value'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 7,
						'publish_up' => $jNow->toSql()
					]
				],
				'state'    => [
					'id' => '2',
				],
				'expected' => [
					'discount' => 10.0,
					'relation' => 5,
					'oldsub'   => 'S1',
					'allsubs'  => ['S1'],
				],
				// FREEWITHSIGNUP to LEVEL2
				'message'  => 'SLL with fixed discount, percent'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 6,
						'publish_up' => $jNow->toSql()
					],
					[
						'level'      => 6,
						'publish_up' => $jNextYear->toSql(),
					    'enabled'    => 0
					]
				],
				'state'    => [
					'id' => '1',
				],
				'expected' => [
					'discount' => 12, // High threshold
					'relation' => 6,
					'oldsub'   => 'S1',
					'allsubs'  => ['S1', 'S2'],
				],
				// FREE to LEVEL1
				'message'  => 'SLL with flexible discount, value, round down – high threshold'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 6,
						'publish_up' => $jLastHalfYear->toSql()
					],
					[
						'level'      => 6,
						'publish_up' => $jNextYear->toSql(),
						'enabled'    => 0
					]
				],
				'state'    => [
					'id' => '1',
				],
				'expected' => [
					'discount' => 6, // 6 months x 1 per month
					'relation' => 6,
					'oldsub'   => 'S1',
					'allsubs'  => ['S1', 'S2'],
				],
				// FREE to LEVEL1
				'message'  => 'SLL with flexible discount, value, round down – flexible period'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 6,
						'publish_up' => $jElevenMonthsAgo->toSql()
					],
					[
						'level'      => 6,
						'publish_up' => $jNextYear->toSql(),
						'enabled'    => 0
					]
				],
				'state'    => [
					'id' => '1',
				],
				'expected' => [
					'discount' => 2, // low threshold
					'relation' => 6,
					'oldsub'   => 'S1',
					'allsubs'  => ['S1', 'S2'],
				],
				// FREE to LEVEL1
				'message'  => 'SLL with flexible discount, value, round down – low threshold'
			],

			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 6,
						'publish_up' => $j13MonthsAgo->toSql(),
					    'enabled'    => 0,
					],
					[
						'level'      => 6,
						'publish_up' => $jElevenMonthsAgo->toSql(),
					]
				],
				'state'    => [
					'id' => '2',
				],
				'expected' => [
					'discount' => 2, // low threshold
					'relation' => 7,
					'oldsub'   => 'S2',
					'allsubs'  => ['S2'],
				],
				// FREE to LEVEL2
				'message'  => 'SLL with flexible discount, include renewals, value, round down – low threshold'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 6,
						'publish_up' => $jNow->toSql()
					],
					[
						'level'      => 6,
						'publish_up' => $jLastYear->toSql(),
						'enabled'    => 0
					]
				],
				'state'    => [
					'id' => '2',
				],
				'expected' => [
					'discount' => 12, // mid range
					'relation' => 7,
					'oldsub'   => 'S1',
					'allsubs'  => ['S1'], // S2 is out of range (expired)
				],
				// FREE to LEVEL2
				'message'  => 'SLL with flexible discount, include renewals, value, round down – mid range'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 6,
						'publish_up' => $jNow->toSql()
					],
					[
						'level'      => 6,
						'publish_up' => $jNextYear->toSql(),
						'enabled'    => 0
					]
				],
				'state'    => [
					'id' => '2',
				],
				'expected' => [
					'discount' => 22, // high threshold
					'relation' => 7,
					'oldsub'   => 'S1',
					'allsubs'  => ['S1', 'S2'],
				],
				// FREE to LEVEL2
				'message'  => 'SLL with flexible discount, include renewals, value, round down – high threshold'
			],

			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 1,
						'publish_up' => $jNow->toSql()
					],
					[
						'level'      => 2,
						'publish_up' => $jLastYear->toSql(),
					    'enabled'    => 0
					]
				],
				'state'    => [
					'id' => '3',
				],
				'expected' => [
					'discount' => 10,
					'relation' => 8,
					'oldsub'   => 'S1',
					'allsubs'  => ['S1'],
				],
				// LEVEL1 to RECURRING
				'message'  => 'SLL with fixed discount, combine, value – first combined rule active'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 1,
						'publish_up' => $jLastYear->toSql(),
						'enabled'    => 0
					],
					[
						'level'      => 2,
						'publish_up' => $jNow->toSql(),
					]
				],
				'state'    => [
					'id' => '3',
				],
				'expected' => [
					'discount' => 15,
					'relation' => 9,
					'oldsub'   => 'S2',
					'allsubs'  => ['S2'],
				],
				// LEVEL2 to RECURRING
				'message'  => 'SLL with fixed discount, combine, value – second combined rule active'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 1,
						'publish_up' => $jNow->toSql(),
					],
					[
						'level'      => 2,
						'publish_up' => $jNow->toSql(),
					]
				],
				'state'    => [
					'id' => '3',
				],
				'expected' => [
					'discount' => 25,
					'relation' => 9,
					'oldsub'   => 'S2',
					'allsubs'  => ['S1', 'S2'],
				],
				// LEVEL2 to RECURRING
				'message'  => 'SLL with fixed discount, combine, value – both combined rules active'
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
		$expected['oldsub'] = self::translateSubToId($expected['oldsub']);
		$expected['allsubs'] = self::translateSubToId($expected['allsubs']);

		if ($actual['relation'] instanceof Relations)
		{
			$actual['relation'] = $actual['relation']->akeebasubs_relation_id;
		}

		parent::performAssertion($expected, $actual, $message);
	}


}