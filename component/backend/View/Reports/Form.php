<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\View\Reports;

// Protect from unauthorized access
defined('_JEXEC') or die();

class Form extends \FOF30\View\DataView\Form
{
	public function onBeforeRenewals()
	{
		$this->onBeforeBrowse();
	}

	public function onBeforeMissinginvoice()
	{
		$this->onBeforeBrowse();
	}
}