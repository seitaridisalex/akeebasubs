<?php
/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

JLoader::import('joomla.plugin.plugin');

use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use FOF30\Container\Container;

/**
 * plgSystemAspaypalcollation plugin. Collates PayPal sales with the information in Akeeba Subscriptions. Useful if you
 * don't get notifications for some payments or the IPN postback is sometimes down and you cannot process transactions.
 *
 * Example call:
 * http://localhost/index.php?option=com_akeebasubs&view=cron&command=paypalcollation&secret=yoursecret
 */
class plgSystemAspaypalcollation extends JPlugin
{
	/**
	 * Should this plugin be allowed to run? True if FOF can be loaded and the Akeeba Subscriptions component is enabled
	 *
	 * @var  bool
	 */
	private $enabled = true;

	/**
	 * @var   boolean  Should I enable debug mode? DO NOT USE IN PRODUCTION.
	 */
	private static $debug = false;

	/**
	 * @var   string  The PayPal API username
	 */
	public static $username = '';

	/**
	 * @var   string  The PayPal API password
	 */
	public static $password = '';

	/**
	 * @var   string  The PayPal API Signature
	 */
	public static $signature = '';

	/**
	 * How long into the past should I be looking for paid subscriptions? This is currently set to 3600 seconds (1
	 * hour) which should be more than ample time, considering that we'll be running this every 15 minutes or
	 * thereabout.
	 *
	 * @const  int
	 */
	const TIME_PERIOD = 3600;

