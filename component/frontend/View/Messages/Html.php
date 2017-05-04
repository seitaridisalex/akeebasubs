<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\View\Messages;

use Akeeba\Subscriptions\Admin\Helper\Message;
use Akeeba\Subscriptions\Site\Model\Subscriptions;

defined('_JEXEC') or die;

class Html extends \FOF30\View\DataView\Html
{
	/**
	 * The subscription we are dealing with, set by the Controller.
	 *
	 * @var  Subscriptions
	 */
	public $subscription = null;

	/**
	 * HTML for the post-subscription message returned by akeebasubs plugins. This is displayed after the Go Back link.
	 *
	 * @var  string
	 */
	public $pluginHTML = '';

	/**
	 * The message to display, from the subscription level.
	 *
	 * @var  string
	 */
	public $message = '';

	public function onBeforeRead($tpl = null)
	{
		switch ($this->getLayout())
		{
			case 'thankyou':
			default:
				$this->prepareView('onOrderMessage', 'orderurl', 'ordertext');
				break;

			case 'cancel':
				$this->prepareView('onCancelMessage', 'cancelurl', 'canceltext');
				break;
		}
	}

	protected function prepareView($event = 'onOrderMessage', $urlField = 'orderurl', $messageField = 'ordertext')
	{
		parent::onBeforeRead();

		$app = \JFactory::getApplication();

		// Do I have a custom redirect URL? Follow it instead of showing the message
		// This check has been put here so controller and model can do all their logic and trigger every event
		if ($this->item->$urlField)
		{
			$this->container->platform->redirect($this->item->$urlField);
		}

		// Get and process the message from the subscription level
		$message = Message::processLanguage($this->item->$messageField);
		$message = Message::processSubscriptionTags($message, $this->subscription);
		$this->message = \JHTML::_('content.prepare', $message);

		// Get additional message HTML from the plugins
		$pluginHtml = '';

		$this->container->platform->importPlugin('akeebasubs');
		$jResponse = $this->container->platform->runPlugins($event, array($this->subscription));

		if (is_array($jResponse) && !empty($jResponse))
		{
			foreach ($jResponse as $pluginResponse)
			{
				if (!empty($pluginResponse))
				{
					$pluginHtml .= $pluginResponse;
				}
			}
		}

		$this->pluginHTML = $pluginHtml;

		// Makes sure SiteGround's SuperCache doesn't cache the subscription page
		$app->setHeader('X-Cache-Control', 'False', true);
	}
}