<?php
/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

JLoader::import('joomla.plugin.plugin');

use FOF30\Container\Container;
use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use FOF30\Date\Date;

/**
 * plgSystemAs2cocollation plugin. Collates 2Checkout sales with the information in Akeeba Subscriptions. Useful if you
 * don't get notifications for AmEx and Discover payments like we do (we are told that we are the only account this
 * happens, but who knows?)
 *
 * Example call:
 * http://localhost/index.php?option=com_akeebasubs&view=cron&command=2cocollation&secret=yoursecret
 */
class plgSystemAs2cocollation extends JPlugin
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
	 * @var   string  The API Username for 2Checkout
	 */
	public static $apiUsername = '';

	/**
	 * @var   string  The API Password for 2Checkout
	 */
	public static $apiPassword = '';

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
		self::$apiUsername = $this->params->get('apiusername', '');
		self::$apiPassword = $this->params->get('apipassword', '');
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

		if ($task != '2cocollation')
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

		// Load a list of latest 2CO sales
		$allSales = $this->getLatestSales();

		if (!is_array($allSales))
		{
			return;
		}

		// Loop through each sale and make a list of which ones do not correspond to an active subscription
		$db = Container::getInstance('com_akeebasubs')->db;
		$needProcessing = array();
		$protoQuery = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->qn('#__akeebasubs_subscriptions'))
			->where($db->qn('processor') . '=' . $db->q('2checkout'))
			->where($db->qn('state') . '=' . $db->q('C'));

		foreach ($allSales['sale_summary'] as $sale)
		{
			$id = $sale['sale_id'];
			$query = clone $protoQuery;
			$query->where(
				'(' . $db->qn('processor_key') . ' LIKE ' . $db->q('%/' . $id) . ') OR ' .
				'(' . $db->qn('processor_key') . ' LIKE ' . $db->q($id . '/%') . ')'
			);
			$db->setQuery($query);
			$countRows = $db->loadResult();

			if ($countRows < 1)
			{
				$needProcessing[] = $id;
			}
		}

		// If there are no pending sales I don't have to do anything.
		if (empty($needProcessing))
		{
			return;
		}

		$thresholdTime = time() + 0.7 * $options['time_limit'];

		// Loop all pending sales, figure out which subscription they are referring to and activate the subscription
		foreach ($needProcessing as $id)
		{
			if (time() > $thresholdTime)
			{
				return;
			}

			// Get the sale details from 2CO
			$sale = $this->loadSaleId($id);

			// Find the subscription ID
			$subId = trim($sale['sale']['invoices'][0]['vendor_order_id']);

			// Construct the processor key
			$invoiceId = $sale['sale']['invoices'][0]['invoice_id'];
			$processorKey = $id . '/' . $invoiceId;

			// Load the subscription
			/** @var Subscriptions $sub */
			$sub = Container::getInstance('com_akeebasubs', [], 'site')->factory->model('Subscriptions')->tmpInstance();
			$sub->find($subId);

			if ($sub->akeebasubs_subscription_id != $subId)
			{
				continue;
			}

			// Update the subscription
			try
			{
				$updates = array(
					'state'         => 'C',
					'processor_key' => $processorKey,
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
	 * Gets all the sales the last 24-48 hours from 2Checkout
	 *
	 * @return  array  You need the sale_summary array
	 */
	protected function getLatestSales()
	{
		// Debug mode
		if (self::$debug)
		{
			$jconfig = Container::getInstance('com_akeebasubs')->platform->getConfig();
			$tmp = $jconfig->get('tmp_path', sys_get_temp_dir());
			$fileName = $tmp . '/test_latest_sales.txt';

			if (file_exists($fileName))
			{
				$array_resp = json_decode(file_get_contents($fileName), true);

				return $array_resp;
			}
		}

		$tz = new DateTimeZone('America/Chicago');
		JLoader::import('joomla.utilities.date');
		$now = new Date();
		$now->setTimezone($tz);

		$prevDay = new Date($now->toUnix() - 86400, $tz);
		$nextDay = new Date($now->toUnix() + 86400, $tz);

		$ch = curl_init("https://www.2checkout.com/api/sales/list_sales?sale_date_begin=" . $prevDay->format('Y-m-d') . '&sale_date_end=' . $nextDay->format('Y-m-d') . '&pagesize=100');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json"));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "2Checkout PHP/0.1.0");
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, self::$apiUsername . ':' . self::$apiPassword);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_CAINFO, JPATH_LIBRARIES . '/fof30/Download/Adapter/cacert.pem');

		$json_resp = curl_exec($ch);
		curl_close($ch);

		//decode to an associative array
		$array_resp = json_decode($json_resp, true);

		if (self::$debug)
		{
			file_put_contents($fileName, json_encode($array_resp));
		}

		return $array_resp;
	}

	/**
	 * Load the sale details from 2Checkout for a given 2CO sale ID
	 *
	 * @param   string  $id  The 2CO sale ID
	 *
	 * @return  array  The sale information
	 */
	protected function loadSaleId($id)
	{
		// Debug mode
		if (self::$debug)
		{
			$jconfig = Container::getInstance('com_akeebasubs')->platform->getConfig();
			$tmp = $jconfig->get('tmp_path', sys_get_temp_dir());
			$fileName = $tmp . '/test_saleid_' . $id . '.txt';

			if (file_exists($fileName))
			{
				$saleData = json_decode(file_get_contents($fileName), true);

				return $saleData;
			}
		}

		$ch = curl_init("https://www.2checkout.com/api/sales/detail_sale?sale_id=$id");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json"));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "2Checkout PHP/0.1.0");
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, self::$apiUsername . ':' . self::$apiPassword);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_CAINFO, JPATH_LIBRARIES . '/fof30/Download/Adapter/cacert.pem');

		$json_resp = curl_exec($ch);
		curl_close($ch);

		//decode to an associative array
		$array_resp = json_decode($json_resp, true);

		if (self::$debug)
		{
			file_put_contents($fileName, json_encode($array_resp));
		}

		return $array_resp;
	}
}