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
 * Test the BestAutomaticDiscount validator
 *
 * @covers Akeeba\Subscriptions\Site\Model\Subscribe\Validation\BestAutomaticDiscount
 */
class BestAutomaticDiscountTest extends ValidatorWithSubsTestCase
{

	public static function setUpBeforeClass()
	{
		// Set the validator type
		self::$validatorType = 'BestAutomaticDiscount';

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
					'discount'   => 0.0,
					'expiration' => 'overlap',
					'oldsub'     => null,
					'allsubs'    => [],
					'upgrade_id' => null,
				],
				'message'  => 'Not logged in, no SLL or upgrade rule'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 8,
						'publish_up' => $jNow->toSql()
					]
				],
				'state'    => [
					'id' => '1',
				],
				'expected' => [
					'discount'   => 0.0,
					'expiration' => 'overlap',
					'oldsub'     => null,
					'allsubs'    => [],
					'upgrade_id' => null,
				],
				'message'  => 'Logged in, no SLL or upgrade rule'
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
					'id' => '9',
				],
				'expected' => [
					'discount'   => 25.0,
					'expiration' => 'overlap',
					'oldsub'     => null,
					'allsubs'    => [],
					'upgrade_id' => 13,
				],
				'message'  => 'Only upgrade rule'
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
					'id' => '10',
				],
				'expected' => [
					'discount'   => 15.0,
					'expiration' => 'replace',
					'oldsub'     => 'S1',
					'allsubs'    => ['S1'],
					'upgrade_id' => null,
				],
				'message'  => 'Upgrade and SLL, SLL wins due to bigger discount'
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
					'id' => '9',
				],
				'expected' => [
					'discount'   => 15.0,
					'expiration' => 'replace',
					'oldsub'     => 'S1',
					'allsubs'    => ['S1'],
					'upgrade_id' => null,
				],
				'message'  => 'SLL only'
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
					'id' => '9',
				],
				'expected' => [
					'discount'   => 15.0,
					'expiration' => 'replace',
					'oldsub'     => 'S1',
					'allsubs'    => ['S1'],
					'upgrade_id' => null,
				],
				'message'  => 'SLL only'
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
					'id' => '10',
				],
				'expected' => [
					'discount'   => 25.0,
					'expiration' => 'replace',
					'oldsub'     => 'S1',
					'allsubs'    => ['S1'],
					'upgrade_id' => 15,
				],
				'message'  => 'Upgrade and SLL, the upgrade wins but SLL applies the subscription replacement policy, combine is ignored'
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

		parent::performAssertion($expected, $actual, $message);
	}
}