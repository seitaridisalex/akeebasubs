<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\View\MakeCoupons;

// Protect from unauthorized access
defined('_JEXEC') or die();

class Html extends \FOF30\View\DataView\Html
{
	public function onBeforeDisplay()
	{
		$coupons = $this->container->platform->getSessionVar('makecoupons.coupons', false, 'com_akeebasubs');
		$this->container->platform->setSessionVar('makecoupons.coupons', null, 'com_akeebasubs');

		$this->coupons = $coupons;
	}
}
