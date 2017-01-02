<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Helper;

use FOF30\Container\Container;
use FOF30\Download\Download;

defined('_JEXEC') or die;

/**
 * Foreign exchange conversions using exchange rate data from the European Central Bank
 */
class Forex
{
	/**
	 * The timestamp update key
	 *
	 * @var  string
	 */
	protected static $timestampKey = 'akeebasubs_forex_update_timestamp';

	/**
	 * The European Central Bank exchange rate data source
	 *
	 * @var  string
	 */
	protected static $rateSourceUrl = 'http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

	/**
	 * Cached foreign exchange rates quotes against Euro, indexed by currency code
	 *
	 * @var  array
	 */
	protected static $rates = [];

	protected static $countryCurrency = [];

	protected static $currencySymbols = [];

	/**
	 * Updates the exchange rates from the latest ECB data
	 *
	 * @param   bool       $force      Should I force an update (true) or decide based on latest update timestamp (false)?
	 * @param   Container  $container  The container of the application we're runnign in
	 *
	 * @return  void
	 *
	 * @throws  \Exception If the download or the database operation fails
	 */
	public static function updateRates($force = false, Container $container = null)
	{
		if (!($container instanceof Container))
		{
			$container = Container::getInstance('com_akeebasubs');
		}

		// Should I update the rates? I will only do if forced or they have expired.
		$shouldUpdate = true;

		if (!$force)
		{
			$shouldUpdate = self::shouldUpdate($container);
		}

		if (!$shouldUpdate)
		{
			return;
		}

		// Update the timestamp
		self::setLastUpdateTimestamp($container);

		// Get the raw XML source
		$download = new Download($container);

		$xmlData = $download->getFromURL(self::$rateSourceUrl);

		// Parse the data into an array
		$rates = [];
		$xml = new \SimpleXMLElement($xmlData);

		foreach($xml->Cube->Cube->Cube as $rate)
		{
			$rates[] = (object)[
				'akeebasubs_forexrate_id'	=> (string)$rate["currency"],
				'rate'						=> (float)$rate["rate"]
			];
		}

		// Clear the table and insert the new data, all wrapped inside a transaction
		$db = $container->db;

		$db->transactionStart();

		try
		{
			// Can't use TRUNCATE TABLE because since MySQL 5.1 it's considered DDL, not DML, causing an implicit flush
			// See http://stackoverflow.com/questions/5972364/mysql-truncate-table-within-transaction
			$query = $db->getQuery(true)
				->delete('#__akeebasubs_forexrates');
			$db->setQuery($query)->execute();

			foreach ($rates as $rateObject)
			{
				$db->insertObject('#__akeebasubs_forexrates', $rateObject);
			}
		}
		catch (\Exception $e)
		{
			$db->transactionRollback();

			throw $e;
		}

		$db->transactionCommit();

		self::reloadRates(true, $container);
	}

	/**
	 * Reload the exchange rates from the database
	 *
	 * @param   bool       $force      Should I force-reload the rates?
	 * @param   Container  $container  The container of the application we're running in
	 *
	 * @return  void
	 */
	public static function reloadRates($force = false, Container $container = null)
	{
		if (!$force && !empty(self::$rates))
		{
			return;
		}

		if (!($container instanceof Container))
		{
			$container = Container::getInstance('com_akeebasubs');
		}

		$db = $container->db;

		$query = $db->getQuery(true)
			->select('*')
			->from('#__akeebasubs_forexrates');

		try
		{
			self::$rates = $db->setQuery($query)->loadAssocList('akeebasubs_forexrate_id');
		}
		catch (\Exception $e)
		{
			// Couldn't load rates? Do nothing.
		}
	}

	/**
	 * Reload the currency data from the database. This loads the country to currency mapping and the currency code to
	 * currency symbol mapping.
	 *
	 * @param   bool      $force      Force reload this information? If false it will be loaded only if it's not loaded yet.
	 * @param   Container $container  The container of the application we're running in
	 *
	 * @return  void
	 */
	public static function reloadCurrencyData($force = false, Container $container = null)
	{
		if (!$force && !empty(self::$countryCurrency))
		{
			return;
		}

		if (!($container instanceof Container))
		{
			$container = Container::getInstance('com_akeebasubs');
		}

		$db = $container->db;

		$query = $db->getQuery(true)
					->select('*')
					->from('#__akeebasubs_countrycurrencies');

		try
		{
			self::$countryCurrency = $db->setQuery($query)->loadAssocList('country');
			self::$currencySymbols = [];

			foreach (self::$countryCurrency as $country => $currencyInfo)
			{
				self::$currencySymbols[$currencyInfo['currency']] = $currencyInfo['symbol'];
			}
		}
		catch (\Exception $e)
		{
			// Couldn't load rates? Do nothing.
		}
	}

