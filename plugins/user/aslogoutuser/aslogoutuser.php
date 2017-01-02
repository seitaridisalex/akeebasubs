<?php

/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */
defined('_JEXEC') or die();

JLoader::import('joomla.plugin.plugin');

use FOF30\Container\Container;
use Akeeba\Subscriptions\Admin\Model\Users;

class plgUserAslogoutuser extends JPlugin
{
	/**
	 * Should this plugin be allowed to run? True if FOF can be loaded and the Akeeba Subscriptions component is enabled
	 *
	 * @var  bool
	 */
	private $enabled = true;

	/**
	 * Public constructor. Overridden to load the language strings.
	 */
	public function __construct(& $subject, $config = array())
	{
		if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
		{
			$this->enabled = false;
		}

		// Do not run if Akeeba Subscriptions is not enabled
		JLoader::import('joomla.application.component.helper');

		if (!JComponentHelper::isEnabled('com_akeebasubs'))
		{
			$this->enabled = false;
		}

		if (!is_object($config['params']))
		{
			JLoader::import('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}

		parent::__construct($subject, $config);

		// Timezone fix; avoids errors printed out by PHP 5.3.3+ (thanks Yannick!)
		if (function_exists('date_default_timezone_get') && function_exists('date_default_timezone_set'))
		{
			$hasErrorReporting = function_exists('error_reporting');

			if ($hasErrorReporting)
			{
				$oldLevel = error_reporting(0);
			}

			$serverTimezone = @date_default_timezone_get();

			if (empty($serverTimezone) || !is_string($serverTimezone))
			{
				$serverTimezone = 'UTC';
			}

			if ($hasErrorReporting)
			{
				error_reporting($oldLevel);
			}

			@date_default_timezone_set($serverTimezone);
		}
	}

	/**
	 * Removes the flag for logging out/in inside Akeeba Subscription user table
	 */
	public function onUserLogin($response, $options)
	{
		if (!$this->enabled)
		{
			return true;
		}

		$userid = JUserHelper::getUserId($response['username']);
		$juser = JFactory::getUser($userid);

		$container = Container::getInstance('com_akeebasubs');
		/** @var Users $user */
		$user = $container->factory->model('Users')->tmpInstance();
		$user->find(['user_id' => $juser->id]);

		// Mhm... the user was not found inside Akeeba Subscription, better stop here
		if (!$user->akeebasubs_user_id)
		{
			return true;
		}

		// Do not go through the model as it ends up destroying the session when the Remember Me plugin tries to log you
		// back in.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->update($db->qn('#__akeebasubs_users'))
			->set($db->qn('needs_logout') . ' = ' . $db->q(0))
			->where($db->qn('akeebasubs_user_id') . ' = ' . $db->q($user->akeebasubs_user_id));

		try
		{
			$db->setQuery($query)->execute();
		}
		catch (\Exception $e)
		{
			// Ignore it
		}

		return true;
	}
}