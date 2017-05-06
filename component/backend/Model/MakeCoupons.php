<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Model;

defined('_JEXEC') or die;

use FOF30\Model\Model;
use JFactory;
use JText;

class MakeCoupons extends Model
{
	/**
	 * Create a number of coupons
	 */
	public function makeCoupons()
	{
		// Fetch all state variables
		$title         = $this->getState('title', JText::_('COM_AKEEBASUBS_MAKECOUPONS_LBL_DEFAULTTITLE'), 'string');
		$prefix        = $this->getState('prefix', '', 'cmd');
		$quantity      = $this->getState('quantity', 1, 'int');
		$type          = $this->getState('type', 'percent', 'cmd');
		$value         = $this->getState('value', '100');
		$subscriptions = $this->getState('subscriptions', '');
		$userhits      = $this->getState('userhits', '1', 'int');
		$hitslimit     = $this->getState('hits', '0', 'int');
		$expiration    = $this->getState('expiration', '');

		// Sanitize input data
		$title = trim($title);

		if (empty($title))
		{
			$title = JText::_('COM_AKEEBASUBS_MAKECOUPONS_LBL_DEFAULTTITLE');
		}

		$prefix = strtoupper(trim($prefix));

		if ($quantity <= 0)
		{
			$quantity = 1;
		}

		if (!in_array($type, array('value', 'percent')))
		{
			$type = 'percent';
		}

		$value = floatval($value);

		if ($type == 'percent')
		{
			if ($value < 0)
			{
				$value = 0;
			}
			elseif ($value > 100)
			{
				$value = 100;
			}
		}
		elseif ($value < 0)
		{
			$value = 0;
		}

		if (!is_array($subscriptions))
		{
			$subscriptions = explode(',', $subscriptions);
		}

		if (!empty($subscriptions))
		{
			$subscriptions = array_map('intval', $subscriptions);
		}

		if ($userhits < 0)
		{
			$userhits = 0;
		}

		if ($hitslimit < 0)
		{
			$hitslimit = 0;
		}

		// Set back the values into the state
		$this->setState('title', $title);
		$this->setState('prefix', $prefix);
		$this->setState('quantity', $quantity);
		$this->setState('type', $type);
		$this->setState('value', $value);
		$this->setState('subscriptions', implode(',', $subscriptions));
		$this->setState('userhits', $userhits);
		$this->setState('hits', $hitslimit);
		$this->setState('expiration', $expiration);

		// Initialise
		$ret = array();

		// Get a reference to the coupons model
		$model = $this->container->factory->model('Coupons')->tmpInstance();

		// Get the maximum coupon code
		$db    = $this->container->platform->getDbo();
		$query = $db->getQuery(true)
		            ->select('MAX(ordering)')
		            ->from($db->qn('#__akeebasubs_coupons'));
		$db->setQuery($query);
		$maxOrdering = $db->loadResult();

		// Make sure the coupon code will be big enough for all required coupon
		// codes.
		$len = max(6, 2 * ceil($quantity / 32));

		for ($i = 0; $i < $quantity; $i ++)
		{
			$coupon = $prefix . $this->genRandomString($len);

			$data = array(
				'title'         => $title,
				'coupon'        => $coupon,
				'publish_down'  => $expiration,
				'subscriptions' => $subscriptions,
				'userhits'      => $userhits,
				'hitslimit'     => $hitslimit,
				'type'          => $type,
				'value'         => $value,
				'enabled'       => 1,
				'ordering'      => $maxOrdering + $i,
				'hits'          => 0
			);

			$newCoupon = $model->getClone()->clearState()->reset(true, true)->save($data);

			$ret[] = $coupon;
		}

		$this->container->platform->setSessionVar('makecoupons.coupons', $ret, 'com_akeebasubs');
	}

	/**
	 * Generate a (mostly) random string, for use as coupon code
	 *
	 * @param   int  $length  String length
	 *
	 * @return  string  The (mostly) random string
	 */
	private function genRandomString($length = 6)
	{
		$pool   = "ABCDEFGHJKLMNPQRSTWXYZ0123456789";
		$len    = strlen($pool);
		$string = '';

		for ($i = 0; $i < $length; $i ++)
		{
			$string .= $pool[ mt_rand(0, $len - 1) ];
		}

		return $string;
	}
}
