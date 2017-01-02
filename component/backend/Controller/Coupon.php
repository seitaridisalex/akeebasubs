<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Controller;

defined('_JEXEC') or die;

use FOF30\Controller\DataController;

class Coupon extends DataController
{
	protected function onBeforeApplySave(&$data)
	{
		if (!isset($data['usergroups']))
		{
			$data['usergroups'] = array();
		}

		if (!isset($data['subscriptions']))
		{
			$data['subscriptions'] = array();
		}
	}
}