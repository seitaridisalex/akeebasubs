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

class APICoupons extends DataController
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

		$this->predefinedTaskList = ['create', 'getlimits'];
	}

    public function create()
    {
        // We have to use a real method for every task, so we can hook at them inside the view
        $this->display();
    }

    public function getlimits()
    {
        // We have to use a real method for every task, so we can hook at them inside the view
        $this->display();
    }

	/**
	 * Make sure we only call the create event through the json format.
	 */
	protected function onBeforeCreate()
	{
		$format = $this->input->getCmd('format', 'html');

		if ($format != 'json')
		{
			throw new AccessForbidden;
		}
	}
}