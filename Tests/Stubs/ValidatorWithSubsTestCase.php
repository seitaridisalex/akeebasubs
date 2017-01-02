<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Stubs;

abstract class ValidatorWithSubsTestCase extends ValidatorTestCase
{
	protected static $subscriptions = [];

	public static function setUpBeforeClass()
	{
		// Create the base objects
		parent::setUpBeforeClass();

		// Fake the EU VAT checks
		$reflector     = new \ReflectionClass('Akeeba\Subscriptions\Admin\Helper\EUVATInfo');
		$propReflector = $reflector->getProperty('cache');
		$propReflector->setAccessible(true);
		$propReflector->setValue([
			'vat' => [
				'EL123456789' => false,
				'EL070298898' => true,
				'EL666666666' => false,
				'CY123456789' => false,
				'CY999999999' => true,
			]
		]);
	}


	/**
	 * The subscription records are created dynamically. Therefore our 'expected' array cannot have hard-coded IDs.
	 * Instead we use fake subscription IDs in the format S1, S2 and so on. The number after the 'S' denotes the
	 * subscription created by the createSubscriptions() method. The translateSubToId() method translates these fake
	 * IDs into the actual numeric subscription IDs. The $sub operand can be a string or an array. If $sub is null we
	 * will simply return null.
	 *
	 * @param   mixed $sub
	 *
	 * @return  mixed
	 */
	protected static function translateSubToId($sub)
	{
		if (is_null($sub))
		{
			return null;
		}

		if (is_array($sub))
		{
			$ret = [];

			foreach ($sub as $s)
			{
				$ret[] = self::translateSubToId($s);
			}

			return $ret;
		}

		if (isset(self::$subscriptions[ $sub ]))
		{
			return self::$subscriptions[ $sub ];
		}

		return null;
	}

	/**
	 * Creates subscriptions by directly inserting into the database, without going through DataModel.
	 *
	 * @param   array $subs    An array of arrays containing the customised subscription fields we're going to insert
	 * @param   int   $user_id The numeric user ID of the subscriber
	 *
	 * @return  void
	 */
	protected function createSubscriptions(array $subs, $user_id = 1020)
	{
		self::$subscriptions = [];
		$db                  = \JFactory::getDbo();
		$db->transactionStart();

		$query = $db->getQuery(true)
		            ->delete('#__akeebasubs_subscriptions')
		            ->where($db->qn('user_id') . ' = ' . $db->q($user_id));
		$db->setQuery($query)->execute();

		if (!empty($subs))
		{
			$i = 0;

			foreach ($subs as $params)
			{
				if (isset($params['level']))
				{
					$params['akeebasubs_level_id'] = $params['level'];
					unset ($params['level']);
				}

				if (!isset($params['user_id']))
				{
					$params['user_id'] = $user_id;
				}

				$query = $this->getSubscriptionQuery($params);
				$db->setQuery($query)->execute();

				$id = $db->insertid();

				$i ++;
				self::$subscriptions[ 'S' . $i ] = $id;
			}
		}

		$db->transactionCommit();
	}

	/**
	 * Get the SQL for inserting a subscription row
	 *
	 * @param   array $params Fields for creating the subscription row
	 *
	 * @return  string
	 */
	protected function getSubscriptionQuery(array $params)
	{
		$db = \JFactory::getDbo();

		$jNow = \JFactory::getDate();

		$defaultParams = [
			'user_id'               => 1020,
			'akeebasubs_level_id'   => 1,
			'publish_up'            => $jNow->toSql(),
			'publish_down'          => null,
			'enabled'               => 1,
			'processor'             => 'none',
			'processor_key'         => md5(microtime()),
			'state'                 => 'C',
			'net_amount'            => 100,
			'tax_amount'            => 0,
			'gross_amount'          => 0,
			'recurring_amount'      => 0,
			'tax_percent'           => 0,
			'created_on'            => $jNow->toSql(),
			'params'                => '',
			'akeebasubs_coupon_id'  => 0,
			'akeebasubs_upgrade_id' => 0,
			'prediscount_amount'    => 0,
			'discount_amount'       => 0,
			'contact_flag'          => 0,
			'first_contact'         => $db->getNullDate(),
			'second_contact'        => $db->getNullDate(),
			'after_contact'         => $db->getNullDate(),
		];

		$params = array_merge($defaultParams, $params);

		$params['gross_amount'] = $params['net_amount'] + $params['tax_amount'];

		if ($params['gross_amount'] > 0.001)
		{
			$params['tax_percent'] = 100.0 * $params['tax_amount'] / $params['gross_amount'];
		}

		if (empty($params['publish_down']))
		{
			$oneYear                = new \DateInterval('P1Y');
			$jTo                    = \JFactory::getDate($params['publish_up'])->add($oneYear);
			$params['publish_down'] = $jTo->toSql();
		}

		$query = $db->getQuery(true)
		            ->insert($db->qn('#__akeebasubs_subscriptions'));

		$columns = [];
		$values  = [];

		foreach ($params as $field => $value)
		{
			$columns[] = $db->qn($field);
			$values[]  = $db->q($value);
		}

		$query->columns($columns)
		      ->values(implode(',', $values));

		return (string) $query;
	}
}