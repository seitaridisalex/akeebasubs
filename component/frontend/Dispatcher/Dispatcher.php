<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Dispatcher;

defined('_JEXEC') or die;

class Dispatcher extends \FOF30\Dispatcher\Dispatcher
{
	/** @var   string  The name of the default view, in case none is specified */
	public $defaultView = 'Levels';

	public function onBeforeDispatch()
	{
		if (!@include_once(JPATH_ADMINISTRATOR . '/components/com_akeebasubs/version.php'))
		{
			define('AKEEBASUBS_VERSION', 'dev');
			define('AKEEBASUBS_DATE', date('Y-m-d'));
		}

		// Load Akeeba Strapper, if it is installed
		\JLoader::import('joomla.filesystem.folder');

		$useStrapper = $this->container->params->get('usestrapper', 3);

		if (in_array($useStrapper, [1, 3]) && \JFolder::exists(JPATH_SITE . '/media/strapper30'))
		{
			@include_once JPATH_SITE . '/media/strapper30/strapper.php';

			if (class_exists('\\AkeebaStrapper30', false))
			{
				\AkeebaStrapper30::bootstrap();
				\AkeebaStrapper30Loader();
			}
		}

		// Load common CSS JavaScript
		\JHtml::_('jquery.framework');
		$this->container->template->addCSS('media://com_akeebasubs/css/frontend.css', $this->container->mediaVersion);

		// Translate view names from Akeeba Subscriptions 1.x, 2.x, 3.x and 4.x
		$this->translateOldViewNames();

		// Handle the submitted form from the tax country module
		$taxCountry = \JFactory::getApplication()->input->getCmd('mod_aktaxcountry_country', null);

		if (!is_null($taxCountry))
		{
			$this->container->platform->setSessionVar('country', $taxCountry, 'mod_aktaxcountry');
		}
	}

	/**
	 * Translates the view name of an old version of Akeeba Subscriptions to the new names used in Akeeba Subscriptions
	 * 5.x and later.
	 */
	protected function translateOldViewNames()
	{
		// Map Akeeba Subscriptions 1.x-4.x view name to Akeeba Subscriptions 5.x+ view name
		$map = [
			'apicoupons'    => 'APICoupons',
			'apicoupon'     => 'APICoupons',
			'callbacks'     => 'Callbacks',
			'callback'      => 'Callbacks',
			'cron'          => 'Cron',
			'crons'         => 'Cron',
			'invoice'       => 'Invoices',
			'invoices'      => 'Invoices',
			'level'         => 'Level',
			'levels'        => 'Levels',
			'messages'      => 'Messages',
			'message'       => 'Messages',
			'subscribes'    => 'Subscribe',
			'subscribe'     => 'Subscribe',
			'subscription'  => 'Subscriptions',
			'subscriptions' => 'Subscriptions',
			'userinfos'     => 'UserInfo',
			'userinfo'      => 'UserInfo',
			'validates'     => 'Validate',
			'validate'      => 'Validate',
		];

		$oldViewName = strtolower($this->view);

		if (isset($map[$oldViewName]))
		{
			$this->view = $map[$oldViewName];
		}
	}
}