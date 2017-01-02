<?php
/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

JLoader::import('joomla.plugin.plugin');

use FOF30\Container\Container;
use Akeeba\Subscriptions\Admin\Model\Levels;
use Akeeba\Subscriptions\Admin\Model\Subscriptions;

class plgContentAsrestricted extends JPlugin
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
	public function onContentPrepare($context, &$row, &$params, $page = 0)
	{
		if (!$this->enabled)
		{
			return true;
		}

		if (is_object($row))
		{
			// Check whether the plugin should process or not
			if (JString::strpos($row->text, 'akeebasubs') === false)
			{
				return true;
			}

			// Search for this tag in the content
			$regex = "#{akeebasubs(.*?)}(.*?){/akeebasubs}#s";
			$row->text = preg_replace_callback($regex, array('self', 'process'), $row->text);
		}
		else
		{
			if (JString::strpos($row, 'akeebasubs') === false)
			{
				return true;
			}

			$regex = "#{akeebasubs(.*?)}(.*?){/akeebasubs}#s";
			$row   = preg_replace_callback($regex, array('self', 'process'), $row);
		}

		return true;
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
	 * Checks if a user has a valid, active subscription by that particular ID
	 *
	 * @param $id int The subscription level ID
	 *
	 * @return bool True if there is such a subscription
	 */
	private static function isTrue($id)
	{
		static $subscriptions = null;

		// Don't process empty or invalid IDs
		$id = trim($id);

		if (empty($id) || (($id <= 0) && ($id != '*')))
		{
			return false;
		}

		// Don't process for guests
		$user = JFactory::getUser();

		if ($user->guest)
		{
			$subscriptions = array();
		}
		elseif (is_null($subscriptions))
		{
			$subscriptions = array();
			JLoader::import('joomla.utilities.date');
			$jNow = new JDate();

			/** @var Subscriptions $subsModel */
			$subsModel = Container::getInstance('com_akeebasubs', [], 'site')->factory->model('Subscriptions')->tmpInstance();
			$list = $subsModel
	            ->user_id($user->id)
	            ->expires_from($jNow->toSql())
	            ->paystate('C')
	            ->get(true);

			if ($list->count())
			{
				/** @var Subscriptions $sub */
				foreach ($list as $sub)
				{
					if ($sub->enabled)
					{
						if (!in_array($sub->akeebasubs_level_id, $subscriptions))
						{
							$subscriptions[] = $sub->akeebasubs_level_id;
						}
					}
				}
			}
		}

		if ($id == '*')
		{
			return !empty($subscriptions);
		}
		else
		{
			return in_array($id, $subscriptions);
		}
	}

	/**
	 * preg_match callback to process each match
	 */
	private static function process($match)
	{
		$ret = '';

		if (self::analyze($match[1]))
		{
			$ret = $match[2];
		}

		return $ret;
	}

	/**
	 * Analyzes a filter statement and decides if it's true or not
	 *
	 * @return boolean
	 */
	private static function analyze($statement)
	{
		$ret = false;

		if ($statement)
		{
			// Stupid, stupid crap... ampersands replaced by &amp;...
			$statement = str_replace('&amp;&amp;', '&&', $statement);

			// First, break down to OR statements
			$items = explode("||", trim($statement));

			for ($i = 0; $i < count($items) && !$ret; $i ++)
			{
				// Break down AND statements
				$expression = trim($items[ $i ]);
				$subitems   = explode('&&', $expression);
				$ret        = true;

				foreach ($subitems as $item)
				{
					$item   = trim($item);
					$negate = false;

					if (substr($item, 0, 1) == '!')
					{
						$negate = true;
						$item   = substr($item, 1);
						$item   = trim($item);
					}

					$id = trim($item);

					if ($id != '*')
					{
						$id = self::getId($id);
					}

					$result = self::isTrue($id);
					$ret    = $ret && ($negate ? !$result : $result);
				}
			}
		}

		return $ret;
	}
}