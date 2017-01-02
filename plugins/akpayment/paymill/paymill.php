<?php
/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

use FOF30\Container\Container;
use Akeeba\Subscriptions\Admin\Model\Levels;
use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use Akeeba\Subscriptions\Admin\PluginAbstracts\AkpaymentBase;

class plgAkpaymentPaymill extends AkpaymentBase
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'  => 'paymill',
			'ppKey'   => 'PLG_AKPAYMENT_PAYMILL_TITLE',
			'ppImage' => rtrim(JURI::base(), '/') . '/media/com_akeebasubs/images/frontend/paymill.png',
		));

		parent::__construct($subject, $config);

		require_once __DIR__ . '/paymill/lib/Services/Paymill/Base.php';
		require_once __DIR__ . '/paymill/lib/Services/Paymill/Transactions.php';
		require_once __DIR__ . '/paymill/lib/Services/Paymill/Payments.php';
		require_once __DIR__ . '/paymill/lib/Services/Paymill/Clients.php';
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

		$doc = JFactory::getDocument();
		$doc->addScriptDeclaration(
			"\n;//\nvar PAYMILL_PUBLIC_KEY = '" . $this->getPublicKey() . "';\n");
		$doc->addScript("https://bridge.paymill.de/");

		$callbackUrl = JURI::base() . 'index.php?option=com_akeebasubs&view=Callback&paymentmethod=paymill&sid=' . $subscription->akeebasubs_subscription_id;
		$data = (object)array(
			'url'         => $callbackUrl,
			'amount'      => (int)($subscription->gross_amount * 100),
			'currency'    => strtoupper($this->container->params->get('currency', 'EUR')),
			'description' => $level->title . ' #' . $subscription->akeebasubs_subscription_id,
			'carholder'   => $user->name,
		);

		@ob_start();
		include dirname(__FILE__) . '/paymill/form.php';
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
		$isValid = true;

		// Load the relevant subscription row
		$id = $data['sid'];
		$subscription = null;

		// CHECK: Is this a valid subscription record?
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

		/** @var Subscriptions $subscription */

		if (!$isValid)
		{
			$data['akeebasubs_failure_reason'] = 'The subscription ID is invalid';
		}

		// CHECK: Is the amount correct?
		$isPartialRefund = false;

		if ($isValid)
		{
			$mc_gross = $data['amount'];

			// Remember: the amount is in cents, e.g. 400 means 4.00 Euros
			$gross = (int)($subscription->gross_amount * 100);

			$isValid = ($gross - $mc_gross) < 0.01;

			if (!$isValid)
			{
				$data['akeebasubs_failure_reason'] = 'Paid amount does not match the subscription amount';
			}
		}

		// CHECK: Is this transaction valid?
		// Log the IPN data
		$this->logIPN($data, $isValid, 'CALLBACK');

		// Fraud attempt? Do nothing more!
		if (!$isValid)
		{
			$level = $subscription->level;
			$error_url = 'index.php?option=com_akeebasubs&view=Level&slug=' . $level->slug;
			$error_url = JRoute::_($error_url, false);

			JFactory::getApplication()->redirect($error_url, $data['akeebasubs_failure_reason'], 'error');

			return false;
		}

		// ACTION: Initialise common variables
		if ($isValid)
		{
			$apiKey = $this->getPrivateKey();
			$apiEndpoint = 'https://api.paymill.de/v2/';
		}

		$db = JFactory::getDbo();

		// CHECK: Do we have a user already defined in PayMill?
		$user = JFactory::getUser($subscription->user_id);

		$clientsObject = new Services_Paymill_Clients($apiKey, $apiEndpoint);

		$filters = array(
			'email' => $user->email
		);

		$clients = $clientsObject->get($filters);

		// ACTION: Get the client ID or create and save a new user in PayMill if necessary
		if (count($clients))
		{
			$clientRecord = array_pop($clients);
		}
		else
		{
			$params = array(
				'email'       => $user->email,
				'description' => $user->name . ' [' . $user->username . ']'
			);

			try
			{
				$clientRecord = $clientsObject->create($params);
			}
			catch (Exception $exc)
			{
				$isValid = false;
				$params['akeebasubs_failure_reason'] = $exc->getMessage();
			}

			if (!array_key_exists('id', $clientRecord) || empty($clientRecord['id']))
			{
				// Apparently the client creation failed
				$isValid = false;
				$params['akeebasubs_failure_reason'] = JText::_('PLG_AKPAYMENT_PAYMILL_ERROR_CLIENT');
			}

			// Log the user creation data
			$this->logIPN($data, $isValid, 'USER');

			// Fraud attempt? Do nothing more!
			if (!$isValid)
			{
				$level = $subscription->level;
				$error_url = 'index.php?option=com_akeebasubs&view=Level&slug=' . $level->slug;
				$error_url = JRoute::_($error_url, false);

				JFactory::getApplication()->redirect($error_url, $params['akeebasubs_failure_reason'], 'error');

				return false;
			}
		}
		$client = $clientRecord['id'];

		// CHECK: Do we already have a payment for this subscription?
		// -- Load the processor key from database. This prevents race conditions.
		$query = $db->getQuery(true)
			->select($db->qn('processor_key'))
			->from('#__akeebasubs_subscriptions')
			->where($db->qn('akeebasubs_subscription_id') . ' = ' . $db->q($subscription->akeebasubs_subscription_id));
		$db->setQuery($query);
		$payment_id = $db->loadResult();

		// ACTION: Create and save a new payment for this subscription if there is no payment or transaction yet
		if ((substr($payment_id, 0, 4) != 'pay_') && (substr($payment_id, 0, 5) != 'tran_'))
		{
			$params = array(
				'client' => $client,
				'token'  => $data['token']
			);
			$paymentsObject = new Services_Paymill_Payments($apiKey, $apiEndpoint);

			try
			{
				$creditcard = $paymentsObject->create($params);
			}
			catch (Exception $exc)
			{
				$isValid = false;
				$params['akeebasubs_failure_reason'] = $exc->getMessage();
			}

			if (!array_key_exists('id', $creditcard) || empty($creditcard['id']))
			{
				// Apparently the credit card capture creation failed
				$isValid = false;
				$params['akeebasubs_failure_reason'] = JText::_('PLG_AKPAYMENT_PAYMILL_ERROR_CC') . '<br/>Tech info: <tt>' . htmlentities($creditcard['error']) . '</tt>';
			}

			// Log the payment creation data
			$this->logIPN($data, $isValid, 'PAYMENT');

			// Fraud attempt? Do nothing more!
			if (!$isValid)
			{
				$level = $subscription->level;
				$error_url = 'index.php?option=com_akeebasubs&view=Level&slug=' . $level->slug;
				$error_url = JRoute::_($error_url, false);

				JFactory::getApplication()->redirect($error_url, $params['akeebasubs_failure_reason'], 'error');

				return false;
			}

			$subscription->processor_key = $creditcard['id'];
			$payment_id = $creditcard['id'];

			// Save the payment information WITHOUT using the table (skips the plugins)
			// This prevents double payments from being recorded
			$oUpdate = (object)array(
				'akeebasubs_subscription_id' => $subscription->akeebasubs_subscription_id,
				'processor_key'              => $subscription->processor_key,
				'state'                      => 'P',
			);

			JFactory::getDbo()->updateObject('#__akeebasubs_subscriptions', $oUpdate, 'akeebasubs_subscription_id');
		}

		// CHECK: Do we already have a transaction for this subscription?
		// -- Load the processor key from database. This prevents race conditions.
		$query = $db->getQuery(true)
			->select($db->qn('processor_key'))
			->from('#__akeebasubs_subscriptions')
			->where($db->qn('akeebasubs_subscription_id') . ' = ' . $db->q($subscription->akeebasubs_subscription_id));
		$db->setQuery($query);
		$payment_id = $db->loadResult();

		// ACTION: Create a transaction if necessary
		if (substr($payment_id, 0, 5) != 'tran_')
		{
			// First update the object with a fake transaction
			$subscription->processor_key = 'tran_in_progress';

			// Save the payment information WITHOUT using the table (skips the plugins)
			// This prevents double payments from being recorded
			$oUpdate = (object)array(
				'akeebasubs_subscription_id' => $subscription->akeebasubs_subscription_id,
				'processor_key'              => $subscription->processor_key,
				'state'                      => 'P',
			);

			JFactory::getDbo()->updateObject('#__akeebasubs_subscriptions', $oUpdate, 'akeebasubs_subscription_id');

			// Create the transaction
			$params = array(
				'amount'      => $data['amount'],
				'currency'    => $data['currency'],
				'client'      => $client,
				'payment'     => $payment_id,
				'description' => $data['description']
			);

			try
			{
				$transactionsObject = new Services_Paymill_Transactions(
					$apiKey, $apiEndpoint
				);
				$transaction = $transactionsObject->create($params);
			}
			catch (Exception $exc)
			{
				$isValid = false;
				$params['akeebasubs_failure_reason'] = $exc->getMessage();
			}

			if (!array_key_exists('id', $transaction) || empty($transaction['id']))
			{
				// Apparently the transaction creation failed
				$isValid = false;
				$params['akeebasubs_failure_reason'] = JText::_('PLG_AKPAYMENT_PAYMILL_ERROR_TRANS');
			}

			// Log the payment creation data
			$this->logIPN($data, $isValid, 'TRANSACTION');

			if (!$isValid)
			{
				$transaction_id = $payment_id;
			}
			else
			{
				$transaction_id = $transaction['id'];
			}

			// First update the object
			$subscription->processor_key = $transaction_id;

			// Save the payment information WITHOUT using the table (skips the plugins)
			// This prevents double payments from being recorded
			$oUpdate = (object)array(
				'akeebasubs_subscription_id' => $subscription->akeebasubs_subscription_id,
				'processor_key'              => $subscription->processor_key,
			);
			JFactory::getDbo()->updateObject('#__akeebasubs_subscriptions', $oUpdate, 'akeebasubs_subscription_id');

			// Fraud attempt? Do nothing more!
			if (!$isValid)
			{
				$level = $subscription->level;
				$error_url = 'index.php?option=com_akeebasubs&view=Level&slug=' . $level->slug;
				$error_url = JRoute::_($error_url, false);

				JFactory::getApplication()->redirect($error_url, $params['akeebasubs_failure_reason'], 'error');

				return false;
			}
		}
		else
		{
			// ACTION: If no transaction is necessary, show an error
			$level = $subscription->level;
			$error_url = 'index.php?option=com_akeebasubs&view=Level&slug=' . $level->slug;
			$error_url = JRoute::_($error_url, false);

			JFactory::getApplication()->redirect($error_url, 'Cannot process the transaction twice. Wait to receive your subscription confirmation email and do not retry submitting the payment form again.', 'error');

			return false;
		}

		if ($isValid)
		{
			if ($this->params->get('sandbox') == $transaction['livemode'])
			{
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Transaction done in wrong mode.";
			}
		}

		// Payment status
		// Check the payment_status
		switch ($transaction['status'])
		{
			case 'closed':
			case 'partial_refunded':
				$newStatus = 'C';
				break;

			case 'open':
			case 'pending':
			case 'preauthorize':
				$newStatus = 'P';
				break;

			case 'failed':
			case 'refunded':
				$newStatus = 'X';
				break;
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
			'akeebasubs_subscription_id' => $id,
			'processor_key'              => $transaction_id,
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

		// Redirect the user to the "thank you" page
		$level = $subscription->level;
		$thankyouUrl = JRoute::_('index.php?option=com_akeebasubs&view=Message&slug=' . $level->slug . '&task=thankyou&subid=' . $subscription->akeebasubs_subscription_id, false);
		JFactory::getApplication()->redirect($thankyouUrl);

		return true;
	}

	private function getPublicKey()
	{
		$sandbox = $this->params->get('sandbox', 0);
		if ($sandbox)
		{
			return trim($this->params->get('sb_public_key', ''));
		}
		else
		{
			return trim($this->params->get('public_key', ''));
		}
	}

	private function getPrivateKey()
	{
		$sandbox = $this->params->get('sandbox', 0);
		if ($sandbox)
		{
			return trim($this->params->get('sb_private_key', ''));
		}
		else
		{
			return trim($this->params->get('private_key', ''));
		}
	}

	public function selectMonth()
	{
		$options = array();
		$options[] = JHTML::_('select.option', 0, '--');
		for ($i = 1; $i <= 12; $i++)
		{
			$m = sprintf('%02u', $i);
			$options[] = JHTML::_('select.option', $m, $m);
		}

		return JHTML::_('select.genericlist', $options, 'card-expiry-month', 'class="input-small"', 'value', 'text', '', 'card-expiry-month');
	}

	public function selectYear()
	{
		$year = (int) gmdate('Y');

		$options = array();
		$options[] = JHTML::_('select.option', 0, '--');
		for ($i = 0; $i <= 10; $i++)
		{
			$y = sprintf('%04u', $i + $year);
			$options[] = JHTML::_('select.option', $y, $y);
		}

		return JHTML::_('select.genericlist', $options, 'card-expiry-year', 'class="input-small"', 'value', 'text', '', 'card-expiry-year');
	}
}