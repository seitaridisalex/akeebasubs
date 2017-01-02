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

class plgAkpaymentPaypal extends AkpaymentBase
{
	/**
	 * According to https://www.paypal-knowledge.com/infocenter/index?page=content&id=FAQ1914&expand=true&locale=en_US
	 * we are supposed to use www.paypal.com before June 30th, 2017.
	 *
	 * As of October 20th, 2016 PayPal recommends using the ipnpb.paypal.com domain name. See
	 * https://www.paypal.com/au/webapps/mpp/ipn-verification-https
	 */
	const IPNPostbackDomain = 'www.paypal.com';

	// const IPNPostbackDomain = 'ipnpb.paypal.com';

	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'  => 'paypal',
			'ppKey'   => 'PLG_AKPAYMENT_PAYPAL_TITLE',
			'ppImage' => 'https://www.paypal.com/en_US/i/bnr/horizontal_solution_PPeCheck.gif'
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

		$lastName = '';

		if (count($nameParts) > 1)
		{
			$lastName = $nameParts[1];
		}

		$slug = $level->slug;

		$rootURL    = rtrim(JUri::base(), '/');
		$subpathURL = JUri::base(true);

		if (!empty($subpathURL) && ($subpathURL != '/'))
		{
			$rootURL = substr($rootURL, 0, - 1 * strlen($subpathURL));
		}

		$data = (object) array(
			'url'       => $this->getPaymentURL(),
			'merchant'  => $this->getMerchantID(),
			'postback'  => $this->getPostbackURL(),
			'success'   => $rootURL . str_replace('&amp;', '&', JRoute::_('index.php?option=com_akeebasubs&view=Message&slug=' . $slug . '&task=thankyou&subid=' . $subscription->akeebasubs_subscription_id)),
			'cancel'    => $rootURL . str_replace('&amp;', '&', JRoute::_('index.php?option=com_akeebasubs&view=Message&slug=' . $slug . '&task=cancel&subid=' . $subscription->akeebasubs_subscription_id)),
			'currency'  => strtoupper($this->container->params->get('currency', 'EUR')),
			'firstname' => $firstName,
			'lastname'  => $lastName,
			'cmd'       => $level->recurring ? '_xclick-subscriptions' : '_xclick',
			// If there's a signup fee set 'recurring' to 2
			'recurring' => $level->recurring ? ($subscription->recurring_amount >= 0.01 ? 2 : 1) : 0
		);

		if ($data->recurring > 0)
		{
			// Recalculate duration from publish date because it might have been changed by onValidateSubscriptionLength
			$jStartDate = new JDate($subscription->publish_up);
			$jEndDate   = new JDate($subscription->publish_down);
			$duration   = floor(($jEndDate->toUnix() - $jStartDate->toUnix()) / 3600 / 24);
			$ppDuration = $this->_toPPDuration($duration);

			if ($data->recurring == 1)
			{
				$data->t3 = $ppDuration->unit;
				$data->p3 = $ppDuration->value;
			}
			elseif ($data->recurring == 2)
			{
				$data->t1 = $ppDuration->unit;
				$data->p1 = $ppDuration->value;
				$data->t3 = $ppDuration->unit;
				$data->p3 = $ppDuration->value;
				$data->a3 = $subscription->recurring_amount;
			}
		}

		$kuser = $subscription->user;

		if (is_null($kuser))
		{
			/** @var \Akeeba\Subscriptions\Site\Model\Users $userModel */
			$userModel = $this->container->factory->model('Users')->tmpInstance();
			$kuser     = $userModel->user_id($subscription->user_id)->firstOrNew();
		}

		@ob_start();
		include dirname(__FILE__) . '/paypal/form.php';
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

		$isValid = false;

		if ($this->params->get('debug', 0))
		{
			$isValid = true;
		}

		// Check IPN data for validity (i.e. protect against fraud attempt)
		try
		{
			if (!$isValid)
			{
				$isValid = $this->isValidIPN($data);
			}
		}
		catch (RuntimeException $e)
		{
			$isValid = false;
			$data['akeebasubs_ipn_failure_reason'] = $e->getMessage();
		}

		try
		{
			$this->checkIPNPostbackRequirements();
		}
		catch (RuntimeException $e)
		{
			$data['akeebasubs_ipn_warning'] = $e->getMessage();
		}

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

			$validTypes = array('workaround_to_missing_txn_type', 'web_accept', 'subscr_payment', 'recurring_payment',
				'recurring_payment_outstanding_payment');
			$isValid    = in_array($data['txn_type'], $validTypes);

