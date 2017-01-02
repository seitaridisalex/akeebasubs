<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Dispatcher;

defined('_JEXEC') or die;

use FOF30\Container\Container;

class Dispatcher extends \FOF30\Dispatcher\Dispatcher
{
	/** @var   string  The name of the default view, in case none is specified */
	public $defaultView = 'ControlPanel';

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

		if (in_array($useStrapper, [2, 3]) && \JFolder::exists(JPATH_SITE . '/media/strapper30'))
		{
			@include_once JPATH_SITE . '/media/strapper30/strapper.php';

			if (class_exists('\\AkeebaStrapper30', false))
			{
				\AkeebaStrapper30::bootstrap();
				\AkeebaStrapper30Loader();
			}
		}
		// Render submenus as drop-down navigation bars powered by Bootstrap
		$this->container->renderer->setOption('linkbar_style', 'classic');

		// Load common CSS and JavaScript
		\JHtml::_('jquery.framework');
		$this->container->template->addCSS('media://com_akeebasubs/css/backend.css', $this->container->mediaVersion);
		$this->container->template->addJS('media://com_akeebasubs/js/backend.js', false, false, $this->container->mediaVersion);
	}
}