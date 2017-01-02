<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Model;

defined('_JEXEC') or die;

use FOF30\Container\Container;
use FOF30\Model\DataModel;

class RenewalsForReports extends DataModel
{
	public function __construct(Container $container, array $config = array())
	{
		$config = [
			'tableName'   => '#__akeebasubs_users',
			'idFieldName' => 'akeebasubs_user_id',
		    'name'        => 'Reports'
		];

		parent::__construct($container, $config);
	}

	public function buildQuery($overrideLimits = false)
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$state = $this->getFilterValues();

		$query
			->select(array(
				$db->qn('tbl') . '.*',
				$db->qn('u') . '.' . $db->qn('name'),
				$db->qn('u') . '.' . $db->qn('username'),
				$db->qn('u') . '.' . $db->qn('email'),
			))
			->from($db->qn('#__akeebasubs_users') . ' AS ' . $db->qn('tbl'))
			->join('INNER', $db->qn('#__users') . ' AS ' . $db->qn('u') . ' ON ' .
				$db->qn('u') . '.' . $db->qn('id') . ' = ' .
				$db->qn('tbl') . '.' . $db->qn('user_id')
			);

		$this->addKnownField('name', '', 'varchar(255)');
		$this->addKnownField('email', '', 'varchar(255)');

		if (!is_null($state->getRenewals))
		{
			$query
				->innerJoin(
					$db->qn('#__akeebasubs_subscriptions') . ' AS ' . $db->qn('subs') . ' ON ' .
					$db->qn('subs') . '.' . $db->qn('user_id') . ' = ' . $db->qn('tbl') . '.' . $db->qn('user_id')
				)
				->group($db->qn('tbl') . '.' . $db->qn('user_id'))
				->select(
					'GROUP_CONCAT(DISTINCT ' . $db->qn('subs') . '.' . $db->qn('akeebasubs_level_id') .
					' SEPARATOR ",") as raw_subs'
				)
				->select(
					'COUNT(' . $db->qn('subs') . '.' . $db->qn('akeebasubs_level_id') . ') as count_renewals'
				);

			$this->addKnownField('raw_subs', 0, 'integer');
			$this->addKnownField('count_renewals', 0, 'integer');
		}

		if (is_numeric($state->user_id) && ($state->user_id > 0))
		{
			$query->where($db->qn('tbl') . '.' . $db->qn('user_id') .
				'=' . $state->user_id);
		}

		if ($state->username)
		{
			$query->where($db->qn('u') . '.' . $db->qn('username') .
				' LIKE ' . $db->q('%' . $state->username . '%'));
		}

		if ($state->name)
		{
			$query->where($db->qn('u') . '.' . $db->qn('name') .
				' LIKE ' . $db->q('%' . $state->name . '%'));
		}

		if ($state->email)
		{
			$query->where($db->qn('u') . '.' . $db->qn('email') .
				' LIKE ' . $db->q('%' . $state->email . '%'));
		}

		if ($state->search)
		{
			$search = '%' . $state->search . '%';
			$query->where(
				'(' .
				'(' . $db->qn('tbl') . '.' . $db->qn('businessname') .
				' LIKE ' . $db->q($search) . ') OR ' .
				'(' . $db->qn('tbl') . '.' . $db->qn('occupation') .
				' LIKE ' . $db->q($search) . ') OR ' .
				'(' . $db->qn('tbl') . '.' . $db->qn('vatnumber') .
				' LIKE ' . $db->q($search) . ') OR ' .
				'(' . $db->qn('tbl') . '.' . $db->qn('address1') .
				' LIKE ' . $db->q($search) . ') OR ' .
				'(' . $db->qn('tbl') . '.' . $db->qn('address2') .
				' LIKE ' . $db->q($search) . ') OR ' .
				'(' . $db->qn('tbl') . '.' . $db->qn('city') .
				' LIKE ' . $db->q($search) . ') OR ' .
				'(' . $db->qn('tbl') . '.' . $db->qn('state') .
				' LIKE ' . $db->q($search) . ') OR ' .
				'(' . $db->qn('tbl') . '.' . $db->qn('zip') .
				' LIKE ' . $db->q($search) . ')'
				. ')'
			);
		}

