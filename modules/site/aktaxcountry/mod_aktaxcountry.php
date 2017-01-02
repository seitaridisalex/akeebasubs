<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

// no direct access
defined('_JEXEC') or die;

if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
{
	throw new RuntimeException('FOF 3.0 is not installed', 500);
}

// Get the Akeeba Subscriptions container. Also includes the autoloader.
$container = FOF30\Container\Container::getInstance('com_akeebasubs');

// Load the language files
$lang = JFactory::getLanguage();
$lang->load('mod_aktaxcountry', JPATH_SITE, 'en-GB', true);
$lang->load('mod_aktaxcountry', JPATH_SITE, null, true);
$lang->load('com_akeebasubs', JPATH_SITE, 'en-GB', true);
$lang->load('com_akeebasubs', JPATH_SITE, null, true);

// Get a list of options
$countries = $params->get('countries', 'US');
$eucountries = $params->get('eucountries', 1);
$eubusiness = $params->get('eubusiness', 1);
$international = $params->get('international', 1);
$prompt = $params->get('prompt', '');

$countryCodes = array();

if (!empty($countries))
{
	$countryCodes = explode(',', $countries);
	$countryCodes = array_map('trim', $countryCodes);
}

if ($eucountries)
{
	$additionalCountries = \Akeeba\Subscriptions\Admin\Helper\EUVATInfo::$EuropeanUnionVATInformation;

	foreach ($additionalCountries as $code => $info)
	{
		if (!in_array($code, $countryCodes))
		{
			$countryCodes[] = $code;
		}
	}
}

if ($eubusiness && !in_array('EU-VIES', $countryCodes))
{
	$countryCodes[] = 'EU-VIES';
}

if ($international && !in_array('XX', $countryCodes))
{
	$countryCodes[] = 'XX';
}

$options = array();
$countryNames = \Akeeba\Subscriptions\Admin\Helper\Select::getCountries();

foreach ($countryCodes as $code)
{
	switch ($code)
	{
		case 'XX':
			$options[] = JHtml::_('select.option', $code, JText::_('MOD_AKTAXCOUNTRY_LBL_INTERNATIONAL'));
			break;

		case 'EU-VIES':
			$options[] = JHtml::_('select.option', $code, JText::_('MOD_AKTAXCOUNTRY_LBL_EUBUSINES'));
			break;

		default:
			if (array_key_exists($code, $countryNames))
			{
				$options[] = JHtml::_('select.option', $code, $countryNames[$code]);
			}
			else
			{
				$options[] = JHtml::_('select.option', $code, $code);
			}
			break;
	}
}

// Try to guess the default value
$default_option = JFactory::getSession()->get('country', null, 'mod_aktaxcountry');

if (empty($default_option))
{
	/** @var \Akeeba\Subscriptions\Site\Model\TaxHelper $taxHelper */
	$taxHelper = $container->factory->model('TaxHelper')->tmpInstance();
	$taxparams = $taxHelper->getTaxDefiningParameters();
	$default_option = $taxparams['country'];

	if ($taxparams['vies'] && \Akeeba\Subscriptions\Admin\Helper\EUVATInfo::isEUVATCountry($taxparams['country']))
	{
		$default_option = 'EU-VIES';
	}
}

if (empty($default_option))
{
	$default_option = 'XX';
}

// Load the layout file
require_once JModuleHelper::getLayoutPath($module->module);