	/**
	 * Public constructor. Overridden to load the language strings.
	 */
	public function __construct(&$subject, $config = array())
	{
		if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
		{
			$this->enabled = false;
		}

		// Do not run if Akeeba Subscriptions is not enabled
		JLoader::import('joomla.application.component.helper');

		if (!JComponentHelper::isEnabled('com_akeebasubs'))
		{
			$this->enabled = false;
		}

		if (!is_object($config['params']))
		{
			JLoader::import('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}

		parent::__construct($subject, $config);

		// Timezone fix; avoids errors printed out by PHP 5.3.3+ (thanks Yannick!)
		if (function_exists('date_default_timezone_get') && function_exists('date_default_timezone_set'))
		{
			if (function_exists('error_reporting'))
			{
				$oldLevel = error_reporting(0);
			}
			$serverTimezone = @date_default_timezone_get();
			if (empty($serverTimezone) || !is_string($serverTimezone))
			{
				$serverTimezone = 'UTC';
			}
			if (function_exists('error_reporting'))
			{
				error_reporting($oldLevel);
			}
			@date_default_timezone_set($serverTimezone);
		}

		// Get the parameters
		self::$username  = $this->params->get('username', '');
		self::$password  = $this->params->get('password', '');
		self::$signature = $this->params->get('signature', '');
	}

	/**
	 * Handles the CRON task of
	 *
	 * @param       $task
	 * @param array $options
	 */
	public function onAkeebasubsCronTask($task, $options = array())
	{
		if (!$this->enabled)
		{
			return;
		}

		if ($task != 'paypalcollation')
		{
			return;
		}

		if (empty(self::$username) || empty(self::$password) || empty(self::$signature))
		{
			return;
		}

		// Load the language files
		$jlang = JFactory::getLanguage();
		$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, null, true);

		$jlang->load('com_akeebasubs', JPATH_SITE, 'en-GB', true);
		$jlang->load('com_akeebasubs', JPATH_SITE, $jlang->getDefault(), true);
		$jlang->load('com_akeebasubs', JPATH_SITE, null, true);

		$jlang->load('plg_system_as2cocollation', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_system_as2cocollation', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_system_as2cocollation', JPATH_ADMINISTRATOR, null, true);

		// Load a list of latest PayPal sales
		$allSales = $this->getLatestSales(self::TIME_PERIOD);

		if (!is_array($allSales))
		{
			return;
		}

		// Loop through each sale and make a list of which ones do not correspond to an active subscription
		$db             = JFactory::getDbo();
		$needProcessing = array();
		$protoQuery     = $db->getQuery(true)
		                     ->select('COUNT(*)')
		                     ->from($db->qn('#__akeebasubs_subscriptions'))
		                     ->where($db->qn('processor') . '=' . $db->q('paypal'))
		                     ->where($db->qn('state') . '=' . $db->q('C'));

		foreach ($allSales as $sale)
		{
			$transactionId    = $sale['L_TRANSACTIONID'];
			$query = clone $protoQuery;
			$query->where($db->qn('processor_key') . ' = ' . $db->q($transactionId));
			$db->setQuery($query);
			$countRows = $db->loadResult();

			if ($countRows < 1)
			{
				$needProcessing[] = $transactionId;
			}
		}

		// If there are no pending sales I don't have to do anything.
		if (empty($needProcessing))
		{
			return;
		}

		$thresholdTime = time() + 0.7 * $options['time_limit'];

		// Loop all pending sales, figure out which subscription they are referring to and activate the subscription
		foreach ($needProcessing as $transactionId)
		{
			if (time() > $thresholdTime)
			{
				return;
			}

			// Get the subscription ID from PayPal
			$subId = $this->getSubscriptionIdFromTransactionId($transactionId);

			if (empty($subId))
			{
				continue;
			}

			// Load the subscription
			/** @var Subscriptions $sub */
			$sub = Container::getInstance('com_akeebasubs', [], 'site')->factory->model('Subscriptions')->tmpInstance();

			// Make sure we can find the subscription
			try
			{
				$sub->findOrFail($subId);
			}
			catch (Exception $e)
			{
				continue;
			}

			// Is it REALLY the right sub?
			if ($sub->akeebasubs_subscription_id != $subId)
			{
				continue;
			}

			// If the price paid doesn't match we don't accept the transaction
			if (abs($sub->gross_amount - $allSales[$transactionId]['L_AMT']) >= 0.01)
			{
				continue;
			}

			// Update the subscription
			try
			{
				$updates = array(
					'state'         => 'C',
					'processor_key' => $transactionId,
				);
				$sub->save($updates);
			}
			catch (Exception $e)
			{
				// Whoopsie!
			}
		}
	}

	/**
	 * Gets all PayPal sales since a specific time period
	 *
	 * @param   int  $timePeriod  Time period in the past (in seconds) to search. Default: 86400 (24 hours)
	 *
	 * @see     https://developer.paypal.com/docs/classic/api/merchant/TransactionSearch_API_Operation_NVP/
	 *
	 * @return  array  You need the payments array
	 */
	protected function getLatestSales($timePeriod = 86400)
	{
		// Used for debug mode
		$jconfig  = JFactory::getConfig();
		$tmp      = $jconfig->get('tmp_path', sys_get_temp_dir());
		$fileName = $tmp . '/test_paypal_latest_sales.txt';

		// Debug mode
		if (self::$debug)
		{
			if (file_exists($fileName))
			{
				$array_resp = json_decode(file_get_contents($fileName), true);

				return $array_resp;
			}
		}

		JLoader::import('joomla.utilities.date');
		$now = new JDate();

		$fromDate = new JDate($now->toUnix() - $timePeriod);

		$targetURL = new JUri('https://api-3t.paypal.com/nvp');
		$targetURL->setVar('USER', self::$username);
		$targetURL->setVar('PWD', self::$password);
		$targetURL->setVar('SIGNATURE', self::$signature);
		$targetURL->setVar('METHOD', 'TransactionSearch');
		$targetURL->setVar('VERSION', '106.0');
		$targetURL->setVar('TRANSACTIONCLASS', 'Received');
		$targetURL->setVar('STATUS', 'Success');
		$targetURL->setVar('STARTDATE', $fromDate->format('Y-m-d\TH:i:s\Z', false, false));

		// Set up the request through cURL
		$ch = curl_init($targetURL->toString());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "AkeebaSubscriptions/5.0");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_CAINFO, JPATH_LIBRARIES . '/fof30/Download/Adapter/cacert.pem');
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
		// Force the use of TLS (therefore SSLv3 is not used, mitigating POODLE; see https://github.com/paypal/merchant-sdk-php)
		curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');
		// This forces the use of TLS 1.x
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);

		$rawResponse = curl_exec($ch);
		curl_close($ch);

		// We get a long query string as a response. We need to decode it to a hash array
		$fakeURI    = new JUri('http://localhost/index.php?' . $rawResponse);
		$array_resp = [];

		// Loop all transactions (the API returns up to 100 transactions with indices 0 to 99 respectively)
		// See https://developer.paypal.com/docs/classic/api/merchant/TransactionSearch_API_Operation_NVP/
		for ($transactionId = 0; $transactionId < 100; $transactionId++)
		{
			if (is_null(($fakeURI->getVar('L_TIMESTAMP' . $transactionId, null))))
			{
				break;
			}

			$transaction = [
				'L_TIMESTAMP'     => $fakeURI->getVar('L_TIMESTAMP' . $transactionId, null),
				'L_TIMEZONE'      => $fakeURI->getVar('L_TIMEZONE' . $transactionId, null),
				'L_TYPE'          => $fakeURI->getVar('L_TYPE' . $transactionId, null),
				'L_EMAIL'         => $fakeURI->getVar('L_EMAIL' . $transactionId, null),
				'L_NAME'          => $fakeURI->getVar('L_NAME' . $transactionId, null),
				'L_TRANSACTIONID' => $fakeURI->getVar('L_TRANSACTIONID' . $transactionId, null),
				'L_STATUS'        => $fakeURI->getVar('L_STATUS' . $transactionId, null),
				'L_AMT'           => $fakeURI->getVar('L_AMT' . $transactionId, null),
				'L_CURRENCYCODE'  => $fakeURI->getVar('L_CURRENCYCODE' . $transactionId, null),
				'L_FEEAMT'        => $fakeURI->getVar('L_FEEAMT' . $transactionId, null),
				'L_NETAMT'        => $fakeURI->getVar('L_NETAMT' . $transactionId, null),
			];

			$array_resp[$transaction['L_TRANSACTIONID']] = $transaction;
		}

		if (self::$debug)
		{
			file_put_contents($fileName, json_encode($array_resp));
		}

		return $array_resp;
	}

	/**
	 * Gets the subscription ID stored in PayPal for the given Merchant Transaction ID
	 *
	 * @param   string  $transactionId  The Merchant Transaction ID
	 *
	 * @return  string|null The subscription ID stored in PayPal or null if an error occurred.
	 */
	protected function getSubscriptionIdFromTransactionId($transactionId)
	{
		$targetURL = new JUri('https://api-3t.paypal.com/nvp');
		$targetURL->setVar('USER', self::$username);
		$targetURL->setVar('PWD', self::$password);
		$targetURL->setVar('SIGNATURE', self::$signature);
		$targetURL->setVar('METHOD', 'GetTransactionDetails');
		$targetURL->setVar('VERSION', '106.0');
		$targetURL->setVar('TRANSACTIONID', $transactionId);

		// Set up the request through cURL
		$ch = curl_init($targetURL->toString());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "AkeebaSubscriptions/5.0");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_CAINFO, JPATH_LIBRARIES . '/fof30/Download/Adapter/cacert.pem');
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
		// Force the use of TLS (therefore SSLv3 is not used, mitigating POODLE; see https://github.com/paypal/merchant-sdk-php)
		curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');
		// This forces the use of TLS 1.x
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);

		$rawResponse = curl_exec($ch);
		curl_close($ch);

		// We get a long query string as a response. We need to decode it to a hash array
		$fakeURI    = new JUri('http://localhost/index.php?' . $rawResponse);

		return $fakeURI->getVar('CUSTOM', null);
	}
}