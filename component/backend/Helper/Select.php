<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Helper;

use Akeeba\Subscriptions\Admin\Model\PaymentMethods;
use Akeeba\Subscriptions\Admin\Model\States;
use FOF30\Container\Container;
use FOF30\Model\DataModel;
use JFolder;
use JHtml;
use JLoader;
use JText;

defined('_JEXEC') or die;

/**
 * A helper class for drop-down selection boxes
 */
abstract class Select
{

	/**
	 * Maps the two letter codes to country names (in English)
	 *
	 * @var  array
	 */
	public static $countries = array(
		''   => '----',
		'AD' => 'Andorra',
		'AE' => 'United Arab Emirates',
		'AF' => 'Afghanistan',
		'AG' => 'Antigua and Barbuda',
		'AI' => 'Anguilla',
		'AL' => 'Albania',
		'AM' => 'Armenia',
		'AO' => 'Angola',
		'AQ' => 'Antarctica',
		'AR' => 'Argentina',
		'AS' => 'American Samoa',
		'AT' => 'Austria',
		'AU' => 'Australia',
		'AW' => 'Aruba',
		'AX' => 'Aland Islands',
		'AZ' => 'Azerbaijan',
		'BA' => 'Bosnia and Herzegovina',
		'BB' => 'Barbados',
		'BD' => 'Bangladesh',
		'BE' => 'Belgium',
		'BF' => 'Burkina Faso',
		'BG' => 'Bulgaria',
		'BH' => 'Bahrain',
		'BI' => 'Burundi',
		'BJ' => 'Benin',
		'BL' => 'Saint Barthélemy',
		'BM' => 'Bermuda',
		'BN' => 'Brunei Darussalam',
		'BO' => 'Bolivia, Plurinational State of',
		'BQ' => 'Bonaire, Saint Eustatius and Saba',
		'BR' => 'Brazil',
		'BS' => 'Bahamas',
		'BT' => 'Bhutan',
		'BV' => 'Bouvet Island',
		'BW' => 'Botswana',
		'BY' => 'Belarus',
		'BZ' => 'Belize',
		'CA' => 'Canada',
		'CC' => 'Cocos (Keeling) Islands',
		'CD' => 'Congo, the Democratic Republic of the',
		'CF' => 'Central African Republic',
		'CG' => 'Congo',
		'CH' => 'Switzerland',
		'CI' => 'Cote d\'Ivoire',
		'CK' => 'Cook Islands',
		'CL' => 'Chile',
		'CM' => 'Cameroon',
		'CN' => 'China',
		'CO' => 'Colombia',
		'CR' => 'Costa Rica',
		'CU' => 'Cuba',
		'CV' => 'Cape Verde',
		'CW' => 'Curaçao',
		'CX' => 'Christmas Island',
		'CY' => 'Cyprus',
		'CZ' => 'Czech Republic',
		'DE' => 'Germany',
		'DJ' => 'Djibouti',
		'DK' => 'Denmark',
		'DM' => 'Dominica',
		'DO' => 'Dominican Republic',
		'DZ' => 'Algeria',
		'EC' => 'Ecuador',
		'EE' => 'Estonia',
		'EG' => 'Egypt',
		'EH' => 'Western Sahara',
		'ER' => 'Eritrea',
		'ES' => 'Spain',
		'ET' => 'Ethiopia',
		'FI' => 'Finland',
		'FJ' => 'Fiji',
		'FK' => 'Falkland Islands (Malvinas)',
		'FM' => 'Micronesia, Federated States of',
		'FO' => 'Faroe Islands',
		'FR' => 'France',
		'GA' => 'Gabon',
		'GB' => 'United Kingdom',
		'GD' => 'Grenada',
		'GE' => 'Georgia',
		'GF' => 'French Guiana',
		'GG' => 'Guernsey',
		'GH' => 'Ghana',
		'GI' => 'Gibraltar',
		'GL' => 'Greenland',
		'GM' => 'Gambia',
		'GN' => 'Guinea',
		'GP' => 'Guadeloupe',
		'GQ' => 'Equatorial Guinea',
		'GR' => 'Greece',
		'GS' => 'South Georgia and the South Sandwich Islands',
		'GT' => 'Guatemala',
		'GU' => 'Guam',
		'GW' => 'Guinea-Bissau',
		'GY' => 'Guyana',
		'HK' => 'Hong Kong',
		'HM' => 'Heard Island and McDonald Islands',
		'HN' => 'Honduras',
		'HR' => 'Croatia',
		'HT' => 'Haiti',
		'HU' => 'Hungary',
		'ID' => 'Indonesia',
		'IE' => 'Ireland',
		'IL' => 'Israel',
		'IM' => 'Isle of Man',
		'IN' => 'India',
		'IO' => 'British Indian Ocean Territory',
		'IQ' => 'Iraq',
		'IR' => 'Iran, Islamic Republic of',
		'IS' => 'Iceland',
		'IT' => 'Italy',
		'JE' => 'Jersey',
		'JM' => 'Jamaica',
		'JO' => 'Jordan',
		'JP' => 'Japan',
		'KE' => 'Kenya',
		'KG' => 'Kyrgyzstan',
		'KH' => 'Cambodia',
		'KI' => 'Kiribati',
		'KM' => 'Comoros',
		'KN' => 'Saint Kitts and Nevis',
		'KP' => 'Korea, Democratic People\'s Republic of',
		'KR' => 'Korea, Republic of',
		'KW' => 'Kuwait',
		'KY' => 'Cayman Islands',
		'KZ' => 'Kazakhstan',
		'LA' => 'Lao People\'s Democratic Republic',
		'LB' => 'Lebanon',
		'LC' => 'Saint Lucia',
		'LI' => 'Liechtenstein',
		'LK' => 'Sri Lanka',
		'LR' => 'Liberia',
		'LS' => 'Lesotho',
		'LT' => 'Lithuania',
		'LU' => 'Luxembourg',
		'LV' => 'Latvia',
		'LY' => 'Libyan Arab Jamahiriya',
		'MA' => 'Morocco',
		'MC' => 'Monaco',
		'MD' => 'Moldova, Republic of',
		'ME' => 'Montenegro',
		'MF' => 'Saint Martin (French part)',
		'MG' => 'Madagascar',
		'MH' => 'Marshall Islands',
		'MK' => 'Macedonia, the former Yugoslav Republic of',
		'ML' => 'Mali',
		'MM' => 'Myanmar',
		'MN' => 'Mongolia',
		'MO' => 'Macao',
		'MP' => 'Northern Mariana Islands',
		'MQ' => 'Martinique',
		'MR' => 'Mauritania',
		'MS' => 'Montserrat',
		'MT' => 'Malta',
		'MU' => 'Mauritius',
		'MV' => 'Maldives',
		'MW' => 'Malawi',
		'MX' => 'Mexico',
		'MY' => 'Malaysia',
		'MZ' => 'Mozambique',
		'NA' => 'Namibia',
		'NC' => 'New Caledonia',
		'NE' => 'Niger',
		'NF' => 'Norfolk Island',
		'NG' => 'Nigeria',
		'NI' => 'Nicaragua',
		'NL' => 'Netherlands',
		'NO' => 'Norway',
		'NP' => 'Nepal',
		'NR' => 'Nauru',
		'NU' => 'Niue',
		'NZ' => 'New Zealand',
		'OM' => 'Oman',
		'PA' => 'Panama',
		'PE' => 'Peru',
		'PF' => 'French Polynesia',
		'PG' => 'Papua New Guinea',
		'PH' => 'Philippines',
		'PK' => 'Pakistan',
		'PL' => 'Poland',
		'PM' => 'Saint Pierre and Miquelon',
		'PN' => 'Pitcairn',
		'PR' => 'Puerto Rico',
		'PS' => 'Palestinian Territory, Occupied',
		'PT' => 'Portugal',
		'PW' => 'Palau',
		'PY' => 'Paraguay',
		'QA' => 'Qatar',
		'RE' => 'Reunion',
		'RO' => 'Romania',
		'RS' => 'Serbia',
		'RU' => 'Russian Federation',
		'RW' => 'Rwanda',
		'SA' => 'Saudi Arabia',
		'SB' => 'Solomon Islands',
		'SC' => 'Seychelles',
		'SD' => 'Sudan',
		'SE' => 'Sweden',
		'SG' => 'Singapore',
		'SH' => 'Saint Helena, Ascension and Tristan da Cunha',
		'SI' => 'Slovenia',
		'SJ' => 'Svalbard and Jan Mayen',
		'SK' => 'Slovakia',
		'SL' => 'Sierra Leone',
		'SM' => 'San Marino',
		'SN' => 'Senegal',
		'SO' => 'Somalia',
		'SR' => 'Suriname',
		'SS' => 'South Sudan',
		'ST' => 'Sao Tome and Principe',
		'SV' => 'El Salvador',
		'SX' => 'Sint Maarten',
		'SY' => 'Syrian Arab Republic',
		'SZ' => 'Swaziland',
		'TC' => 'Turks and Caicos Islands',
		'TD' => 'Chad',
		'TF' => 'French Southern Territories',
		'TG' => 'Togo',
		'TH' => 'Thailand',
		'TJ' => 'Tajikistan',
		'TK' => 'Tokelau',
		'TL' => 'Timor-Leste',
		'TM' => 'Turkmenistan',
		'TN' => 'Tunisia',
		'TO' => 'Tonga',
		'TR' => 'Turkey',
		'TT' => 'Trinidad and Tobago',
		'TV' => 'Tuvalu',
		'TW' => 'Taiwan',
		'TZ' => 'Tanzania, United Republic of',
		'UA' => 'Ukraine',
		'UG' => 'Uganda',
		'UM' => 'United States Minor Outlying Islands',
		'US' => 'United States',
		'UY' => 'Uruguay',
		'UZ' => 'Uzbekistan',
		'VA' => 'Holy See (Vatican City State)',
		'VC' => 'Saint Vincent and the Grenadines',
		'VE' => 'Venezuela, Bolivarian Republic of',
		'VG' => 'Virgin Islands, British',
		'VI' => 'Virgin Islands, U.S.',
		'VN' => 'Viet Nam',
		'VU' => 'Vanuatu',
		'WF' => 'Wallis and Futuna',
		'WS' => 'Samoa',
		'YE' => 'Yemen',
		'YT' => 'Mayotte',
		'ZA' => 'South Africa',
		'ZM' => 'Zambia',
		'ZW' => 'Zimbabwe'
	);

