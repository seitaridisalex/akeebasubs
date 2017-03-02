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

class MissingInvoices extends DataModel
{
	public function __construct(Container $container, array $config = array())
	{
		$config = [
			'tableName'   => '#__akeebasubs_subscriptions',
			'idFieldName' => 'akeebasubs_subscription_id',
		    'name'        => 'Reports'
		];

		parent::__construct($container, $config);
	}

	public function buildQuery($overrideLimits = false)
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$query
			->select($db->qn('s') .'.*')
			->from($db->qn('#__akeebasubs_subscriptions') . ' AS ' . $db->qn('s'))
			->leftJoin($db->qn('#__akeebasubs_invoices') . ' AS ' . $db->qn('i') . ' USING(' . $db->qn('akeebasubs_subscription_id') . ')')
			->where($db->qn('s.enabled') . ' = ' . $db->q(1))
			->where($db->qn('s.state') . ' = ' . $db->q('C'))
			->where($db->qn('s.gross_amount') . ' > ' . $db->q('0'))
			->where($db->qn('invoice_no') . ' IS NULL')
			->where($db->qn('s.publish_down') . ' < ' . $db->q('2037-01-01'))
		;

		$order = $this->getState('filter_order', 'akeebasubs_subscription_id', 'cmd');

		// I can have fields that aren't in the table object
		if (!in_array($order, array_keys($this->toArray())))
		{
			$order = 'akeebasubs_subscription_id';
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
			->clear('select')
			->select('COUNT(*)');

		$total = $db->setQuery($query)->loadResult();

		return $total;
	}
}