	/**
	 * Converts a value expressed in the component's default currency (default: Euros) into the specified country's
	 * local currency. The information is returned as a hashed array with the keys:
	 * - currency  The currency code of that country, e.g. USD
	 * - symbol    The currency symbol of that country, e.g. $
	 * - value     The converted value
	 * - rate      The exchange rate used
	 *
	 * @param   string     $country    The country which the data will be converted for, 2 letter ISO code, e.g. US
	 * @param   float      $value      The value you want to convert
	 * @param   Container  $container  The container of the application we're running in
	 *
	 * @return  array  Keys: currency (e.g. USD), symbol (e.g. $), value (converted), rate (exchange rate)
	 */
	public static function convertToLocal($country, $value, Container $container = null)
	{
		self::reloadCurrencyData(false, $container);

		$defaultAkeebaSubsCurrency = self::getContainer()->params->get('currency', 'EUR');
		$defaultAkeebaSubsCurrency = strtoupper($defaultAkeebaSubsCurrency);

		$defaultReturn = [
			'currency' => $defaultAkeebaSubsCurrency,
			'symbol'   => self::getContainer()->params->get('currencysymbol', '€'),
			'value'    => $value,
			'rate'     => 1.00
		];

		$country = strtoupper($country);

		if (!isset(self::$countryCurrency[$country]))
		{
			return $defaultReturn;
		}

		$newCurrency = self::$countryCurrency[$country]['currency'];

		if ($newCurrency == $defaultAkeebaSubsCurrency)
		{
			return $defaultReturn;
		}

		try
		{
			$newValue = self::convertCurrency($defaultAkeebaSubsCurrency, $newCurrency, $value, $container);
			$exchangeRate = self::exhangeRate($defaultAkeebaSubsCurrency, $newCurrency, $container);
			$newSymbol = self::getCurrencySymbol($newCurrency);
		}
		catch (\Exception $e)
		{
			// On error (e.g. currency not known in our system) we return the default return, i.e. no conversion
			return $defaultReturn;
		}

		return [
			'currency' => $newCurrency,
			'symbol'   => $newSymbol,
			'value'    => $newValue,
			'rate'     => $exchangeRate
		];
	}

	/**
	 * Return the currency symbol for the specified currency.
	 *
	 * @param   string  $currency  The currency to get the symbol for, e.g. EUR
	 *
	 * @return  string  The currency symbol, e.g. €
	 */
	public static function getCurrencySymbol($currency)
	{
		$currency = strtoupper($currency);

		if (isset(self::$currencySymbols[$currency]))
		{
			return self::$currencySymbols[$currency];
		}

		return $currency;
	}

	/**
	 * Convert a value between currencies
	 *
	 * @param   string     $from       The currency you're converting from (the one $value is expressed in), e.g. EUR
	 * @param   string     $to         The currency you're converting to, e.g. USD
	 * @param   float      $value      The value you want to convert
	 * @param   Container  $container  The container of the application we're running in
	 *
	 * @return  float
	 *
	 * @throw  \InvalidArgumentException  If an empty currency is specified or the currency is unknown
	 */
	public static function convertCurrency($from, $to, $value, Container $container = null)
	{
		if (empty($from) || empty($to))
		{
			throw new \InvalidArgumentException('Invalid currency');
		}

		$from = strtoupper($from);
		$to = strtoupper($to);

		if ($from == $to)
		{
			return $value;
		}

		if ($from == 'EUR')
		{
			return self::convertFromEuro($to, $value, $container);
		}
		elseif ($to == 'EUR')
		{
			return self::convertToEuro($from, $value, $container);
		}
		else
		{
			$intermediate = self::convertToEuro($from, $value, $container);
			return self::convertFromEuro($to, $intermediate, $container);
		}
	}

	/**
	 * Get the exchange rate between two currencies
	 *
	 * @param   string     $from       Currency code to convert from, e.g. EUR
	 * @param   string     $to         Currency code to convert to, e.g. USD
	 * @param   Container  $container  The container of the application we're running in
	 *
	 * @return  float  The exchange rate
	 */
	public static function exhangeRate($from, $to, Container $container = null)
	{
		return self::convertCurrency($from, $to, 1.00, $container);
	}