	/**
	 * Maps countries to state short codes and names
	 *
	 * @var  array
	 */
	public static $states = array();

	/**
	 * Returns a list of all countries except the empty option (no country)
	 *
	 * @return  array
	 */
	public static function getCountriesForHeader()
	{
		static $countries = array();

		if (empty($countries))
		{
			$countries = self::$countries;
			unset($countries['']);
		}

		return $countries;
	}

	/**
	 * Returns a list of all countries including the empty option (no country)
	 *
	 * @return  array
	 */
	public static function getCountries()
	{
		return self::$countries;
	}

	/**
	 * Returns a list of all states
	 *
	 * @return  array
	 */
	public static function getStates()
	{
		static $states = array();

		if (empty($states))
		{
			$states = array();

			foreach (self::$states as $country => $s)
			{
				$states = array_merge($states, $s);
			}
		}

		return $states;
	}

	/**
	 * Returns a list of known invoicing extensions supported by plugins
	 *
	 * @return  array  extension => title
	 */
	public static function getInvoiceExtensions()
	{
		static $invoiceExtensions = null;

		if (is_null($invoiceExtensions))
		{
			$source = Container::getInstance('com_akeebasubs')->factory
				->model('Invoices')->tmpInstance()
				->getExtensions(0);
			$invoiceExtensions = array();

			if (!empty($source))
			{
				foreach ($source as $item)
				{
					$invoiceExtensions[ $item['extension'] ] = $item['title'];
				}
			}
		}

		return $invoiceExtensions;
	}

