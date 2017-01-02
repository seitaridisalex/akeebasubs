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
use FOF30\Controller\Controller;

class Cron extends Controller
{
	use Mixin\PredefinedTaskList;

	/**
	 * Overridden. Limit the tasks we're allowed to execute.
	 *
	 * @param   Container $container
	 * @param   array     $config
	 */
	public function __construct(Container $container, array $config = array())
	{
		$config['modelName'] = 'Subscribe';
		$config['csrfProtection'] = 0;

		parent::__construct($container, $config);

		$this->predefinedTaskList = ['cron'];

		$this->cacheableTasks = [];
	}

	public function cron()
	{
		// Makes sure SiteGround's SuperCache doesn't cache the CRON view
		$app = \JFactory::getApplication();
		$app->setHeader('X-Cache-Control', 'False', true);

		$configuredSecret = $this->container->params->get('secret', '');

		if (empty($configuredSecret))
		{
			header('HTTP/1.1 503 Service unavailable due to configuration');

			$this->container->platform->closeApplication();
		}

		$secret = $this->input->get('secret', null, 'raw');

		if ($secret != $configuredSecret)
		{
			header('HTTP/1.1 403 Forbidden');

			$this->container->platform->closeApplication();
		}

		$command = $this->input->get('command', null, 'raw');
		$command = trim(strtolower($command));

		if (empty($command))
		{
			header('HTTP/1.1 501 Not implemented');

			$this->container->platform->closeApplication();
		}

		$this->container->platform->importPlugin('system');
		$this->container->platform->importPlugin('akeebasubs');
		$this->container->platform->runPlugins('onAkeebasubsCronTask', array(
			$command,
			array(
				'time_limit' => 10
			)
		));

		echo "$command OK";

		$this->container->platform->closeApplication();
	}
}