<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Helper;

use Akeeba\Subscriptions\Admin\Model\Levels;
use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use Akeeba\Subscriptions\Admin\Model\Users;
use FOF30\Container\Container;
use FOF30\Date\Date;
use FOF30\Model\DataModel;
use JFactory;
use JLoader;
use JText;
use Joomla\Registry\Registry as JRegistry;
use Akeeba\ReleaseSystem\Site\Helper\Filter as Filter;

defined('_JEXEC') or die;

/**
 * A helper class for sending out emails
 */
abstract class Message
{
	/**
	 * The component's container
	 *
	 * @var   Container
	 */
	protected static $container;

	/**
	 * Returns the component's container
	 *
	 * @return  Container
	 */
	protected static function getContainer()
	{
		if (is_null(self::$container))
		{
			self::$container = Container::getInstance('com_akeebasubs');
		}

		return self::$container;
	}

	/**
	 * Pre-processes the message text in $text, replacing merge tags with those
	 * fetched based on subscription $sub
	 *
	 * @param   string         $text               The message to process
	 * @param   Subscriptions  $sub                A subscription object
	 * @param   bool           $businessInfoAware  If true and the user is not a business the business info will be left blank
	 *
	 * @return  string  The processed string
	 */
	public static function processSubscriptionTags($text, $sub, $extras = array(), $businessInfoAware = false)
	{
		// Get the user object for this subscription
		$joomlaUser = self::getContainer()->platform->getUser($sub->user_id);

		// Get the extra user parameters object for the subscription
		/** @var Users $subsUser */
		$subsUser = $sub->user;

		if (
			!is_object($subsUser)
			||
			(($sub->user instanceof Users) && ($sub->user->user_id != $sub->user_id))
		)
		{
			$subsUser = Container::getInstance('com_akeebasubs')->factory->model('Users')->tmpInstance();
		}

		// Get the subscription level
		/** @var Levels $level */
		$level = $sub->level;

		if (
			!is_object($level)
			||
			(($sub->level instanceof Levels) && ($sub->level->akeebasubs_level_id != $sub->akeebasubs_level_id))
		)
		{
			/** @var Levels $levelModel */
			$levelModel = Container::getInstance('com_akeebasubs')->factory->model('Levels')->tmpInstance();
			$level      = $levelModel->id($sub->akeebasubs_level_id)->firstOrNew();
		}

		// Merge the user objects
		$userData = array_merge((array)$joomlaUser, (array)($subsUser->getData()));

		// Create and replace merge tags for subscriptions. Format [SUB:KEYNAME]
		if ($sub instanceof DataModel)
		{
			$subData = (array)($sub->getData());
		}
		else
		{
			// Why am I here?!
			$subData = (array)$sub;
		}

		$currency_name = self::getContainer()->params->get('currency', 'EUR');
		$currency_alt  = self::getContainer()->params->get('invoice_altcurrency', '');
		$exchange_rate = 0;

		// Let's get the exchange rate (rates are automatically updated)
		if ($currency_alt)
		{
			$exchange_rate = Forex::exhangeRate($currency_name, $currency_alt, self::getContainer());
		}

		foreach ($subData as $k => $v)
		{
			if (is_array($v) || is_object($v))
			{
				continue;
			}

			if (substr($k, 0, 1) == '_')
			{
				continue;
			}

			if ($k == 'akeebasubs_subscription_id')
			{
				$k = 'id';
			}

			$tag = '[SUB:' . strtoupper($k) . ']';

			if (in_array($k, array(
				'net_amount',
				'gross_amount',
				'tax_amount',
				'prediscount_amount',
				'discount_amount',
				'affiliate_comission',
				'net_amount_alt',
				'gross_amount_alt',
				'tax_amount_alt',
				'prediscount_amount_alt',
				'discount_amount_alt',
				'affiliate_comission_alt'
			)))
			{
				$v = sprintf('%.2f', $v);
			}

			$text = str_replace($tag, $v, $text);
		}

		// Create and replace merge tags for the subscription level. Format [LEVEL:KEYNAME]
		$levelData = [];

		if ($level instanceof Levels)
		{
			$levelData = (array)($level->getData());
		}

		foreach ($levelData as $k => $v)
		{
			if (is_array($v) || is_object($v))
			{
				continue;
			}

			if (substr($k, 0, 1) == '_')
			{
				continue;
			}

			if ($k == 'akeebasubs_level_id')
			{
				$k = 'id';
			}

			$tag  = '[LEVEL:' . strtoupper($k) . ']';
			$text = str_replace($tag, $v, $text);
		}

		// Create and replace merge tags for custom per-subscription data. Format [SUBCUSTOM:KEYNAME]
		if (array_key_exists('params', $subData))
		{
			if (is_string($subData['params']))
			{
				$custom = json_decode($subData['params'], true);
			}
			elseif (is_array($subData['params']))
			{
				$custom = $subData['params'];
			}
			elseif (is_object($subData['params']))
			{
				$custom = (array)$subData['params'];
			}
			else
			{
				$custom = array();
			}

			// Extra check for subcustom params: if you save a subscription form the backend,
			// custom fields are inside an array named subcustom
			if (is_array($custom) && isset($custom['subcustom']))
			{
				$custom = $custom['subcustom'];
			}

			if (is_object($custom))
			{
				$custom = (array)$custom;
			}

			if (!empty($custom))
			{
				foreach ($custom as $k => $v)
				{
					if (is_object($v))
					{
						continue;
					}

					if (substr($k, 0, 1) == '_')
					{
						continue;
					}

					$tag = '[SUBCUSTOM:' . strtoupper($k) . ']';

					if (is_array($v))
					{
						continue;
					}

					$text = str_replace($tag, $v, $text);
				}
			}
		}

		// If this is not a business and the $businessInfoAware flag is set blank out the business name, occupation,
		// VAT number and tax authority fields when processing the message.
		$businessOnlyFields = [];

		if ($businessInfoAware && !$userData['isbusiness'])
		{
			$businessOnlyFields = ['businessname', 'occupation', 'vatnumber', 'taxauthority'];
		}

		// Create and replace merge tags for user data. Format [USER:KEYNAME]
		$EUCountryInfo = EUVATInfo::$EuropeanUnionVATInformation;

		foreach ($userData as $k => $v)
		{
			if (is_object($v) || is_array($v))
			{
				continue;
			}

			if (substr($k, 0, 1) == '_')
			{
				continue;
			}

			if ($k == 'akeebasubs_subscription_id')
			{
				$k = 'id';
			}

			if ($k == 'vatnumber')
			{
				$country = $userData['country'];

				if (array_key_exists($country, $EUCountryInfo))
				{
					$v = $EUCountryInfo[$country][1] . $v;
				}
			}

			if (in_array($k, $businessOnlyFields))
			{
				$v = '';
			}

			$tag  = '[USER:' . strtoupper($k) . ']';
			$text = str_replace($tag, $v, $text);
		}

		// Create and replace merge tags for custom fields data. Format [CUSTOM:KEYNAME]
		if (array_key_exists('params', $userData))
		{
			if (is_string($userData['params']))
			{
				$custom = json_decode($userData['params']);
			}
			elseif (is_array($userData['params']))
			{
				$custom = $userData['params'];
			}
			elseif (is_object($userData['params']))
			{
				$custom = (array)$userData['params'];
			}
			else
			{
				$custom = array();
			}

			if (!empty($custom))
			{
				foreach ($custom as $k => $v)
				{
					if (substr($k, 0, 1) == '_')
					{
						continue;
					}

					$tag = '[CUSTOM:' . strtoupper($k) . ']';

					if ($v instanceof \stdClass)
					{
						$v = (array)$v;
					}

					if (is_array($v))
					{
						$v = implode(', ', $v);
					}

					$text = str_replace($tag, $v, $text);
				}
			}
		}

		// Extra variables replacement
		// -- Coupon code
		$couponCode = '';

		if ($sub->akeebasubs_coupon_id)
		{
			try
			{
				$couponData = Container::getInstance('com_akeebasubs')
					->factory->model('Coupons')->tmpInstance()
					->findOrFail($sub->akeebasubs_coupon_id);

				$couponCode = $couponData->coupon;
			}
			catch (\RuntimeException $e)
			{
				$couponCode = '';
			}
		}

		// -- Get the site name
		$config   = self::getContainer()->platform->getConfig();
		$sitename = $config->get('sitename');

		// -- First/last name
		$fullname  = $joomlaUser->name;
		$nameParts = explode(' ', $fullname, 2);
		$firstname = array_shift($nameParts);
		$lastname  = !empty($nameParts) ? array_shift($nameParts) : '';

		// -- Site URL
		$container = Container::getInstance('com_akeebasubs');
		$isCli     = $container->platform->isCli();
		$isAdmin   = $container->platform->isBackend();

		if ($isCli)
		{
			JLoader::import('joomla.application.component.helper');
			$baseURL    = \JComponentHelper::getParams('com_akeebasubs')->get('siteurl', 'http://www.example.com');
			$temp       = str_replace('http://', '', $baseURL);
			$temp       = str_replace('https://', '', $temp);
			$parts      = explode($temp, '/', 2);
			$subpathURL = count($parts) > 1 ? $parts[1] : '';
		}
		else
		{
			$baseURL    = \JURI::base();
			$subpathURL = \JURI::base(true);
		}

		$baseURL    = str_replace('/administrator', '', $baseURL);
		$subpathURL = str_replace('/administrator', '', $subpathURL);
		$subpathURL = ltrim($subpathURL, '/');

		// -- My Subscriptions URL
		if ($isAdmin || $isCli)
		{
			$url = 'index.php?option=com_akeebasubs&view=Subscriptions';
		}
		else
		{
			$url =
				str_replace('&amp;', '&', \JRoute::_('index.php?option=com_akeebasubs&view=Subscriptions&layout=default'));
		}

		$url = ltrim($url, '/');

		if (substr($url, 0, strlen($subpathURL) + 1) == "$subpathURL/")
		{
			$url = substr($url, strlen($subpathURL) + 1);
		}

		$mysubsurl = rtrim($baseURL, '/') . '/' . ltrim($url, '/');

		// -- Renewal URL
		$slug = $level->slug;
		$url  = 'index.php?option=com_akeebasubs&view=Level&slug=' . $slug . '&layout=default';

		if (!$isAdmin && !$isCli)
		{
			$url = str_replace('&amp;', '&', \JRoute::_($url));
		}

		$url = ltrim($url, '/');

		if (substr($url, 0, strlen($subpathURL) + 1) == "$subpathURL/")
		{
			$url = substr($url, strlen($subpathURL) + 1);
		}

		$renewalURL = rtrim($baseURL, '/') . '/' . ltrim($url, '/');

		// Currency
		$currency     = self::getContainer()->params->get('currency', 'EUR');
		$symbol       = self::getContainer()->params->get('currencysymbol', 'EUR');
		$alt_symbol   = '';
		$currency_alt = self::getContainer()->params->get('invoice_altcurrency', '');

		if ($currency_alt)
		{
			$alt_symbol = Forex::getCurrencySymbol($currency_alt);
		}

		// Dates
		JLoader::import('joomla.utilities.date');
		$jFrom = new Date($sub->publish_up);
		$jTo   = new Date($sub->publish_down);

		// Download ID

		if (!class_exists('Akeeba\ReleaseSystem\Site\Helper\Filter') && file_exists(JPATH_SITE . '/components/com_ars/Helper/Filter.php'))
		{
			@include_once JPATH_SITE . '/components/com_ars/Helper/Filter.php';
		}

		$dlid = '';

		if (class_exists('Akeeba\ReleaseSystem\Site\Helper\Filter'))
		{
			$dlid = Filter::myDownloadID($sub->user_id);
		}

		// User's state, human readable
		$formatted_state = '';
		$state           = $subsUser->getFieldValue('state', 'N');

		if (!empty($state))
		{
			$formatted_state = Select::formatState($state);
		}

		// User's country, human readable
		$formatted_country = '';
		$country           = $subsUser->country;

		if (!empty($country))
		{
			$formatted_country = Select::formatCountry($country);
		}

		// -- The actual replacement
		$extras = array_merge(array(
			"\\n"                      => "\n",
			'[SITENAME]'               => $sitename,
			'[SITEURL]'                => $baseURL,
			'[FULLNAME]'               => $fullname,
			'[FIRSTNAME]'              => $firstname,
			'[LASTNAME]'               => $lastname,
			'[USERNAME]'               => $joomlaUser->username,
			'[USEREMAIL]'              => $joomlaUser->email,
			'[LEVEL]'                  => $level->title,
			'[SLUG]'                   => $level->slug,
			'[RENEWALURL]'             => $renewalURL,
			'[RENEWALURL:]'            => $renewalURL, // Malformed tag without a coupon code...
			'[ENABLED]'                => JText::_('COM_AKEEBASUBS_SUBSCRIPTION_COMMON_' . ($sub->enabled ? 'ENABLED' :
					'DISABLED')),
			'[PAYSTATE]'               => JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE_' . $sub->getFieldValue('state', 'N')),
			'[PUBLISH_UP]'             => $jFrom->format(JText::_('DATE_FORMAT_LC2'), true),
			'[PUBLISH_UP_EU]'          => $jFrom->format('d/m/Y H:i:s', true),
			'[PUBLISH_UP_USA]'         => $jFrom->format('m/d/Y h:i:s a', true),
			'[PUBLISH_UP_JAPAN]'       => $jFrom->format('Y/m/d H:i:s', true),
			'[PUBLISH_DOWN]'           => $jTo->format(JText::_('DATE_FORMAT_LC2'), true),
			'[PUBLISH_DOWN_EU]'        => $jTo->format('d/m/Y H:i:s', true),
			'[PUBLISH_DOWN_USA]'       => $jTo->format('m/d/Y h:i:s a', true),
			'[PUBLISH_DOWN_JAPAN]'     => $jTo->format('Y/m/d H:i:s', true),
			'[MYSUBSURL]'              => $mysubsurl,
			'[URL]'                    => $mysubsurl,
			'[CURRENCY]'               => $currency,
			'[CURRENCY_ALT]'           => $currency_alt,
			'[$]'                      => $symbol,
			'[$_ALT]'                  => $alt_symbol,
			'[EXCHANGE_RATE]'          => $exchange_rate,
			'[DLID]'                   => $dlid,
			'[COUPONCODE]'             => $couponCode,
			'[USER:STATE_FORMATTED]'   => $formatted_state,
			'[USER:COUNTRY_FORMATTED]' => $formatted_country,
			// Legacy keys
			'[NAME]'                   => $firstname,
			'[STATE]'                  => JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE_' . $sub->getFieldValue('state', 'N')),
			'[FROM]'                   => $jFrom->format(JText::_('DATE_FORMAT_LC2'), true),
			'[TO]'                     => $jTo->format(JText::_('DATE_FORMAT_LC2'), true),
		), $extras);

		foreach ($extras as $key => $value)
		{
			$text = str_replace($key, $value, $text);
		}

		// Special replacement for RENEWALURL:COUPONCODE
		$text = self::substituteRenewalURLWithCoupon($text, $renewalURL);

		$container->platform->runPlugins('onAkeebasubsAfterProcessTags', array(&$text, $sub, $extras));

		return $text;
	}