	/**
	 * Translate a two letter country code into the country name (in English). If the country is unknown the country
	 * code itself is returned.
	 *
	 * @param   string  $cCode  The country code
	 *
	 * @return  string  The name of the country or, of it's not known, the country code itself.
	 */
	public static function decodeCountry($cCode)
	{
		if (array_key_exists($cCode, self::$countries))
		{
			return self::$countries[ $cCode ];
		}
		else
		{
			return $cCode;
		}
	}

	/**
	 * Translate a two letter country code into the country name (in English). If the country is unknown three em-dashes
	 * are returned. This is different to decode country which returns the country code in this case.
	 *
	 * @param   string  $cCode  The country code
	 *
	 * @return  string  The name of the country or, of it's not known, the country code itself.
	 */
	public static function formatCountry($cCode = '')
	{
		$name = self::decodeCountry($cCode);

		if ($name == $cCode)
		{
			$name = '&mdash;';
		}

		return $name;
	}

	/**
	 * Translate the short state code into the full, human-readable state name. If the state is unknown three em-dashes
	 * are returned instead.
	 *
	 * @param   string  $state  The state code
	 *
	 * @return  string  The human readable state name
	 */
	public static function formatState($state)
	{
		$name = '&mdash;';

		foreach (self::$states as $country => $states)
		{
			if (array_key_exists($state, $states))
			{
				$name = $states[ $state ];
			}
		}

		return $name;
	}

	/**
	 * Return a generic drop-down list
	 *
	 * @param   array   $list      An array of objects, arrays, or scalars.
	 * @param   string  $name      The value of the HTML name attribute.
	 * @param   mixed   $attribs   Additional HTML attributes for the <select> tag. This
	 *                             can be an array of attributes, or an array of options. Treated as options
	 *                             if it is the last argument passed. Valid options are:
	 *                             Format options, see {@see JHtml::$formatOptions}.
	 *                             Selection options, see {@see JHtmlSelect::options()}.
	 *                             list.attr, string|array: Additional attributes for the select
	 *                             element.
	 *                             id, string: Value to use as the select element id attribute.
	 *                             Defaults to the same as the name.
	 *                             list.select, string|array: Identifies one or more option elements
	 *                             to be selected, based on the option key values.
	 * @param   mixed   $selected  The key that is selected (accepts an array or a string).
	 * @param   string  $idTag     Value of the field id or null by default
	 *
	 * @return  string  HTML for the select list
	 */
	protected static function genericlist($list, $name, $attribs = null, $selected = null, $idTag = null)
	{
		if (empty($attribs))
		{
			$attribs = null;
		}
		else
		{
			$temp = '';

			foreach ($attribs as $key => $value)
			{
				$temp .= ' ' . $key . '="' . $value . '"';
			}

			$attribs = $temp;
		}

		return JHtml::_('select.genericlist', $list, $name, $attribs, 'value', 'text', $selected, $idTag);
	}

	/**
	 * Generates an HTML radio list.
	 *
	 * @param   array    $list       An array of objects
	 * @param   string   $name       The value of the HTML name attribute
	 * @param   string   $attribs    Additional HTML attributes for the <select> tag
	 * @param   string   $selected   The name of the object variable for the option text
	 * @param   boolean  $idTag      Value of the field id or null by default
	 *
	 * @return  string  HTML for the select list
	 */
	protected static function genericradiolist($list, $name, $attribs = null, $selected = null, $idTag = null)
	{
		if (empty($attribs))
		{
			$attribs = null;
		}
		else
		{
			$temp = '';

			foreach ($attribs as $key => $value)
			{
				$temp .= $key . ' = "' . $value . '"';
			}

			$attribs = $temp;
		}

		return JHtml::_('select.radiolist', $list, $name, $attribs, 'value', 'text', $selected, $idTag);
	}

