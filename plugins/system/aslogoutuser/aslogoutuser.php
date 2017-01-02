<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

JLoader::import('joomla.plugin.plugin');

use FOF30\Container\Container;
use Akeeba\Subscriptions\Admin\Model\Users;

class plgSystemAslogoutuser extends JPlugin
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
			if (function_exists('error_reporting'))
			{
				$oldLevel = error_reporting(0);
			}

			$serverTimezone	 = @date_default_timezone_get();

			if (empty($serverTimezone) || !is_string($serverTimezone))
			{
				$serverTimezone	 = 'UTC';
			}

			if (function_exists('error_reporting'))
			{
				error_reporting($oldLevel);
			}

			@date_default_timezone_set($serverTimezone);
		}

	}

	/**
	 * Called when Joomla! is booting up and checks the user status.
	 * Logs out the user if requested by Akeeba Subscriptions.
	 */
	public function onAfterInitialise()
	{
		if (!$this->enabled)
		{
			return;
		}

        $juser = JFactory::getUser();

        // Guest user? No need to check
        if ($juser->guest)
        {
            return;
        }

		$container = Container::getInstance('com_akeebasubs');
		/** @var Users $user */
		$user = $container->factory->model('Users')->tmpInstance();
        $user->find(['user_id' => $juser->id]);

        // Mhm... the user was not found inside Akeeba Subscription, better stop here
        if (!$user->akeebasubs_user_id)
        {
            return;
        }

        // No need to logout, let's stop here
        if (!$user->needs_logout)
        {
            return;
        }

        $app = JFactory::getApplication();
        $returnurl = JURI::getInstance();
        $returnurl = base64_encode($returnurl->toString());

        $app->logout();
        $app->redirect(JRoute::_('index.php?option=com_users&view=login&return='.$returnurl, false));
	}
}