	/**
	 * Processes the language merge tags ([IFLANG langCode], [/IFLANG]) in some
	 * block of text.
	 *
	 * @param   string  $text  The text to process
	 * @param   string  $lang  Which language to keep. Null means the default language.
	 *
	 * @return  string
	 */
	public static function processLanguage($text, $lang = null)
	{
		// Get the default language
		if (empty($lang))
		{
			$enableTranslation = JFactory::getApplication()->getLanguageFilter();

			if ($enableTranslation)
			{
				$lang = JFactory::getLanguage()->getTag();
			}
			else
			{
				$user = self::getContainer()->platform->getUser();

				if (property_exists($user, 'language'))
				{
					$lang = $user->language;
				}
				else
				{
					$params = $user->params;

					if (!is_object($params))
					{
						JLoader::import('joomla.registry.registry');
						$params = new JRegistry($params);
					}

					$lang = $params->get('language', '');
				}
				if (empty($lang))
				{
					$lang = \JComponentHelper::getParams('com_languages')->get('site', 'en-GB');
				}
			}
		}

		// Find languages
		$translations = array();

		while (strpos($text, '[IFLANG ') !== false)
		{
			$start                     = strpos($text, '[IFLANG ');
			$end                       = strpos($text, '[/IFLANG]');
			$langEnd                   = strpos($text, ']', $start);
			$langCode                  = substr($text, $start + 8, $langEnd - $start - 8);
			$langText                  = substr($text, $langEnd + 1, $end - $langEnd - 1);
			$translations[ $langCode ] = $langText;

			if ($start > 0)
			{
				$temp = substr($text, 0, $start - 1);
			}
			else
			{
				$temp = 0;
			}

			$temp .= substr($text, $end + 9);
			$text = $temp;
		}
		if (!empty($text))
		{
			if (!array_key_exists('*', $translations))
			{
				$translations['*'] = $text;
			}
		}

		$siteLang = \JComponentHelper::getParams('com_languages')->get('site', 'en-GB');

		if (array_key_exists($lang, $translations))
		{
			return $translations[ $lang ];
		}
		elseif (array_key_exists($siteLang, $translations))
		{
			return $translations[ $siteLang ];
		}
		elseif (array_key_exists('*', $translations))
		{
			return $translations['*'];
		}
		else
		{
			return $text;
		}
	}

