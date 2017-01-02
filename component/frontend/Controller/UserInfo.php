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
use Akeeba\Subscriptions\Site\Model\Users;
use FOF30\Container\Container;
use FOF30\Controller\Controller;
use FOF30\View\Exception\AccessForbidden;

class UserInfo extends Controller
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

		$this->modelName = 'Subscribe';
		$this->predefinedTaskList = ['browse', 'save'];
	}

	public function browse()
	{
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

		$view = $this->getView();

		/** @var Subscribe $model */
		$model = $this->getModel();

		// Get the user model and load the user data
		/** @var Users $usersModel */
		$usersModel = $this->getModel('Users')->savestate(0)->setIgnoreRequest(1);
		$userparams = $usersModel
			->getMergedData($user->id);

		$view->userparams = $userparams;

		$cache = (array)($model->getData());

		if ($cache['firstrun'])
		{
			foreach ($cache as $k => $v)
			{
				if (empty($v))
				{
					if (property_exists($userparams, $k))
					{
						$cache[$k] = $userparams->$k;
					}
				}
			}
		}
		$view->cache = (array)$cache;
		$view->validation = $model->getValidation();

		$this->display(false);
	}

	public function save()
	{
		$user = $this->container->platform->getUser();

		if ($user->guest)
		{
			throw new AccessForbidden;
		}

		// CSRF prevention
		if ($this->csrfProtection)
		{
			$this->csrfProtection();
		}

		// Set error message in case data won't be updated below
		$msgType = 'error';
		$msg = \JText::_('COM_AKEEBASUBS_LBL_USERINFO_ERROR');

		/** @var Subscribe $model */
		$model = $this->getModel();

		// Is this a valid form?
		$isValid = $model->isValid();

		if ($isValid)
		{
			// Try saving the user data
			$result = $model->updateUserInfo(false);

			if ($result)
			{
				// And save the custom fields, too.
				$model->saveCustomFields();

				$msgType = 'info';
				$msg = \JText::_('COM_AKEEBASUBS_LBL_USERINFO_SAVED');
			}
		}

		// Redirect to the display task
		$itemId = \JFactory::getApplication()->input->getInt('Itemid');
		$itemId = $itemId ? '*itemid=' . $itemId : '';
		$url = \JRoute::_('index.php?option=com_akeebasubs&view=UserInfo' . $itemId);


		$this->setRedirect($url, $msg, $msgType);
	}
}