<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Site\Model\Subscribe\Validation;

use Akeeba\Subscriptions\Admin\Helper\Select;
use Akeeba\Subscriptions\Tests\Stubs\ValidatorTestCase;

/**
 * Test the State validator
 *
 * @covers Akeeba\Subscriptions\Site\Model\Subscribe\Validation\State
 */
class StateTest extends ValidatorTestCase
{
	public static function setUpBeforeClass()
	{
		self::$validatorType = 'State';

		parent::setUpBeforeClass();

		// Only enable the USA, Greece and Spain states
		$db = \JFactory::getDbo();
		$query = $db->getQuery(true)
			->update($db->qn('#__akeebasubs_states'))
			->set($db->qn('enabled') . ' = ' . $db->q(0))
			->where($db->qn('country') . ' != ' . $db->q('US'));
		$db->setQuery($query)->execute();
		$query = $db->getQuery(true)
			->update($db->qn('#__akeebasubs_states'))
			->set($db->qn('enabled') . ' = ' . $db->q(1))
			->where($db->qn('country') . ' = ' . $db->q('US'));
		$db->setQuery($query)->execute();
		$query = $db->getQuery(true)
			->update($db->qn('#__akeebasubs_states'))
			->set($db->qn('enabled') . ' = ' . $db->q(1))
			->where($db->qn('country') . ' = ' . $db->q('GR'));
		$db->setQuery($query)->execute();
		$query = $db->getQuery(true)
			->update($db->qn('#__akeebasubs_states'))
			->set($db->qn('enabled') . ' = ' . $db->q(1))
			->where($db->qn('country') . ' = ' . $db->q('ES'));
		$db->setQuery($query)->execute();

		\Akeeba\Subscriptions\Admin\Helper\akeebasubsHelperSelect_init();
	}

	public function getTestData()
	{
		return [
			[
				'componentParams' => [
					'_expectEmpty' => true,
					'showstatefield' => 1,
				],
				'state' => [
					'country' => 'GR',
					'state' => 'AL'
				],
				'expected' => false,
				'message' => 'Correct country, state belongs to other country: invalid'
			],

			// ========== Personal information: 1 (YES)
			[
				'componentParams' => [
					'_expectEmpty' => false,
					'showstatefield' => 1,
				],
				'state' => [
					'country' => 'US',
					'state' => 'AL'
				],
				'expected' => true,
				'message' => 'Correct country, state belongs to country: valid'
			],
			[
				'componentParams' => [
					'_expectEmpty' => true,
					'showstatefield' => 1,
				],
				'state' => [
					'country' => 'US',
					'state' => 'BC'
				],
				'expected' => false,
				'message' => 'Correct country, state not belongs to country: invalid'
			],
			[
				'componentParams' => [
					'_expectEmpty' => true,
					'showstatefield' => 1,
				],
				'state' => [
					'country' => 'US',
					'state' => ''
				],
				'expected' => false,
				'message' => 'Correct country, no state: invalid'
			],
			[
				'componentParams' => [
					'_expectEmpty' => true,
					'showstatefield' => 1,
				],
				'state' => [
					'country' => '',
					'state' => ''
				],
				'expected' => false,
				'message' => 'Empty country, no state: invalid'
			],
			[
				'componentParams' => [
					'_expectEmpty' => false,
					'showstatefield' => 1,
				],
				'state' => [
					'country' => 'XX',
					'state' => ''
				],
				'expected' => true,
				'message' => 'No country (XX), no state: valid'
			],
			[
				'componentParams' => [
					'_expectEmpty' => true,
					'showstatefield' => 1,
				],
				'state' => [
					'country' => 'CY',
					'state' => ''
				],
				'expected' => true,
				'message' => 'Country without published states, no state: valid'
			],
			[
				'componentParams' => [
					'_expectEmpty' => true,
					'showstatefield' => 1,
				],
				'state' => [
					'country' => 'GR',
					'state' => 'AL'
				],
				'expected' => false,
				'message' => 'Correct country, state belongs to other country: invalid'
			],

		];
	}

	/**
	 * Test the validator
	 *
	 * @dataProvider getTestData
	 */
	public function testGetValidationResult($componentParams, $state, $expected, $message)
	{
		foreach ($componentParams as $k => $v)
		{
			if (substr($k, 0, 1) == '_')
			{
				continue;
			}

			if (static::$container->params->get($k) != $v)
			{
				static::$container->params->set($k, $v);
				static::$container->params->save();
			}
		}

		Select::getFilteredCountries(true);

		parent::testGetValidationResult($state, $expected, $message);

		if ($componentParams['_expectEmpty'])
		{
			$this->assertEquals('', self::$state->state, $message . ' (State data MUST be reset!)');
		}
	}


}