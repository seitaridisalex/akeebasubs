<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Admin\PluginAbstracts;

use Akeeba\Subscriptions\Admin\PluginAbstracts\AkpaymentBase;
use Akeeba\Subscriptions\Site\Model\Subscriptions;
use Akeeba\Subscriptions\Tests\Stubs\ValidatorWithSubsTestCase;
use FOF30\Container\Container;
use FOF30\Date\Date;

class AkpaymentBaseFixDatesTest extends \PHPUnit_Framework_TestCase
{

	/** @var   Container  The container of the component */
	public static $container = null;

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
	}

	/**
	 * The data to set up and run tests.
	 *
	 * The return is an array of arrays.
	 *
	 * @return  array  See above
	 */
	public function getFixSubscriptionDatesData()
	{
		return [
			[
				'uid'         => 2000,
				'submods2000' => [
					'publish_up' => 'past', // past, future, now
					'fixdates'   => null // null or array
				],
				'expected'    => [
					'publish_up'   => 'Now',
					'publish_down' => null,
					'enabled'      => 1,
				],
				'message'     => "No fixdates keys, publish_up in the past"
			],
			[
				'uid'         => 2000,
				'submods2000' => [
					'publish_up' => 'now', // past, future, now
					'fixdates'   => null // null or array
				],
				'expected'    => [
					'publish_up'   => 'Now',
					'publish_down' => null,
					'enabled'      => 1,
				],
				'message'     => "No fixdates keys, publish_up now"
			],
			[
				'uid'         => 2000,
				'submods2000' => [
					'publish_up' => 'future', // past, future, now
					'fixdates'   => null // null or array
				],
				'expected'    => [
					'publish_up'   => 'Same',
					'publish_down' => null,
					'enabled'      => 1,
				],
				'message'     => "No fixdates keys, publish_up in the future"
			],
			[
				'uid'         => 2000,
				'submods2000' => [
					'level'        => 4,
					'publish_up'   => 'past', // past, future, now
					'publish_down' => '2038-01-01 00:00:00',
					'fixdates'     => null // null or array
				],
				'expected'    => [
					'publish_up'   => 'Now',
					'publish_down' => '2038-01-01 00:00:00',
					'enabled'      => 1,
				],
				'message'     => "No fixdates, forever/fixed date subscription"
			],
			[
				'uid'         => 2000,
				'submods2000' => [
					'level'      => 4,
					'publish_up' => 'past', // past, future, now
					'fixdates'   => [
						'oldsub'     => null,
						'allsubs'    => [],
						'expiration' => 'overlap'
					]
				],
				'expected'    => [
					'publish_up'   => 'Now',
					'publish_down' => null,
					'enabled'      => 1,
				],
				'message'     => "oldsub is null. publish_up in the past."
			],
			[
				'uid'         => 2000,
				'submods2000' => [
					'level'      => 4,
					'publish_up' => 'past', // past, future, now
					'fixdates'   => [
						'oldsub'     => null,
						'allsubs'    => [],
						'expiration' => 'replace'
					]
				],
				'expected'    => [
					'publish_up'   => 'Now',
					'publish_down' => null,
					'enabled'      => 1,
				],
				'message'     => "oldsub is null and expiration=replace (downgraded to overlap). publish_up in the past."
			],
			[
				'uid'         => 2000,
				'submods2000' => [
					'level'      => 4,
					'publish_up' => 'past', // past, future, now
					'fixdates'   => [
						'oldsub'     => null,
						'allsubs'    => [],
						'expiration' => 'after'
					]
				],
				'expected'    => [
					'publish_up'   => 'Now',
					'publish_down' => null,
					'enabled'      => 1,
				],
				'message'     => "oldsub is null and expiration=after (downgraded to overlap). publish_up in the past."
			],
			[
				'uid'         => 2000,
				'submods2000' => [
					'level'      => 4,
					'publish_up' => 'past', // past, future, now
					'fixdates'   => [
						'oldsub'     => 9999,
						'allsubs'    => [],
						'expiration' => 'after'
					]
				],
				'expected'    => [
					'publish_up'   => 'Now',
					'publish_down' => null,
					'enabled'      => 1,
				],
				'message'     => "oldsub does not exist. publish_up in the past."
			],

			// Expiration = replace. The old subscription is on the same level and gets disabled.
			[
				'uid'         => 2010,
				'submods2000' => [
					'publish_up' => 'past', // past, future, now
					'fixdates'   => [
						'oldsub'     => 2010,
						'allsubs'    => [2010],
						'expiration' => 'replace'
					]
				],
				'expected'    => [
					'publish_up'   => 'Now',
					'publish_down' => null,
					'enabled'      => 1,
				],
				'message'     => "expiration = replace. oldsub in the same level (active), publish_up in the past"
			],
			[
				'uid'         => 2010,
				'submods2000' => [
					'publish_up' => 'now', // past, future, now
					'fixdates'   => [
						'oldsub'     => 2010,
						'allsubs'    => [2010],
						'expiration' => 'replace'
					]
				],
				'expected'    => [
					'publish_up'   => 'Now',
					'publish_down' => null,
					'enabled'      => 1,
				],
				'message'     => "expiration = replace. oldsub in the same level (active), publish_up now"
			],
			[
				'uid'         => 2010,
				'submods2000' => [
					'publish_up' => 'future', // past, future, now
					'fixdates'   => [
						'oldsub'     => 2010,
						'allsubs'    => [2010],
						'expiration' => 'replace'
					]
				],
				'expected'    => [
					'publish_up'   => 'Same',
					'publish_down' => null,
					'enabled'      => 1,
				],
				'message'     => "expiration = replace. oldsub in the same level (active), publish_up in the future"
			],

			// Expiration = replace. The old subscription is on a different level and MUST be disabled
			[
				'uid'         => 2030,
				'submods2000' => [
					'publish_up' => 'past', // past, future, now
					'fixdates'   => [
						'oldsub'     => 2030,
						'allsubs'    => [2030],
						'expiration' => 'replace'
					]
				],
				'expected'    => [
					'publish_up'   => 'Now',
					'publish_down' => null,
					'enabled'      => 1,
				],
				'message'     => "expiration = replace. oldsub in another level (active), publish_up in the past"
			],
			[
				'uid'         => 2030,
				'submods2000' => [
					'publish_up' => 'now', // past, future, now
					'fixdates'   => [
						'oldsub'     => 2030,
						'allsubs'    => [2030],
						'expiration' => 'replace'
					]
				],
				'expected'    => [
					'publish_up'   => 'Now',
					'publish_down' => null,
					'enabled'      => 1,
				],
				'message'     => "expiration = replace. oldsub in another level (active), publish_up now"
			],
			[
				'uid'         => 2030,
				'submods2000' => [
					'publish_up' => 'future', // past, future, now
					'fixdates'   => [
						'oldsub'     => 2030,
						'allsubs'    => [2030],
						'expiration' => 'replace'
					]
				],
				'expected'    => [
					'publish_up'   => 'Same',
					'publish_down' => null,
					'enabled'      => 1,
				],
				'message'     => "expiration = replace. oldsub in another level (active), publish_up in the future"
			],

			// Expiration = replace. With many allsubs
			[
				'uid'         => 2040,
				'submods2000' => [
					'publish_up' => 'past', // past, future, now
					'fixdates'   => [
						'oldsub'     => 2040,
						'allsubs'    => [2040, 2041, 2042, 2043, 2044, 2045],
						'expiration' => 'replace'
					]
				],
				'expected'    => [
					'publish_up'   => 'Now',
					'publish_down' => null,
					'enabled'      => 1,
				],
				'message'     => "expiration = replace. oldsub in the same level (active), allsubs in mixed levels (active & expired), publish_up in the past."
			],
			[
				'uid'         => 2040,
				'submods2000' => [
					'publish_up' => 'now', // past, future, now
					'fixdates'   => [
						'oldsub'     => 2040,
						'allsubs'    => [2040, 2041, 2042, 2043, 2044, 2045],
						'expiration' => 'replace'
					]
				],
				'expected'    => [
					'publish_up'   => 'Now',
					'publish_down' => null,
					'enabled'      => 1,
				],
				'message'     => "expiration = replace. oldsub in the same level (active), allsubs in mixed levels (active & expired), publish_up now"
			],
			[
				'uid'         => 2040,
				'submods2000' => [
					'publish_up' => 'future', // past, future, now
					'fixdates'   => [
						'oldsub'     => 2040,
						'allsubs'    => [2040, 2041, 2042, 2043, 2044, 2045],
						'expiration' => 'replace'
					]
				],
				'expected'    => [
					'publish_up'   => 'Same',
					'publish_down' => null,
					'enabled'      => 1,
				],
				'message'     => "expiration = replace. oldsub in the same level (active), allsubs in mixed levels (active & expired), publish_up in the future"
			],

			// Expiration = after. The old subscription 2010 is on the same level and was disabled anyway.
			[
				'uid'         => 2010,
				'submods2000' => [
					'publish_up' => 'past', // past, future, now
					'fixdates'   => [
						'oldsub'     => 2010,
						'allsubs'    => [2010],
						'expiration' => 'after'
					]
				],
				'expected'    => [
					'publish_up'   => 'Now', // The old sub's expiration date is in the past (yesterday). We can't start a subscription in the past when it's paid NOW!
					'publish_down' => null,
					'enabled'      => 1,
				],
				'message'     => "expiration = after. oldsub in the same level (active), publish_up in the past"
			],
			[
				'uid'         => 2010,
				'submods2000' => [
					'publish_up' => 'now', // past, future, now
					'fixdates'   => [
						'oldsub'     => 2010,
						'allsubs'    => [2010],
						'expiration' => 'after'
					]
				],
				'expected'    => [
					'publish_up'   => 'Now',
					'publish_down' => null, // The old sub's expiration date is in the past (yesterday). We can't start a subscription in the past when it's paid NOW!
					'enabled'      => 1,
				],
				'message'     => "expiration = after. oldsub in the same level (active), publish_up now"
			],
			[
				'uid'         => 2020,
				'submods2000' => [
					'publish_up' => 'now', // past, future, now
					'fixdates'   => [
						'oldsub'     => 2020,
						'allsubs'    => [2020],
						'expiration' => 'after'
					]
				],
				'expected'    => [
					'publish_up'   => 'down@2020', // The old sub's expiration is in the future, so that's when our new subscription starts
					'publish_down' => null,
					'enabled'      => 1,
				    '_allsubs_active' => [2020] // Subscription 2020 is active and remains active (since the expiration is AFTER the existing subscription)
				],
				'message'     => "expiration = after. oldsub in the same level (active), publish_up now, must become half year from now"
			],
			[
				'uid'         => 2010,
				'submods2000' => [
					'publish_up' => 'future', // past, future, now
					'fixdates'   => [
						'oldsub'     => 2010,
						'allsubs'    => [2010],
						'expiration' => 'after'
					]
				],
				'expected'    => [
					'publish_up'   => 'Same', // Clearly, our start is in the future and it shall remain so
					'publish_down' => null,
					'enabled'      => 1,
				],
				'message'     => "expiration = after. oldsub in the same level (active), publish_up in the future"
			],

			// Expiration = after. The old subscription is in another level
			[
				'uid'         => 2030,
				'submods2000' => [
					'publish_up' => 'past', // past, future, now
					'fixdates'   => [
						'oldsub'     => 2030,
						'allsubs'    => [2030],
						'expiration' => 'after'
					]
				],
				'expected'    => [
					'publish_up'   => 'down@2030',  // The old sub's expiration is in the future
					'publish_down' => null,
					'enabled'      => 1,
					'_allsubs_active' => [2030]
				],
				'message'     => "expiration = after. oldsub in the same level (active), publish_up in the past"
			],
			[
				'uid'         => 2030,
				'submods2000' => [
					'publish_up' => 'now', // past, future, now
					'fixdates'   => [
						'oldsub'     => 2030,
						'allsubs'    => [2030],
						'expiration' => 'after'
					]
				],
				'expected'    => [
					'publish_up'   => 'down@2030',  // The old sub's expiration is in the future
					'publish_down' => null,
					'enabled'      => 1,
					'_allsubs_active' => [2030]
				],
				'message'     => "expiration = after. oldsub in the same level (active), publish_up now"
			],
			[
				'uid'         => 2030,
				'submods2000' => [
					'publish_up' => 'future', // past, future, now
					'fixdates'   => [
						'oldsub'     => 2030,
						'allsubs'    => [2030],
						'expiration' => 'after'
					]
				],
				'expected'    => [
					'publish_up'   => 'Same', // Clearly, our start is in the future and it shall remain so
					'publish_down' => null,
					'enabled'      => 1,
					'_allsubs_active' => [2030]
				],
				'message'     => "expiration = after. oldsub in the same level (active), publish_up in the future"
			],

			// Expiration = after. With many allsubs
			[
				'uid'         => 2040,
				'submods2000' => [
					'publish_up' => 'past', // past, future, now
					'fixdates'   => [
						'oldsub'     => 2040,
						'allsubs'    => [2040, 2041, 2042, 2043, 2044, 2045],
						'expiration' => 'after'
					]
				],
				'expected'    => [
					'publish_up'   => 'Now',
					'publish_down' => null,
					'enabled'      => 1,
					'_allsubs_active' => [2041, 2042, 2044, 2045],
				],
				'message'     => "expiration = replace. oldsub in the same level (active), allsubs in mixed levels (active & expired), publish_up in the past."
			],
			[
				'uid'         => 2040,
				'submods2000' => [
					'publish_up' => 'now', // past, future, now
					'fixdates'   => [
						'oldsub'     => 2040,
						'allsubs'    => [2040, 2041, 2042, 2043, 2044, 2045],
						'expiration' => 'after'
					]
				],
				'expected'    => [
					'publish_up'   => 'Now',
					'publish_down' => null,
					'enabled'      => 1,
					'_allsubs_active' => [2041, 2042, 2044, 2045],
				],
				'message'     => "expiration = replace. oldsub in the same level (active), allsubs in mixed levels (active & expired), publish_up now"
			],
			[
				'uid'         => 2040,
				'submods2000' => [
					'publish_up' => 'future', // past, future, now
					'fixdates'   => [
						'oldsub'     => 2040,
						'allsubs'    => [2040, 2041, 2042, 2043, 2044, 2045],
						'expiration' => 'after'
					]
				],
				'expected'    => [
					'publish_up'   => 'Same',
					'publish_down' => null,
					'enabled'      => 1,
					'_allsubs_active' => [2041, 2042, 2044, 2045],
				],
				'message'     => "expiration = replace. oldsub in the same level (active), allsubs in mixed levels (active & expired), publish_up in the future"
			],

			// Expiration = after. With many allsubs and subscription in the future
			[
				'uid'         => 2040,
				'submods2000' => [
					'publish_up' => 'past', // past, future, now
					'fixdates'   => [
						'oldsub'     => 2042,
						'allsubs'    => [2040, 2041, 2042, 2043, 2044, 2045],
						'expiration' => 'after'
					]
				],
				'expected'    => [
					'publish_up'   => 'down@2042',
					'publish_down' => null,
					'enabled'      => 1,
					'_allsubs_active' => [2041, 2042, 2044, 2045],
				],
				'message'     => "expiration = replace. oldsub in the same level (active), allsubs in mixed levels (active & expired), publish_up in the past."
			],
			[
				'uid'         => 2040,
				'submods2000' => [
					'publish_up' => 'now', // past, future, now
					'fixdates'   => [
						'oldsub'     => 2042,
						'allsubs'    => [2040, 2041, 2042, 2043, 2044, 2045],
						'expiration' => 'after'
					]
				],
				'expected'    => [
					'publish_up'   => 'down@2042',
					'publish_down' => null,
					'enabled'      => 1,
					'_allsubs_active' => [2041, 2042, 2044, 2045],
				],
				'message'     => "expiration = replace. oldsub in the same level (active), allsubs in mixed levels (active & expired), publish_up now"
			],

			// What happens if my new subscription starts in the future but BEFORE the existing subscription expires?
			// This will never happen in the real world. The start date of the subscription would already be the
			// latest expiration date. You can't even have two subscriptions waiting for payment confirmation at the
			// same time without violating another check made by the Subscribe model. Basically, this test case requires
			// several bugs to all happen at the same time. Well, I can't test that, can I?
//
//			[
//				'uid'         => 2040,
//				'submods2000' => [
//					'publish_up' => 'future', // past, future, now
//					'fixdates'   => [
//						'oldsub'     => 2042,
//						'allsubs'    => [2040, 2041, 2042, 2043, 2044, 2045],
//						'expiration' => 'after'
//					]
//				],
//				'expected'    => [
//					'publish_up'   => 'down@2042', // 2042 Expires in 18 months. "Future" is in just 12 months.
//					'publish_down' => null,
//					'enabled'      => 1,
//					'_allsubs_active' => [2041, 2042, 2044, 2045],
//				],
//				'message'     => "expiration = replace. oldsub in the same level (active), allsubs in mixed levels (active & expired), publish_up in the future"
//			],

		];
	}

	/**
	 * Test the validator
	 *
	 * @dataProvider getFixSubscriptionDatesData
	 */
	public function testFixSubscriptionDates($uid, array $subMods2000, array $expected, $message)
	{
		$subDates = self::changeSubscriptionDates();

		// First modify subscription with ID 2000
		/** @var Subscriptions $sub */
		$sub = self::$container->factory->model('Subscriptions')->tmpInstance();
		$sub->findOrFail(2000);

		$sub->user_id = $uid;

		$level                    = isset($subMods2000['level']) ? $subMods2000['level'] : 100;
		$sub->akeebasubs_level_id = $level;
		$sub->getRelations()->rebase($sub);

		$jPublishUp = new Date();

		switch ($subMods2000['publish_up'])
		{
			case 'past':
				$jPublishUp->sub(new \DateInterval('P30D'));
				break;

			case 'future':
				$jPublishUp->add(new \DateInterval('P30D'));
				break;
		}

		$sub->publish_up = $jPublishUp->toSql();

		$sub->publish_down = isset($subMods2000['publish_down']) ? $subMods2000['publish_down'] : $jPublishUp->add(new \DateInterval('P365D'))
		                                                                                                     ->toSql();

		if (is_null($subMods2000['fixdates']))
		{
			$sub->params = [];
		}
		else
		{
			$oldparams             = $sub->params;
			$oldparams['fixdates'] = $subMods2000['fixdates'];
			$sub->params           = $oldparams;
		}

		$actual = [];

		// Run the test
		AkpaymentBase::fixSubscriptionDates($sub, $actual);

		// Post-process the $expected array for the dates
		if ($expected['publish_up'] == 'Now')
		{
			$expected['publish_up'] = (new Date())->toUnix();
		}
		elseif ($expected['publish_up'] == 'Same')
		{
			$expected['publish_up'] = $jPublishUp->sub(new \DateInterval('P365D'))->toUnix();
		}
		elseif (strpos($expected['publish_up'], '@') !== false)
		{
			list ($upDown, $subId) = explode('@', $expected['publish_up']);

			$expected['publish_up'] = (new Date($subDates[ $subId ][ $upDown ]))->toUnix();
		}

		if (is_null($expected['publish_down']))
		{
			$expected['publish_down'] = (new Date($expected['publish_up']))->add(new \DateInterval('P365D'))
			                                                                 ->toUnix();
		}
		else
		{
			$expected['publish_down'] = (new Date($expected['publish_down']))->toUnix();
		}

		$actual['publish_up']   = (new Date($actual['publish_up']))->toUnix();
		$actual['publish_down'] = (new Date($actual['publish_down']))->toUnix();

		// Perform assertions. Date matches with up to 2 hours difference to cater for GMT discrepancies in our test system.
		$this->assertEquals($expected['publish_up'], $actual['publish_up'], $message . ' –– publish_up', 7200);
		$this->assertEquals($expected['publish_down'], $actual['publish_down'], $message . ' –– publish_down', 7200);

		// Other assertions
		$this->assertEquals($expected['enabled'], 1, $message . ' –– enabled');

		$this->assertEquals([], $actual['params'], $message . ' –– params');

		if (is_array($subMods2000['fixdates']) && isset($subMods2000['fixdates']['allsubs']) && !empty($subMods2000['fixdates']['allsubs']))
		{
			$expect_enabled = [];

			if (isset($expected['_allsubs_active']))
			{
				$expect_enabled = $expected['_allsubs_active'];
			}

			foreach ($subMods2000['fixdates']['allsubs'] as $oldSubId)
			{
				/** @var Subscriptions $oldRecord */
				$oldRecord = self::$container->factory->model('Subscriptions')->tmpInstance();
				$oldRecord->findOrFail($oldSubId);

				$expectedStatus = in_array($oldSubId, $expect_enabled) ? 1 : 0;
				$expectedStatusText = $expectedStatus ? 'enabled'  : 'disabled';

				$this->assertEquals($expectedStatus, $oldRecord->enabled, $message . " –– old sub $oldSubId is not $expectedStatusText");
			}
		}
	}

	/**
	 * Change the stored subscriptions' dates before runnign each test
	 *
	 * @return  array  [subscription_id => [up, down], ...]
	 *
	 * @see  initialise.sql
	 */
	public static function changeSubscriptionDates()
	{
		$yearAgo                 = (new Date())->sub(new \DateInterval('P1Y1D'))->toSQL();
		$yesterday               = (new Date())->sub(new \DateInterval('P1D'))->toSQL();
		$halfYearAgo             = (new Date())->sub(new \DateInterval('P180D'))->toSQL();
		$afterAYearFromYesterday = (new Date())->add(new \DateInterval('P1Y'))
		                                         ->sub(new \DateInterval('P1D'))
		                                         ->toSQL();
		$afterHalfYear           = (new Date())->add(new \DateInterval('P180D'))->toSQL();
		$after18Months           = (new Date())->add(new \DateInterval('P1Y180D'))->toSQL();

		$mods = [
			[2000, $yesterday, $afterAYearFromYesterday, 0],
			[2010, $yearAgo, $yesterday, 0],
			[2020, $halfYearAgo, $afterHalfYear, 1],
			[2030, $halfYearAgo, $afterHalfYear, 1],
			[2040, $yearAgo, $yesterday, 0],
			[2041, $halfYearAgo, $afterHalfYear, 1],
			[2042, $afterHalfYear, $after18Months, 1],
			[2043, $yearAgo, $yesterday, 0],
			[2044, $halfYearAgo, $afterHalfYear, 1],
			[2045, $afterHalfYear, $after18Months, 1],
		];

		$ret = [];

		foreach ($mods as $mod)
		{
			$ret[ $mod[0] ] = [
				'up'   => $mod[1],
				'down' => $mod[2],
			];
		}

		$db = \JFactory::getDbo();
		$db->transactionStart();

		foreach ($mods as $stuff)
		{
			list($id, $publish_up, $publish_down, $enabled) = $stuff;
			$query = $db->getQuery(true)
			            ->update($db->qn('#__akeebasubs_subscriptions'))
			            ->set($db->qn('publish_up') . ' = ' . $db->q($publish_up) . ', ' .
			                  $db->qn('publish_down') . ' = ' . $db->q($publish_down) . ', ' .
			                  $db->qn('enabled') . ' = ' . $db->q($enabled))
			            ->where($db->qn('akeebasubs_subscription_id') . ' = ' . $db->q($id));
			$db->setQuery($query);
			$db->execute();
		}

		$db->transactionCommit();

		return $ret;
	}
}