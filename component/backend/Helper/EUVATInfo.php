<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Helper;

use FOF30\Container\Container;
use JFactory;
use JHttpFactory;
use SoapClient;
use SoapFault;

defined('_JEXEC') or die;

/**
 * Helper functions for European Union VAT tax calculations.
 *
 * @see http://ec.europa.eu/taxation_customs/resources/documents/taxation/vat/how_vat_works/rates/vat_rates_en.pdf
 */
abstract class EUVATInfo
{
	/**
	 * European Union VAT Information
	 *
	 * Format: hash array of Country code => array(Country name, VAT prefix, VAT Rate)
	 *
	 * @var array
	 */
	public static $EuropeanUnionVATInformation = array(
		// Core member states
		'BE' => array('Belgium', 'BE', 21),
		'BG' => array('Bulgaria', 'BG', 20),
		'CZ' => array('Czech Rebulic', 'CZ', 21),
		'DK' => array('Denmark', 'DK', 25),
		'DE' => array('Germany', 'DE', 19),
		'EE' => array('Estonia', 'EE', 20),
		'GR' => array('Greece', 'EL', 24),
		'ES' => array('Spain', 'ES', 21),
		'FR' => array('France', 'FR', 20),
		'HR' => array('Croatia', 'HR', 25),
		'IE' => array('Ireland', 'IE', 23),
		'IT' => array('Italy', 'IT', 22),
		'CY' => array('Cyprus', 'CY', 19),
		'LV' => array('Latvia', 'LV', 21),
		'LT' => array('Lithuania', 'LT', 21),
		'LU' => array('Luxembourg', 'LU', 17),
		'HU' => array('Hungary', 'HU', 27),
		'MT' => array('Malta', 'MT', 18),
		'NL' => array('Netherlands', 'NL', 21),
		'AT' => array('Austria', 'AT', 20),
		'PL' => array('Poland', 'PL', 23),
		'PT' => array('Portugal', 'PT', 23),
		'RO' => array('Romania', 'RO', 19),
		'SI' => array('Slovenia', 'SI', 22),
		'SK' => array('Slovakia', 'SK', 20),
		'FI' => array('Finland', 'FI', 24),
		'SE' => array('Sweden', 'SE', 25),
		'GB' => array('United Kingdom', 'GB', 20),
		// Special cases of countries which belong to a core member state for VAT calculation
		'MC' => array('Monaco', 'FR', 20), // Monaco -> France
		'IM' => array('Isle of Man', 'GB', 20),  // Isle of Man -> Great Britain
	);

	protected static $cache = array();

	/**
	 * Returns a list of the country codes of all of the countries which are part of the European Union VAT Territory
	 *
	 * @return  array
	 */
	public static function getEUVATCountries()
	{
		return array_keys(self::$EuropeanUnionVATInformation);
	}

	/**
	 * Are the European Union VAT rules applicable to a given country?
	 *
	 * @param   string $countryCode The country code to check
	 *
	 * @return  bool  True if it's a country where EU VAT is applicable
	 */
	public static function isEUVATCountry($countryCode)
	{
		$countryCode = strtoupper($countryCode);

		return array_key_exists($countryCode, self::$EuropeanUnionVATInformation);
	}

	/**
	 * Get the EU VAT number prefix for a country. This may not be the same as the country name, e.g. the Greek VAT
	 * number prefix is EL instead of GR. Moreover we have sovereign territories which belong to a member state for
	 * taxation, e.g. Isle of Man (IM) gets British (GB) VAT numbers.
	 *
	 * @param   string $countryCode The country code to get the EU VAT prefix for
	 *
	 * @return  string  The VAT number prefix or an empty string if this is not an EU VAT Territory country
	 */
	public static function getEUVATPrefix($countryCode)
	{
		$countryCode = strtoupper($countryCode);

		if (!self::isEUVATCountry($countryCode))
		{
			return '';
		}

		$info = self::$EuropeanUnionVATInformation[$countryCode];

		return $info[1];
	}

