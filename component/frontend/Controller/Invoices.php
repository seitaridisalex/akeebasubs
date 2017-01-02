<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Controller\Mixin;
use FOF30\Container\Container;
use FOF30\Controller\DataController;
use FOF30\View\Exception\AccessForbidden;

class Invoices extends \Akeeba\Subscriptions\Admin\Controller\Invoice
{
	use Mixin\PredefinedTaskList;

	/**
	 * Overridden. Limit the tasks we're allowed to execute.
	 *
	 * @param   Container  $container
	 * @param   array      $config
	 */
	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		// In the front-end we only allow displaying and downloading an invoice. Access control is performed by the
		// parent Controller which checks if the currently logged in user is a Super User or the owner of the
		// subscription corresponding to this invoice.
		$this->predefinedTaskList = ['download', 'read'];
	}
}