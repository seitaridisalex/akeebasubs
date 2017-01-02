<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\View\Invoices;

use Akeeba\Subscriptions\Site\Model\Invoices;

defined('_JEXEC') or die;

class Html extends \FOF30\View\DataView\Html
{
	public function onRead($tpl = null)
	{
		$this->setPreRender(false);
		$this->setPostRender(false);
	}
}