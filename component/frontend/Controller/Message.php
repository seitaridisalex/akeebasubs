<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Controller\Mixin;
use Akeeba\Subscriptions\Site\Model\Levels;
use Akeeba\Subscriptions\Site\Model\Subscriptions;
use FOF30\Container\Container;
use FOF30\Controller\DataController;
use FOF30\View\Exception\AccessForbidden;

class Message extends DataController
{
	use Mixin\PredefinedTaskList;

	/**
	 * Did I have to log in a user (and need to log them out?)
	 *
	 * @var  bool
	 */
	private static $loggedinUser = true;

	/**
	 * Overridden. Limit the tasks we're allowed to execute.
	 *
	 * @param   Container $container
	 * @param   array     $config
	 */
	public function __construct(Container $container, array $config = array())
	{
		// We need to use the Levels model
		$config['modelName'] = 'Levels';

		// Disable token checks (CSRF protection) since this view is called by the payment services, outside our user session
		$config['csrfProtection'] = false;

		parent::__construct($container, $config);

		$this->registerTask('thankyou', 'read');
		$this->registerTask('cancel', 'read');
		$this->predefinedTaskList = ['thankyou', 'cancel'];
	}

	/**
	 * Runs before executing the "thankyou" task. Used to force-set the layout.
	 *
	 * @return  void
	 */
	public function onBeforeThankyou()
	{
		// Set the layout in the input and the object property
		$this->input->set('layout', 'thankyou');
		$this->layout = 'thankyou';

		// Call the common code
		$this->onBeforeRead();
	}

	/**
	 * Runs after executing the "thankyou" task.
	 *
	 * @return  void
	 */
	public function onAfterThankyou()
	{
		// Call the common code
		$this->onAfterRead();
	}

	/**
	 * Runs before executing the "cancel" task. Used to force-set the layout.
	 *
	 * @return  void
	 */
	public function onBeforeCancel()
	{
		// Set the layout in the input and the object property
		$this->input->set('layout', 'cancel');
		$this->layout = 'cancel';

		// Call the common code
		$this->onBeforeRead();
	}

	/**
	 * Runs after executing the "cancel" task.
	 *
	 * @return  void
	 */
	public function onAfterCancel()
	{
		// Call the common code
		$this->onAfterRead();
	}

	/**
	 * Use the slug instead of the id to read a record
	 *
	 * @return  void
	 */
	public function onBeforeRead()
	{
		/** @var Levels $levelsModel */
		$levelsModel = $this->getModel();

		$this->getIDsFromRequest($levelsModel, true);

		$id = $levelsModel->getId();
		$slug = $this->input->getString('slug', null);

		if (!$id && $slug)
		{
			$levelsModel->find(['slug' => $slug]);
			$id = $levelsModel->getId();
		}

		$subid = $this->input->getInt('subid', 0);

		/** @var Subscriptions $subscription */
		$subscription = $this->getModel('Subscriptions')->savestate(0)->setIgnoreRequest(0)->clearState()->find($subid);

		// Working around Progressive Caching
		\JFactory::getApplication()->input->set('subid', $subid);

		$this->registerUrlParams(array(
			'subid' => 'INT'
		));

		$this->getView()->subscription = $subscription;

		if ($subscription->akeebasubs_level_id && ($subscription->akeebasubs_level_id != $levelsModel->getId()))
		{
			$levelsModel->find($subscription->akeebasubs_level_id);
			$id = $levelsModel->getId();
		}

		$levelsModel->setState('id', $id);

		/**
		 * We have to effectively "re-login" the user, otherwise his their ACL privileges are stale.
		 */

		// Get the current user's ID
		$userId = $this->container->platform->getUser()->id;

		if (empty($userId))
		{
			// Guest user; we'll have to log him in
			$userId = $subscription->user_id;

			// Is it the same user who initiated the subscription payment?
			$subscriber_user_id = $this->container->platform->getSessionVar('subscribes.user_id', null, 'com_akeebasubs');

			if ($subscriber_user_id == $subscription->user_id)
			{
				// Do not log him out; he's the user who initiated this subscription
				self::$loggedinUser = false;

				// Unset the subscriber user ID value
				$this->container->platform->setSessionVar('subscribes.user_id', null, 'com_akeebasubs');
			}
			else
			{
				// This is just someone who knows the URL. Let's log him out
				// after we're done showing the page.
				self::$loggedinUser = true;
			}
		}
		elseif ($userId == $subscription->user_id)
		{
			// User already logged in. We'll log him back in (due to Joomla!
			// ACLs not being applied otherwise) but we are not going to log him
			// back out.
			self::$loggedinUser = false;
		}
		elseif ($userId != $subscription->user_id)
		{
			// The logged in user doesn't match the subscription's user; deny access
			self::$loggedinUser = false;

			throw new AccessForbidden;
		}

		// This line returns an empty JUser object
		$newUserObject = new \JUser();

		// This line FORCE RELOADS the user record.
		$newUserObject->load($userId);

		if (($newUserObject->id != $userId))
		{
			// The user cannot be found. Abort.
			self::$loggedinUser = false;

			throw new AccessForbidden;
		}

		// If it is a blocked user let's log him out after loading this page.
		// This decision is made no matter how we ended up deciding to log in
		// this user.
		if ($newUserObject->block)
		{
			self::$loggedinUser = true;
		}

		// Mark the user as logged in
		$newUserObject->block = 0;
		$newUserObject->set('guest', 0);

		// Register the needed session variables
		$this->container->platform->setSessionVar('user', $newUserObject);

		$db = $this->container->db;

		// Check to see the the session already exists.
		$app = \JFactory::getApplication();
		$app->checkSession();

		// Update the user related fields for the Joomla sessions table.
		$query = $db->getQuery(true)
			->update($db->qn('#__session'))
			->set(array(
				$db->qn('guest') . ' = ' . $db->q($newUserObject->get('guest')),
				$db->qn('username') . ' = ' . $db->q($newUserObject->get('username')),
				$db->qn('userid') . ' = ' . (int)$newUserObject->get('id')
			))->where($db->qn('session_id') . ' = ' . $db->q(\JFactory::getSession()->getId()));
		$db->setQuery($query);
		$db->execute();

		// Hit the user last visit field
		$newUserObject->setLastVisit();
	}

	public function onAfterRead()
	{
		// Log out the logged in user
		if (self::$loggedinUser)
		{
			$userId = $this->container->platform->getUser()->id;
			$newUserObject = new \JUser();
			$newUserObject->load($userId);

			$app = \JFactory::getApplication();

			// Perform the log out.
			$app->logout();

			if ($newUserObject->block)
			{
				$newUserObject->lastvisitDate = $this->container->db->getNullDate();
				$newUserObject->save();
			}
		}

		return true;
	}

	/**
	 * Registers page-identifying parameters to the application object. This is used by the Joomla! caching system to
	 * get the unique identifier of a page and decide its caching status (cached, not cached, cache expired).
	 *
	 * @param array $urlparams
	 */
	protected function registerUrlParams($urlparams = array())
	{
		/** @var \JApplicationSite $app */
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