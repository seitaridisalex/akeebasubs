<?php
/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\Model\Levels;
use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use Akeeba\Subscriptions\Admin\PluginAbstracts\AkpaymentBase;
use Akeeba\Subscriptions\Admin\Helper\Email;
use Akeeba\Subscriptions\Admin\Helper\Message;

class plgAkpaymentOffline extends AkpaymentBase
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'  => 'offline',
			'ppKey'   => 'PLG_AKPAYMENT_OFFLINE_TITLE',
			'ppImage' => rtrim(JURI::base(), '/') . '/media/com_akeebasubs/images/frontend/offline.png',
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

		$updates = array(
			'state'         => 'P',
			'enabled'       => 0,
			'processor_key' => md5(time()),
		);

		$subscription->save($updates);

		// Activate the user account, if the option is selected
		$activate = $this->params->get('activate', 0);

		if ($activate && $user->block)
		{
			$updates = array(
				'block'      => 0,
				'activation' => ''
			);
			$user->bind($updates);
			$user->save(true);
		}

		// Render the HTML form
		$nameParts = explode(' ', $user->name, 2);
		$firstName = $nameParts[0];

		if (count($nameParts) > 1)
		{
			$lastName = $nameParts[1];
		}
		else
		{
			$lastName = '';
		}

		$html = $this->params->get('instructions', '');

		if (empty($html))
		{
			$html = <<<HTML
<p>Dear Sir/Madam,<br/>
In order to complete your payment, please deposit {AMOUNT}€ to our bank account:</p>
<p>
<b>IBAN</b>: XX00.000000.00000000.00000000<br/>
<b>BIC</b>: XXXXXXXX
</p>
<p>Please reference subscription code {SUBSCRIPTION} in your payment. Make sure that any bank charges are paid by you in full and not deducted from the transferred amount. If you're using e-Banking to transfer the funds, please select the "OUR" bank expenses option.</p>
<p>Thank you in advance,<br/>
The management</p>
HTML;
		}
		$html = str_replace('{AMOUNT}', sprintf('%01.02f', $subscription->gross_amount), $html);
		$html = str_replace('{SUBSCRIPTION}', sprintf('%06u', $subscription->akeebasubs_subscription_id), $html);
		$html = str_replace('{FIRSTNAME}', $firstName, $html);
		$html = str_replace('{LASTNAME}', $lastName, $html);
		$html = str_replace('{LEVEL}', $level->title, $html);

		// Get a preloaded mailer

		$mailer = Email::getPreloadedMailer($subscription, 'plg_akeebasubs_subscriptionemails_offline');

		// Replace custom [INSTRUCTIONS] tag
		$body = str_replace('[INSTRUCTIONS]', $html, $mailer->Body);

		if ($mailer !== false)
		{
			$mailer->setBody($body);
			$mailer->addRecipient($user->email);
			$mailer->Send();
			$mailer = null;
		}

		if (class_exists('AkeebasubsHelperMessage'))
		{
			$html = Message::processLanguage($html);
		}

		$html = '<div>' . $html . '</div>';

		return $html;
	}

	/**
	 * Processes a callback from the payment processor
	 *
	 * @param   string $paymentmethod The currently used payment method. Check it against $this->ppName
	 * @param   array  $data          Input (request) data
	 *
	 * @return  boolean  True if the callback was handled, false otherwise
	 */
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		return false;
	}
}