	/**
	 * Substitutes the [RENEWALURL:couponcode] tag in messages
	 *
	 * @param   string  $text        The message text
	 * @param   string  $renewalURL  The base renewal URL (without a coupon code)
	 *
	 * @return  string  The text with the tag replaced with the proper URLs
	 */
	public static function substituteRenewalURLWithCoupon($text, $renewalURL)
	{
		// Find where the tag starts
		$nextPos      = 0;
		$tagStartText = '[RENEWALURL:';

		if (!class_exists('JUri', true))
		{
			JLoader::import('joomla.environment.uri');
			JLoader::import('joomla.uri.uri');
		}

		$uri = new \JUri($renewalURL);

		do
		{
			$pos = strpos($text, $tagStartText, $nextPos);

			if ($pos === false)
			{
				// Not found? No change.
				continue;
			}

			// Get the start position of the coupon name
			$couponStartPos = $pos + strlen($tagStartText);

			// Get the end position of the tag
			$endPos = strpos($text, ']', $couponStartPos);

			// If no end position is found, ignore the tag
			if ($endPos == $couponStartPos)
			{
				$nextPos = $couponStartPos + 1;
				continue;
			}

			// Get the coupon code
			$couponCode = substr($text, $couponStartPos, $endPos - $couponStartPos);

			// Create the URL
			$uri->setVar('coupon', $couponCode);

			$toReplace = substr($text, $pos, $endPos - $pos + 1);
			$text      = str_replace($toReplace, $uri->toString(), $text);
		}
		while ($pos !== false);

		return $text;
	}
}
