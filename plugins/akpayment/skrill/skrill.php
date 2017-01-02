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

class plgAkpaymentSkrill extends AkpaymentBase
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName' => 'skrill',
			'ppKey'  => 'PLG_AKPAYMENT_SKRILL_TITLE',
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

		$slug = $level->slug;

		$rootURL = rtrim(JURI::base(), '/');
		$subpathURL = JURI::base(true);

		if (!empty($subpathURL) && ($subpathURL != '/'))
		{
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}

		$kuser = $subscription->user;

		if (is_null($kuser))
		{
			/** @var \Akeeba\Subscriptions\Site\Model\Users $userModel */
			$userModel = $this->container->factory->model('Users')->tmpInstance();
			$kuser = $userModel->user_id($subscription->user_id)->firstOrNew();
		}

		$data = (object)array(
			'url'       => $this->getPaymentURL(),
			'merchant'  => $this->params->get('merchant', ''),
			'postback'  => JURI::base() . 'index.php?option=com_akeebasubs&view=Callback&paymentmethod=skrill',
			'success'   => $rootURL . str_replace('&amp;', '&', JRoute::_('index.php?option=com_akeebasubs&view=Message&slug=' . $slug . '&task=thankyou&subid=' . $subscription->akeebasubs_subscription_id)),
			'cancel'    => $rootURL . str_replace('&amp;', '&', JRoute::_('index.php?option=com_akeebasubs&view=Message&slug=' . $slug . '&task=cancel&subid=' . $subscription->akeebasubs_subscription_id)),
			'currency'  => strtoupper($this->container->params->get('currency', 'EUR')),
			'firstname' => $firstName,
			'lastname'  => $lastName,
			'country'   => $this->translateCountry($kuser->country)
		);

		@ob_start();

		include dirname(__FILE__) . '/skrill/form.php';

		$html = @ob_get_clean();

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
		JLoader::import('joomla.utilities.date');

		// Check if we're supposed to handle this
		if ($paymentmethod != $this->ppName)
		{
			return false;
		}

		// Check IPN data for validity (i.e. protect against fraud attempt)
		$isValid = $this->isValidIPN($data);

		if (!$isValid)
		{
			$data['akeebasubs_failure_reason'] = 'MD5 hash does not validate';
		}

		// Load the relevant subscription row
		if ($isValid)
		{
			$id = array_key_exists('transaction_id', $data) ? (int)$data['custom'] : -1;
			$subscription = null;
			if ($id > 0)
			{
				/** @var Subscriptions $subscription */
				$subscription = $this->container->factory->model('Subscriptions')->tmpInstance();
				$subscription->find($id);

				if (($subscription->akeebasubs_subscription_id <= 0) || ($subscription->akeebasubs_subscription_id != $id))
				{
					$subscription = null;
					$isValid = false;
				}
			}
			else
			{
				$isValid = false;
			}

			if (!$isValid)
			{
				$data['akeebasubs_failure_reason'] = 'The referenced subscription ID ("transaction_id" field) is invalid';
			}
		}

		/** @var Subscriptions $subscription */

		// Check that receiver is what the site owner has configured
		if ($isValid)
		{
			$receiver_email = $data['pay_to_email'];
			$valid_id = $this->params->get('merchant', '');

			$isValid =
				($receiver_email == $valid_id)
				|| (strtolower($receiver_email) == strtolower($receiver_email));

			if (!$isValid)
			{
				$data['akeebasubs_failure_reason'] = 'Merchant ID does not match pay_to_email';
			}
		}

		// Check that amount is correct
		$isPartialRefund = false;

		if ($isValid)
		{
			$mc_gross = floatval($data['amount']);
			$gross = $subscription->gross_amount;

			if ($mc_gross > 0)
			{
				// A positive value means "payment". The prices MUST match!
				// Important: NEVER, EVER compare two floating point values for equality.
				$isValid = ($gross - $mc_gross) < 0.01;
			}
			else
			{
				$isPartialRefund = false;
				$temp_mc_gross = -1 * $mc_gross;
				$isPartialRefund = ($gross - $temp_mc_gross) > 0.01;
			}

			if (!$isValid)
			{
				$data['akeebasubs_failure_reason'] = 'Paid amount does not match the subscription amount';
			}
		}

		// Check that mb_transaction_id has not been previously processed
		if ($isValid && !$isPartialRefund)
		{
			if ($subscription->processor_key == $data['mb_transaction_id'])
			{
				if ($subscription->state == 'C')
				{
					$isValid = false;
					$data['akeebasubs_failure_reason'] = "I will not process the same mb_transaction_id twice";
				}
			}
		}

		// Check that currency is correct
		if ($isValid)
		{
			$mc_currency = strtoupper($data['currency']);
			$currency = strtoupper($this->container->params->get('currency', 'EUR'));

			if ($mc_currency != $currency)
			{
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Invalid currency; expected $currency, got $mc_currency";
			}
		}

		// Log the IPN data
		$this->logIPN($data, $isValid);

		// Fraud attempt? Do nothing more!
		if (!$isValid)
		{
			return false;
		}

		// Check the payment_status
		switch ($data['status'])
		{
			case '2':
				$newStatus = 'C';
				break;

			case '0':
				$newStatus = 'P';
				break;

			case '-3':
				if ($isPartialRefund)
				{
					$newStatus = 'C';
				}
				else
				{
					$newStatus = 'X';
				}
				break;

			case '-1':
			case '-2':
			default:
				$newStatus = 'X';
				break;
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
			'akeebasubs_subscription_id' => $id,
			'processor_key'              => $data['txn_id'],
			'state'                      => $newStatus,
			'enabled'                    => 0
		);

		JLoader::import('joomla.utilities.date');

		if ($newStatus == 'C')
		{
			self::fixSubscriptionDates($subscription, $updates);
		}

		$subscription->save($updates);

		// Run the onAKAfterPaymentCallback events
		$this->container->platform->importPlugin('akeebasubs');
		$this->container->platform->runPlugins('onAKAfterPaymentCallback', array(
			$subscription
		));

		return true;
	}

	/**
	 * Validates the incoming data against PayPal's IPN to make sure this is not a
	 * fraudelent request.
	 */
	private function isValidIPN($data)
	{
		$secret = $this->params->get('secret', '');
		$secret_md5 = strtoupper(md5($secret));

		$signature_string = $data['merchant_id'] . $data['transaction_id'] .
			$secret_md5 . $data['mb_amount'] . $data['mb_currency'] .
			$data['status'];

		$calculated = strtoupper(md5($signature_string));
		$incoming = strtoupper($data['md5sig']);

		return ($calculated == $incoming);
	}

	/**
	 * Gets the form action URL for the payment
	 */
	private function getPaymentURL()
	{
		$sandbox = $this->params->get('sandbox', 0);
		if ($sandbox)
		{
			return 'http://www.moneybookers.com/app/test_payment.pl';
		}
		else
		{
			return 'https://www.moneybookers.com/app/payment.pl';
		}
	}
}