	/**
	 * Get the applicable standard VAT tax rate for an EU country.
	 *
	 * @param   string $countryCode The country code to get the EU VAT rate for
	 *
	 * @return  float  The VAT rate or zero if this is not an EU VAT Territory country
	 */
	public static function getEUVATRate($countryCode)
	{
		$countryCode = strtoupper($countryCode);

		if (!self::isEUVATCountry($countryCode))
		{
			return 0;
		}

		$info = self::$EuropeanUnionVATInformation[$countryCode];

		return $info[2];
	}

	public static function isVIESValidVATNumber($country, $vat)
	{
		$session = Container::getInstance('com_akeebasubs')->session;

		// Get the VAT validation cache from the session
		if (!array_key_exists('vat', self::$cache))
		{
			$encodedCacheData = $session->get('vat_validation_cache_data', null, 'com_akeebasubs');

			if (!empty($encodedCacheData))
			{
				self::$cache = json_decode($encodedCacheData, true);
			}
			else
			{
				self::$cache = array();
			}
		}

		if (!is_array(self::$cache))
		{
			self::$cache = array();
		}

		// Sanitize the VAT number
		$vatFormatCheck = self::checkVATFormat($country, $vat);

		$vat    = $vatFormatCheck->vatnumber;
		$prefix = $vatFormatCheck->prefix;

		if (!$vatFormatCheck->valid || empty($vat))
		{
			return false;
		}

		$key = $prefix . $vat;
		$ret = null;

		if (array_key_exists('vat', self::$cache))
		{
			if (array_key_exists($key, self::$cache['vat']))
			{
				$ret = self::$cache['vat'][$key];
			}
		}

		if (!is_null($ret))
		{
			return $ret;
		}

		if (class_exists('SoapClient'))
		{
			// Using the SOAP API
			// Code credits: Angel Melguiz / KMELWEBDESIGN SLNE (www.kmelwebdesign.com)
			try
			{
				$sOptions = array(
					'user_agent'         => 'PHP',
					'connection_timeout' => 5,
				);
				$sClient  = new SoapClient('http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl', $sOptions);
				$params   = array('countryCode' => $prefix, 'vatNumber' => $vat);

				for ($i = 0; $i < 2; $i ++)
				{
					$response = $sClient->checkVat($params);
					if (is_object($response))
					{
						$ret = ($response->valid ? true : false);
						break;
					}
					else
					{
						sleep(1);
					}
				}
			}
			catch (SoapFault $e)
			{
			}
		}

		if (is_null($ret))
		{
			try
			{
				$http = JHttpFactory::getHttp();
				$url  = 'http://ec.europa.eu/taxation_customs/vies/viesquer.do?locale=en'
				        . '&ms=' . urlencode($prefix)
				        . '&iso=' . urlencode($prefix)
				        . '&vat=' . urlencode($vat);

				for ($i = 0; $i < 2; $i ++)
				{
					$response = $http->get($url, null, 5);
					if ($response->code >= 200 && $response->code < 400)
					{
						if (preg_match('/invalid VAT number/i', $response->body))
						{
							$ret = false;
							break;
						}
						elseif (preg_match('/valid VAT number/i', $response->body))
						{
							$ret = true;
							break;
						}
					}
					else
					{
						sleep(1);
					}
				}
			}
			catch (\RuntimeException $e)
			{
			}
		}

		if (is_null($ret))
		{
			$ret = false;
		}

		// Cache the result
		if (!array_key_exists('vat', self::$cache))
		{
			self::$cache['vat'] = array();
		}

		self::$cache['vat'][$key] = $ret;

		$encodedCacheData = json_encode(self::$cache);
		$session->set('vat_validation_cache_data', $encodedCacheData, 'com_akeebasubs');

		// Return the result
		return $ret;
	}

