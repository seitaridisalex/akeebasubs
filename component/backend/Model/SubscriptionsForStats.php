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
use JDate;

/**
 * A very complicated model to query subscription information, primarily used for generating statistics and reports.
 * By having a separate model for the funky statistics gathering we can keep the main Subscriptions model simple
 * and tidy.
 */
class SubscriptionsForStats extends Subscriptions
{
	public function __construct(Container $container, array $config = array())
	{
		$config = [
			'tableName'   => '#__akeebasubs_subscriptions',
			'idFieldName' => 'akeebasubs_subscription_id',
		];

		parent::__construct($container, $config);

		$this->setBehaviorParam('tableAlias', 'tbl');
	}

	/**
	 * Get the number of users with a currently active subscription
	 *
	 * @return  int
	 */
	public function getActiveSubscribers()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
		            ->select(array('COUNT(DISTINCT(' . $db->qn('user_id') . '))'))
		            ->from($db->qn('#__akeebasubs_subscriptions'))
		            ->where($db->qn('enabled') . ' = ' . $db->q('1'));
		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Build the COUNT query
	 *
	 * @param   \JDatabaseQuery $query
	 */
	protected function onBuildCountQuery(&$query)
	{
		$db = $this->getDbo();

		$state = $this->getFilterValues();

		// Get a "count all" query
		$query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->qn('#__akeebasubs_subscriptions') . ' AS ' . $db->qn('tbl'));

		if ($state->refresh == 1)
		{
			$query1 = $db->getQuery(true)
			            ->select('COUNT(*)')
			            ->from($db->qn('#__akeebasubs_subscriptions') . ' AS ' . $db->qn('tbl'));

			// $query1 retruns X rows, where X is the number of users. We need the count of users, so...
			$query = $db->getQuery(true)
			             ->select('COUNT(*)')
			             ->from('(' . (string) $query1 . ') AS ' . $db->qn('tbl'));
		}
		elseif ($state->moneysum == 1)
		{
			$query = $db->getQuery(true)
			            ->select('SUM(' . $db->qn('net_amount') . ') AS ' . $db->qn('x'))
			            ->from($db->qn('#__akeebasubs_subscriptions') . ' AS ' . $db->qn('tbl'));
		}

		// Apply user filtering
		$this->filterByUser($query);

		// Apply custom WHERE clauses
		if (count($this->whereClauses))
		{
			foreach ($this->whereClauses as $clause)
			{
				$query->where($clause);
			}
		}

