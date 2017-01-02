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

class plgAkpayment2conew extends AkpaymentBase
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'                  => '2conew',
			'ppKey'                   => 'PLG_AKPAYMENT_2CONEW_TITLE',
			'ppImage'                 => 'https://www.2checkout.com/upload/images/paymentlogoshorizontal.png',
			'ppRecurringCancellation' => true
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

		$slug = $level->slug;

		$rootURL = rtrim(JURI::base(), '/');
		$subpathURL = JURI::base(true);

		if (!empty($subpathURL) && ($subpathURL != '/'))
		{
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}

		$data = (object)array(
			'url'                => ($this->params->get('checkout') == 'single') ? 'https://www.2checkout.com/checkout/spurchase' : 'https://www.2checkout.com/checkout/purchase',
			'sid'                => $this->params->get('sid', ''),
			'x_receipt_link_url' => $rootURL . str_replace('&amp;', '&', JRoute::_('index.php?option=com_akeebasubs&view=Message&slug=' . $slug . '&task=thankyou&subid=' . $subscription->akeebasubs_subscription_id)),
			'params'             => $this->params,
			'name'               => $user->name,
			'email'              => $user->email,
			'currency'           => strtoupper($this->container->params->get('currency', 'EUR')),
			'recurring'          => $level->recurring ? ($subscription->recurring_amount >= 0.01 ? 2 : 1) : 0,
		);

		if ($data->recurring >= 1)
		{
			$ppDuration = $this->_toPPDuration($level->duration);
			$data->t3 = $ppDuration->unit;
			$data->p3 = $ppDuration->value;
		}

		$kuser = $subscription->user;

		if (is_null($kuser))
		{
			/** @var \Akeeba\Subscriptions\Site\Model\Users $userModel */
			$userModel = $this->container->factory->model('Users')->tmpInstance();
			$kuser = $userModel->user_id($subscription->user_id)->firstOrNew();
		}

		@ob_start();
		include dirname(__FILE__) . '/2conew/form.php';
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

		// Check if it's one of the message types supported by this plugin
		$message_type = $data['message_type'];
		$isValid = in_array($message_type, array(
			'ORDER_CREATED', 'REFUND_ISSUED', 'RECURRING_INSTALLMENT_SUCCESS', 'FRAUD_STATUS_CHANGED', 'INVOICE_STATUS_CHANGED'
		));

		if (!$isValid)
		{
			$data['akeebasubs_failure_reason'] = 'INS message type "' . $message_type . '" is not supported.';
		}

		// Check IPN data for validity (i.e. protect against fraud attempt)
		if ($isValid)
		{
			$isValid = $this->isValidIPN($data);

			if (!$isValid)
			{
				$data['akeebasubs_failure_reason'] = 'Transaction MD5 signature is invalid. Fraudulent transaction or testing mode enabled.';
			}
		}

		// Load the relevant subscription row
		if ($isValid)
		{
			$id = array_key_exists('merchant_order_id', $data) ? (int)$data['merchant_order_id'] : -1;
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
				$data['akeebasubs_failure_reason'] = 'The referenced subscription ID ("merchant_order_id" field) is invalid';
			}
		}

		/** @var Subscriptions $subscription */

		// Check that order_number has not been previously processed
		if ($isValid && !is_null($subscription))
		{
			if ($subscription->processor_key == $data['sale_id'] . '/' . $data['invoice_id'])
			{
				if (($subscription->state == 'C') && ($message_type == 'ORDER_CREATED'))
				{
					$isValid = false;
					$data['akeebasubs_failure_reason'] = "I will not process the same sale_id/invoice_id twice";
				}
			}
		}

		// Check that total is correct
		if ($isValid && !is_null($subscription))
		{
			$mc_gross = floatval($data['invoice_list_amount']);
			$gross = $subscription->gross_amount;

			if ($mc_gross > 0)
			{
				// A positive value means "payment". The prices MUST match!
				// Important: NEVER, EVER compare two floating point values for equality.
				$isValid = ($gross - $mc_gross) < 0.01;
			}
			else
			{
				$isValid = false;
			}

			if (!$isValid)
			{
				$data['akeebasubs_failure_reason'] = 'Paid amount (invoice_list_amount) does not match the subscription amount';
			}
		}

		// Log the IPN data
		$this->logIPN($data, $isValid);

		// Fraud attempt? Do nothing more!
		if (!$isValid)
		{
			return false;
		}

		// Load the subscription level and get its slug
		$recurring_installment = false;

		switch ($message_type)
		{
			case 'ORDER_CREATED':
			case 'FRAUD_STATUS_CHANGED':
			case 'INVOICE_STATUS_CHANGED':
				// Let me translate the goofy statuses sent by 2Checkout to English for ya
				switch ($data['invoice_status'])
				{
					case 'approved':
						// "Approved" means "we're about to request the money" or something like that, dunno
						$newStatus = 'C';
						break;

					case 'pending':
						// "Pending" means "accepted by bank, the money is not in your account yet"
						$newStatus = 'C';
						break;

					case 'deposited':
						// "Deposited" means "the money is yours".
						$newStatus = 'C';
						// However, if the subscription is CANCELLED then a refund has already
						// been issued, but 2Checkout sends a "deposited" status. What the hell?!
						if ($subscription->state == 'X')
						{
							$newStatus = 'X';
						}
						break;

					case 'declined':
					default:
						// "Declined" means "you ain't gonna have your money, bro"
						$newStatus = 'X';
						break;
				}
				break;

			case 'REFUND_ISSUED':
				$newStatus = 'X';
				break;

			case 'RECURRING_INSTALLMENT_SUCCESS':
				// Handle recurring payments
				$newStatus = 'C';
				$recurring_installment = true;
				break;
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
			'akeebasubs_subscription_id' => $id,
			'processor_key'              => $data['sale_id'] . '/' . $data['invoice_id'],
			'state'                      => $newStatus,
			'enabled'                    => 0
		);

		// On recurring payments also store the subscription ID
		if ($recurring_installment)
		{
			$subscr_id = $data['sale_id'];
			$params = $subscription->params;
			if (!is_array($params))
			{
				$params = json_decode($params, true);
			}
			if (is_null($params) || empty($params))
			{
				$params = array();
			}
			$params['recurring_id'] = $subscr_id;
			$updates['params'] = $params;
		}

		JLoader::import('joomla.utilities.date');
		if ($newStatus == 'C')
		{
			self::fixSubscriptionDates($subscription, $updates);
		}

		// In the case of a successful recurring payment, fetch the old subscription's data
		if ($recurring_installment && ($newStatus == 'C') && ($subscription->state == 'C'))
		{
			$this->handleRecurringSubscription($subscription, $updates);
		}
		elseif ($recurring_installment && ($newStatus != 'C'))
		{
			// Recurring payment, but payment_status is not Completed. We have
			// stop right now and not save the changes. Otherwise the status of
			// the subscription will become P or X and the recurring payment
			// code above will not run when 2Checkout sends us a new IPN with the
			// status set to Completed.
			return true;
		}

		// Save the changes
		$subscription->save($updates);

		// Run the onAKAfterPaymentCallback events
		$this->container->platform->importPlugin('akeebasubs');
		$this->container->platform->runPlugins('onAKAfterPaymentCallback', array(
			$subscription
		));

		return true;
	}

	public function onAKPaymentCancelRecurring($paymentmethod, $data)
	{
		// Check if we're supposed to handle this
		if ($paymentmethod != $this->ppName)
		{
			return false;
		}

		// No subscription id? Stop here
		if (!$data['sid'])
		{
			return false;
		}

		// No cURL? Well, that's no point on continuing...
		if (!function_exists('curl_init'))
		{
			throw new Exception('2CO payment plugin needs cURL extension in order to cancel recurring payments', 500);
		}

		// No API credentials? Let's stop here
		if (!$this->params->get('api_username') || !$this->params->get('api_password'))
		{
			throw new Exception('You need to provide API username and password in order to cancel recurring payments', 500);
		}

		require_once '2conew/lib/Twocheckout.php';

		/** @var Subscriptions $sub */
		$sub = $this->container->factory->model('Subscriptions')->tmpInstance();
		$sub->find($data['sid']);

		list($sale_id,) = explode('/', $sub->processor_key);

		Twocheckout::setCredentials($this->params->get('api_username'), $this->params->get('api_password'));

		$args = array('sale_id' => $sale_id);

		try
		{
			$result = Twocheckout_Sale::stop($args, 'array');
		}
		catch (Twocheckout_Error $e)
		{
			// Uh oh.. something bad happened. Let's log it
			$log['subid'] = $data['sid'];
			$log['sale_id'] = $sale_id;
			$log['message'] = $e->getMessage();

			$this->logData($log, false, 'CANCEL RECURRING');

			return false;
		}

		// Request was ok, but there was an error processing it
		if (strtoupper($result['response_code']) != 'OK')
		{
			$log['subid'] = $data['sid'];
			$log['sale_id'] = $sale_id;
			$log['result'] = print_r($result, true);

			$this->logData($log, false, 'CANCEL RECURRING');

			return false;
		}

		// Everything went ok, let's log it
		$log['subid'] = $data['sid'];
		$log['sale_id'] = $sale_id;
		$log['result'] = print_r($result, true);

		$this->logData($log, true, 'CANCEL RECURRING');

		return true;
	}

	protected function logData($data, $isValid, $type = 'TRANSACTION', $header = null)
	{
		$config = JFactory::getConfig();

		$logpath = $config->get('log_path');

		$logFilenameBase = $logpath . '/akpayment_' . strtolower($this->ppName) . '_ipn';

		$logFile = $logFilenameBase . '.php';
		JLoader::import('joomla.filesystem.file');

		if (!JFile::exists($logFile))
		{
			$dummy = "<?php die(); ?>\n";
			JFile::write($logFile, $dummy);
		}
		else
		{
			if (@filesize($logFile) > 1048756)
			{
				$altLog = $logFilenameBase . '-1.php';

				if (JFile::exists($altLog))
				{
					JFile::delete($altLog);
				}

				JFile::copy($logFile, $altLog);
				JFile::delete($logFile);

				$dummy = "<?php die(); ?>\n";

				JFile::write($logFile, $dummy);
			}
		}

		$logData = JFile::read($logFile);

		if ($logData === false)
		{
			$logData = '';
		}

		$logData .= "\n" . str_repeat('-', 80);
		$pluginName = strtoupper($this->ppName);

		if ($header)
		{
			$logData .= $header;
		}
		else
		{
			$logData .= $isValid ? 'VALID ' . $pluginName . ' ' . $type : 'INVALID ' . $pluginName . ' ' . $type . ' *** FRAUD ATTEMPT OR INVALID CALLBACK ***';
		}

		$logData .= "\nDate/time : " . gmdate('Y-m-d H:i:s') . " GMT\n\n";

		foreach ($data as $key => $value)
		{
			$logData .= '  ' . str_pad($key, 30, ' ') . $value . "\n";
		}

		$logData .= "\n";

		JFile::write($logFile, $logData);
	}

	/**
	 * Validates the incoming data against the md5 posted by 2Checkout to make sure this is not a
	 * fraudulent request.
	 */
	private function isValidIPN($data)
	{
		// This is the MD5 calculations in 2Checkout's INS guide
		$incoming_md5 = strtoupper($data['md5_hash']);
		$calculated_md5 = md5(
			$data['sale_id'] .
			$data['vendor_id'] .
			$data['invoice_id'] .
			$this->params->get('secret', '')
		);
		$calculated_md5 = strtoupper($calculated_md5);

		return ($calculated_md5 == $incoming_md5);
	}


	private function _toPPDuration($days)
	{
		$ret = (object)array(
			'unit'  => 'Week',
			'value' => 1
		);

		// 0-7 => return one week (sorry!)
		if ($days < 7)
		{
			return $ret;
		}

		// Translate to weeks, months and years
		$weeks = (int)($days / 7);
		$months = (int)($days / 30);
		$years = (int)($days / 365);

		// Find which one is the closest match
		$deltaW = abs($days - $weeks * 7);
		$deltaM = abs($days - $months * 30);
		$deltaY = abs($days - $years * 365);
		$minDelta = min($deltaW, $deltaM, $deltaY);

		// Counting weeks gives a better approximation
		if ($minDelta == $deltaW)
		{
			$ret->unit = 'Week';
			$ret->value = $weeks;

			// Make sure we have 1-52 weeks, otherwise go for a months or years
			if (($ret->value > 0) && ($ret->value <= 52))
			{
				return $ret;
			}
			else
			{
				$minDelta = min($deltaM, $deltaY);
			}
		}

		// Counting months gives a better approximation
		if ($minDelta == $deltaM)
		{
			$ret->unit = 'Month';
			$ret->value = $months;

			// Make sure we have a number of months which is not a multiple of 12
			if (($ret->value % 12) == 0)
			{
				$minDelta = min($deltaM, $deltaY);
			}
			else
			{
				return $ret;
			}
		}

		// If we're here, we're better off translating to years
		$ret->unit = 'Year';
		$ret->value = $years;

		if ($ret->value <= 0)
		{
			// Too short? Make it 1 (should never happen)
			$ret->value = 1;
		}

		return $ret;
	}
}