	/**
	 * Sanitizes the VAT number and checks if it's valid for a specific country.
	 * Ref: http://ec.europa.eu/taxation_customs/vies/faq.html#item_8
	 *
	 * @param   string $country Country code
	 * @param   string $vat     VAT number to check
	 *
	 * @return   \stdClass  The VAT number and the format validity check (prefix, vatnumber, valid)
	 */
	public static function checkVATFormat($country, $vat)
	{
		$ret = (object)array(
			'prefix'    => $country,
			'vatnumber' => $vat,
			'valid'     => true
		);

		list($vat, $prefix) = self::sanitizeVATNumber($country, $vat);

		if (empty($vat))
		{
			return (object)array(
				'prefix'    => $country,
				'vatnumber' => '',
				'valid'     => false
			);
		}

		$ret->prefix = $prefix;
		$ret->vatnumber = $vat;

		switch ($ret->prefix)
		{
			case 'AT':
				// AUSTRIA
				// VAT number is called: MWST.
				// Format: U + 8 numbers

				if (strlen($vat) != 9)
				{
					$ret->valid = false;
				}
				if ($ret->valid)
				{
					if (substr($vat, 0, 1) != 'U')
					{
						$ret->valid = false;
					}
				}
				if ($ret->valid)
				{
					$rest = substr($vat, 1);
					if (preg_replace('/[0-9]/', '', $rest) != '')
					{
						$ret->valid = false;
					}
				}
				break;

			case 'BG':
				// BULGARIA
				// Format: 9 or 10 digits
				if ((strlen($vat) != 10) && (strlen($vat) != 9))
				{
					$ret->valid = false;
				}
				if ($ret->valid)
				{
					if (preg_replace('/[0-9]/', '', $vat) != '')
					{
						$ret->valid = false;
					}
				}
				break;

			case 'CY':
				// CYPRUS
				// Format: 8 digits and a trailing letter
				if (strlen($vat) != 9)
				{
					$ret->valid = false;
				}
				if ($ret->valid)
				{
					$check = substr($vat, -1);
					if (preg_replace('/[0-9]/', '', $check) == '')
					{
						$ret->valid = false;
					}
				}
				if ($ret->valid)
				{
					$check = substr($vat, 0, -1);
					if (preg_replace('/[0-9]/', '', $check) != '')
					{
						$ret->valid = false;
					}
				}
				break;

			case 'CZ':
				// CZECH REPUBLIC
				// Format: 8, 9 or 10 digits
				$len = strlen($vat);
				if (!in_array($len, array(8, 9, 10)))
				{
					$ret->valid = false;
				}
				if ($ret->valid)
				{
					if (preg_replace('/[0-9]/', '', $vat) != '')
					{
						$ret->valid = false;
					}
				}
				break;

			case 'BE':
				// BELGIUM
				// VAT number is called: BYW.
				// Format: 9 digits
				if ((strlen($vat) == 10) && (substr($vat, 0, 1) == '0'))
				{
					if (preg_replace('/[0-9]/', '', $vat) != '')
					{
						$ret->valid = false;
					}
					break;
				}
				break;

			case 'DE':
				// GERMANY
				// VAT number is called: MWST.
				// Format: 9 digits
			case 'GR':
			case 'EL':
				// GREECE
				// VAT number is called: ΑΦΜ.
				// Format: 9 digits
			case 'PT':
				// PORTUGAL
				// VAT number is called: IVA.
				// Format: 9 digits
			case 'EE':
				// ESTONIA
				// Format: 9 digits
				if (strlen($vat) != 9)
				{
					$ret->valid = false;
				}
				if ($ret->valid)
				{
					if (preg_replace('/[0-9]/', '', $vat) != '')
					{
						$ret->valid = false;
					}
				}
				break;

			case 'DK':
				// DENMARK
				// VAT number is called: MOMS.
				// Format: 8 digits
			case 'FI':
				// FINLAND
				// VAT number is called: ALV.
				// Format: 8 digits
			case 'LU':
				// LUXEMBURG
				// VAT number is called: TVA.
				// Format: 8 digits
			case 'HU':
				// HUNGARY
				// Format: 8 digits
			case 'MT':
				// MALTA
				// Format: 8 digits
			case 'SI':
				// SLOVENIA
				// Format: 8 digits
				if (strlen($vat) != 8)
				{
					$ret->valid = false;
				}
				if ($ret->valid)
				{
					if (preg_replace('/[0-9]/', '', $vat) != '')
					{
						$ret->valid = false;
					}
				}
				break;

			case 'FR':
				// FRANCE
				// VAT number is called: TVA.
				// Format: 11 digits; or 10 digits and a letter; or 9 digits and two letters
				// Eg: 12345678901 or X2345678901 or 1X345678901 or XX345678901
				if (strlen($vat) != 11)
				{
					$ret->valid = false;
				}
				if ($ret->valid)
				{
					// Letters O and I are forbidden
					if (strstr($vat, 'O'))
					{
						$ret->valid = false;
					}
					if (strstr($vat, 'I'))
					{
						$ret->valid = false;
					}
				}
				if ($ret->valid)
				{
					$valid = false;
					// Case I: no letters
					if (preg_replace('/[0-9]/', '', $vat) == '')
					{
						$valid = true;
					}

					// Case II: first character is letter, rest is numbers
					if (!$valid)
					{
						if (preg_replace('/[0-9]/', '', substr($vat, 1)) == '')
						{
							$valid = true;
						}
					}

					// Case III: second character is letter, rest is numbers
					if (!$valid)
					{
						$check = substr($vat, 0, 1) . substr($vat, 2);
						if (preg_replace('/[0-9]/', '', $check) == '')
						{
							$valid = true;
						}
					}

					// Case IV: first two characters are letters, rest is numbers
					if (!$valid)
					{
						$check = substr($vat, 2);
						if (preg_replace('/[0-9]/', '', $check) == '')
						{
							$valid = true;
						}
					}

					$ret->valid = $valid;
				}
				break;

			case 'IE':
				// IRELAND
				// VAT number is called: VAT.
				// Format: seven digits and a letter; or six digits and two letters
				// Eg: 1234567X or 1X34567X
				if (strlen($vat) != 8)
				{
					$ret->valid = false;
				}
				if ($ret->valid)
				{
					// The last position must be a letter
					$check = substr($vat, -1);
					if (preg_replace('/[0-9]/', '', $check) == '')
					{
						$ret->valid = false;
					}
				}
				if ($ret->valid)
				{
					// Skip the second position (it's a number or letter, who cares), check the rest
					$check = substr($vat, 0, 1) . substr($vat, 2, -1);
					if (preg_replace('/[0-9]/', '', $check) != '')
					{
						$ret->valid = false;
					}
				}
				break;

			case 'IT':
				// ITALY
				// VAT number is called: IVA.
				// Format: 11 digits
				if (strlen($vat) != 11)
				{
					$ret->valid = false;
				}
				if ($ret->valid)
				{
					if (preg_replace('/[0-9]/', '', $vat) != '')
					{
						$ret->valid = false;
					}
				}
				break;

			case 'LT':
				// LITHUANIA
				// Format: 9 or 12 digits
				if ((strlen($vat) != 9) && (strlen($vat) != 12))
				{
					$ret->valid = false;
				}
				if ($ret->valid)
				{
					if (preg_replace('/[0-9]/', '', $vat) != '')
					{
						$ret->valid = false;
					}
				}
				break;

			case 'LV':
				// LATVIA
				// Format: 11 digits
				if ((strlen($vat) != 11))
				{
					$ret->valid = false;
				}
				if ($ret->valid)
				{
					if (preg_replace('/[0-9]/', '', $vat) != '')
					{
						$ret->valid = false;
					}
				}
				break;

			case 'PL':
				// POLAND
				// Format: 10 digits
			case 'SK':
				// SLOVAKIA
				// Format: 10 digits
				if ((strlen($vat) != 10))
				{
					$ret->valid = false;
				}
				if ($ret->valid)
				{
					if (preg_replace('/[0-9]/', '', $vat) != '')
					{
						$ret->valid = false;
					}
				}
				break;

			case 'RO':
				// ROMANIA
				// Format: 2 to 10 digits
				$len = strlen($vat);
				if (($len < 2) || ($len > 10))
				{
					$ret->valid = false;
				}
				if ($ret->valid)
				{
					if (preg_replace('/[0-9]/', '', $vat) != '')
					{
						$ret->valid = false;
					}
				}
				break;

			case 'NL':
				// NETHERLANDS
				// VAT number is called: BTW.
				// Format: 12 characters long, first 9 characters are numbers, last three characters are B01 to B99
				if (strlen($vat) != 12)
				{
					$ret->valid = false;
				}
				if ($ret->valid)
				{
					if ((substr($vat, 9, 1) != 'B'))
					{
						$ret->valid = false;
					}
				}
				if ($ret->valid)
				{
					$check = substr($vat, 0, 9) . substr($vat, 11);
					if (preg_replace('/[0-9]/', '', $check) == '')
					{
						$ret->valid = true;
					}
				}
				break;

			case 'ES':
				// SPAIN
				// VAT number is called: IVA.
				// Format: Eight digits and one letter; or seven digits and two letters
				// E.g.: X12345678 or 12345678X or X1234567X
				if (strlen($vat) != 9)
				{
					$ret->valid = false;
				}
				if ($ret->valid)
				{
					// If first is number last must be letter
					$check = substr($vat, 0, 1);
					if (preg_replace('/[0-9]/', '', $check) == '')
					{
						$check = substr($vat, 0);
						if (preg_replace('/[0-9]/', '', $check) == '')
						{
							$ret->valid = false;
						}
					}
				}
				if ($ret->valid)
				{
					// If first is not a number, the  last can be anything; just check the middle
					$check = substr($vat, 1, -1);
					if (preg_replace('/[0-9]/', '', $check) != '')
					{
						$ret->valid = false;
					}
				}
				break;

			case 'SE':
				// SWEDEN
				// VAT number is called: MOMS.
				// Format: Twelve digits, last two must be 01
				if (strlen($vat) != 12)
				{
					$ret->valid = false;
				}
				if ($ret->valid)
				{
					if (substr($vat, -2) != '01')
					{
						$ret->valid = false;
					}
				}
				if ($ret->valid)
				{
					if (preg_replace('/[0-9]/', '', $vat) != '')
					{
						$ret->valid = false;
					}
				}
				break;

			case 'GB':
				// UNITED KINGDOM
				// VAT number is called: VAT.
				// Format: Nine or twelve digits; or 5 characters (alphanumeric)
				if (strlen($vat) == 5)
				{
					break;
				}
				if ((strlen($vat) != 9) && (strlen($vat) != 12))
				{
					$ret->valid = false;
				}
				if ($ret->valid)
				{
					if (preg_replace('/[0-9]/', '', $vat) != '')
					{
						$ret->valid = false;
					}
				}
				break;

			case 'HR':
				// CROATIA
				// VAT number is called: PDV.
				// Format: 11 digits
				if (strlen($vat) != 11)
				{
					$ret->valid = false;
				}

				if ($ret->valid)
				{
					if (preg_replace('/[0-9]/', '', $vat) != '')
					{
						$ret->valid = false;
					}
				}
				break;

			default:
				$allowNonEUVAT = self::getContainer()->params->get('noneuvat', 0);
				$ret->valid = $allowNonEUVAT ? true : false;
				break;
		}

		return $ret;
	}

	/**
	 * Sanitize a VAT number
	 *
	 * @param   string $country The country code
	 * @param   string $vat     The unsanitized VAT number
	 *
	 * @return array
	 */
	public static function sanitizeVATNumber($country, $vat)
	{
		$vat = trim(strtoupper($vat));
		$vat = trim($vat, " \t\n\r\0"); // Yes we need to run this extra trim before...
		$vat = preg_replace(array('/\s+/', '/[^A-Za-z0-9\-_]/'), array('', ''), $vat);
		$vat = trim($vat, " \t\n\r\0"); // ...and after

		// Get the VAT number prefix
		$prefix = self::getEUVATPrefix($country);

		// Remove the VAT/country prefix if present from the VAT number
		if (substr($vat, 0, 2) == $prefix)
		{
			$vat = trim(substr($vat, 2));
		}
		elseif (substr($vat, 0, 2) == $country)
		{
			$vat = trim(substr($vat, 2));
		}

		return array($vat, $prefix);
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