		// Ok I asked to get all the users with/without at least a renewal. So first of all let's get the expired ones
		if (!is_null($state->getRenewals))
		{
			$renewals = array_merge($this->getRenewals($state->getRenewals), array(-1));

			if (!empty($renewals))
			{
				$query->where($db->qn('tbl') . '.' . $db->qn('user_id') . ' IN(' . implode(',', $renewals) . ')');
			}
		}

		$order = $this->getState('filter_order', 'akeebasubs_user_id', 'cmd');

		// I can have fields that aren't in the table object
		$whiteList = array_merge(array_keys($this->toArray()), ['username']);

		if (!in_array($order, $whiteList))
		{
			$order = 'akeebasubs_user_id';
		}

		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order . ' ' . $dir);

		return $query;
	}

	/**
	 * Get the number of all items
	 *
	 * @return  integer
	 */
	public function count()
	{
		// Get a "count all" query
		$db = $this->getDbo();
		$query = $this->buildQuery(true);
		$query
			->clear('select')->clear('from')->clear('join')->clear('group')
			->select('COUNT(*)')
			->from($db->qn('#__akeebasubs_users') . ' AS ' . $db->qn('tbl'))
			->join('INNER', $db->qn('#__users') . ' AS ' . $db->qn('u') . ' ON ' .
				$db->qn('u') . '.' . $db->qn('id') . ' = ' .
				$db->qn('tbl') . '.' . $db->qn('user_id')
			);

		$total = $db->setQuery($query)->loadResult();

		return $total;
	}

	/**
	 * Get all the filter values from the model's state
	 *
	 * @return  object
	 */
	private function getFilterValues()
	{
		return (object)array(
			'user_id'        => $this->getState('user_id', '', 'int'),
			'groupbydate'    => $this->getState('groupbydate', '', 'int'),
			'search'         => $this->getState('search', null, 'string'),
			'username'       => $this->getState('username', null, 'string'),
			'name'           => $this->getState('name', null, 'string'),
			'email'          => $this->getState('email', null, 'string'),
			'levelid'        => $this->getState('levelid', null, 'int'),
			'getRenewals'    => $this->getState('getRenewals', null, 'int'),
		);
	}

	private function getRenewals($type)
	{
		static $return = array();

		$db = $this->getDbo();
		$jDate = new \JDate();
		$state = $this->getFilterValues();

		if (!in_array($type, array(1, -1)))
		{
			return array();
		}

		if (isset($return[$type]))
		{
			return $return[$type];
		}

		$subquery = $db->getQuery(true)
			->select('DISTINCT user_id')
			->from('#__akeebasubs_subscriptions')
			->where('publish_down < ' . $db->q($jDate->toSql()));

		if ($state->levelid)
		{
			$subquery->where($db->qn('akeebasubs_level_id') . ' = ' . $state->levelid);
		}

		$expired = $db->setQuery($subquery)->loadColumn();

		if (!empty($expired))
		{
			// I want users with a renewal, so let's search for users that once expired, they bought a new sub
			if ($type == 1)
			{
				$subquery = $db->getQuery(true)
					->select('user_id')
					->from('#__akeebasubs_subscriptions')
					->where('publish_down > ' . $db->q($jDate->toSql()))
					->where('user_id IN(' . implode(',', $expired) . ')');

				if (empty($expired))
				{
					$return[$type] = array();
				}
				else
				{
					$return[$type] = $db->setQuery($subquery)->loadColumn();
				}
			}
			elseif ($type == -1)
			{
				// I want users without a renewal, so let's get the people who renewed and then exclude them
				$subquery = $db->getQuery(true)
					->select('user_id')
					->from('#__akeebasubs_subscriptions')
					->where('publish_down > ' . $db->q($jDate->toSql()))
					->where('user_id IN(' . implode(',', $expired) . ')');

				if (empty($expired))
				{
					$renewed = array();
				}
				else
				{
					$renewed = $db->setQuery($subquery)->loadColumn();
				}

				if (!empty($renewed))
				{
					$subquery = $db->getQuery(true)
						->select('user_id')
						->from('#__akeebasubs_subscriptions')
						->where('publish_down < ' . $db->q($jDate->toSql()))
						->where('user_id NOT IN(' . implode(',', $renewed) . ')');
					$return[$type] = $db->setQuery($subquery)->loadColumn();
				}
				else
				{
					$return[$type] = array();
				}
			}
		}

		if (!isset($return[$type]))
		{
			$return[$type] = array();
		}

		return $return[$type];
	}

}