<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Controller\Mixin;
use Akeeba\Subscriptions\Site\Model\Subscribe as ModelSubscribe;
use Akeeba\Subscriptions\Site\Model\Subscriptions;
use FOF30\Container\Container;
use FOF30\Controller\Controller;
use FOF30\Controller\Exception\ItemNotFound;
use FOF30\View\Exception\AccessForbidden;

class Subscribe extends Controller
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

		parent::__construct($container, $config);

		$this->predefinedTaskList = ['subscribe'];

		$this->cacheableTasks = [];
	}

	/**
	 * Handles the POST of the subscription form. It will try to create a new subscription (and user, if the POST came
	 * from a guest and there's valid information).
	 *
	 * @return  void
	 */
	public function subscribe()
	{
		// Load the models

		/** @var ModelSubscribe $model */
		$model = $this->getModel();

		/** @var \Akeeba\Subscriptions\Site\Model\Levels $levelsModel */
		$levelsModel = $this->getModel('Levels');

		// Load the id and slug. Either one defines which level we shall load
		$id = $model->getState('id', 0, 'int');
		$slug = $model->getState('slug', null, 'string');

		// If the ID is not set but slug is let's try to find the level by slug
		if (!$id && $slug)
		{
			// Note: do note replace $item with $levelsModel or the view won't see the loaded record because of how
			// references work in PHP.
			$item = $levelsModel
				->id(0)
				->slug([
					'method' => 'exact',
					'value' => $slug
				])
				->firstOrNew();

			$id = $item->getId();
		}

		// If we do not have a valid level ID throw a 404
		if (!$id)
		{
			throw new ItemNotFound(\JText::_($slug), 404);
		}

		// Load the level
		$level = $levelsModel->find($id);

		// If the level is marked as only_once we need to make sure we're allowed to access it
		if ($level->only_once)
		{
			/** @var Subscriptions $subscriptions */
			$subscriptions = $this->getModel('subscriptions');
			$subscriptions
				->level($level->akeebasubs_level_id)
				->enabled(1)
				->get(true);

			if ($subscriptions->count())
			{
				// User trying to renew a level which is marked as only_once
				throw new AccessForbidden;
			}
		}

		// Check the Joomla! View Access Level for this subscription level
		$accessLevels = $this->container->platform->getUser()->getAuthorisedViewLevels();

		if (!in_array($level->access, $accessLevels))
		{
			// User trying to subscribe to a level he doesn't have access to
			throw new AccessForbidden;
		}

		// Try to create a new subscription record
		$model->setState('id', $id);

		$result = $model->createNewSubscription();

		// Did we fail to create a new subscription?
		if (!$result)
		{
			$this->container->platform->setSessionVar('firstrun', false, 'com_akeebasubs');

			$url = str_replace('&amp;', '&', \JRoute::_('index.php?option=com_akeebasubs&view=Level&layout=default&slug=' . $model->slug));
			$msg = \JText::_('COM_AKEEBASUBS_LEVEL_ERR_VALIDATIONOVERALL');

			$this->setRedirect($url, $msg, 'error');

			return;
		}

		// Set up and display the view
		$view = $this->getView();

		$view->setLayout('form');
		$view->form = $model->getPaymentForm();
		$view->setDefaultModelName('Subscribe');
		$view->setModel('Subscribe', $model);

		$view->display();
	}
}