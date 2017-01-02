<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Controller\Mixin;
use Akeeba\Subscriptions\Site\Model\Subscribe;
use FOF30\Container\Container;
use FOF30\Controller\Controller;
use FOF30\View\Exception\AccessForbidden;

class Validate extends Controller
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
		$config['modelName'] = 'Subscribe';

		parent::__construct($container, $config);

		$this->predefinedTaskList = ['validate', 'getpayment'];

		$this->cacheableTasks = [];
	}

	/**
	 * Make sure we only call the create event through the json format.
	 */
	protected function onBeforeValidate()
	{
		// Makes sure SiteGround's SuperCache doesn't cache the subscription page
		\JFactory::getApplication()->setHeader('X-Cache-Control', 'False', true);

		/** @var Subscribe $model */
		$model = $this->getModel();

		$data = $model
			->getValidation();

		echo json_encode($data);

		$this->container->platform->closeApplication();
	}

	/**
	 * Returns the payment list HTML code accordingly to the country of the user and plugins parameters
	 *
	 * @throws \Exception
	 */
	public function getpayment()
	{
		// Makes sure SiteGround's SuperCache doesn't cache the subscription page
		\JFactory::getApplication()->setHeader('X-Cache-Control', 'False', true);

		$this->display(false);
	}
}