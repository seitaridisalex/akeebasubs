<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Controller;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Model\Updates;
use FOF30\Container\Container;
use FOF30\Controller\Controller;
use JUri;
use JText;

class ControlPanel extends Controller
{
	use Mixin\PredefinedTaskList;

	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		$this->predefinedTaskList = ['main', 'hide2copromo', 'wizardstep', 'updateinfo', 'updategeoip'];
	}

	/**
	 * Runs before the main task, used to perform housekeeping function automatically
	 */
	protected function onBeforeMain()
	{
		/** @var \Akeeba\Subscriptions\Admin\Model\ControlPanel $model */
		$model = $this->getModel();
		$model
			->checkAndFixDatabase()
			->saveMagicVariables();

		/** @var \Akeeba\Subscriptions\Admin\Model\Updates $updatesModel */
		$updatesModel = $this->getModel('Updates');
		$updatesModel->refreshUpdateSite();
	}

	/**
	 * Hide the 2Checkout promotion banner
	 */
	public function hide2copromo()
	{
		/** @var \Akeeba\Subscriptions\Admin\Model\ControlPanel $model */
		$model = $this->getModel();
		$model->setComponentParameter('show2copromo', 0);

		// Redirect back to the control panel
		$url = '';
		$returnurl = $this->input->getBase64('returnurl', '');

		if (!empty($returnurl))
		{
			$url = base64_decode($returnurl);
		}

		if (empty($url))
		{
			$url = JUri::base().'index.php?option=com_akeebasubs';
		}

		$this->setRedirect($url);
	}

	/**
	 * Set the current configuration wizard step
	 */
	public function wizardstep()
	{
		$wizardstep = (int)$this->input->getInt('wizardstep', 0);

		/** @var \Akeeba\Subscriptions\Admin\Model\ControlPanel $model */
		$model = $this->getModel();
		$model->setComponentParameter('wizardstep', $wizardstep);

		// Redirect back to the control panel
		$url = '';
		$returnurl = $this->input->getBase64('returnurl', '');

		if (!empty($returnurl))
		{
			$url = base64_decode($returnurl);
		}

		if (empty($url))
		{
			$url = JUri::base().'index.php?option=com_akeebasubs';
		}

		$this->setRedirect($url);
	}

	/**
	 * Force reload the update information
	 */
	public function updateinfo()
	{
		/** @var Updates $updateModel */
		$updateModel = $this->getModel('Updates');
		$updateInfo = (object)$updateModel->getUpdates();

		$result = '';

		if ($updateInfo->hasUpdate)
		{
			$strings = array(
				'header'		=> JText::sprintf('COM_AKEEBASUBS_CPANEL_MSG_UPDATEFOUND', $updateInfo->version),
				'button'		=> JText::sprintf('COM_AKEEBASUBS_CPANEL_MSG_UPDATENOW', $updateInfo->version),
				'infourl'		=> $updateInfo->infoURL,
				'infolbl'		=> JText::_('COM_AKEEBASUBS_CPANEL_MSG_MOREINFO'),
			);

			$result = <<<ENDRESULT
	<div class="alert alert-warning">
		<h3>
			<span class="icon icon-exclamation-sign glyphicon glyphicon-exclamation-sign"></span>
			{$strings['header']}
		</h3>
		<p>
			<a href="index.php?option=com_installer&view=update" class="btn btn-primary">
				{$strings['button']}
			</a>
			<a href="{$strings['infourl']}" target="_blank" class="btn btn-small btn-info">
				{$strings['infolbl']}
			</a>
		</p>
	</div>
ENDRESULT;
		}

		echo '###' . $result . '###';

		// Cut the execution short
		$this->container->platform->closeApplication();
	}

	/**
	 * Update the GeoIP database
	 */
	public function updategeoip()
	{
		if ($this->csrfProtection)
		{
			$this->csrfProtection();
		}

		// Load the GeoIP library if it's not already loaded
		if (!class_exists('AkeebaGeoipProvider'))
		{
			if (@file_exists(JPATH_PLUGINS . '/system/akgeoip/lib/akgeoip.php'))
			{
				if (@include_once JPATH_PLUGINS . '/system/akgeoip/lib/vendor/autoload.php')
				{
					@include_once JPATH_PLUGINS . '/system/akgeoip/lib/akgeoip.php';
				}
			}
		}

		$geoip = new \AkeebaGeoipProvider();
		$result = $geoip->updateDatabase();

		$url = 'index.php?option=com_akeebasubs';

		if ($result === true)
		{
			$msg = JText::_('COM_AKEEBASUBS_GEOIP_MSG_DOWNLOADEDGEOIPDATABASE');
			$this->setRedirect($url, $msg);
		}
		else
		{
			$this->setRedirect($url, $result, 'error');
		}
	}
}