<?php
/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

namespace Akeeba\Subscriptions\Admin\PluginAbstracts;

use Akeeba\Subscriptions\Admin\Model\Levels;
use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use Akeeba\Subscriptions\Site\Model\TaxHelper;
use Akeeba\Subscriptions\Site\Model\Users;
use FOF30\Container\Container;
use JDate;
use JFactory;
use JFile;
use JLoader;
use JPlugin;
use JRegistry;
use JText;
use JUser;

defined('_JEXEC') or die();

/**
 * Akeeba Subscriptions payment plugin abstract class
 */
abstract class AkpaymentBase extends JPlugin
{
	/**
	 * Name of the plugin, returned to the component
	 *
	 * @var  string
	 */
	protected $ppName = 'abstract';

	/**
	 * Translation key of the plugin's title, returned to the component
	 *
	 * @var  string
	 */
	protected $ppKey = 'PLG_AKPAYMENT_ABSTRACT_TITLE';

	/**
	 * Image path, returned to the component
	 *
	 * @var string
	 */
	protected $ppImage = '';

	/**
	 * Does this payment processor supports cancellation of recurring payments?
	 *
	 * @var bool
	 */
	protected $ppRecurringCancellation = false;

	/**
	 * @var  Container
	 */
	protected $container;

	/**
	 * Public constructor for the plugin
	 *
	 * @param   object $subject The object to observe
	 * @param   array  $config  An optional associative array of configuration settings.
	 */
	public function __construct(&$subject, $config = array())
	{
		if (!is_object($config['params']))
		{
			$config['params'] = new JRegistry($config['params']);
		}

		parent::__construct($subject, $config);

		// Get the container
		$this->container = Container::getInstance('com_akeebasubs');

		if (array_key_exists('ppName', $config))
		{
			$this->ppName = $config['ppName'];
		}

		if (array_key_exists('ppImage', $config))
		{
			$this->ppImage = $config['ppImage'];
		}

		$name = $this->ppName;

		if (array_key_exists('ppKey', $config))
		{
			$this->ppKey = $config['ppKey'];
		}
		else
		{
			$this->ppKey = "PLG_AKPAYMENT_{$name}_TITLE";
		}

		if (array_key_exists('ppRecurringCancellation', $config))
		{
			$this->ppRecurringCancellation = $config['ppRecurringCancellation'];
		}

		// Load the language files
		$jlang = JFactory::getLanguage();
		$jlang->load('plg_akpayment_' . $name, JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akpayment_' . $name, JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akpayment_' . $name, JPATH_ADMINISTRATOR, null, true);
	}

	/**
	 * Plugin event which returns the identity information of this payment
	 * method. The result is an array containing one or more associative arrays.
	 * If the plugin only provides a single payment method you should only
	 * return an array containing just one associative array. The assoc array
	 * has the keys 'name' (the name of the payment method), 'title'
	 * (translation key for the payment method's name) and 'image' (the URL to
	 * the image used for this payment method).
	 *
	 * @return  array
	 */
	public function onAKPaymentGetIdentity()
	{
		$title = $this->params->get('title', '');
		$image = trim($this->params->get('ppimage', ''));

		if (empty($title))
		{
			$title = JText::_($this->ppKey);
		}

		if (empty($image))
		{
			$image = $this->ppImage;
		}

		$ret = array(
			$this->ppName =>
				(object) array(
					'name'                  => $this->ppName,
					'title'                 => $title,
					'image'                 => $image,
					'recurringCancellation' => $this->ppRecurringCancellation,
					'activeCountries'       => array(
						'type'     => $this->params->get('typeCountryList', 2),
						'list'     => $this->params->get('countryList', array()),
						'priority' => $this->params->get('countryPriority', array())
					)
				)
		);

		return $ret;
	}

