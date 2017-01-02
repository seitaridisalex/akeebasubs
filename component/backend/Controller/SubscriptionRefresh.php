<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Controller;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use FOF30\Container\Container;
use FOF30\Controller\Controller;

class SubscriptionRefresh extends Controller
{
	use Mixin\PredefinedTaskList;

	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		$this->registerTask('overview', 'display');
		$this->setPredefinedTaskList(['process']);
		$this->cacheableTasks = array();
	}

	/**
	 * Process the integration plugins of a bunch of subscriptions
	 */
	public function process()
	{
		/** @var Subscriptions $model */
		$model = $this->getModel('Subscriptions');

		$limitStart = $this->input->getInt('forceoffset', 0);
		$limit      = $this->input->getInt('forcelimit', 100);

		$model->limitstart($limitStart)->limit($limit);
		$model->setState('refresh', 1);
		$list      = $model->get();
		$total     = $model->count();
		$processed = count($list);

		if ($processed)
		{
			$this->container->platform->importPlugin('akeebasubs');

			foreach ($list as $item)
			{
				$user_id = $item->user_id;
				$this->container->platform->runPlugins('onAKUserRefresh', array($user_id));
			}
		}

		$response = array(
			'total'     => $total,
			'processed' => $processed
		);

		echo json_encode($response);

		$this->container->platform->closeApplication();
	}
}