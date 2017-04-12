<?php
/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

JLoader::import('joomla.plugin.plugin');

use FOF30\Container\Container;
use Akeeba\Subscriptions\Admin\Model\Levels;
use Akeeba\Subscriptions\Admin\Helper\Price;
use Joomla\String\StringHelper;

class plgContentAsprice extends JPlugin
{
	/**
	 * Should this plugin be allowed to run? True if FOF can be loaded and the Akeeba Subscriptions component is enabled
	 *
	 * @var  bool
	 */
	private $enabled = true;

	/**
	 * List of currently enabled subscription levels
	 *
	 * @var   Levels[]
	 */
	protected static $levels = null;

	/**
	 * Maps subscription level titles to slugs
	 *
	 * @var   array
	 */
	protected static $slugs = null;

	/**
	 * Maps subscription level titles to UPPERCASE slugs
	 *
	 * @var   array
	 */
	protected static $upperSlugs = null;

	/**
	 * Maps subscription level IDs to pricing information
	 *
	 * @var   array
	 */
	protected static $prices = null;

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

		parent::__construct($subject, $config);
	}

	/**
	 * Handles the content preparation event fired by Joomla!
	 *
	 * @param   mixed     $context     Unused in this plugin.
	 * @param   stdClass  $article     An object containing the article being processed.
	 * @param   mixed     $params      Unused in this plugin.
	 * @param   int       $limitstart  Unused in this plugin.
	 *
	 * @return bool
	 */
	public function onContentPrepare($context, &$article, &$params, $limitstart = 0)
	{
		if (!$this->enabled)
		{
			return true;
		}

		$accceptableActions = [
			'ifashasdiscount', 'ifashassignupfee',
			'asprice', 'asfancyprice', 'asfancydiscount', 'asfancysignup',
		    'asforexnotice', 'asdiscountnotice'
		];

		$mustProcess = false;

		foreach ($accceptableActions as $action)
		{
			// Check whether the plugin should process or not
			if (StringHelper::strpos($article->text, $action) !== false)
			{
				$mustProcess = true;

				break;
			}
		}

		if (!$mustProcess)
		{
			return true;
		}

		// {ifashasdiscount MYLEVEL}something{/ifashasdiscount} ==> Only show something if MYLEVEL's price is discounted
		$regex = "#{ifashasdiscount (.*?)}(.*){/ifashasdiscount}#s";
		$article->text = preg_replace_callback($regex, array('self', 'processIfHasDiscount'), $article->text);

		// {ifashassignupfee MYLEVEL}something{/ifashassignupfee} ==> Only show something if MYLEVEL's price includes a signup fee
		$regex = "#{ifashassignupfee(.*?)}(.*){/ifashassignupfee}#s";
		$article->text = preg_replace_callback($regex, array('self', 'processIfIncludesSignup'), $article->text);

		// {asprice MYLEVEL} ==> 10.00€
		$regex = "#{asprice (.*?)}#s";
		$article->text = preg_replace_callback($regex, array('self', 'processPrice'), $article->text);

		// {asfancyprice MYLEVEL} ==> HTML block based on the Strappy layout with just the display price
		$regex = "#{asfancyprice (.*?)}#s";
		$article->text = preg_replace_callback($regex, array('self', 'processFancyPrice'), $article->text);

		// {asfancydiscount MYLEVEL} ==> HTML block based on the Strappy layout with the discount price
		$regex = "#{asfancydiscount (.*?)}#s";
		$article->text = preg_replace_callback($regex, array('self', 'processFancyDiscount'), $article->text);

		// {asfancysignup MYLEVEL} ==> HTML block based on the Strappy layout with the signup price
		$regex = "#{asfancysignup (.*?)}#s";
		$article->text = preg_replace_callback($regex, array('self', 'processFancySignup'), $article->text);

		// {asforexnotice} ==> Foreign exchange rate notice
		$regex = "#{asforexnotice(.*?)}#s";
		$article->text = preg_replace_callback($regex, array('self', 'processForexNotice'), $article->text);

		// {asdiscountnotice} ==> Discount is included notice
		$regex = "#{asdiscountnotice(.*?)}#s";
		$article->text = preg_replace_callback($regex, array('self', 'processDiscountNotice'), $article->text);
	}

	/**
	 * Gets the level ID out of a level title. If an ID was passed, it simply returns the ID.
	 * If a non-existent subscription level is passed, it returns -1.
	 *
	 * @param   string|int $title The subscription level title or ID
	 *
	 * @return  int  The subscription level ID
	 */
	private static function getId($title, $slug = false)
	{
		// Don't process invalid titles
		if (empty($title))
		{
			return -1;
		}

		// Fetch a list of subscription levels if we haven't done so already
		if (is_null(self::$levels))
		{
			/** @var Levels $levelsModel */
			$levelsModel      = Container::getInstance('com_akeebasubs', [], 'site')->factory->model('Levels')
			                                                                                 ->tmpInstance();
			self::$levels     = array();
			self::$slugs      = array();
			self::$upperSlugs = array();
			$list             = $levelsModel->get(true);

			if (count($list))
			{
				/** @var Levels $level */
				foreach ($list as $level)
				{
					$thisTitle                                  = strtoupper($level->title);
					self::$levels[$thisTitle]                   = $level->akeebasubs_level_id;
					self::$slugs[$thisTitle]                    = $level->slug;
					self::$upperSlugs[strtoupper($level->slug)] = $level->slug;
				}
			}
		}

		$title = strtoupper($title);

		if (array_key_exists($title, self::$levels))
		{
			// Mapping found
			return $slug ? self::$slugs[$title] : self::$levels[$title];
		}
		elseif (array_key_exists($title, self::$upperSlugs))
		{
			$mySlug = self::$upperSlugs[$title];

			if ($slug)
			{
				return $mySlug;
			}
			else
			{
				foreach (self::$slugs as $t => $s)
				{
					if ($s = $mySlug)
					{
						return self::$levels[$t];
					}
				}

				return -1;
			}
		}
		elseif ((int) $title == $title)
		{
			$id    = (int) $title;
			$title = '';

			// Find the title from the ID
			foreach (self::$levels as $t => $lid)
			{
				if ($lid == $id)
				{
					$title = $t;

					break;
				}
			}

			if (empty($title))
			{
				return $slug ? '' : -1;
			}
			else
			{
				return $slug ? self::$slugs[$title] : self::$levels[$title];
			}
		}
		else
		{
			// No match!
			return $slug ? '' : -1;
		}
	}

	/**
	 * Callback to preg_replace_callback in the onContentPrepare event handler of this plugin.
	 *
	 * @param   array  $match  A match to the {ifashasdiscount} plugin tag
	 *
	 * @return  string  The processed result
	 */
	private static function processIfHasDiscount($match)
	{
		$levelId   = self::getId($match[1], false);
		$container = Price::getContainer();
		$params    = Price::getPricingParameters();
		/** @var \Akeeba\Subscriptions\Site\Model\Levels $level */
		$level = $container->factory->model('Levels')->tmpInstance();

		if (!$params->includeDiscount)
		{
			return '';
		}

		if ($levelId <= 0)
		{
			return '';
		}

		try
		{
			$level->findOrFail($levelId);
		}
		catch (Exception $e)
		{
			return '';
		}

		$priceInfo = Price::getLevelPriceInformation($level);
		$ret       = '';

		if ((abs($priceInfo->discount) >= 0.01) && (abs($priceInfo->prediscount) >= 0.01))
		{
			$ret = $match[2];
		}

		return $ret;
	}


	/**
	 * Callback to preg_replace_callback in the onContentPrepare event handler of this plugin.
	 *
	 * @param   array  $match  A match to the {ifashassignupfee} plugin tag
	 *
	 * @return  string  The processed result
	 */
	private static function processIfIncludesSignup($match)
	{
		$levelId   = self::getId($match[1], false);
		$container = Price::getContainer();
		$params    = Price::getPricingParameters();
		/** @var \Akeeba\Subscriptions\Site\Model\Levels $level */
		$level = $container->factory->model('Levels')->tmpInstance();

		if ($params->includeSignup !== 2)
		{
			return '';
		}

		if ($levelId <= 0)
		{
			return '';
		}

		try
		{
			$level->findOrFail($levelId);
		}
		catch (Exception $e)
		{
			return '';
		}

		$priceInfo = Price::getLevelPriceInformation($level);
		$ret       = '';

		if (abs($priceInfo->signupFee))
		{
			$ret = $match[2];
		}

		return $ret;
	}

	/**
	 * Callback to preg_replace_callback in the onContentPrepare event handler of this plugin.
	 *
	 * @param   array  $match  A match to the {asprice} plugin tag
	 *
	 * @return  string  The processed result
	 */
	private static function processPrice($match)
	{
		$ret = '';

		$levelId = self::getId($match[1], false);

		if ($levelId <= 0)
		{
			return $ret;
		}

		$ret = self::getPrice($levelId);

		return $ret;
	}

	/**
	 * Callback to preg_replace_callback in the onContentPrepare event handler of this plugin.
	 *
	 * @param   array  $match  A match to the {asfancyprice} plugin tag
	 *
	 * @return  string  The processed result
	 */
	private static function processFancyPrice($match)
	{
		$levelId   = self::getId($match[1], false);
		$container = Price::getContainer();
		$params    = Price::getPricingParameters();
		/** @var \Akeeba\Subscriptions\Site\Model\Levels $level */
		$level     = $container->factory->model('Levels')->tmpInstance();

		if ($levelId <= 0)
		{
			return '';
		}

		try
		{
			$level->findOrFail($levelId);
		}
		catch (Exception $e)
		{
			return '';
		}

		$priceInfo = Price::getLevelPriceInformation($level);

		if ($params->renderAsFree && ($priceInfo->levelPrice < 0.01))
		{
			return JText::_('COM_AKEEBASUBS_LEVEL_LBL_FREE');
		}

		$ret = '';

		// Do I need to show the currency symbol BEFORE the price?
		if ($params->currencyPosition == 'before')
		{
			$ret .= '<span class="akeebasubs-asprice-price-currency">' . $params->currencySymbol . '</span>';
		}

		// Show INTEGER PART of price
		$ret .= '<span class="akeebasubs-asprice-price-integer">' . $priceInfo->priceInteger . '</span>';

		// Show DECIMAL PART of price
		if ( (int)$priceInfo->priceFractional > 0)
		{
			$ret .= '<span class="akeebasubs-asprice-price-separator">.</span>';
			$ret .= '<span class="akeebasubs-asprice-price-decimal">' . $priceInfo->priceFractional . '</span>';
		}

		// Do I need to show the currency symbol AFTER the price?
		if ($params->currencyPosition == 'after')
		{
			$ret .= '<span class="akeebasubs-asprice-price-currency">' . $params->currencySymbol . '</span>';
		}

		// Local (currency converted) price notice
		if ($params->showLocalPrices)
		{
			$ret .= '<div class="akeebasubs-asprice-forexrate">';
			$ret .= JText::sprintf('COM_AKEEBASUBS_LEVELS_FOREXNOTICE_LBL', Price::toLocalCurrency((float)$priceInfo->priceForFormatting));
			$ret .= '</div>';
		}

		// VAT notice
		if (((float)$priceInfo->vatRule->taxrate > 0.01) && ($priceInfo->levelPrice > 0.01))
		{
			$ret .= '<div class="akeebasubs-asprice-taxnotice">';
			$ret .= JText::sprintf('COM_AKEEBASUBS_LEVELS_INCLUDESVAT', (float)$priceInfo->vatRule->taxrate);
			$ret .= '</div>';
		}

		return $ret;
	}

	/**
	 * Callback to preg_replace_callback in the onContentPrepare event handler of this plugin.
	 *
	 * @param   array  $match  A match to the {asfancydiscount} plugin tag
	 *
	 * @return  string  The processed result
	 */
	private static function processFancyDiscount($match)
	{
		$levelId   = self::getId($match[1], false);
		$container = Price::getContainer();
		$params    = Price::getPricingParameters();
		/** @var \Akeeba\Subscriptions\Site\Model\Levels $level */
		$level     = $container->factory->model('Levels')->tmpInstance();

		if ($levelId <= 0)
		{
			return '';
		}

		try
		{
			$level->findOrFail($levelId);
		}
		catch (Exception $e)
		{
			return '';
		}

		$priceInfo = Price::getLevelPriceInformation($level);

		if (!((abs($priceInfo->discount) >= 0.01) && (abs($priceInfo->prediscount) >= 0.01)))
		{
			return '';
		}

		$ret = '';

		// Prefix
		$ret .= '<span class="akeebasubs-asprice-prediscount-label">' . JText::_('COM_AKEEBASUBS_LEVEL_FIELD_PREDISCOUNT') . '</span>';

		$ret .= '<s>';

		// Do I need to show the currency symbol BEFORE the price?
		if ($params->currencyPosition == 'before')
		{
			$ret .= '<span class="akeebasubs-asprice-price-currency">' . $params->currencySymbol . '</span>';
		}

		// Show INTEGER PART of price
		$ret .= '<span class="akeebasubs-asprice-price-integer">' . $priceInfo->prediscountInteger . '</span>';

		// Show DECIMAL PART of price
		if ( (int)$priceInfo->prediscountFractional > 0)
		{
			$ret .= '<span class="akeebasubs-asprice-price-separator">.</span>';
			$ret .= '<span class="akeebasubs-asprice-price-decimal">' . $priceInfo->prediscountFractional . '</span>';
		}

		// Do I need to show the currency symbol AFTER the price?
		if ($params->currencyPosition == 'after')
		{
			$ret .= '<span class="akeebasubs-asprice-price-currency">' . $params->currencySymbol . '</span>';
		}

		$ret .= '</s>';

		// Local (currency converted) price notice
		if ($params->showLocalPrices)
		{
			$ret .= '<div class="akeebasubs-asprice-forexrate-discount">';
			$ret .= JText::sprintf('COM_AKEEBASUBS_LEVELS_FOREXNOTICE_LBL', Price::toLocalCurrency((float)$priceInfo->prediscount));
			$ret .= '</div>';
		}

		return $ret;
	}

	/**
	 * Callback to preg_replace_callback in the onContentPrepare event handler of this plugin.
	 *
	 * @param   array  $match  A match to the {asfancysignup} plugin tag
	 *
	 * @return  string  The processed result
	 */
	private static function processFancySignup($match)
	{
		$levelId   = self::getId($match[1], false);
		$container = Price::getContainer();
		$params    = Price::getPricingParameters();
		/** @var \Akeeba\Subscriptions\Site\Model\Levels $level */
		$level     = $container->factory->model('Levels')->tmpInstance();

		if ($levelId <= 0)
		{
			return '';
		}

		try
		{
			$level->findOrFail($levelId);
		}
		catch (Exception $e)
		{
			return '';
		}

		$priceInfo = Price::getLevelPriceInformation($level);

		if (!(abs($priceInfo->signupFee) >= 0.01))
		{
			return '';
		}

		$ret = '';

		// Prefix
		$ret .= JText::_('COM_AKEEBASUBS_LEVEL_FIELD_SIGNUPFEE_LIST');

		// Do I need to show the currency symbol BEFORE the price?
		if ($params->currencyPosition == 'before')
		{
			$ret .= '<span class="akeebasubs-asprice-price-currency">' . $params->currencySymbol . '</span>';
		}

		// Show INTEGER PART of price
		$ret .= '<span class="akeebasubs-asprice-price-integer">' . $priceInfo->signupInteger . '</span>';

		// Show DECIMAL PART of price
		if ( (int)$priceInfo->prediscountFractional > 0)
		{
			$ret .= '<span class="akeebasubs-asprice-price-separator">.</span>';
			$ret .= '<span class="akeebasubs-asprice-price-decimal">' . $priceInfo->signupInteger . '</span>';
		}

		// Do I need to show the currency symbol AFTER the price?
		if ($params->currencyPosition == 'after')
		{
			$ret .= '<span class="akeebasubs-asprice-price-currency">' . $params->currencySymbol . '</span>';
		}

		// Local (currency converted) price notice
		if ($params->showLocalPrices)
		{
			$ret .= '<div class="akeebasubs-asprice-forexrate-signup">';
			$ret .= JText::sprintf('COM_AKEEBASUBS_LEVELS_FOREXNOTICE_LBL', Price::toLocalCurrency((float)$priceInfo->signupFee));
			$ret .= '</div>';
		}

		return $ret;
	}

	/**
	 * Callback to preg_replace_callback in the onContentPrepare event handler of this plugin.
	 *
	 * @param   array  $match  A match to the {asforexnotice} plugin tag
	 *
	 * @return  string  The processed result
	 */
	private static function processForexNotice($match)
	{
		$ret = '';
		$params = Price::getPricingParameters();

		if ($params->showLocalPrices)
		{
			$ret = JText::sprintf('COM_AKEEBASUBS_LEVELS_FOREXNOTICE',
				$params->localCurrency, $params->localSymbol, $params->currency, $params->exchangeRate);
		}

		return $ret;
	}

	/**
	 * Callback to preg_replace_callback in the onContentPrepare event handler of this plugin.
	 *
	 * @param   array  $match  A match to the {asdiscountnotice} plugin tag
	 *
	 * @return  string  The processed result
	 */
	private static function processDiscountNotice($match)
	{
		$ret = '';
		$params = Price::getPricingParameters();

		if ($params->includeDiscount)
		{
			$ret = JText::_('COM_AKEEBASUBS_LEVELS_PREDISCOUNT_NOTE');
		}

		return $ret;
	}

	private static function getPrice($levelId)
	{
		static $prices = [];

		if (!array_key_exists($levelId, $prices))
		{
			$container = Container::getInstance('com_akeebasubs', [], 'site');
			/** @var \Akeeba\Subscriptions\Site\Model\Levels $level */
			$level = $container->factory->model('Levels');
			$level->load($levelId);

			/** @var Akeeba\Subscriptions\Site\View\Levels\Html $view */
			$view = $container->factory->view('Levels', 'html');
			$view->applyViewConfiguration();
			$priceInfo = $view->getLevelPriceInformation($level);

			$price = '';

			if ($view->renderAsFree && ($priceInfo->levelPrice < 0.01))
			{
				$price = JText::_('COM_AKEEBASUBS_LEVEL_LBL_FREE');
			}
			else
			{
				if ($container->params->get('currencypos','before') == 'before')
				{
					$price .= $container->params->get('currencysymbol','€');
				}

				$price .= $priceInfo->formattedPrice;

				if ($container->params->get('currencypos','before') == 'after')
				{
					$price .= $container->params->get('currencysymbol','€');
				}
			}

			$prices[$levelId] = $price;
		}

		return $prices[$levelId];
	}
}