<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\View\Invoices;

use Akeeba\Subscriptions\Admin\Model\Invoices;

defined('_JEXEC') or die;

class Form extends \FOF30\View\DataView\Form
{
	public function onBeforeRead($tpl = null)
	{
		$this->setPreRender(false);
		$this->setPostRender(false);
	}
}