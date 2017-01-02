<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Controller;

defined('_JEXEC') or die;

use FOF30\Container\Container;
use FOF30\Controller\DataController;
use FOF30\View\Exception\AccessForbidden;

class SubscriptionStatistics extends DataController
{
	use Mixin\PredefinedTaskList;

	/**
	 * Overridden. We want to use a custom model name and limit the task list to "browse" only.
	 *
	 * @param   Container  $container
	 * @param   array      $config
	 */
	public function __construct(Container $container, array $config = array())
	{
		$config['modelName'] = 'SubscriptionsForStats';

		parent::__construct($container, $config);

		$this->predefinedTaskList = ['browse'];
	}

	/**
	 * Before processing the browse task make sure that the format is JSON. If it's anything else throw an Access
	 * Forbidden error.
	 */
	protected function onBeforeBrowse()
	{
		$format = $this->input->getCmd('format', 'html');

		if ($format != 'json')
		{
			throw new AccessForbidden;
		}
	}
}