		// Run filters and apply WHERE, JOIN and GROUP BY clauses
		$this->triggerEvent('onAfterBuildQuery', array(&$query));
	}

	/**
	 * Build the JOINs for the select and count queries
	 *
	 * @param  \JDatabaseQuery  $query
	 */
	protected function _buildQueryJoins(\JDatabaseQuery $query)
	{
		$db    = $this->getDbo();
		$state = $this->getFilterValues();

		if (!empty($state->nojoins))
		{
			return;
		}
		elseif ($state->groupbydate == 1)
		{
			return;
		}
		elseif ($state->groupbylevel == 1)
		{
			$query
				->join('INNER', $db->qn('#__akeebasubs_levels') . ' AS ' . $db->qn('l') . ' ON ' .
				                $db->qn('l') . '.' . $db->qn('akeebasubs_level_id') . ' = ' .
				                $db->qn('tbl') . '.' . $db->qn('akeebasubs_level_id'));
		}
		else
		{
			$query
				->join('INNER', $db->qn('#__akeebasubs_levels') . ' AS ' . $db->qn('l') . ' ON ' .
				                $db->qn('l') . '.' . $db->qn('akeebasubs_level_id') . ' = ' .
				                $db->qn('tbl') . '.' . $db->qn('akeebasubs_level_id'))
				->join('LEFT OUTER', $db->qn('#__users') . ' AS ' . $db->qn('u') . ' ON ' .
				                     $db->qn('u') . '.' . $db->qn('id') . ' = ' .
				                     $db->qn('tbl') . '.' . $db->qn('user_id'))
				->join('LEFT OUTER', $db->qn('#__akeebasubs_users') . ' AS ' . $db->qn('a') . ' ON ' .
				                     $db->qn('a') . '.' . $db->qn('user_id') . ' = ' .
				                     $db->qn('tbl') . '.' . $db->qn('user_id'));
		}
	}

	/**
	 * Build the column selection part of the select and count queries
	 *
	 * @param   \JDatabaseQuery  $query
	 * @param   bool             $overrideLimits
	 */
	protected function _buildQueryColumns(\JDatabaseQuery $query, $overrideLimits = false)
	{
		$db    = $this->getDbo();
		$state = $this->getFilterValues();

		if ($state->refresh == 1)
		{
			$query->select(array(
				$db->qn('tbl') . '.' . $db->qn('akeebasubs_subscription_id'),
				$db->qn('tbl') . '.' . $db->qn('user_id')
			));
		}
		elseif ($state->groupbydate == 1)
		{
			$query->select(array(
				'DATE(' . $db->qn('created_on') . ') AS ' . $db->qn('date'),
				'SUM(' . $db->qn('net_amount') . ') AS ' . $db->qn('net'),
				'COUNT(' . $db->qn('akeebasubs_subscription_id') . ') AS ' . $db->qn('subs')
			));

			$this->addKnownField('date', $db->getNullDate(), 'datetime');
			$this->addKnownField('net', 0.0, 'float');
			$this->addKnownField('subs', 0, 'integer');
		}
		elseif ($state->groupbyweek == 1)
		{
			$query->select(array(
				$db->qn('tbl') . '.' . $db->qn('akeebasubs_level_id'),
				'YEARWEEK(' . $db->qn('tbl') . '.' . $db->qn('publish_down') . ', 6) as yearweek',
				'publish_down',
				'COUNT(' . $db->qn('akeebasubs_subscription_id') . ') AS ' . $db->qn('subs')
			));

			$this->addKnownField('yearweek', 1, 'integer');
			$this->addKnownField('subs', 0, 'integer');

		}
		elseif ($state->groupbylevel == 1)
		{
			$query->select(array(
				$db->qn('l') . '.' . $db->qn('title'),
				'SUM(' . $db->qn('tbl') . '.' . $db->qn('net_amount') . ') AS ' . $db->qn('net'),
				'COUNT(' . $db->qn('tbl') . '.' . $db->qn('akeebasubs_subscription_id') . ') AS ' . $db->qn('subs'),
			));

			$this->addKnownField('title', '', 'varchar(255)');
			$this->addKnownField('net', 0.0, 'float');
			$this->addKnownField('subs', 0, 'integer');
		}
		elseif (!empty($state->nojoins))
		{
			$query->select(array(
				$db->qn('tbl') . '.*',
			));
		}
		else
		{
			$query->select(array(
				$db->qn('tbl') . '.*',
				$db->qn('l') . '.' . $db->qn('title'),
				$db->qn('l') . '.' . $db->qn('image'),
				$db->qn('l') . '.' . $db->qn('akeebasubs_levelgroup_id'),
				$db->qn('u') . '.' . $db->qn('name'),
				$db->qn('u') . '.' . $db->qn('username'),
				$db->qn('u') . '.' . $db->qn('email'),
				$db->qn('u') . '.' . $db->qn('block'),
				$db->qn('a') . '.' . $db->qn('isbusiness'),
				$db->qn('a') . '.' . $db->qn('businessname'),
				$db->qn('a') . '.' . $db->qn('occupation'),
				$db->qn('a') . '.' . $db->qn('vatnumber'),
				$db->qn('a') . '.' . $db->qn('viesregistered'),
				$db->qn('a') . '.' . $db->qn('taxauthority'),
				$db->qn('a') . '.' . $db->qn('address1'),
				$db->qn('a') . '.' . $db->qn('address2'),
				$db->qn('a') . '.' . $db->qn('city'),
				$db->qn('a') . '.' . $db->qn('state') . ' AS ' . $db->qn('userstate'),
				$db->qn('a') . '.' . $db->qn('zip'),
				$db->qn('a') . '.' . $db->qn('country'),
				$db->qn('a') . '.' . $db->qn('params') . ' AS ' . $db->qn('userparams'),
				$db->qn('a') . '.' . $db->qn('notes') . ' AS ' . $db->qn('usernotes'),
			));

			$this->addKnownField('title', '', 'varchar(255)');
			$this->addKnownField('image', '', 'varchar(255)');
			$this->addKnownField('akeebasubs_levelgroup_id', 0, 'integer');

			$this->addKnownField('name', '', 'varchar(255)');
			$this->addKnownField('username', '', 'varchar(255)');
			$this->addKnownField('email', '', 'varchar(255)');
			$this->addKnownField('block', 0, 'integer');
			$this->addKnownField('isbusiness', 0, 'integer');
			$this->addKnownField('businessname', '', 'varchar(255)');
			$this->addKnownField('occupation', '', 'varchar(255)');
			$this->addKnownField('vatnumber', '', 'varchar(255)');
			$this->addKnownField('viesregistered', 0, 'integer');
			$this->addKnownField('taxauthority', '', 'varchar(255)');
			$this->addKnownField('address1', '', 'varchar(255)');
			$this->addKnownField('address2', '', 'varchar(255)');
			$this->addKnownField('city', '', 'varchar(255)');
			$this->addKnownField('userstate', '', 'varchar(255)');
			$this->addKnownField('zip', '', 'varchar(255)');
			$this->addKnownField('country', '', 'varchar(255)');
			$this->addKnownField('userparams', '', 'varchar(255)');
			$this->addKnownField('usernotes', '', 'varchar(255)');

			$order = $this->getState('filter_order', 'akeebasubs_subscription_id', 'cmd');

			if (!in_array($order, array_keys($this->toArray())))
			{
				$order = 'akeebasubs_subscription_id';
			}

			$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
			$query->order($order . ' ' . $dir);
		}
	}

	/**
	 * Build the GROUP clause of select and count queries
	 *
	 * @param   \JDatabaseQuery  $query
	 */
	protected function _buildQueryGroup(\JDatabaseQuery $query)
	{
		$db    = $this->getDbo();
		$state = $this->getFilterValues();

		if ($state->refresh == 1)
		{
			$query->group(array(
				$db->qn('tbl') . '.' . $db->qn('user_id')
			));
		}
		elseif ($state->groupbydate == 1)
		{
			$query->group(array(
				'DATE(' . $db->qn('tbl') . '.' . $db->qn('created_on') . ')'
			));
		}
		elseif ($state->groupbyweek == 1)
		{
			$query->group(array(
				'YEARWEEK(' . $db->qn('tbl') . '.' . $db->qn('publish_down') . ', 6)',
				$db->qn('tbl') . '.' . $db->qn('akeebasubs_level_id')
			));
		}
		elseif ($state->groupbylevel == 1)
		{
			$query->group(array(
				$db->qn('tbl') . '.' . $db->qn('akeebasubs_level_id')
			));
		}
	}

	/**
	 * Map state variables from their old names to their new names, for a modicum of backwards compatibility
	 *
	 * @param   \JDatabaseQuery  $query           The query I'm modifying
	 * @param   bool             $overrideLimits  Am I asked to override limit/limitstart?
	 */
	protected function onBeforeBuildQuery(\JDatabaseQuery &$query, $overrideLimits = false)
	{
		// We need to disable auto filtering when group by date or level is enabled
		$state = $this->getFilterValues();

		if ($state->groupbydate && $state->groupbylevel)
		{
			$this->blacklistFilters([
				'enabled', 'title', 'akeebasubs_coupon_id', 'user_id', 'contact_flag', 'publish_up', 'publish_down'
			]);
		}
		else
		{
			// Filtering by user information only applies when we're not grouping by date / level
			$this->filterByUser($query);
		}

		// Map state variables to what is used by automatic filters
		foreach (
			[
				'subid'     => 'akeebasubs_subscription_id',
				'level'     => 'akeebasubs_level_id',
				'paystate'  => 'state',
				'paykey'    => 'processor_key',
				'coupon_id' => 'akeebasubs_coupon_id',
			] as $from => $to)
		{
			$this->setState($to, $this->getState($from, null));
		}

		// Replace the query with our custom query
		$db = $this->getDbo();
		$query = $db->getQuery(true)
		    ->from($db->qn('#__akeebasubs_subscriptions') . ' AS ' . $db->qn('tbl'));

		// Build the query columns
		$this->_buildQueryColumns($query, $overrideLimits);
	}

	/**
	 * Apply additional filtering to the select query
	 *
	 * @param   \JDatabaseQuery  $query  The query to modify
	 */
	protected function onAfterBuildQuery(\JDatabaseQuery &$query, $overrideLimits = false)
	{
		$db    = $this->getDbo();
		$state = $this->getFilterValues();

		// Build the JOIN and GROUP BY clauses
		$this->_buildQueryJoins($query);
		$this->_buildQueryGroup($query);

		if ($state->refresh == 1)
		{
			// Remove already added WHERE clauses
			$query->clear('where');

			// Remove user-defined WHERE clauses
			$this->whereClauses = [];

			// Remove relation filters which would result in WHERE clauses with sub-queries
			$this->relationFilters = [];

			// Do not process anything else, we're done
			return;
		}

		if (!$state->groupbydate && !$state->groupbylevel)
		{
			// Filter by discount mode and code (filter_discountmode / filter_discountcode)
			$this->filterByDiscountCode($query);

			// Filter by publish_up / publish_down dates
			$this->filterByDate($query);
		}

		// Filter by created date (since / until)
		$this->filterByCreatedOn($query);

		// Filter by expiration date range (expires_from / expires_to)
		$this->filterByExpirationDate($query);

		// Fitler by non-free subscriptions (nozero)
		$this->filterByNonFree($query);
	}

	/**
	 * A quick way to get the values of all interesting state parameters
	 *
	 * @return  object
	 */
	private function getFilterValues()
	{
		$enabled = $this->getState('enabled', '', 'cmd');

		return (object) array(
			'subid'               => $this->getState('subid', 0, 'int'),
			'search'              => $this->getState('search', null, 'string'),
			'title'               => $this->getState('title', null, 'string'),
			'enabled'             => $enabled,
			'level'               => $this->getState('level', null, 'int'),
			'publish_up'          => $this->getState('publish_up', null, 'string'),
			'publish_down'        => $this->getState('publish_down', null, 'string'),
			'user_id'             => $this->getState('user_id', null, 'int'),
			'paystate'            => $this->getState('paystate', null, 'string'),
			'processor'           => $this->getState('processor', null, 'string'),
			'paykey'              => $this->getState('paykey', null, 'string'),
			'since'               => $this->getState('since', null, 'string'),
			'until'               => $this->getState('until', null, 'string'),
			'contact_flag'        => $this->getState('contact_flag', null, 'int'),
			'expires_from'        => $this->getState('expires_from', null, 'string'),
			'expires_to'          => $this->getState('expires_to', null, 'string'),
			'refresh'             => $this->getState('refresh', null, 'int'),
			'groupbydate'         => $this->getState('groupbydate', null, 'int'),
			'groupbyweek'         => $this->getState('groupbyweek', null, 'int'),
			'groupbylevel'        => $this->getState('groupbylevel', null, 'int'),
			'moneysum'            => $this->getState('moneysum', null, 'int'),
			'coupon_id'           => $this->getState('coupon_id', null, 'int'),
			'filter_discountmode' => $this->getState('filter_discountmode', null, 'cmd'),
			'filter_discountcode' => $this->getState('filter_discountcode', null, 'string'),
			'nozero'              => $this->getState('nozero', null, 'int'),
			'nojoins'             => $this->getState('nojoins', null, 'int'),
		);
	}

}