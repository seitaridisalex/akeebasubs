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
use FOF30\Container\Container;
use FOF30\Controller\DataController;
use FOF30\View\Exception\AccessForbidden;

class Subscriptions extends DataController
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
		parent::__construct($container, $config);

		$this->predefinedTaskList = ['browse', 'read', 'save', 'apply'];

		$this->cacheableTasks = ['read', 'browse'];
	}

	/**
	 * Runs before the browse task. Makes sure we're displaying the list of our own subscriptions.
	 */
	protected function onBeforeBrowse()
	{
		// If no user is logged in we can't display any subscriptions
		$user = $this->container->platform->getUser();

		// If we have a guest user, show the login page
		if ($user->guest)
		{
			// Show login page
			$jURI = \JUri::getInstance();
			$myURI = base64_encode($jURI->toString());
			\JFactory::getApplication()->redirect(\JUri::base() . 'index.php?option=com_users&view=login&return=' . $myURI);

			return;
		}

		/** @var \Akeeba\Subscriptions\Site\Model\Subscriptions $subsModel */
		$subsModel = $this->getModel();

		// Does the user have core.manage access or belongs to SA group?
		$isAdmin = $user->authorise('core.manage', 'com_akeebasubs');

		if ($this->input->getInt('allUsers', 0) && $isAdmin)
		{
			$subsModel->user_id(null);
		}
		else
		{
			$subsModel->user_id($user->id);
		}

		if ($this->input->getInt('allStates', 0) && $isAdmin)
		{
			$subsModel->paystate(null);
		}
		else
		{
			$subsModel->paystate(['C', 'P']);
		}

		// Let me cheat. If the request doesn't specify how many records to show, show them all!
		if ($this->input->getCmd('format', 'html') != 'html')
		{
			if (!$this->input->getInt('limit', 0) && !$this->input->getInt('limitstart', 0))
			{
				$subsModel->limit(0);
				$subsModel->limitstart(0);
			}
		}
	}

	/**
	 * Runs before the read task. Makes sure we're not trying to access someone else's subscription.
	 */
	protected function onBeforeRead()
	{
		// Work around router.php setting the subscription ID in the slug instead of the id request parameter
		$reqId = $this->input->getInt('id', null);
		$reqSlug = $this->input->getInt('slug', null);

		if (!$reqId && $reqSlug)
		{
			$this->input->set('slug', null);
			$this->input->set('id', $reqSlug);
		}

		/** @var \Akeeba\Subscriptions\Site\Model\Subscriptions $model */
		$model = $this->getModel();
		$user = $this->container->platform->getUser();

		// If we have a guest user, show the login page
		if ($user->guest)
		{
			// Show login page
			$jURI = \JUri::getInstance();
			$myURI = base64_encode($jURI->toString());
			\JFactory::getApplication()->redirect(\JUri::base() . 'index.php?option=com_users&view=login&return=' . $myURI);

			return;
		}

		// Force the item layout
		$view = $this->getView();
		$this->layout = 'default';
		$view->setLayout('default');
		$model->paystate(['C', 'P']);

		// If the record is not found throw a generic "Access forbidden" error
		if (!$model->getId())
		{
			$ids = $this->getIDsFromRequest($model, true);

			if (($model->getId() != reset($ids)) || !in_array($model->getFieldValue('state', null), ['C', 'P']))
			{
				throw new AccessForbidden;
			}
		}

		// If the subscription user does not match the currently logged in user throw an "Access forbidden" error
		if ($model->user_id != $this->container->platform->getUser()->id)
		{
			throw new AccessForbidden;
		}

		// Working around Progressive Caching
		\JFactory::getApplication()->input->set('_x_userid', $user->id);

		$this->registerUrlParams(array(
			'_x_userid' => 'INT',
			'id'        => 'INT',
			'cid'       => 'ARRAY',
		));
	}

	/**
	 * Performs auth checks before saving subscription data
	 *
	 * @return bool
	 */
	protected function onBeforeSave()
	{
		$user = $this->container->platform->getUser();
		$subId = $this->input->getInt('akeebasubs_subscription_id', 0);

		// Guest user, go away!
		if ($user->guest)
		{
			return false;
		}

		// No subscription info? Go away!
		if (!$subId)
		{
			return false;
		}

		// No info about custom fields? It's the only thing that user can update, so why continuing?
		if (!$this->input->get('subcustom', '', 2))
		{
			return false;
		}

		/** @var \Akeeba\Subscriptions\Site\Model\Subscriptions $sub */
		$sub = $this->getModel()->savestate(0)->setIgnoreRequest(1)->reset(true, true);
		$sub->find($subId);

		// Editing a subscription of another user? Go away!
		if ($user->id != $sub->user_id)
		{
			return false;
		}

		return true;
	}

	public function save()
	{
		// CSRF prevention
		if ($this->csrfProtection)
		{
			$this->csrfProtection();
		}

		// Set error message in case data won't be updated below
		$msgType = 'error';
		$msg = \JText::_('COM_AKEEBASUBS_SUBSCRIPTION_UPDATE_ERROR');

		$subcustom = $this->input->get('subcustom', '', 2);

		$subId = $this->input->getInt('akeebasubs_subscription_id');
		$this->input->set('opt', 'plugins');
		$this->input->set('id', $subId);

		/** @var \Akeeba\Subscriptions\Site\Model\Subscriptions $subscription */
		$subscription = $this->getModel()->savestate(0)->setIgnoreRequest(1);
		$subscription->find($subId);

		/** @var ModelSubscribe $subscribes */
		$subscribes = $this->getModel('Subscribe')->savestate(0);

		// Subscription validation (plugins only)
		$data = $subscribes->getValidation();

		if ($data->custom_valid && $data->subscription_custom_valid)
		{
			// Let's get the info from previous slave subscriptions
			if (isset($subscription->params['slavesubs_ids']) && !empty($subscription->params['slavesubs_ids']))
			{
				$subcustom['slavesubs_ids'] = $subscription->params['slavesubs_ids'];
			}

			$subscription->params = $subcustom;

			if ($subscription->store())
			{
				$msgType = 'info';
				$msg = \JText::_('COM_AKEEBASUBS_SUBSCRIPTION_UPDATE_OK');
			}
		}

		$this->setRedirect(\JRoute::_('index.php?option=com_akeebasubs&view=Subscription&id=' . $subId, false), $msg, $msgType);
	}

	/**
	 * Registers page-identifying parameters to the application object. This is used by the Joomla! caching system to
	 * get the unique identifier of a page and decide its caching status (cached, not cached, cache expired).
	 *
	 * @param array $urlparams
	 */
	protected function registerUrlParams($urlparams = array())
	{
		$app = \JFactory::getApplication();

		$registeredurlparams = null;

		if (!empty($app->registeredurlparams))
		{
			$registeredurlparams = $app->registeredurlparams;
		}
		else
		{
			$registeredurlparams = new \stdClass;
		}

		foreach ($urlparams as $key => $value)
		{
			// Add your safe url parameters with variable type as value {@see JFilterInput::clean()}.
			$registeredurlparams->$key = $value;
		}

		$app->registeredurlparams = $registeredurlparams;
	}
}