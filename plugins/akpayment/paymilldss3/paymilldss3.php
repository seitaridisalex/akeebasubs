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

class plgAkpaymentPaymilldss3 extends AkpaymentBase
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'  => 'paymilldss3',
			'ppKey'   => 'PLG_AKPAYMENT_PAYMILL_TITLE',
			'ppImage' => rtrim(JURI::base(), '/') . '/media/com_akeebasubs/images/frontend/paymill.png',
		));

		parent::__construct($subject, $config);

		require_once __DIR__ . '/paymilldss3/autoload.php';

		$this->loadLanguage('plg_akpayment_paymilldss3');
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

		$container = Container::getInstance('com_akeebasubs');
		$container->template->addJSInline(
			"\n;//\nvar PAYMILL_PUBLIC_KEY = '" . $this->getPublicKey() . "';\n");
		$container->template->addJS("https://bridge.paymill.com/dss3");
		$container->template->addJS(JUri::base() . 'plugins/akpayment/paymilldss3/js/publicapi.js');

		$callbackUrl = JURI::base() . 'index.php?option=com_akeebasubs&view=Callback&paymentmethod=paymilldss3&sid=' . $subscription->akeebasubs_subscription_id;
		$data = (object)array(
			'url'         => $callbackUrl,
			'amount'      => (int)($subscription->gross_amount * 100),
			'currency'    => strtoupper($this->container->params->get('currency', 'EUR')),
			'description' => $level->title . ' #' . $subscription->akeebasubs_subscription_id,
			'carholder'   => $user->name,
		);

		@ob_start();
		include dirname(__FILE__) . '/paymilldss3/form.php';
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
		$container = Container::getInstance('com_akeebasubs');

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

			$this->container->platform->redirect($error_url, 303, $data['akeebasubs_failure_reason'], 'error');

			return false;
		}

		// ACTION: Initialise common variables
		$apiKey = $this->getPrivateKey();
		$apiEndpoint = 'https://api.paymill.de/v2/';

		$db = $container->db;

		// CHECK: Do we have a user already defined in PayMill?
		$user = $container->platform->getUser($subscription->user_id);

		$request = new Paymill\Request($apiKey);
		$clientsObject = new Paymill\Models\Request\Client();
		$clientsObject->setFilter([
			'email' => $user->email
		]);

		try
		{
			$clients = $request->getAll($clientsObject);
		}
		catch (\Paymill\Services\PaymillException $e)
		{
			$clients = [];
		}

		// ACTION: Get the client ID or create and save a new user in PayMill if necessary
		if (count($clients))
		{
			$clientRecord = array_pop($clients);
			$client = $clientRecord['id'];
		}
		else
		{
			try
			{
				$clientsObject->setEmail($user->email);
				$clientsObject->setDescription($user->name . ' [' . $user->username . ']');

				$clientRecord = $request->create($clientsObject);
				$client = $clientRecord->getId();
			}
			catch (Exception $exc)
			{
				$isValid = false;
				$params['akeebasubs_failure_reason'] = JText::_('PLG_AKPAYMENT_PAYMILL_ERROR_CLIENT') . ' – ' . $exc->getMessage();
			}

			// Log the user creation data
			$this->logIPN($data, $isValid, 'USER');

			// Fraud attempt? Do nothing more!
			if (!$isValid)
			{
				$level = $subscription->level;
				$error_url = 'index.php?option=com_akeebasubs&view=Level&slug=' . $level->slug;
				$error_url = JRoute::_($error_url, false);

				$this->container->platform->redirect($error_url, 303, $params['akeebasubs_failure_reason'], 'error');

				return false;
			}
		}

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
			$paymentsObject = new Paymill\Models\Request\Payment();
			$paymentsObject->setClient($client);
			$paymentsObject->setToken($data['token']);

			try
			{
				$creditcard = $request->create($paymentsObject);
			}
			catch (Exception $exc)
			{
				$isValid = false;
				$params['akeebasubs_failure_reason'] = JText::_('PLG_AKPAYMENT_PAYMILL_ERROR_CC') . '<br/>Tech info: <tt>' . htmlentities($exc->getMessage()) . '</tt>';
			}

			// Log the payment creation data
			$this->logIPN($data, $isValid, 'PAYMENT');

			// Fraud attempt? Do nothing more!
			if (!$isValid)
			{
				$level = $subscription->level;
				$error_url = 'index.php?option=com_akeebasubs&view=Level&slug=' . $level->slug;
				$error_url = JRoute::_($error_url, false);

				$this->container->platform->redirect($error_url, 303, $params['akeebasubs_failure_reason'], 'error');

				return false;
			}

			$subscription->processor_key = $creditcard->getId();

			// Save the payment information WITHOUT using the table (skips the plugins)
			// This prevents double payments from being recorded
			$oUpdate = (object)array(
				'akeebasubs_subscription_id' => $subscription->akeebasubs_subscription_id,
				'processor_key'              => $subscription->processor_key,
				'state'                      => 'P',
			);

			$this->container->db->updateObject('#__akeebasubs_subscriptions', $oUpdate, 'akeebasubs_subscription_id');
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

			$this->container->db->updateObject('#__akeebasubs_subscriptions', $oUpdate, 'akeebasubs_subscription_id');

			// Create the transaction
			$transactionsObject = new Paymill\Models\Request\Transaction();
			$transactionsObject->setAmount($data['amount']);
			$transactionsObject->setCurrency($data['currency']);
			$transactionsObject->setClient($client);
			$transactionsObject->setPayment($payment_id);
			$transactionsObject->setDescription($data['description']);

			try
			{
				$transaction = $request->create($transactionsObject);
			}
			catch (Exception $exc)
			{
				$isValid = false;
				$params['akeebasubs_failure_reason'] = JText::_('PLG_AKPAYMENT_PAYMILL_ERROR_TRANS') . ' – ' . $exc->getMessage();
			}

			// Log the payment creation data
			$this->logIPN($data, $isValid, 'TRANSACTION');

			if (!$isValid)
			{
				$transaction_id = $payment_id;
			}
			else
			{
				$transaction_id = $transaction->getId();
			}

			// First update the object
			$subscription->processor_key = $transaction_id;

			// Save the payment information WITHOUT using the table (skips the plugins)
			// This prevents double payments from being recorded
			$oUpdate = (object)array(
				'akeebasubs_subscription_id' => $subscription->akeebasubs_subscription_id,
				'processor_key'              => $subscription->processor_key,
			);
			$this->container->db->updateObject('#__akeebasubs_subscriptions', $oUpdate, 'akeebasubs_subscription_id');

			// Fraud attempt? Do nothing more!
			if (!$isValid)
			{
				$level = $subscription->level;
				$error_url = 'index.php?option=com_akeebasubs&view=Level&slug=' . $level->slug;
				$error_url = JRoute::_($error_url, false);

				$this->container->platform->redirect($error_url, 303, $params['akeebasubs_failure_reason'], 'error');

				return false;
			}
		}
		else
		{
			// ACTION: If no transaction is necessary, show an error
			$level = $subscription->level;
			$error_url = 'index.php?option=com_akeebasubs&view=Level&slug=' . $level->slug;
			$error_url = JRoute::_($error_url, false);

			$this->container->platform->redirect($error_url, 303, 'Cannot process the transaction twice. Wait to receive your subscription confirmation email and do not retry submitting the payment form again.', 'error');

			return false;
		}

		if ($isValid)
		{
			/** @var \Paymill\Models\Response\Transaction $transaction */
			if ($this->params->get('sandbox') == $transaction->getLiveMode())
			{
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Transaction done in wrong mode.";
			}
		}

		// Payment status
		// Check the payment_status
		/** @var \Paymill\Models\Response\Transaction $transaction */
		switch ($transaction->getStatus())
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
			default:
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
		$this->container->platform->redirect($thankyouUrl);

		return true;
	}

	/**
	 * Get the correct PayMill public key (live or sandbox)
	 *
	 * @return  string
	 */
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

	/**
	 * Get the correct PayMill private key (live or sandbox)
	 *
	 * @return  string
	 */
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

}