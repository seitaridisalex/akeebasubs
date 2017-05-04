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
use FOF30\Date\Date;

class plgAkpaymentPaypalpaymentspro extends AkpaymentBase
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'  => 'paypalpaymentspro',
			'ppKey'   => 'PLG_AKPAYMENT_PAYPALPAYMENTSPRO_TITLE',
			'ppImage' => rtrim(JURI::base(), '/') . '/media/com_akeebasubs/images/frontend/paypaldirectcc.png'
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

		$nameParts = explode(' ', trim($user->name), 2);
		$firstName = $nameParts[0];

		if (count($nameParts) > 1)
		{
			$lastName = $nameParts[1];
		}
		else
		{
			$lastName = '';
		}

		$kuser = $subscription->user;

		if (is_null($kuser))
		{
			/** @var \Akeeba\Subscriptions\Site\Model\Users $userModel */
			$userModel = $this->container->factory->model('Users')->tmpInstance();
			$kuser = $userModel->user_id($subscription->user_id)->firstOrNew();
		}

		$callbackUrl = JURI::base() . 'index.php?option=com_akeebasubs&view=Callback&paymentmethod=paypalpaymentspro';
		$data = (object)array(
			'URL'           => $callbackUrl . '&mode=init',
			'NOTIFYURL'     => $callbackUrl,
			'VERSION'       => '85.0',
			'PAYMENTACTION' => 'Sale',
			'IPADDRESS'     => $_SERVER['REMOTE_ADDR'],
			'FIRSTNAME'     => $firstName,
			'LASTNAME'      => $lastName,
			'STREET'        => trim($kuser->address1),
			'STREET2'       => trim($kuser->address2),
			'CITY'          => trim($kuser->city),
			'STATE'         => trim($kuser->state),
			'COUNTRYCODE'   => strtoupper(trim($kuser->country)),
			'ZIP'           => trim($kuser->zip),
			'AMT'           => sprintf('%.2f', $subscription->gross_amount),
			'ITEMAMT'       => sprintf('%.2f', $subscription->net_amount),
			'TAXAMT'        => sprintf('%.2f', $subscription->tax_amount),
			'CURRENCYCODE'  => strtoupper($this->container->params->get('currency', 'EUR')),
			'DESC'          => $level->title . ' - [' . $user->username . ']'
		);

		if ($level->recurring)
		{
			$data->METHOD = 'CreateRecurringPaymentsProfile';
			$data->PROFILEREFERENCE = $subscription->akeebasubs_subscription_id;
			$data->BILLINGPERIOD = 'Day';
			$data->BILLINGFREQUENCY = $level->duration;
		}
		else
		{
			$data->METHOD = 'DoDirectPayment';
			$data->INVNUM = $subscription->akeebasubs_subscription_id;
		}

		@ob_start();
		include dirname(__FILE__) . '/paypalpaymentspro/form.php';
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
		// Check if we're supposed to handle this
		if ($paymentmethod != $this->ppName)
		{
			return false;
		}

		if ($data['mode'] == 'init')
		{
			return $this->formCallback($data);
		}
		else
		{
			return $this->IPNCallback($data);
		}
	}

	private function formCallback($data)
	{
		JLoader::import('joomla.utilities.date');

		$isRecurring = ($data['METHOD'] == 'CreateRecurringPaymentsProfile');
		$jNow = new Date();
		$responseData = array();

		// Load the relevant subscription row
		$isValid = true;
		if ($isValid)
		{
			$id = $isRecurring ? (int)$data['PROFILEREFERENCE'] : (int)$data['INVNUM'];
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
				$responseData['akeebasubs_failure_reason'] = 'The referenced subscription ID is invalid';
			}
		}

		/** @var Subscriptions $subscription */

		// Call paypal to check the payment
		if ($isValid)
		{
			// Build the payment request
			$requestData = array();

			foreach ($data as $key => $val)
			{
				if ($key == 'option'
					|| $key == 'view'
					|| $key == 'paymentmethod'
				)
				{
					continue;
				}

				$requestData[$key] = trim($val);

				if ($key == 'CVV2')
				{
					break;
				}
			}

			if ($isRecurring)
			{
				$requestData['PROFILESTARTDATE'] = $jNow->toISO8601();
			}

			$requestQuery = http_build_query($requestData);

			$http_header = array(
				'X-PAYPAL-SECURITY-USERID'    => $this->getMerchantUsername(),
				'X-PAYPAL-SECURITY-PASSWORD'  => $this->getMerchantPassword(),
				'X-PAYPAL-SECURITY-SIGNATURE' => $this->getMerchantSignature()
			);

			$curlOptions = array(
				CURLOPT_HTTPHEADER     => $http_header,
				CURLOPT_URL            => $this->getPaymentURL(),
				CURLOPT_VERBOSE        => 1,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_POST           => 1,
				CURLOPT_POSTFIELDS     => $requestQuery
			);

			$ch = curl_init();
			curl_setopt_array($ch, $curlOptions);

			$responseQuery = curl_exec($ch); //make the request

			if (curl_errno($ch))
			{
				$this->_errors = curl_error($ch);
				curl_close($ch);

				return false;
			}
			else
			{
				curl_close($ch);
			}

			// Payment Response
			parse_str($responseQuery, $responseData);
			if (!preg_match('/^Success/', $responseData['ACK']))
			{
				$responseData['akeebasubs_failure_reason'] = $responseData['L_LONGMESSAGE0'];
				$isValid = false;
			}
			else if ($isRecurring)
			{
				// If recurring payment do another request to paypal, to receive
				// the details (the amount and transaction id) of the payment.
				$recDetailsRequestData = array(
					'METHOD'    => 'GetRecurringPaymentsProfileDetails',
					'USER'      => $this->getMerchantUsername(),
					'PWD'       => $this->getMerchantPassword(),
					'SIGNATURE' => $this->getMerchantSignature(),
					'VERSION'   => '85.0',
					'PROFILEID' => $responseData['PROFILEID']
				);

				$recDetailsRequestQuery = http_build_query($recDetailsRequestData);

				$recDetailsRequestContext = stream_context_create(array(
					'http' => array(
						'method'  => 'POST',
						'header'  => "Connection: close\r\n" .
							"Content-Length: " . strlen($recDetailsRequestQuery) . "\r\n",
						'content' => $recDetailsRequestQuery
					)
				));

				$recDetailsResponseQuery = file_get_contents(
					$this->getPaymentURL(),
					false,
					$recDetailsRequestContext);

				$recDetailsResponseData = array();
				parse_str($recDetailsResponseQuery, $recDetailsResponseData);
				$responseData = $recDetailsResponseData;

				if (!preg_match('/^Success/', $responseData['ACK']))
				{
					$responseData['akeebasubs_failure_reason'] = $responseData['L_LONGMESSAGE0'];
					$isValid = false;
				}
			}
		}

		// Check that TRANSACTIONID has not been previously processed
		$transactionId = $isRecurring ? $responseData['CORRELATIONID'] : $responseData['TRANSACTIONID'];

		if ($isValid)
		{
			if ($subscription->processor_key == $transactionId)
			{
				$isValid = false;
				$responseData['akeebasubs_failure_reason'] = "I will not process the same TRANSACTIONID/CORRELATIONID " . $responseData['TRANSACTIONID'] . " twice";
			}
		}

		// Check that CURRENCYCODE is correct
		if ($isValid)
		{
			$currency = strtoupper($this->container->params->get('currency', 'EUR'));

			if ($currency != $responseData['CURRENCYCODE'])
			{
				$isValid = false;
				$responseData['akeebasubs_failure_reason'] = "The currency code doesn't match (expected: " . $currency . ", received: " . $responseData['CURRENCYCODE'] . ")";
			}
		}

		// Check that amount is correct
		$isPartialRefund = false;

		if ($isValid && !is_null($subscription))
		{
			$mc_gross = floatval($responseData['AMT']);
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
				$responseData['akeebasubs_failure_reason'] = 'Paid amount does not match the subscription amount';
			}
		}

		if (!$isValid)
		{
			// Mark the payment as failed
			$updates = array(
				'akeebasubs_subscription_id' => $id,
				'processor_key'              => $transactionId,
				'state'                      => 'X',
				'enabled'                    => 0
			);

			$subscription->save($updates);

			// Redirect to the subscription form and show the error message
			$level = $subscription->level;

			$error_url = 'index.php?option=com_akeebasubs&view=level&slug=' . $level->slug;
			$error_url = JRoute::_($error_url, false);
			$this->container->platform->redirect($error_url, 303, $responseData['akeebasubs_failure_reason'], 'error');

			return false;
		}

		// Redirect the user to the "thank you" page
		$level = $subscription->level;
		$thankyouUrl = JRoute::_('index.php?option=com_akeebasubs&view=Message&slug=' . $level->slug . '&task=thankyou&subid=' . $subscription->akeebasubs_subscription_id, false);
		$this->container->platform->redirect($thankyouUrl);

		return true;
	}

	private function IPNCallback($data)
	{
		JLoader::import('joomla.utilities.date');

		// Check IPN data for validity (i.e. protect against fraud attempt)
		$isValid = $this->isValidIPN($data);

		if (!$isValid)
		{
			$data['akeebasubs_failure_reason'] = 'PayPal reports transaction as invalid';
		}

		// Check txn_type; we only accept web_accept transactions with this plugin
		$recurring = false;

		if ($isValid)
		{
			// This is required to process some IPNs, such as Reversed and Canceled_Reversal
			if (!array_key_exists('txn_type', $data))
			{
				$data['txn_type'] = 'workaround_to_missing_txn_type';
			}

			$validTypes = array('workaround_to_missing_txn_type', 'web_accept', 'recurring_payment', 'subscr_payment', 'express_checkout', 'pro_api');
			$isValid = in_array($data['txn_type'], $validTypes);

			if (!$isValid)
			{
				$data['akeebasubs_failure_reason'] = "Transaction type " . $data['txn_type'] . " can't be processed by this payment plugin.";
			}
			else
			{
				$recurring = ($data['txn_type'] == 'recurring_payment');
			}
		}

		// Load the relevant subscription row
		if ($isValid)
		{
			$id = $recurring ? $data['rp_invoice_id'] : $data['invoice'];
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
				$data['akeebasubs_failure_reason'] = 'The referenced subscription ID ("custom" field) is invalid';
			}
		}

		/** @var Subscriptions $subscription */

		// Check that mc_gross is correct
		$isPartialRefund = false;

		if ($isValid)
		{
			$mc_gross = floatval($data['mc_gross']);
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

		// Check that txn_id has not been previously processed
		if ($isValid && !$isPartialRefund)
		{
			if ($subscription->processor_key == $data['txn_id'])
			{
				if ($subscription->state == 'C')
				{
					$isValid = false;
					$data['akeebasubs_failure_reason'] = "I will not process the same txn_id twice";
				}
			}
		}

		// Check that mc_currency is correct
		if ($isValid)
		{
			$mc_currency = strtoupper($data['mc_currency']);
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
		switch ($data['payment_status'])
		{
			case 'Canceled_Reversal':
			case 'Completed':
				$newStatus = 'C';
				break;

			case 'Created':
			case 'Pending':
			case 'Processed':
				$newStatus = 'P';
				break;

			case 'Denied':
			case 'Expired':
			case 'Failed':
			case 'Refunded':
			case 'Reversed':
			case 'Voided':
			default:
				// Partial refunds can only by issued by the merchant. In that case,
				// we don't want the subscription to be cancelled. We have to let the
				// merchant adjust its parameters if needed.
				if ($isPartialRefund)
				{
					$newStatus = 'C';
				}
				else
				{
					$newStatus = 'X';
				}
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

		// In the case of a successful recurring payment, fetch the old subscription's data
		if ($recurring && ($newStatus == 'C') && ($subscription->state == 'C'))
		{
			$jNow = new Date();
			$jStart = new Date($subscription->publish_up);
			$jEnd = new Date($subscription->publish_down);
			$now = $jNow->toUnix();
			$start = $jStart->toUnix();
			$end = $jEnd->toUnix();

			// Create a new record for the old subscription
			$oldData = $subscription->getData();
			$oldData['akeebasubs_subscription_id'] = 0;
			$oldData['publish_down'] = $jNow->toSql();
			$oldData['enabled'] = 0;
			$oldData['contact_flag'] = 3;
			$oldData['notes'] = "Automatically renewed subscription on " . $jNow->toSql();

			// Calculate new start/end time for the subscription
			$allSubs = $subscription->tmpInstance()
				->paystate('C')
				->level($subscription->akeebasubs_level_id)
				->user_id($subscription->user_id)
				->get(true);

			$max_expire = 0;

			if ($allSubs->count())
			{
				foreach ($allSubs as $aSub)
				{
					$jExpire = new Date($aSub->publish_down);
					$expire = $jExpire->toUnix();

					if ($expire > $max_expire)
					{
						$max_expire = $expire;
					}
				}
			}

			$duration = $end - $start;
			$start = max($now, $max_expire);
			$end = $start + $duration;
			$jStart = new Date($start);
			$jEnd = new Date($end);

			$updates['publish_up'] = $jStart->toSql();
			$updates['publish_down'] = $jEnd->toSql();

			// Save the record for the old subscription
			$table = $subscription->tmpInstance();
			$table->save($oldData);
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

	/**
	 * Validates the incoming data against PayPal's IPN to make sure this is not a
	 * fraudelent request.
	 */
	private function isValidIPN($data)
	{
		$sandbox = $this->params->get('sandbox', 0);
		$hostname = $sandbox ? 'www.sandbox.paypal.com' : 'www.paypal.com';

		$url = 'ssl://' . $hostname;
		$port = 443;

		$req = 'cmd=_notify-validate';
		foreach ($data as $key => $value)
		{
			$value = urlencode($value);
			$req .= "&$key=$value";
		}
		$header = '';
		$header .= "POST /cgi-bin/webscr HTTP/1.1\r\n";
		$header .= "Host: $hostname:$port\r\n";
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header .= "Content-Length: " . strlen($req) . "\r\n";
		$header .= "User-Agent: AkeebaSubscriptions\r\n";
		$header .= "Connection: Close\r\n\r\n";


		$fp = fsockopen($url, $port, $errno, $errstr, 30);

		if (!$fp)
		{
			// HTTP ERROR
			return false;
		}
		else
		{
			fputs($fp, $header . $req);
			while (!feof($fp))
			{
				$res = fgets($fp, 1024);
				if (stristr($res, "VERIFIED"))
				{
					return true;
				}
				else if (stristr($res, "INVALID"))
				{
					return false;
				}
			}
			fclose($fp);
		}
	}

	private function getPaymentURL()
	{
		$sandbox = $this->params->get('sandbox', 0);
		if ($sandbox)
		{
			return 'https://api-3t.sandbox.paypal.com/nvp';
		}
		else
		{
			return 'https://api-3t.paypal.com/nvp';
		}
	}

	private function getMerchantUsername()
	{
		$sandbox = $this->params->get('sandbox', 0);
		if ($sandbox)
		{
			return trim($this->params->get('sb_apiuser', ''));
		}
		else
		{
			return trim($this->params->get('apiuser', ''));
		}
	}

	private function getMerchantPassword()
	{
		$sandbox = $this->params->get('sandbox', 0);
		if ($sandbox)
		{
			return trim($this->params->get('sb_apipw', ''));
		}
		else
		{
			return trim($this->params->get('apipw', ''));
		}
	}

	private function getMerchantSignature()
	{
		$sandbox = $this->params->get('sandbox', 0);
		if ($sandbox)
		{
			return trim($this->params->get('sb_apisig', ''));
		}
		else
		{
			return trim($this->params->get('apisig', ''));
		}
	}

	public function selectExpirationDate()
	{
		$year = (int) gmdate('Y');

		$options = array();
		$options[] = JHTML::_('select.option', 0, '--');
		for ($i = 0; $i <= 10; $i++)
		{
			$y = sprintf('%04u', $i + $year);
			for ($j = 1; $j <= 12; $j++)
			{
				$m = sprintf('%02u', $j);
				$options[] = JHTML::_('select.option', ($m . $y), ($m . '/' . $y));
			}
		}

		return JHTML::_('select.genericlist', $options, 'EXPDATE', 'class="input-medium"', 'value', 'text', '', 'EXPDATE');
	}
}