	/**
	 * Plugin event to modify the subscription's net price. This is used in
	 * payment plugins to implement an optional surcharge per payment
	 * method.
	 *
	 * @param   object $data The input data
	 *
	 * @return  float  The surcharge for this subscription level
	 */
	public function onValidateSubscriptionPrice($data)
	{
		$surcharge = 0;

		if ($data->paymentmethod == $this->ppName)
		{
			$percent   = false;
			$surcharge = $this->params->get('surcharge', '0');

			if (substr($surcharge, - 1) == '%')
			{
				$percent   = true;
				$surcharge = substr($surcharge, 0, - 1);
			}

			$surcharge = floatval($surcharge);

			if ($percent)
			{
				$surcharge = $data->netprice * ($surcharge / 100);
			}
		}

		return $surcharge;
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
	abstract public function onAKPaymentNew($paymentmethod, JUser $user, Levels $level, Subscriptions $subscription);

	/**
	 * Processes a callback from the payment processor
	 *
	 * @param   string $paymentmethod The currently used payment method. Check it against $this->ppName
	 * @param   array  $data          Input (request) data
	 *
	 * @return  boolean  True if the callback was handled, false otherwise
	 */
	abstract public function onAKPaymentCallback($paymentmethod, $data);

	/**
	 * Fixes the starting and end dates when a payment is accepted after the
	 * subscription's start date. This works around the case where someone pays
	 * by e-Check on January 1st and the check is cleared on January 5th. He'd
	 * lose those 4 days without this trick. Or, worse, if it was a one-day pass
	 * the user would have paid us and we'd never given him a subscription!
	 *
	 * @param   Subscriptions $subscription The subscription record
	 * @param   array         $updates      By reference (output) array to the updates being applied to $subscription
	 *
	 * @return  void
	 */
	public static function fixSubscriptionDates(Subscriptions $subscription, &$updates)
	{
		// Take into account the params->fixdates data to determine when
		// the new subscription should start and/or expire the old subscription
		$subcustom = (!empty($updates['params']) ? $updates['params'] : $subscription->params);

		if (is_string($subcustom))
		{
			$subcustom = json_decode($subcustom, true);
		}
		elseif (is_object($subcustom))
		{
			$subcustom = (array) $subcustom;
		}

		$oldsub     = null;
		$expiration = 'overlap';
		$allsubs    = array();
		$noContact  = array();

		if (isset($subcustom['fixdates']))
		{
			$oldsub     = isset($subcustom['fixdates']['oldsub']) ? $subcustom['fixdates']['oldsub'] : null;
			$expiration = isset($subcustom['fixdates']['expiration']) ? $subcustom['fixdates']['expiration'] : 'overlap';
			$allsubs    = isset($subcustom['fixdates']['allsubs']) ? $subcustom['fixdates']['allsubs'] : array();
			$noContact  = isset($subcustom['fixdates']['nocontact']) ? $subcustom['fixdates']['nocontact'] : array();

			unset($subcustom['fixdates']);
		}

		// Mark all subscriptions being renewed by this subscription as "no contact" (contact_flag is set to 3)
		if (!empty($noContact))
		{
			foreach ($noContact as $subId)
			{
				/** @var Subscriptions $row */
				$row = $subscription->getContainer()->factory->model('Subscriptions')->tmpInstance();

				try
				{
					$row->findOrFail($subId)->save(['contact_flag' => 3]);
				}
				catch (\Exception $e)
				{
					// Failure *is* an option.
				}
			}
		}

		if (is_numeric($oldsub))
		{
			$sub = $subscription->getClone()->savestate(0)->setIgnoreRequest(true)->reset(true, true);
			$sub->load($oldsub, true);

			if ($sub->akeebasubs_subscription_id == $oldsub)
			{
				$oldsub = $sub;
			}
			else
			{
				$oldsub     = null;
				$expiration = 'overlap';
			}
		}
		else
		{
			$oldsub     = null;
			$expiration = 'overlap';
		}

		// Fix the starting date if the payment was accepted after the subscription's start date. This
		// works around the case where someone pays by e-Check on January 1st and the check is cleared
		// on January 5th. He'd lose those 4 days without this trick. Or, worse, if it was a one-day pass
		// the user would have paid us and we'd never given him a subscription!
		$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';

		if (!preg_match($regex, $subscription->publish_up))
		{
			$subscription->publish_up = '2001-01-01';
		}

		if (!preg_match($regex, $subscription->publish_down))
		{
			$subscription->publish_down = '2038-01-01';
		}

		$jNow   = new JDate();
		$jStart = new JDate($subscription->publish_up);
		$jEnd   = new JDate($subscription->publish_down);
		$now    = $jNow->toUnix();
		$start  = $jStart->toUnix();
		$end    = $jEnd->toUnix();

		/** @var Subscriptions $oldsub */

		if (is_null($oldsub))
		{
			$oldSubExpirationTimestamp = $now;
		}
		else
		{
			if (!preg_match($regex, $oldsub->publish_down))
			{
				$oldSubExpirationTimestamp = $now;
			}
			else
			{
				$jOldSubExpiration         = new JDate($oldsub->publish_down);
				$oldSubExpirationTimestamp = $jOldSubExpiration->toUnix();
			}
		}

		if ($start <= $now)
		{
			if ($end >= 2145916800)
			{
				// End date after 2038-01-01; forever subscription
				$start = $now;
			}
			else
			{
				// Get the subscription level and determine if this is a Fixed
				// Expiration subscription
				$nullDate = JFactory::getDbo()->getNullDate();

				/** @var Levels $level */
				if ($subscription->level instanceof Levels)
				{
					$level = $subscription->level;
				}
				else
				{
					$level = Container::getInstance('com_akeebasubs')->factory->model('Levels')->tmpInstance();
					$level->find($subscription->akeebasubs_level_id);
				}

				$fixed_date = $level->fixed_date;

				if (!is_null($fixed_date) && !($fixed_date == $nullDate))
				{
					// Is the fixed date in the future?
					$jFixedDate = JFactory::getDate($fixed_date);

					if ($now > $jFixedDate->toUnix())
					{
						// If the fixed date is in the past handle it as a regular subscription
						$fixed_date = null;
					}

					if (!empty($fixed_date))
					{
						$start = $now;
						$end   = $jFixedDate->toUnix();
					}
				}

				if (is_null($fixed_date) || ($fixed_date == $nullDate))
				{
					// Regular subscription
					$duration = $end - $start;

					// Assume expiration != after => start date = now
					$start = $now;

					// But if expiration = after => start date = end date of old sub
					if ($expiration == 'after')
					{
						// Make sure the activation date is never in the past
						$start = max($now, $oldSubExpirationTimestamp);
					}

					$end = $start + $duration;
				}
			}

			$jStart = new JDate($start);
			$jEnd   = new JDate($end);
		}

		// Expiration = replace => expire old subscription
		if ($expiration == 'replace')
		{
			// Disable the primary subscription used to determine the subscription date
			$newdata = array(
				'publish_down' => $jNow->toSql(),
				'enabled'      => 0,
				'contact_flag' => 3,
				'notes'        => $oldsub->notes . "\n\n" . "SYSTEM MESSAGE: This subscription was upgraded and replaced with " . $oldsub->akeeabsubs_subscription_id . "\n"
			);

			$oldsub->save($newdata);

			// Disable all old subscriptions
			if (!empty($allsubs))
			{
				foreach ($allsubs as $sub_id)
				{
					/** @var Subscriptions $table */
					$table = $subscription->getClone()->savestate(false)->reset(true, true);
					$table->find($sub_id);

					if ($table->akeebasubs_subscription_id == $oldsub->akeebasubs_subscription_id)
					{
						// Don't try to disable the same subscription twice
						continue;
					}

					$data = $table->getData();

					$newdata = array_merge($data, array(
						'publish_down' => $jNow->toSql(),
						'enabled'      => 0,
						'contact_flag' => 3,
						'notes'        => $oldsub->notes . "\n\n" . "SYSTEM MESSAGE: This subscription was upgraded and replaced with " . $table->akeeabsubs_subscription_id . "\n"
					));

					$table->save($newdata);
				}
			}
		}

		$updates['publish_up']   = $jStart->toSql();
		$updates['publish_down'] = $jEnd->toSql();
		$updates['enabled']      = 1;
		$updates['params']       = $subcustom;
	}

	/**
	 * Logs the received IPN information to file
	 *
	 * @param   array   $data    Request data
	 * @param   boolean $isValid Is it a valid payment?
	 *
	 * @return  void
	 */
	protected function logIPN($data, $isValid)
	{
		$config  = JFactory::getConfig();
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

		$logData = @file_get_contents($logFile);

		if ($logData === false)
		{
			$logData = '';
		}

		$logData .= "\n" . str_repeat('-', 80);
		$pluginName = strtoupper($this->ppName);
		$logData .= $isValid ? 'VALID ' . $pluginName . ' IPN' : 'INVALID ' . $pluginName . ' IPN *** FRAUD ATTEMPT OR INVALID NOTIFICATION ***';
		$logData .= "\nDate/time : " . gmdate('Y-m-d H:i:s') . " GMT\n\n";

		foreach ($data as $key => $value)
		{
			$logData .= '  ' . str_pad($key, 30, ' ') . $value . "\n";
		}

		$logData .= "\n";

		JFile::write($logFile, $logData);
	}

	/**
	 * Translates the given 2-digit country code into the 3-digit country code.
	 *
	 * @param   string $country The 2 digit country code
	 *
	 * @return  string  The 3 digit country code
	 */
	protected function translateCountry($country)
	{
		$countryMap = array(
			'AX' => 'ALA',
			'AF' => 'AFG',
			'AL' => 'ALB',
			'DZ' => 'DZA',
			'AS' => 'ASM',
			'AD' => 'AND',
			'AO' => 'AGO',
			'AI' => 'AIA',
			'AQ' => 'ATA',
			'AG' => 'ATG',
			'AR' => 'ARG',
			'AM' => 'ARM',
			'AW' => 'ABW',
			'AU' => 'AUS',
			'AT' => 'AUT',
			'AZ' => 'AZE',
			'BS' => 'BHS',
			'BH' => 'BHR',
			'BD' => 'BGD',
			'BB' => 'BRB',
			'BY' => 'BLR',
			'BE' => 'BEL',
			'BZ' => 'BLZ',
			'BJ' => 'BEN',
			'BM' => 'BMU',
			'BT' => 'BTN',
			'BO' => 'BOL',
			'BA' => 'BIH',
			'BW' => 'BWA',
			'BV' => 'BVT',
			'BR' => 'BRA',
			'IO' => 'IOT',
			'BN' => 'BRN',
			'BG' => 'BGR',
			'BF' => 'BFA',
			'BI' => 'BDI',
			'KH' => 'KHM',
			'CM' => 'CMR',
			'CA' => 'CAN',
			'CV' => 'CPV',
			'KY' => 'CYM',
			'CF' => 'CAF',
			'TD' => 'TCD',
			'CL' => 'CHL',
			'CN' => 'CHN',
			'CX' => 'CXR',
			'CC' => 'CCK',
			'CO' => 'COL',
			'KM' => 'COM',
			'CD' => 'COD',
			'CG' => 'COG',
			'CK' => 'COK',
			'CR' => 'CRI',
			'CI' => 'CIV',
			'HR' => 'HRV',
			'CU' => 'CUB',
			'CY' => 'CYP',
			'CZ' => 'CZE',
			'DK' => 'DNK',
			'DJ' => 'DJI',
			'DM' => 'DMA',
			'DO' => 'DOM',
			'EC' => 'ECU',
			'EG' => 'EGY',
			'SV' => 'SLV',
			'GQ' => 'GNQ',
			'ER' => 'ERI',
			'EE' => 'EST',
			'ET' => 'ETH',
			'FK' => 'FLK',
			'FO' => 'FRO',
			'FJ' => 'FJI',
			'FI' => 'FIN',
			'FR' => 'FRA',
			'GF' => 'GUF',
			'PF' => 'PYF',
			'TF' => 'ATF',
			'GA' => 'GAB',
			'GM' => 'GMB',
			'GE' => 'GEO',
			'DE' => 'DEU',
			'GH' => 'GHA',
			'GI' => 'GIB',
			'GR' => 'GRC',
			'GL' => 'GRL',
			'GD' => 'GRD',
			'GP' => 'GLP',
			'GU' => 'GUM',
			'GT' => 'GTM',
			'GN' => 'GIN',
			'GW' => 'GNB',
			'GY' => 'GUY',
			'HT' => 'HTI',
			'HM' => 'HMD',
			'HN' => 'HND',
			'HK' => 'HKG',
			'HU' => 'HUN',
			'IS' => 'ISL',
			'IN' => 'IND',
			'ID' => 'IDN',
			'IR' => 'IRN',
			'IQ' => 'IRQ',
			'IE' => 'IRL',
			'IL' => 'ISR',
			'IT' => 'ITA',
			'JM' => 'JAM',
			'JP' => 'JPN',
			'JO' => 'JOR',
			'KZ' => 'KAZ',
			'KE' => 'KEN',
			'KI' => 'KIR',
			'KP' => 'PRK',
			'KR' => 'KOR',
			'KW' => 'KWT',
			'KG' => 'KGZ',
			'LA' => 'LAO',
			'LV' => 'LVA',
			'LB' => 'LBN',
			'LS' => 'LSO',
			'LR' => 'LBR',
			'LY' => 'LBY',
			'LI' => 'LIE',
			'LT' => 'LTU',
			'LU' => 'LUX',
			'MO' => 'MAC',
			'MK' => 'MKD',
			'MG' => 'MDG',
			'MW' => 'MWI',
			'MY' => 'MYS',
			'MV' => 'MDV',
			'ML' => 'MLI',
			'MT' => 'MLT',
			'MH' => 'MHL',
			'MQ' => 'MTQ',
			'MR' => 'MRT',
			'MU' => 'MUS',
			'YT' => 'MYT',
			'MX' => 'MEX',
			'FM' => 'FSM',
			'MD' => 'MDA',
			'MC' => 'MCO',
			'MN' => 'MNG',
			'MS' => 'MSR',
			'MA' => 'MAR',
			'MZ' => 'MOZ',
			'MM' => 'MMR',
			'NA' => 'NAM',
			'NR' => 'NRU',
			'NP' => 'NPL',
			'NL' => 'NLD',
			'AN' => 'ANT',
			'NC' => 'NCL',
			'NZ' => 'NZL',
			'NI' => 'NIC',
			'NE' => 'NER',
			'NG' => 'NGA',
			'NU' => 'NIU',
			'NF' => 'NFK',
			'MP' => 'MNP',
			'NO' => 'NOR',
			'OM' => 'OMN',
			'PK' => 'PAK',
			'PW' => 'PLW',
			'PS' => 'PSE',
			'PA' => 'PAN',
			'PG' => 'PNG',
			'PY' => 'PRY',
			'PE' => 'PER',
			'PH' => 'PHL',
			'PN' => 'PCN',
			'PL' => 'POL',
			'PT' => 'PRT',
			'PR' => 'PRI',
			'QA' => 'QAT',
			'RE' => 'REU',
			'RO' => 'ROU',
			'RU' => 'RUS',
			'RW' => 'RWA',
			'SH' => 'SHN',
			'KN' => 'KNA',
			'LC' => 'LCA',
			'PM' => 'SPM',
			'VC' => 'VCT',
			'WS' => 'WSM',
			'SM' => 'SMR',
			'ST' => 'STP',
			'SA' => 'SAU',
			'SN' => 'SEN',
			'CS' => 'SCG',
			'SC' => 'SYC',
			'SL' => 'SLE',
			'SG' => 'SGP',
			'SK' => 'SVK',
			'SI' => 'SVN',
			'SB' => 'SLB',
			'SO' => 'SOM',
			'ZA' => 'ZAF',
			'GS' => 'SGS',
			'ES' => 'ESP',
			'LK' => 'LKA',
			'SD' => 'SDN',
			'SR' => 'SUR',
			'SJ' => 'SJM',
			'SZ' => 'SWZ',
			'SE' => 'SWE',
			'CH' => 'CHE',
			'SY' => 'SYR',
			'TW' => 'TWN',
			'TJ' => 'TJK',
			'TZ' => 'TZA',
			'TH' => 'THA',
			'TL' => 'TLS',
			'TG' => 'TGO',
			'TK' => 'TKL',
			'TO' => 'TON',
			'TT' => 'TTO',
			'TN' => 'TUN',
			'TR' => 'TUR',
			'TM' => 'TKM',
			'TC' => 'TCA',
			'TV' => 'TUV',
			'UG' => 'UGA',
			'UA' => 'UKR',
			'AE' => 'ARE',
			'GB' => 'GBR',
			'US' => 'USA',
			'UM' => 'UMI',
			'UY' => 'URY',
			'UZ' => 'UZB',
			'VU' => 'VUT',
			'VA' => 'VAT',
			'VE' => 'VEN',
			'VN' => 'VNM',
			'VG' => 'VGB',
			'VI' => 'VIR',
			'WF' => 'WLF',
			'EH' => 'ESH',
			'YE' => 'YEM',
			'ZM' => 'ZMB',
			'ZW' => 'ZWE'
		);

		if (array_key_exists($country, $countryMap))
		{
			return $countryMap[$country];
		}
		else
		{
			return '';
		}
	}

	protected function debug($string)
	{
		static $logDir = '';

		if (empty($logDir))
		{
			$defLogDir = (version_compare(JVERSION, '3.5.999', 'le') ? JPATH_ROOT : JPATH_ADMINISTRATOR) . '/logs';
			$logDir    = JFactory::getConfig()->get('log_path', $defLogDir);
			$logDir    = rtrim($logDir, '/' . DIRECTORY_SEPARATOR);
		}

		$handle = fopen($logDir . '/log.txt', 'a+');
		fwrite($handle, date('Y-m-d H:i:s') . ' --- ' . $string . PHP_EOL);
		fclose($handle);
	}

	/**
	 * Handles a recurring subscription's payment
	 *
	 * @param   Subscriptions $subscription The currently active subscription
	 * @param   array         $updates      Updates to the currently active subscription
	 *
	 *
	 * @since version
	 */
	protected function handleRecurringSubscription(Subscriptions $subscription, &$updates)
	{
		$jNow = new JDate();

		// Create a new record for the old subscription
		$oldData                               = $subscription->getData();
		$oldData['akeebasubs_subscription_id'] = 0;
		$oldData['publish_down']               = $jNow->toSql();
		$oldData['enabled']                    = 0;
		$oldData['contact_flag']               = 3;
		$oldData['notes']                      = "Automatically renewed subscription on " . $jNow->toSql();

		/**
		 * Update the existing subscription with a fake processor key, appending "_TEMP" to the existing one. This will
		 * eliminate any interference from saving a new record below.
		 */
		$subscription->_dontNotify(true);
		$subscription->bind([
			'processor_key' => $subscription->processor_key . '_TEMP'
		]);
		$subscription->_dontNotify(false);

		// Save the record for the old subscription
		$oldSubscription = $subscription->tmpInstance();
		$oldSubscription->reset();
		$oldSubscription->_dontNotify(true);
		$oldSubscription->bind($oldData)->save();
		$oldSubscription->_dontNotify(false);

		/**
		 * If there's an invoice for the currently active (old) subscription we need to reassign it to the new ID of the
		 * old subscription (since the existing subscription ID is actually reused for the recurring installment). This
		 * will allow a new invoice to be issued for the new installment despite it having an old ID.
		 */
		$updates['akeebasubs_invoice_id'] = 0;

		$db    = $subscription->getDbo();
		$query = $db->getQuery(true)
		            ->update($db->qn('#__akeebasubs_invoices'))
		            ->set($db->qn('akeebasubs_subscription_id') . '=' . $db->q($oldSubscription->getId()))
		            ->where($db->qn('akeebasubs_subscription_id') . '=' . $db->q($subscription->getId()));
		$db->setQuery($query);
		$db->execute();

		// On recurring subscriptions recalculate the net, tax and gross price by removing the signup fee
		if ($subscription->recurring_amount >= 0.01)
		{
			$updates['tax_percent'] = $subscription->tax_percent;

			// Recalculate the tax rate in case it has changed since the last recurring payment (e.g. Brexit)
			$container = Container::getInstance('com_akeebasubs');
			/** @var TaxHelper $taxHelper */
			$taxHelper = $container->factory->model('TaxHelper')->tmpInstance();

			$user = $subscription->user;

			if (!is_object($user) || !($user instanceof Users))
			{
				/** @var Users $user */
				$user = $container->factory->model('Users')->tmpInstance();
				try
				{
					$user->findOrFail([
						'user_id' => $subscription->user_id
					]);
				}
				catch (\Exception $e)
				{
					$user = null;
				}
			}

			if (is_object($user) && ($user instanceof Users) && ($user->user_id))
			{
				$taxInfo = $taxHelper->getTaxRule($subscription->akeebasubs_level_id, $user->country,
					$user->getFieldValue('state'), $user->city,
					$user->viesregistered);
				$updates['tax_percent'] = $taxInfo->taxrate;

				// Gross amount is what the client paid. This is either the recurring_amount (if it's non zero) or the gross_amount
				$updates['gross_amount']       = ($subscription->recurring_amount > 0.01) ? $subscription->recurring_amount : $subscription->gross_amount;
				// We reverse engineer the tax amount from the gross amount since the tax_percent may have changed since the last payment (e.g. a country has increased the tax rate; UK left the EU and so on)
				$updates['tax_amount']         = ($subscription->tax_percent < 0.01) ? 0 : ($updates['gross_amount'] / (0.01 * (100 + $updates['tax_percent'])));
				// The net_amount is calculated by subtraction to make sure we don't suffer any rounding errors which would throw us off by a penny.
				$updates['net_amount']         = $updates['gross_amount'] - $updates['tax_amount'];
				// There is no discount in recurring subscriptions...
				$updates['discount_amount']    = 0;
				// ...therefore the prediscount_amount is the same as the full price paid.
				$updates['prediscount_amount'] = $updates['gross_amount'];
			}
		}
	}

}
