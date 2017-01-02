<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Controller;

defined('_JEXEC') or die;

use FOF30\Controller\DataController;
use FOF30\Inflector\Inflector;

class Subscription extends DataController
{
	public function publish()
	{
		$this->noop();
	}

	public function unpublish()
	{
		$this->noop();
	}

	public function archive()
	{
		$this->noop();
	}

	public function noop()
	{
		// CSRF prevention
		$this->csrfProtection();

		// Redirect
		if ($customURL = $this->input->getBase64('returnurl', ''))
		{
			$customURL = base64_decode($customURL);
		}

		$url = !empty($customURL) ? $customURL : 'index.php?option=' . $this->container->componentName . '&view=' . $this->container->inflector->pluralize($this->view) . $this->getItemidURLSuffix();

		$this->setRedirect($url);
	}

	protected function onBeforeBrowse()
	{
		$format = $this->input->getCmd('format', 'html');

		// Do not apply list limits on CSV export
		if ($format == 'csv')
		{
			$this->getModel()
				->savestate(false)
				->limit(0)
				->limitstart(0);
		}


	}
}
