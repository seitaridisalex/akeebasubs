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

class Level extends Levels
{
	/**
	 * Overridden. Limit the tasks we're allowed to execute.
	 *
	 * @param   Container  $container
	 * @param   array      $config
	 */
	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		$this->predefinedTaskList = ['read'];

		$this->cacheableTasks = [];

		// Force the view name, required because I am extending the Levels (plural) controller but I want my view
		// templates to be read from the Level (singular) directory.
		$this->viewName = 'Level';
	}
}