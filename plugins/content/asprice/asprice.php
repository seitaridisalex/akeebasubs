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
use Joomla\String\StringHelper;

class plgContentAsprice extends JPlugin
{
	/**
	 * Should this plugin be allowed to run? True if FOF can be loaded and the Akeeba Subscriptions component is enabled
	 *
	 * @var  bool
	 */
	private $enabled = true;

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

		// Check whether the plugin should process or not
		if (StringHelper::strpos($article->text, 'asprice') === false)
		{
			return true;
		}

		// Search for this tag in the content
		$regex = "#{asprice (.*?)}#s";

		$article->text = preg_replace_callback($regex, array('self', 'process'), $article->text);
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
		static $levels = null;
		static $slugs = null;
		static $upperSlugs = null;

		// Don't process invalid titles
		if (empty($title))
		{
			return - 1;
		}

		// Fetch a list of subscription levels if we haven't done so already
		if (is_null($levels))
		{
			/** @var Levels $levelsModel */
			$levelsModel = Container::getInstance('com_akeebasubs', [], 'site')->factory->model('Levels')->tmpInstance();
			$levels      = array();
			$slugs       = array();
			$upperSlugs  = array();
			$list        = $levelsModel->get(true);

			if (count($list))
			{
				/** @var Levels $level */
				foreach ($list as $level)
				{
					$thisTitle                              = strtoupper($level->title);
					$levels[ $thisTitle ]                   = $level->akeebasubs_level_id;
					$slugs[ $thisTitle ]                    = $level->slug;
					$upperSlugs[ strtoupper($level->slug) ] = $level->slug;
				}
			}
		}

		$title = strtoupper($title);

		if (array_key_exists($title, $levels))
		{
			// Mapping found
			return $slug ? $slugs[ $title ] : $levels[ $title ];
		}
		elseif (array_key_exists($title, $upperSlugs))
		{
			$mySlug = $upperSlugs[ $title ];

			if ($slug)
			{
				return $mySlug;
			}
			else
			{
				foreach ($slugs as $t => $s)
				{
					if ($s = $mySlug)
					{
						return $levels[ $t ];
					}
				}

				return - 1;
			}
		}
		elseif ((int) $title == $title)
		{
			$id    = (int) $title;
			$title = '';

			// Find the title from the ID
			foreach ($levels as $t => $lid)
			{
				if ($lid == $id)
				{
					$title = $t;

					break;
				}
			}

			if (empty($title))
			{
				return $slug ? '' : - 1;
			}
			else
			{
				return $slug ? $slugs[ $title ] : $levels[ $title ];
			}
		}
		else
		{
			// No match!
			return $slug ? '' : - 1;
		}
	}

	/**
	 * Callback to preg_replace_callback in the onContentPrepare event handler of this plugin.
	 *
	 * @param   array  $match  A match to the {aslink} plugin tag
	 *
	 * @return  string  The processed result
	 */
	private static function process($match)
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