			if (!$isValid)
			{
				$data['akeebasubs_failure_reason'] =
					"Transaction type " . $data['txn_type'] . " can't be processed by this payment plugin.";
			}
			else
			{
				$recurring = (!in_array($data['txn_type'], array('web_accept', 'workaround_to_missing_txn_type')));
			}
		}

		// Load the relevant subscription row
		if ($isValid)
		{
			$id           = array_key_exists('custom', $data) ? (int) $data['custom'] : - 1;
			$subscription = null;

			if ($id > 0)
			{
				/** @var Subscriptions $subscription */
				$subscription = $this->container->factory->model('Subscriptions')->tmpInstance();
				$subscription->find($id);

				if (($subscription->akeebasubs_subscription_id <= 0) || ($subscription->akeebasubs_subscription_id != $id))
				{
					$subscription = null;
					$isValid      = false;
				}
			}
			else
			{
				$isValid = false;
			}

			/** @var Subscriptions $subscription */

			if (!$isValid)
			{
				$data['akeebasubs_failure_reason'] = 'The referenced subscription ID ("custom" field) is invalid';
			}
		}

		/** @var Subscriptions $subscription */

		// Check that receiver_email / receiver_id is what the site owner has configured
		if ($isValid)
		{
			$receiver_email = $data['receiver_email'];
			$receiver_id    = $data['receiver_id'];
			$valid_id       = $this->getMerchantID();
			$isValid        =
				($receiver_email == $valid_id)
				|| (strtolower($receiver_email) == strtolower($receiver_email))
				|| ($receiver_id == $valid_id)
				|| (strtolower($receiver_id) == strtolower($receiver_id));

			if (!$isValid)
			{
				$data['akeebasubs_failure_reason'] = 'Merchant ID does not match receiver_email or receiver_id';
			}
		}

		// Check that mc_gross is correct
		$isPartialRefund = false;

		if ($isValid)
		{
			$mc_gross = floatval($data['mc_gross']);

			if ($recurring && ($subscription->recurring_amount >= 0.01) && $subscription->state != 'N')
			{
				$gross = $subscription->recurring_amount;
			}
			else
			{
				$gross = $subscription->gross_amount;
			}

			if ($mc_gross > 0)
			{
				// A positive value means "payment". The prices MUST match!
				// Important: NEVER, EVER compare two floating point values for equality.
				$isValid = ($gross - $mc_gross) < 0.01;

				if (!$isValid)
				{
					$mc_fee  = floatval($data['mc_fee']);
					$isValid = ($gross - $mc_gross - $mc_fee) < 0.01;
				}
			}
			else
			{
				$temp_mc_gross   = - 1 * $mc_gross;
				$isPartialRefund = ($gross - $temp_mc_gross) > 0.01;
			}

			if (!$isValid)
			{
				$data['akeebasubs_failure_reason'] = 'Paid amount does not match the subscription amount';
			}
		}

		// Check that txn_id has not been previously processed
		if ($isValid && !is_null($subscription) && !$isPartialRefund)
		{
			if ($subscription->processor_key == $data['txn_id'])
			{
				if ($subscription->state == 'C')
				{
					if (!in_array(strtolower($data['payment_status']), array('refunded', 'reversed',
						'canceled_reversal'))
					)
					{
						$isValid                           = false;
						$data['akeebasubs_failure_reason'] = "I will not process the same txn_id twice";
					}
				}
				elseif ($subscription->state == 'X')
				{
					if (strtolower($data['payment_status']) != 'canceled_reversal')
					{
						$isValid                           = false;
						$data['akeebasubs_failure_reason'] = "I will not process the same txn_id twice";
					}
				}
			}
		}

		// Check that mc_currency is correct
		if ($isValid && !is_null($subscription))
		{
			$mc_currency = strtoupper($data['mc_currency']);
			$currency    = strtoupper($this->container->params->get('currency', 'EUR'));

			if ($mc_currency != $currency)
			{
				$isValid                           = false;
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

		// On recurring payments also store the subscription ID
		if (array_key_exists('subscr_id', $data))
		{
			$subscr_id              = $data['subscr_id'];
			$params                 = $subscription->params;
			$params['recurring_id'] = $subscr_id;
			$updates['params']      = $params;
		}

		JLoader::import('joomla.utilities.date');

		if ($newStatus == 'C')
		{
			self::fixSubscriptionDates($subscription, $updates);
		}

		// In the case of a successful recurring payment, fetch the old subscription's data
		if ($recurring && ($newStatus == 'C') && ($subscription->state == 'C'))
		{
			$this->handleRecurringSubscription($subscription, $updates);
		}
		elseif ($recurring && ($newStatus != 'C'))
		{
			// Recurring payment, but payment_status is not Completed. We have
			// stop right now and not save the changes. Otherwise the status of
			// the subscription will become P or X and the recurring payment
			// code above will not run when PayPal sends us a new IPN with the
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

	/**
	 * Gets the form action URL for the payment
	 */
	private function getPaymentURL()
	{
		$sandbox = $this->params->get('sandbox', 0);

		if ($sandbox)
		{
			return 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		}
		else
		{
			return 'https://www.paypal.com/cgi-bin/webscr';
		}
	}

	/**
	 * Gets the PayPal Merchant ID (usually the email address)
	 */
	private function getMerchantID()
	{
		$sandbox = $this->params->get('sandbox', 0);

		if ($sandbox)
		{
			return $this->params->get('sandbox_merchant', '');
		}
		else
		{
			return $this->params->get('merchant', '');
		}
	}

	/**
	 * Creates the callback URL based on the plugins configuration.
	 */
	private function getPostbackURL()
	{

		$url = JURI::base() . 'index.php?option=com_akeebasubs&view=Callback&paymentmethod=paypal';

		$configurationValue = $this->params->get('protocol', 'keep');
		$pattern            = '/https?:\/\//';

		if ($configurationValue == 'secure')
		{
			$url = preg_replace($pattern, "https://", $url);
		}

		if ($configurationValue == 'insecure')
		{
			$url = preg_replace($pattern, "http://", $url);
		}

		return $url;
	}

	/**
	 * Validates the incoming data against PayPal's IPN to make sure this is not a fraudulent request.
	 */
	private function isValidIPN(&$data)
	{
		$sandbox  = $this->params->get('sandbox', 0);
		$hostname = $sandbox ? 'ipnpb.sandbox.paypal.com' : self::IPNPostbackDomain;

		$url = 'https://' . $hostname;

		/**
		 * In some PayPal documentation I've seen that they append /cgi-bin/webscr but I've been simply posting to the
		 * root domain for years without a problem. WTF?
		 */
		// $url = 'https://' . $hostname . '/cgi-bin/webscr';

		$newData = array(
			'cmd' => '_notify-validate'
		);
		$newData = array_merge($newData, $data);

		$options = array(
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_VERBOSE        => false,
			CURLOPT_HEADER         => false,
			CURLINFO_HEADER_OUT    => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CAINFO         => JPATH_LIBRARIES . '/fof30/Download/Adapter/cacert.pem',
			CURLOPT_HTTPHEADER     => [
				'User-Agent: AkeebaSubscriptions',
				'Connection: Close'
			],
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => $newData,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_FORBID_REUSE   => true,
			// Force the use of TLS (therefore SSLv3 is not used, mitigating POODLE; see https://github.com/paypal/merchant-sdk-php)
			CURLOPT_SSL_CIPHER_LIST => 'TLSv1',
			// This forces the use of TLS 1.x
			CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1,
		);

		$ch = curl_init($url);
		curl_setopt_array($ch, $options);
		@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

		$response     = curl_exec($ch);
		$errNo        = curl_errno($ch);
		$error        = curl_error($ch);
		$lastHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		if (($errNo > 0) && !empty($error))
		{
			throw new RuntimeException("Could not open connection to $url, cURL error $errNo: $error");
		}

		if ($lastHttpCode >= 400)
		{
			throw new RuntimeException("Invalid HTTP status $lastHttpCode verifying PayPal's IPN");
		}

		if (stristr($response, "INVALID"))
		{
			throw new RuntimeException('PayPal claims the IPN data is INVALID – Possible fraud!');
		}

		if (stristr($response, "VERIFIED"))
		{
			return true;
		}

		throw new RuntimeException("Unknown PayPal response. HTTP $lastHttpCode. Message: $response");
	}

	/**
	 * Checks if the server meets the minimum PayPal IPN postback requirements. If not a RuntimeException is thrown.
	 *
	 * @return  void
	 *
	 * @throws  RuntimeException
	 */
	protected function checkIPNPostbackRequirements()
	{
		// TLS 1.2 is only supported in OpenSSL 1.0.1c and later AND cURL 7.34.0 and later running on PHP 5.5.19+ or
		// PHP 5.6.3+. If these conditions are met we can use PayPal's minimum requirement of TLS 1.2 which is mandatory
		// since June 2016.
		$curlVersionInfo   = curl_version();
		$curlVersion       = $curlVersionInfo['version'];
		$openSSLVersionRaw = $curlVersionInfo['ssl_version'];
		// OpenSSL version typically reported as "OpenSSL/1.0.1e", I need to convert it to 1.0.1.5
		$parts             = explode('/', $openSSLVersionRaw, 2);
		$openSSLVersionRaw = (count($parts) > 1) ? $parts[1] : $openSSLVersionRaw;
		$openSSLVersion    = substr($openSSLVersionRaw, 0, -1) . '.' . (ord(substr($openSSLVersionRaw, -1)) - 96);
		// PHP version required for TLS 1.2 is 5.5.19+ or 5.6.3+
		$minPHPVersion = version_compare(PHP_VERSION, '5.6.0', 'ge') ? '5.6.3' : '5.5.19';

		if (
			!version_compare($curlVersion, '7.34.0', 'ge') ||
			!version_compare($openSSLVersion, '1.0.1.3', 'ge') ||
			!version_compare(PHP_VERSION, $minPHPVersion, 'ge')
		)
		{
			$phpVersion = PHP_VERSION;

			throw new RuntimeException("WARNING! PayPal demands that connections be made with TLS 1.2. This requires PHP $minPHPVersion+ (you have $phpVersion), libcurl 7.34.0+ (you have $curlVersion) and OpenSSL 1.0.1c+ (you have $openSSLVersionRaw) on your server's PHP. Please upgrade these requirements to meet the stated minimum or the PayPal integration will cease working.");
		}
	}


	private function _toPPDuration($days)
	{
		$ret = (object) array(
			'unit'  => 'D',
			'value' => $days
		);

		// 0-90 => return days
		if ($days < 90)
		{
			return $ret;
		}

		// Translate to weeks, months and years
		$weeks  = (int) ($days / 7);
		$months = (int) ($days / 30);
		$years  = (int) ($days / 365);

		// Find which one is the closest match
		$deltaW   = abs($days - $weeks * 7);
		$deltaM   = abs($days - $months * 30);
		$deltaY   = abs($days - $years * 365);
		$minDelta = min($deltaW, $deltaM, $deltaY);

		// Counting weeks gives a better approximation
		if ($minDelta == $deltaW)
		{
			$ret->unit  = 'W';
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
			$ret->unit  = 'M';
			$ret->value = $months;

			// Make sure we have 1-24 month, otherwise go for years
			if (($ret->value > 0) && ($ret->value <= 24))
			{
				return $ret;
			}
			else
			{
				$minDelta = min($deltaM, $deltaY);
			}
		}

		// If we're here, we're better off translating to years
		$ret->unit  = 'Y';
		$ret->value = $years;

		if ($ret->value < 0)
		{
			// Too short? Make it 1 (should never happen)
			$ret->value = 1;
		}
		elseif ($ret->value > 5)
		{
			// One major pitfall. You can't have renewal periods over 5 years.
			$ret->value = 5;
		}

		return $ret;
	}

	public function onAKPaymentCancelRecurring($paymentmethod, $data)
	{
		if ($paymentmethod != $this->ppName)
		{
			return false;
		}

		$app      = JFactory::getApplication();
		$merchant = $this->getMerchantID();
		$sandbox  = $this->params->get('sandbox');
		/** @var Subscriptions $subscription */
		$subscription = $this->container->factory->model('Subscriptions');
		$noLoad       = false;

		try
		{
			$subscription->findOrFail((int) $data['sid']);
		}
		catch (\Exception $e)
		{
			$noLoad = true;
		}

		if (!$noLoad && !empty($subscription->params['recurring_id']))
		{
			$url = 'https://www.' . ($sandbox ? 'sandbox.' : '') . 'paypal.com/cgi-bin/webscr?cmd=_profile-recurring-payments'
			       . '&encrypted_profile_id=' . $subscription->params['recurring_id'];

			$url = 'https://www.' . ($sandbox ? 'sandbox.' : '') . 'paypal.com/signin/?returnUri=' . urlencode($url);
			$app->redirect($url);
		}
		elseif ($merchant)
		{
			$url = 'https://www.' . ($sandbox ? 'sandbox.' : '') . 'paypal.com/cgi-bin/webscr?cmd=_subscr-find'
			       . '&alias=' . $merchant;
			$app->redirect($url);
		}
		else
		{
			$app->enqueueMessage('Read PayPal FAQ '
			                     . '<a href="https://www.paypal.com/us/webapps/helpcenter/helphub/article/?articleID=FAQ2327" target="_blank" rel="nofollow">'
			                     . 'how to cancel a recurring payment profile'
			                     . '</a>');
		}

		return true;
	}
}