	/**
	 * Generates a yes/no drop-down list.
	 *
	 * @param   string  $name      The value of the HTML name attribute
	 * @param   array   $attribs   Additional HTML attributes for the <select> tag
	 * @param   string  $selected  The key that is selected
	 *
	 * @return  string  HTML for the list
	 */
	public static function booleanlist($name, $attribs = null, $selected = null)
	{
		$options = array(
			JHtml::_('select.option', '', '---'),
			JHtml::_('select.option', '0', JText::_('JNo')),
			JHtml::_('select.option', '1', JText::_('JYes'))
		);

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	public static function getFilteredCountries($force = false)
	{
		static $countries = null;

		if (is_null($countries) || $force)
		{
			$countries = array_merge(self::$countries);
		}

		return $countries;
	}

	/**
	 * Returns a drop-down selection box for countries. Some special attributes:
	 *
	 * show     An array of country codes to display. Takes precedence over hide.
	 * hide     An array of country codes to hide.
	 *
	 * @param   string  $selected  Selected country code
	 * @param   string  $id        Field name and ID
	 * @param   array   $attribs   Field attributes
	 *
	 * @return string
	 */
	public static function countries($selected = null, $id = 'country', $attribs = array())
	{
		// Get the raw list of countries
		$options   = array();
		$countries = self::$countries;
		asort($countries);

		// Parse show / hide options

		// -- Initialisation
		$show = array();
		$hide = array();

		// -- Parse the show attribute
		if (isset($attribs['show']))
		{
			$show = trim($attribs['show']);

			if (!empty($show))
			{
				$show = explode(',', $show);
			}
			else
			{
				$show = array();
			}

			unset($attribs['show']);
		}

		// -- Parse the hide attribute
		if (isset($attribs['hide']))
		{
			$hide = trim($attribs['hide']);

			if (!empty($hide))
			{
				$hide = explode(',', $hide);
			}
			else
			{
				$hide = array();
			}

			unset($attribs['hide']);
		}

		// -- If $show is not empty, filter the countries
		if (count($show))
		{
			$temp = array();

			foreach ($show as $key)
			{
				if (array_key_exists($key, $countries))
				{
					$temp[ $key ] = $countries[ $key ];
				}
			}

			asort($temp);
			$countries = $temp;
		}

		// -- If $show is empty but $hide is not, filter the countries
		elseif (count($hide))
		{
			$temp = array();

			foreach ($countries as $key => $v)
			{
				if (!in_array($key, $hide))
				{
					$temp[ $key ] = $v;
				}
			}

			asort($temp);
			$countries = $temp;
		}

		foreach ($countries as $code => $name)
		{
			$options[] = JHtml::_('select.option', $code, $name);
		}

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	/**
	 * Returns a drop-down box of states grouped by country
	 *
	 * @param   string  $selected  Short code of the already selected state
	 * @param   string  $id        Field name and ID
	 * @param   array   $attribs   Attributes
	 *
	 * @return  string  The HTML of the drop-down list
	 */
	public static function states($selected = null, $id = 'state', $attribs = array())
	{
		$data = array();
		$country = isset($attribs['country']) ? $attribs['country'] : null;

		if (!is_null($country))
		{
			if (isset($attribs['country']))
			{
				unset($attribs['country']);
			}

			$countryName = self::decodeCountry($country);
			$data[]      = JHtml::_('select.option', '', '– ' . JText::_('COM_AKEEBASUBS_LEVEL_FIELD_STATE') . ' –');

			if (isset(self::$states[$countryName]))
			{
				foreach (self::$states[$countryName] as $code => $name)
				{
					$data[] = JHtml::_('select.option', $code, $name);
				}
			}
			else
			{
				$data   = [];
				$data[] = JHtml::_('select.option', '', 'N/A');
			}

			return JHtml::_('select.genericlist', $data, $id, [
				'id' =>$id,
				'list.attr' => $attribs,
				'list.select' => $selected
			]);
		}

		foreach (self::$states as $country => $states)
		{
			$data[$country] = [
				'id' => \JApplicationHelper::stringURLSafe($country),
				'text' => $country,
				'items' => []
			];

			foreach ($states as $code => $name)
			{
				$data[$country]['items'][] = JHtml::_('select.option', $code, $name);
			}
		}

		return JHtml::_('select.groupedlist', $data, $id, [
			'id' =>$id,
			'group.id' => 'id',
			'list.attr' => $attribs,
			'list.select' => $selected
		]);
	}

	/**
	 * Displays a list of the available user groups.
	 *
	 * @param   string   $name      The form field name.
	 * @param   string   $selected  The name of the selected section.
	 * @param   array    $attribs   Additional attributes to add to the select field.
	 *
	 * @return  string   The HTML for the list
	 */
	public static function usergroups($name = 'usergroups', $selected = '', $attribs = array())
	{
		return JHtml::_('access.usergroup', $name, $selected, $attribs, false);
	}

	/**
	 * Generates a Published/Unpublished drop-down list.
	 *
	 * @param   string  $selected  The key that is selected (0 = unpublished / 1 = published)
	 * @param   string  $id        The value of the HTML name attribute
	 * @param   array   $attribs   Additional HTML attributes for the <select> tag
	 *
	 * @return  string  HTML for the list
	 */
	public static function published($selected = null, $id = 'enabled', $attribs = array())
	{
		$options   = array();
		$options[] = JHtml::_('select.option', null, '- ' . JText::_('COM_AKEEBASUBS_COMMON_SELECTSTATE') . ' -');
		$options[] = JHtml::_('select.option', 0, JText::_('JUNPUBLISHED'));
		$options[] = JHtml::_('select.option', 1, JText::_('JPUBLISHED'));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	/**
	 * Generates a drop-down list for the available languages of a multi-language site.
	 *
	 * @param   string  $selected  The key that is selected
	 * @param   string  $id        The value of the HTML name attribute
	 * @param   array   $attribs   Additional HTML attributes for the <select> tag
	 *
	 * @return  string  HTML for the list
	 */
	public static function languages($selected = null, $id = 'language', $attribs = array())
	{
		JLoader::import('joomla.language.helper');
		$languages = \JLanguageHelper::getLanguages('lang_code');
		$options   = array();
		$options[] = JHtml::_('select.option', '*', JText::_('JALL_LANGUAGE'));

		if (!empty($languages))
		{
			foreach ($languages as $key => $lang)
			{
				$options[] = JHtml::_('select.option', $key, $lang->title);
			}
		}

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	/**
	 * Generates a drop-down list for the available subscription payment states.
	 *
	 * @param   string  $selected  The key that is selected
	 * @param   string  $id        The value of the HTML name attribute
	 * @param   array   $attribs   Additional HTML attributes for the <select> tag
	 *
	 * @return  string  HTML for the list
	 */
	public static function paystates($selected = null, $id = 'state', $attribs = array())
	{
		$options   = array();
		$options[] = JHtml::_('select.option', '', '- ' . JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE') . ' -');

		$types = array('N', 'P', 'C', 'X');

		foreach ($types as $type)
		{
			$options[] = JHtml::_('select.option', $type, JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE_' . $type));
		}

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	/**
	 * Generates a drop-down list for the available coupon types.
	 *
	 * @param   string  $name      The value of the HTML name attribute
	 * @param   string  $selected  The key that is selected
	 * @param   array   $attribs   Additional HTML attributes for the <select> tag
	 *
	 * @return  string  HTML for the list
	 */
	public static function coupontypes($name = 'type', $selected = 'value', $attribs = array())
	{
		$options   = array();
		$options[] = JHtml::_('select.option', '', '- ' . JText::_('COM_AKEEBASUBS_COMMON_SELECT') . ' -');
		$options[] = JHtml::_('select.option', 'value', JText::_('COM_AKEEBASUBS_COUPON_TYPE_VALUE'));
		$options[] = JHtml::_('select.option', 'percent', JText::_('COM_AKEEBASUBS_COUPON_TYPE_PERCENT'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	/**
	 * Generates a drop-down list for the available subscription levels. Alias of levels() with different ordering of
	 * parameters and include_clear set to true.
	 *
	 * @param   string  $selected  The key that is selected
	 * @param   string  $id        The value of the HTML name attribute
	 * @param   array   $attribs   Additional HTML attributes for the <select> tag
	 *
	 * @return  string  HTML for the list
	 */
	public static function subscriptionlevels($selected = null, $id = 'akeebasubs_level_id', $attribs = array())
	{
		$attribs['include_clear'] = true;

		return self::levels($id, $selected, $attribs);
	}

	/**
	 * Generates a drop-down list for the available subscription levels.
	 *
	 * Some interesting attributes:
	 *
	 * include_none     Include an option with value -1 titled "None"
	 * include_all      Include an option with value 0 titled "All"
	 * include_clear    Include an option with no value for clearing the selection
	 *
	 * By default none of these attributes is set
	 *
	 * @param   string  $name      The value of the HTML name attribute
	 * @param   string  $selected  The key that is selected
	 * @param   array   $attribs   Additional HTML attributes for the <select> tag
	 *
	 * @return  string  HTML for the list
	 */
	public static function levels($name = 'level', $selected = '', $attribs = array())
	{
		/** @var DataModel $model */
		$model =  Container::getInstance('com_akeebasubs')->factory
			->model('Levels')->tmpInstance();

		$list = $model->filter_order('ordering')->filter_order_Dir('ASC')->get(true);

		$options = array();

		$include_none  = false;
		$include_all   = false;
		$include_clear = false;

		if (array_key_exists('include_none', $attribs))
		{
			$include_none = $attribs['include_none'];
			unset($attribs['include_none']);
		}

		if (array_key_exists('include_all', $attribs))
		{
			$include_all = $attribs['include_all'];
			unset($attribs['include_all']);
		}

		if (array_key_exists('include_clear', $attribs))
		{
			$include_clear = $attribs['include_clear'];
			unset($attribs['include_clear']);
		}

		if ($include_none)
		{
			$options[] = JHtml::_('select.option', '-1', JText::_('COM_AKEEBASUBS_COMMON_SELECTLEVEL_NONE'));
		}

		if ($include_all)
		{
			$options[] = JHtml::_('select.option', '0', JText::_('COM_AKEEBASUBS_COMMON_SELECTLEVEL_ALL'));
		}

		if ($include_clear || (!$include_none && !$include_all))
		{
			$options[] = JHtml::_('select.option', '', '- ' . JText::_('COM_AKEEBASUBS_COMMON_SELECT') . ' -');
		}

		foreach ($list as $item)
		{
			$options[] = JHtml::_('select.option', $item->akeebasubs_level_id, $item->title);
		}

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	/**
	 * Returns the human readable subscription level title based on the numeric subscription level ID given in $id
	 *
	 * Alias of Format::formatLevel
	 *
	 * @param   int  $id  The subscription level ID
	 *
	 * @return  string  The subscription level title, or three em-dashes if it's unknown
	 */
	public static function formatLevel($id)
	{
		return Format::formatLevel($id);
	}

	/**
	 * Create a selection interface (drop-down list, image radios) for the payment method
	 *
	 * Some interesting attributes:
	 *
	 * level_id         int   Show payment methods applicable to this subscription level
	 * always_dropdown  bool  Always render a drop-down list, never an image selection list
	 * default_option   bool  Add a default option to unselect everything else
	 * return_raw_list  bool  Return the raw payments processors array instead of HTML
	 *
	 * @param   string  $name      The field's name
	 * @param   string  $selected  Pre-selected value
	 * @param   array   $attribs   Field attributes
	 *
	 * @return  array|string
	 */
	public static function paymentmethods($name = 'paymentmethod', $selected = '', $attribs = array())
	{
		// Initialise parameters
		$level_id        = isset($attribs['level_id']) ? $attribs['level_id'] : 0;
		$always_dropdown = isset($attribs['always_dropdown']) ? 1 : 0;
		$default_option  = isset($attribs['default_option']) ? 1 : 0;
		$country		 = isset($attribs['country']) ? $attribs['country'] : '';

        /** @var PaymentMethods $pluginsModel */
        $pluginsModel = Container::getInstance('com_akeebasubs')->factory
                            ->model('PaymentMethods')->tmpInstance();

        $plugins = $pluginsModel->getPaymentPlugins($country);

		// Per-level payment option filtering
		if ($level_id > 0)
		{
			/** @var DataModel $levelsModel */
			$levelsModel =  Container::getInstance('com_akeebasubs')->factory
				->model('Levels')->tmpInstance();

			try
			{
				$level           = $levelsModel->findOrFail($level_id);
				$payment_plugins = $level->payment_plugins;

				if (!empty($payment_plugins) && !is_array($payment_plugins))
				{
					$payment_plugins = explode(',', $payment_plugins);
				}
			}
			catch (\Exception $e)
			{
				$payment_plugins = '';
			}


			if (is_array($payment_plugins) && !empty($payment_plugins))
			{
				$temp            = array();

				foreach ($plugins as $plugin)
				{
					if (in_array($plugin->name, $payment_plugins))
					{
						$temp[] = $plugin;
					}
				}

				if (!empty($temp))
				{
					$plugins = $temp;
				}
			}
		}

		$returnRawList = false;

		if (isset($attribs['return_raw_list']))
		{
			$returnRawList = $attribs['return_raw_list'];
			unset($attribs['return_raw_list']);
		}

		if ($returnRawList)
		{
			return $plugins;
		}

		// Determine how to render the payment method (drop-down or radio box)
		if ((self::getContainer()->params->get('useppimages', 1) > 0) && !$always_dropdown)
		{
			// Show images instead of a drop-down
			$options = array();

			foreach ($plugins as $plugin)
			{
				if (!isset($plugin->image))
				{
					$plugin->image = '';
				}
				else
				{
					$plugin->image = trim($plugin->image);
				}

				if (empty($plugin->image))
				{
					$plugin->image = rtrim(\JURI::base(), '/') . '/media/com_akeebasubs/images/frontend/credit_card_logos.gif';
				}

				$innerHTML = '<img border="0" src="' . $plugin->image . '" /> ';

				if (self::getContainer()->params->get('useppimages', 1) == 2)
				{
					$innerHTML .= $plugin->title;
				}

				$options[] = array(
					'value' => $plugin->name,
					'label' => $innerHTML,
				);

				// In case we don't have a default selection, select the first item on the list
				if (empty($selected))
				{
					$selected = $plugin->name;
				}
			}

			$html = '<div class="akeebasubs-paymentmethod-images">';

			if (!empty($options))
			{
				foreach ($options as $o)
				{
					$html .= '<div class="radio"><label><input type="radio" name="' . $name . '" id="' .
					         $name . $o['value'] . '" value="' . $o['value'] . '" ';

					if ($o['value'] == $selected)
					{
						$html .= 'checked="checked"';
					}

					$html .= '/>' . $o['label'] . '</label></div>';
				}
			}

			$html .= '</div>';

			return $html;
		}
		else
		{
			// Show drop-down
			$options = array();

			if ($default_option)
			{
				$options[] = JHtml::_('select.option', '', JText::_('COM_AKEEBASUBS_LEVEL_FIELD_PAYMENT_PLUGINS_UNSELECT'));

				if (!is_array($selected))
				{
					$selected  = explode(',', $selected);
				}
			}

			foreach ($plugins as $plugin)
			{
				$options[] = JHtml::_('select.option', $plugin->name, $plugin->title);
			}

			return self::genericlist($options, $name, $attribs, $selected, $name);
		}
	}

	/**
	 * Drop-down lis of all payment processors. Alias to paymentmethods() always showing the default option and always
	 * showing the list as a dropdown.
	 *
	 * @param   string  $selected  The key that is selected
	 * @param   string  $id        The value of the HTML name attribute
	 * @param   array   $attribs   Additional HTML attributes for the <select> tag
	 *
	 * @return  string  HTML for the list
	 */
	public static function processors($selected = null, $name = 'processor', $attribs = array())
	{
		$attribs['default_option'] = true;
		$attribs['always_dropdown'] = true;

		return self::paymentmethods($name, $selected, $attribs);
	}

	/**
	 * Drop down list of discount modes
	 *
	 * @param   string  $name      The field's name
	 * @param   string  $selected  Pre-selected value
	 * @param   array   $attribs   Field attributes
	 *
	 * @return  string  The HTML of the drop-down
	 */
	public static function discountmodes($name = 'discountmode', $selected = '', $attribs = array())
	{
		$options   = array();
		$options[] = JHtml::_('select.option', '', '- ' . JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_DISCOUNT') . ' -');
		$options[] = JHtml::_('select.option', 'none', JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_DISCOUNT_NONE'));
		$options[] = JHtml::_('select.option', 'coupon', JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_DISCOUNT_COUPON'));
		$options[] = JHtml::_('select.option', 'upgrade', JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_DISCOUNT_UPGRADE'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	/**
	 * Drop down list of upgrade types
	 *
	 * @param   string  $name      The field's name
	 * @param   string  $selected  Pre-selected value
	 * @param   array   $attribs   Field attributes
	 *
	 * @return  string  The HTML of the drop-down
	 */
	public static function upgradetypes($name = 'type', $selected = 'value', $attribs = array())
	{
		$options   = array();
		$options[] = JHtml::_('select.option', '', '- ' . JText::_('COM_AKEEBASUBS_COMMON_SELECT') . ' -');
		$options[] = JHtml::_('select.option', 'value', JText::_('COM_AKEEBASUBS_UPGRADE_TYPE_VALUE'));
		$options[] = JHtml::_('select.option', 'percent', JText::_('COM_AKEEBASUBS_UPGRADE_TYPE_PERCENT'));
		$options[] = JHtml::_('select.option', 'lastpercent', JText::_('COM_AKEEBASUBS_UPGRADE_TYPE_LASTPERCENT'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	/**
	 * Drop down list of level groups
	 *
	 * @param   string  $selected  Pre-selected value
	 * @param   string  $id        The field's name
	 * @param   array   $attribs   Field attributes
	 *
	 * @return  string  The HTML of the drop-down
	 */
	public static function levelgroups($selected = null, $id = 'akeebasubs_levelgroup_id', $attribs = array())
	{
		/** @var DataModel $model */
		$model = Container::getInstance('com_akeebasubs')->factory
			->model('LevelGroups')->tmpInstance();

		$items = $model->get(true);

		$options = array();

		if (count($items))
		{
			foreach ($items as $item)
			{
				$options[] = JHtml::_('select.option', $item->akeebasubs_levelgroup_id, $item->title);
			}
		}

		array_unshift($options, JHtml::_('select.option', 0, JText::_('COM_AKEEBASUBS_SELECT_LEVELGROUP')));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	/**
	 * Drop down list of custom field types
	 *
	 * @param   string  $name      The field's name
	 * @param   string  $selected  Pre-selected value
	 * @param   array   $attribs   Field attributes
	 *
	 * @return  string  The HTML of the drop-down
	 */
	public static function fieldtypes($name = 'type', $selected = 'text', $attribs = array())
	{
		$fieldTypes = self::getFieldTypes();

		$options   = array();
		$options[] = JHtml::_('select.option', '', '- ' . JText::_('COM_AKEEBASUBS_COMMON_SELECT') . ' -');
		foreach ($fieldTypes as $type => $desc)
		{
			$options[] = JHtml::_('select.option', $type, $desc);
		}

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}
	
	/**
	 * Drop down list of subscription level relation modes
	 *
	 * @param   string  $name      The field's name
	 * @param   string  $selected  Pre-selected value
	 * @param   array   $attribs   Field attributes
	 *
	 * @return  string  The HTML of the drop-down
	 */
	public static function relationmode($name = 'mode', $selected = 'rules', $attribs = array())
	{
		$options   = array();
		$options[] = JHtml::_('select.option', '', '- ' . JText::_('COM_AKEEBASUBS_COMMON_SELECT') . ' -');
		$options[] = JHtml::_('select.option', 'rules', JText::_('COM_AKEEBASUBS_RELATIONS_MODE_RULES'));
		$options[] = JHtml::_('select.option', 'fixed', JText::_('COM_AKEEBASUBS_RELATIONS_MODE_FIXED'));
		$options[] = JHtml::_('select.option', 'flexi', JText::_('COM_AKEEBASUBS_RELATIONS_MODE_FLEXI'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	/**
	 * Drop down list of subscription level relations' period units of measurement
	 *
	 * @param   string  $name      The field's name
	 * @param   string  $selected  Pre-selected value
	 * @param   array   $attribs   Field attributes
	 *
	 * @return  string  The HTML of the drop-down
	 */
	public static function flexiperioduoms($name = 'flex_uom', $selected = 'rules', $attribs = array())
	{
		$options   = array();
		$options[] = JHtml::_('select.option', '', '- ' . JText::_('COM_AKEEBASUBS_COMMON_SELECT') . ' -');
		$options[] = JHtml::_('select.option', 'd', JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_UOM_D'));
		$options[] = JHtml::_('select.option', 'w', JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_UOM_W'));
		$options[] = JHtml::_('select.option', 'm', JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_UOM_M'));
		$options[] = JHtml::_('select.option', 'y', JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_UOM_Y'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	/**
	 * Drop down list of subscription level relations' flexible discount time calculation preference
	 *
	 * @param   string  $name      The field's name
	 * @param   string  $selected  Pre-selected value
	 * @param   array   $attribs   Field attributes
	 *
	 * @return  string  The HTML of the drop-down
	 */
	public static function flexitimecalc($name = 'flex_timecalculation', $selected = 'current', $attribs = array())
	{
		$options   = array();
		$options[] = JHtml::_('select.option', '', '- ' . JText::_('COM_AKEEBASUBS_COMMON_SELECT') . ' -');
		$options[] = JHtml::_('select.option', 'current', JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_TIMECALCULATION_CURRENT'));
		$options[] = JHtml::_('select.option', 'future', JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_TIMECALCULATION_FUTURE'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	/**
	 * Drop down list of subscription level relations' flexible discount rounding preference
	 *
	 * @param   string  $name      The field's name
	 * @param   string  $selected  Pre-selected value
	 * @param   array   $attribs   Field attributes
	 *
	 * @return  string  The HTML of the drop-down
	 */
	public static function flexirounding($name = 'flex_rounding', $selected = 'round', $attribs = array())
	{
		$options   = array();
		$options[] = JHtml::_('select.option', '', '- ' . JText::_('COM_AKEEBASUBS_COMMON_SELECT') . ' -');
		$options[] = JHtml::_('select.option', 'floor', JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_TIMEROUNDING_FLOOR'));
		$options[] = JHtml::_('select.option', 'ceil', JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_TIMEROUNDING_CEIL'));
		$options[] = JHtml::_('select.option', 'round', JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_TIMEROUNDING_ROUND'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	/**
	 * Drop down list of subscription level relations' subscription expiration preference
	 *
	 * @param   string  $name      The field's name
	 * @param   string  $selected  Pre-selected value
	 * @param   array   $attribs   Field attributes
	 *
	 * @return  string  The HTML of the drop-down
	 */
	public static function flexiexpiration($name = 'expiration', $selected = 'replace', $attribs = array())
	{
		$options   = array();
		$options[] = JHtml::_('select.option', '', '- ' . JText::_('COM_AKEEBASUBS_COMMON_SELECT') . ' -');
		$options[] = JHtml::_('select.option', 'replace', JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_EXPIRATION_REPLACE'));
		$options[] = JHtml::_('select.option', 'after', JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_EXPIRATION_AFTER'));
		$options[] = JHtml::_('select.option', 'overlap', JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_EXPIRATION_OVERLAP'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	/**
	 * Drop down list of invoice extensions
	 *
	 * @param   string  $name      The field's name
	 * @param   string  $selected  Pre-selected value
	 * @param   array   $attribs   Field attributes
	 *
	 * @return  string  The HTML of the drop-down
	 */
	public static function invoiceextensions($name = 'extension', $selected = '', $attribs = array())
	{
		/** @var \Akeeba\Subscriptions\Admin\Model\Invoices $model */
		$model = Container::getInstance('com_akeebasubs')->factory
			->model('Invoices')->tmpInstance();

		$options = $model->getExtensions(1);
		$option = JHtml::_('select.option', '', '- ' . JText::_('COM_AKEEBASUBS_COMMON_SELECT') . ' -');
		array_unshift($options, $option);

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	/**
	 * Drop down list of invoice templates
	 *
	 * @param   string  $name      The field's name
	 * @param   string  $selected  Pre-selected value
	 * @param   array   $attribs   Field attributes
	 * @param	bool	$enabled   Fetch only enabled templates?
	 *
	 * @return  string  The HTML of the drop-down
	 */
	public static function invoicetemplates($name, $selected = '', $attribs = array(), $enabled = true)
	{
		/** @var \Akeeba\Subscriptions\Admin\Model\InvoiceTemplates $model */
		$model = Container::getInstance('com_akeebasubs')->factory
			->model('InvoiceTemplates')->tmpInstance();

		if($enabled)
		{
			$model->enabled(true);
		}

		/** @var \Akeeba\Subscriptions\Admin\Model\InvoiceTemplates[] $rows */
		$rows = $model->filter_order('title')->filter_order_Dir('ASC')->get(true);

		$options[] = JHtml::_('select.option', '', '- ' . JText::_('COM_AKEEBASUBS_COMMON_SELECT') . ' -');

		foreach($rows as $row)
		{
			$options[] = JHtml::_('select.option', $row->akeebasubs_invoicetemplate_id, $row->title);
		}

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	/**
	 * Drop down list of VIES registration flag
	 *
	 * @param   string  $name      The field's name
	 * @param   int     $selected  Pre-selected value
	 * @param   array   $attribs   Field attributes
	 *
	 * @return  string  The HTML of the drop-down
	 */
	public static function viesregistered($name = 'viesregistered', $selected = 0, $attribs = array())
	{
		$options   = array();
		$options[] = JHtml::_('select.option', '0', JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_USER_VIESREGISTERED_NO'));
		$options[] = JHtml::_('select.option', '1', JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_USER_VIESREGISTERED_YES'));
		$options[] = JHtml::_('select.option', '2', JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_USER_VIESREGISTERED_FORCEYES'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	/**
	 * Drop down list of Is Business preference for invoice templates
	 *
	 * @param   string  $name      The field's name
	 * @param   int     $selected  Pre-selected value
	 * @param   array   $attribs   Field attributes
	 *
	 * @return  string  The HTML of the drop-down
	 */
	public static function invoicetemplateisbusines($name = 'isbusiness', $selected = - 1, $attribs = array())
	{
		$options   = array();
		$options[] = JHtml::_('select.option', '-1', JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_ISBUSINESS_INDIFFERENT'));
		$options[] = JHtml::_('select.option', '0', JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_ISBUSINESS_PERSONAL'));
		$options[] = JHtml::_('select.option', '1', JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_ISBUSINESS_BUSINESS'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	/**
	 * Drop down list of CSV delimiter preference
	 *
	 * @param   string  $name      The field's name
	 * @param   int     $selected  Pre-selected value
	 * @param   array   $attribs   Field attributes
	 *
	 * @return  string  The HTML of the drop-down
	 */
	public static function csvdelimiters($name = 'csvdelimiters', $selected = 1, $attribs = array())
	{
		$options   = array();
		$options[] = JHtml::_('select.option', '1', 'abc, def');
		$options[] = JHtml::_('select.option', '2', 'abc; def');
		$options[] = JHtml::_('select.option', '3', '"abc"; "def"');
		$options[] = JHtml::_('select.option', '-99', JText::_('COM_AKEEBASUBS_IMPORT_DELIMITERS_CUSTOM'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	/**
	 * Drop down list of API coupon limits preferences
	 *
	 * @param   string  $name      The field's name
	 * @param   string  $selected  Pre-selected value
	 * @param   array   $attribs   Field attributes
	 *
	 * @return  string  The HTML of the drop-down
	 */
	public static function apicouponLimits($name, $selected, $attribs = array())
	{
		$options   = array();
		$options[] = JHtml::_('select.option', '1', JText::_('COM_AKEEBASUBS_APICOUPONS_FIELD_CREATION_LIMIT'));
		$options[] = JHtml::_('select.option', '2', JText::_('COM_AKEEBASUBS_APICOUPONS_FIELD_SUBSCRIPTION_LIMIT'));
		$options[] = JHtml::_('select.option', '3', JText::_('COM_AKEEBASUBS_APICOUPONS_FIELD_VALUE_LIMIT'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	public static function getAllPaymentMethods()
	{
		/** @var PaymentMethods $pluginsModel */
		$pluginsModel = Container::getInstance('com_akeebasubs')->factory
			->model('PaymentMethods')->tmpInstance();

		$plugins = $pluginsModel->getPaymentPlugins();

		$ret = [];

		foreach ($plugins as $plugin)
		{
			$ret[$plugin->name ] = $plugin->title;
		}

		return $ret;
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

// Load the states from the database
if(!function_exists('akeebasubsHelperSelect_init'))
{
	function akeebasubsHelperSelect_init()
	{
		/** @var States $model */
		$model                = Container::getInstance('com_akeebasubs')->factory->model('States')->tmpInstance();
		$rawstates            = $model->enabled(1)->orderByLabels(1)->get(true);
		$states               = array();
		$current_country      = '';
		$current_country_name = 'N/A';
		$current_states       = array('' => 'N/A');

		/** @var States $rawstate */
		foreach ($rawstates as $rawstate)
		{
			// Note: you can't use $rawstate->state, it gets the model state
			$rawstate_state = $rawstate->getFieldValue('state', null);

			if ($rawstate->country != $current_country)
			{
				if (!empty($current_country_name))
				{
					$states[ $current_country_name ] = $current_states;
					$current_states                  = array();
					$current_country                 = '';
					$current_country_name            = '';
				}

				if (empty($rawstate->country) || empty($rawstate_state) || empty($rawstate->label))
				{
					continue;
				}

				$current_country      = $rawstate->country;
				$current_country_name = Select::$countries[ $current_country ];
			}

			$current_states[ $rawstate_state ] = $rawstate->label;
		}

		if (!empty($current_country_name))
		{
			$states[ $current_country_name ] = $current_states;
		}

		Select::$states = $states;
	}

	akeebasubsHelperSelect_init();
}
