<?php
/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

use Akeeba\Subscriptions\Admin\Model\Levels;
use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use Akeeba\Subscriptions\Admin\PluginAbstracts\AkpaymentBase;

defined('_JEXEC') or die();

class plgAkpaymentNone extends AkpaymentBase
{
	/**
	 * Public constructor for the plugin
	 *
	 * @param   object $subject The object to observe
	 * @param   array  $config  An optional associative array of configuration settings.
	 */
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'  => 'none',
			'ppKey'   => 'PLG_AKPAYMENT_NONE_TITLE',
			'ppImage' => rtrim(JURI::base(), '/') . '/media/com_akeebasubs/images/frontend/none.png',
		));

		parent::__construct($subject, $config);
	}

	/**
	 * Returns the payment form to be submitted by the user's browser. The form must have an ID of
	 * "paymentForm" and a visible submit button.
	 *
	 * @param   string        $paymentmethod The currently used payment method. Check it against $this->ppName.
	 * @param   JUser         $user          User buying the subscription
	 * @param   Levels        $level         Subscription level
	 * @param   Subscriptions $subscription  The new subscription's object
	 *
	 * @return  string  The payment form to render on the page. Use the special id 'paymentForm' to have it
	 *                  automatically submitted after 5 seconds.
	 */
	public function onAKPaymentNew($paymentmethod, JUser $user, Levels $level, Subscriptions $subscription)
	{
		if ($paymentmethod != $this->ppName)
		{
			return false;
		}

		$rootURL = rtrim(JURI::base(), '/');
		$subpathURL = JURI::base(true);

		if (!empty($subpathURL) && ($subpathURL != '/'))
		{
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}

		$uri = $rootURL . str_replace('&amp;', '&', JRoute::_('index.php?option=com_akeebasubs&view=Callback&paymentmethod=none'));

		$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
		$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');

		$form = <<<ENDFORM
<h3>$t1</h3>
<p>$t2</p>
<form action="$uri" method="POST" id="paymentForm">
	<input type="hidden" name="subscription" value="{$subscription->akeebasubs_subscription_id}" />
	<input type="submit" class="btn" value="Complete subscription" />
</form>
ENDFORM;

		// This is a demo script; just add some GET parameters to the URI
		return $form;
	}

	/**
	 * Processes a callback from the payment processor
	 *
	 * @param   string  $paymentmethod  The currently used payment method. Check it against $this->ppName
	 * @param   array   $data           Input (request) data
	 *
	 * @return  boolean  True if the callback was handled, false otherwise
	 */
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		if ($paymentmethod != $this->ppName)
		{
			return false;
		}

		// Enable the subscription
		$id = (int)$data['subscription'];

		/** @var Subscriptions $subscription */
		$subscription = $this->container->factory->model('Subscriptions')->tmpInstance();

		$subscription->find($id);

		if (empty($subscription->akeebasubs_subscription_id))
		{
			return false;
		}

		if ($subscription->akeebasubs_subscription_id != $id)
		{
			return false;
		}

		$id = (int)$data['subscription'];

		$updates = array(
			'akeebasubs_subscription_id' => $id,
			'processor_key'              => md5(microtime(false)),
			'state'                      => 'C',
			'enabled'                    => 1,
		);

		self::fixSubscriptionDates($subscription, $updates);
		$subscription->save($updates);

		// Run the onAKAfterPaymentCallback events
		$this->container->platform->importPlugin('akeebasubs');
		$this->container->platform->runPlugins('onAKAfterPaymentCallback', array(
			$subscription
		));

		// This plugin is a tricky one; it will redirect you to the thank you page
		$slug = $subscription->level->slug;

		$url = 'index.php?option=com_akeebasubs&view=Message&slug=' . $slug . '&layout=order&subid=' . $subscription->akeebasubs_subscription_id;

		$this->container->platform->redirect($url);

		// Everything is fine, no matter what
		return true;
	}
}