	/**
	 * Convert a value from Euro to a foreign currency
	 *
	 * @param   string     $to         The currency you're converting to, e.g. USD
	 * @param   float      $value      The value you want to convert
	 * @param   Container  $container  The container of the application we're running in
	 *
	 * @return  float
	 *
	 * @throw  \InvalidArgumentException  If an empty currency is specified or the currency is unknown
	 */
	public static function convertFromEuro($to, $value, Container $container = null)
	{
		// Reload rates if necessary
		if (!($container instanceof Container))
		{
			$container = Container::getInstance('com_akeebasubs');
		}

		self::reloadRates(false, $container);

		// Make sure the currency exists
		if (!isset(self::$rates[$to]))
		{
			throw new \InvalidArgumentException("Unkown currency code $to");
		}

		// Return the result
		return $value * self::$rates[$to]['rate'];
	}

	/**
	 * Convert a value from a foreign currency to Euro
	 *
	 * @param   string     $from       The currency you're converting from (the one $value is expressed in), e.g. USD
	 * @param   float      $value      The value you want to convert
	 * @param   Container  $container  The container of the application we're running in
	 *
	 * @return  float
	 *
	 * @throw  \InvalidArgumentException  If an empty currency is specified or the currency is unknown
	 */
	public static function convertToEuro($from, $value, Container $container = null)
	{
		// Reload rates if necessary
		if (!($container instanceof Container))
		{
			$container = Container::getInstance('com_akeebasubs');
		}

		self::reloadRates(false, $container);

		// Make sure the currency exists
		if (!isset(self::$rates[$from]))
		{
			throw new \InvalidArgumentException("Unkown currency code $from");
		}

		// Return the result
		return $value / self::$rates[$from]['rate'];
	}

	/**
	 * Get the last update timestamp from the database
	 *
	 * @param   Container  $container  The container of the application we're running in
	 *
	 * @return  int  The last update timestamp, zero if not set
	 */
	protected static function getLastUpdateTimestamp(Container $container = null)
	{
		if (!($container instanceof Container))
		{
			$container = Container::getInstance('com_akeebasubs');
		}

		$db = $container->db;

		$query = $db->getQuery(true)
			->select($db->qn('value'))
			->from($db->qn('#__akeeba_common'))
			->where($db->qn('key') . ' = ' . $db->q(self::$timestampKey));

		try
		{
			$ret = $db->setQuery($query)->loadResult();

			if (is_null($ret))
			{
				$ret = 0;
			}

			return $ret;
		}
		catch (\Exception $e)
		{
			return 0;
		}
	}

	/**
	 * Set the last update timestamp to right now
	 *
	 * @param   Container  $container  The container of the application we're running in
	 *
	 * @return  void
	 */
	protected static function setLastUpdateTimestamp(Container $container = null)
	{
		if (!($container instanceof Container))
		{
			$container = Container::getInstance('com_akeebasubs');
		}

		$db = $container->db;

		$query = $db->getQuery(true)
					->delete($db->qn('#__akeeba_common'))
					->where($db->qn('key') . ' = ' . $db->q(self::$timestampKey));

		try
		{
			$db->lockTable('#__akeeba_common');
			$db->setQuery($query)->execute();
		}
		catch (\Exception $e)
		{
			// No problem if the record doesn't exists
		}

		$time = time();
		$query = $db->getQuery(true)
			->insert($db->qn('#__akeeba_common'))
			->columns([
				$db->qn('key'), $db->qn('value')
			])->values($db->q(self::$timestampKey) . ', ' . $db->q($time));

		try
		{
			$db->setQuery($query)->execute();
		}
		catch (\Exception $e)
		{
			// If we couldn't update the record it's not the end of the world
		}

		$db->unlockTables();
	}

	/**
	 * Do I need to update the foreign exchange rate?
	 *
	 * @param   Container  $container  The container of the application we're running in
	 *
	 * @return  bool  True if we do have to update
	 */
	protected static function shouldUpdate(Container $container = null)
	{
		if (!($container instanceof Container))
		{
			$container = Container::getInstance('com_akeebasubs');
		}

		$lastTimestamp = self::getLastUpdateTimestamp($container);

		$jNextUpdate = $container->platform->getDate($lastTimestamp);
		$interval = new \DateInterval('P1D');
		$jNextUpdate->add($interval);

		$jNow = $container->platform->getDate();

		return $jNow->toUnix() > $jNextUpdate->toUnix();
	}

	/**
	 * Returns the current Akeeba Subscriptions container object
	 *
	 * @return  Container
	 */
	protected static function getContainer()
	{
		static $container = null;

		if (is_null($container))
		{
			$container = Container::getInstance('com_akeebasubs');
		}

		return $container;
	}
}