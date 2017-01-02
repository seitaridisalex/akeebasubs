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
		$session = $this->getContainer()->session;
		$coupons = $session->get('makecoupons.coupons', false, 'com_akeebasubs');
		$session->set('makecoupons.coupons', null, 'com_akeebasubs');

		$this->coupons = $coupons;
	}
}
