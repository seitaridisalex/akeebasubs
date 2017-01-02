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

class plgContentAslink extends JPlugin
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
		if (JString::strpos($article->text, 'aslink') === false)
		{
			return true;
		}

		// Search for this tag in the content
		$regex = "#{aslink (.*?)}#s";

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

		if (($match[1] != 'view=levels') && ($match[1] != 'view=Levels'))
		{
			$slug = self::getId($match[1], true);

			if (!empty($slug))
			{
				$root = self::getRootUrl();

				$itemId = self::_findItem($slug);
				if ($itemId)
				{
					$itemId = '&Itemid=' . $itemId;
				}

				$ret = rtrim($root, '/') . JRoute::_('index.php?option=com_akeebasubs&view=level&slug=' . $slug . $itemId);
			}
		}
		else
		{
			$root = self::getRootUrl();

			$itemId = self::_findItem('levels');
			if ($itemId)
			{
				$itemId = '&Itemid=' . $itemId;
			}

			$ret = rtrim($root, '/') . JRoute::_('index.php?option=com_akeebasubs&view=levels&Itemid=' . $itemId);
		}

		return $ret;
	}

	/**
	 * Gets the URL to the site's root
	 *
	 * @return  string
	 */
	private static function getRootUrl()
	{
		static $root = null;

		if (is_null($root))
		{
			$root    = rtrim(JURI::base(), '/');
			$subpath = JURI::base(true);

			if (!empty($subpath) && ($subpath != '/'))
			{
				$root = substr($root, 0, - 1 * strlen($subpath));
			}
		}

		return $root;
	}

	/**
	 * Finds out the Itemid for the subscription
	 *
	 * @param   string  $slug  The subscription level slug for which we want to find the Itemid
	 *
	 * @return  null|string  The Itemid or null if nothing is found
	 */
	private static function _findItem($slug)
	{

		$component = JComponentHelper::getComponent('com_akeebasubs');
		$menus     = JFactory::getApplication()->getMenu('site', array());
		$items     = $menus->getItems('component_id', $component->id);
		$itemId    = null;

		if (count($items))
		{
			foreach ($items as $item)
			{
				if (is_string($item->params))
				{
					$params = new JRegistry();
					$params->loadString($item->params, 'JSON');
				}
				else
				{
					$params = $item->params;
				}

				if (@$item->query['view'] == 'level')
				{
					if ((@$params->get('slug') == $slug))
					{
						$itemId = $item->id;
						break;
					}
				}

				if (@$item->query['view'] == 'levels')
				{
					if ($item->query['view'] == $slug)
					{
						$itemId = $item->id;
						break;
					}
				}
			}
		}

		return $itemId;
	}
}