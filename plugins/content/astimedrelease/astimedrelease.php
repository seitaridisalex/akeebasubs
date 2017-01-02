<?php
/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

JLoader::import('joomla.plugin.plugin');

use FOF30\Container\Container;
use Akeeba\Subscriptions\Site\Model\Levels;
use Akeeba\Subscriptions\Site\Model\Subscriptions;

class plgContentAstimedrelease extends JPlugin
{

	/**
	 * Should this plugin be allowed to run? True if FOF can be loaded and the Akeeba Subscriptions component is enabled
	 *
	 * @var  bool
	 */
	private $enabled = true;

	/**
	 * Map level titles to IDs
	 *
	 * @var  array
	 */
	private $levelMap = array();

	/**
	 * Elapsed subscription time per level
	 *
	 * @var  array
	 */
	private $levelElapsed = array();

	/**
	 * Remaining subscription time per level
	 *
	 * @var  array
	 */
	private $levelRemaining = array();

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

		if ($this->enabled)
		{
			$this->initialiseArrays();
		}
	}

	public function onContentPrepare($context, &$row, &$params, $page = 0)
	{
		if (!$this->enabled)
		{
			return true;
		}

		$text = is_object($row) ? $row->text : $row;

		if (JString::strpos($row->text, 'astimedrelease') !== false)
		{
			$regex = "#{astimedrelease(.*?)}(.*?){/astimedrelease}#s";
			$text  = preg_replace_callback($regex, array('self', 'process'), $text);
		}

		if (JString::strpos($row->text, 'asdayselapsed') !== false)
		{
			$regex = "#{asdayselapsed(.*?)}#s";
			$text  = preg_replace_callback($regex, array('self', 'processElapsed'), $text);
		}

		if (JString::strpos($row->text, 'asdaysremaining') !== false)
		{
			$regex = "#{asdaysremaining(.*?)}#s";
			$text  = preg_replace_callback($regex, array('self', 'processRemaining'), $text);
		}

		if (is_object($row))
		{
			$row->text = $text;
		}
		else
		{
			$row = $text;
		}

		return true;
	}

	/**
	 * Initialises the subscription arrays
	 */
	private function initialiseArrays()
	{
		// Initialise
		$this->levelMap       = array();
		$this->levelElapsed   = array();
		$this->levelRemaining = array();

		// Get level title to ID map
		/** @var Levels $levelsModel */
		$levelsModel = Container::getInstance('com_akeebasubs', [], 'site')->factory->model('Levels')->tmpInstance();
		$levels = $levelsModel->get(true);

		if ($levels->count())
		{
			/** @var Levels $level */
			foreach ($levels as $level)
			{
				$level->title                    = trim($level->title);
				$level->title                    = strtoupper($level->title);
				$this->levelMap[ $level->title ] = $level->akeebasubs_level_id;
			}
		}
		else
		{
			return;
		}

		// Get the user's subscriptions and calculates how much time has elapsed
		// and how much is remaining on each subscription level. It is smart.
		// If you have subscribed for 2 one-month subscriptions over the last
		// five years the elapsed time in this subscription level is 2 months,
		// not five years!
		$user = JFactory::getUser();

		if ($user->guest)
		{
			return;
		}

		/** @var Subscriptions $subsModel */
		$subsModel = Container::getInstance('com_akeebasubs', [], 'site')->factory->model('Subscriptions')->tmpInstance();

		$subs = $subsModel
            ->user_id($user->id)
            ->paystate('C')
            ->get(true);

		if (!$subs->count())
		{
			return;
		}

		JLoader::import('joomla.utilities.date');

		$levelElapsed  = array();
		$levelDuration = array();
		$now           = new JDate();
		$now           = $now->toUnix();

		/** @var Subscriptions $sub */
		foreach ($subs as $sub)
		{
			$up   = new JDate($sub->publish_up);
			$up   = $up->toUnix();
			$down = new JDate($sub->publish_down);
			$down = $down->toUnix();

			$duration = $down - $up;

			if ($now < $up)
			{
				$elapsed = 0;
			}
			elseif ($now >= $down)
			{
				$elapsed = $duration;
			}
			else
			{
				$elapsed = $now - $up;
			}

			$levelid = $sub->akeebasubs_level_id;

			if (!array_key_exists($levelid, $levelDuration))
			{
				$levelDuration[ $levelid ] = 0;
			}

			if (!array_key_exists($levelid, $levelElapsed))
			{
				$levelElapsed[ $levelid ] = 0;
			}

			$levelDuration[ $levelid ] += $duration;
			$levelElapsed[ $levelid ] += $elapsed;
		}

		foreach ($levelDuration as $levelid => $duration)
		{
			$elapsed                          = $levelElapsed[ $levelid ];
			$this->levelRemaining[ $levelid ] = ceil(($duration - $elapsed) / 86400);
			$this->levelElapsed[ $levelid ]   = floor($elapsed / 86400);
		}
	}

	/**
	 * Gets the level ID out of a level title. If an ID was passed, it simply returns the ID.
	 * If a non-existent subscription level is passed, it returns -1.
	 *
	 * @param $title string|int The subscription level title or ID
	 *
	 * @return int The subscription level ID
	 */
	private function getId($title)
	{
		$title = strtoupper($title);
		$title = trim($title);

		if (array_key_exists($title, $this->levelMap))
		{
			// Mapping found
			return ($this->levelMap[ $title ]);
		}
		elseif ((int) $title == $title)
		{
			// Numeric ID passed
			return (int) $title;
		}
		else
		{
			// No match!
			return - 1;
		}
	}

	/**
	 * Checks if a time expression is true. The expression syntax is:
	 * LEVEL[(operand1[, operand2])]
	 * e.g.
	 * LEVEL1 -- do we have any remaining time in level 1
	 * LEVEL1(10) -- have more than 10 days elapsed in LEVEL1
	 * LEVEL1(X, 10) -- have less than 10 days elapsed in LEVEL1
	 * LEVEL1(10, 20) -- have 10 to 20 days elapsed in LEVEL1
	 * LEVEL1(-10) -- do we have MORE than 10 days in LEVEL1
	 * LEVEL1(-5,-10) -- are we in the last 10 to 5 days of LEVEL1
	 * LEVEL1(X, -10) -- do he have LESS than 10 days in LEVEL1
	 *
	 * @param string $expr
	 *
	 * @return bool
	 */
	private function isTrue($expr)
	{
		$expression = '';
		$paremPos   = strrpos($expr, '(');

		if ($paremPos !== false)
		{
			$paremPos   = strlen($expr) - $paremPos;
			$level      = $this->getId(substr($expr, 0, - $paremPos));
			$expression = substr($expr, - $paremPos);
		}
		else
		{
			$level = $this->getId($expr);
		}

		// No level? No joy.
		if ($level <= 0)
		{
			return false;
		}

		// Level not in array? No joy.
		if (!array_key_exists($level, $this->levelElapsed))
		{
			return false;
		}

		// Parse the time expression
		$minConstraint = null;
		$maxConstraint = null;

		if (!empty($expression))
		{
			$expression = trim($expression, '() ');
			$expression = str_replace(' ', '', $expression);
			$exParts    = explode(',', $expression);

			if (trim($exParts[0]) == 'X')
			{
				$minConstraint = null;
			}
			else
			{
				$minConstraint = (int) $exParts[0];
			}
			if (count($exParts) > 1)
			{
				if (trim($exParts[1]) == 'X')
				{
					$maxConstraint = null;
				}
				else
				{
					$maxConstraint = (int) $exParts[1];
				}
			}
		}

		$result = true;

		if (is_null($minConstraint))
		{
			// Do nothing
		}
		elseif ($minConstraint < 0)
		{
			// Negative min constraint
			$result = $result && ($this->levelRemaining[ $level ] >= - $minConstraint);
		}
		else
		{
			// Positive min constraint
			$result = $result && ($this->levelElapsed[ $level ] >= $minConstraint);
		}

		if (is_null($maxConstraint))
		{
			// Do nothing
		}
		elseif ($maxConstraint < 0)
		{
			// Negative max constraint
			$result = $result && ($this->levelRemaining[ $level ] <= - $maxConstraint);
		}
		else
		{
			// Positive max constraint
			$result = $result && ($this->levelElapsed[ $level ] <= $maxConstraint);
		}

		return $result;
	}

	/**
	 * preg_match callback to process each match
	 */
	private function process($match)
	{
		$ret = '';

		if ($this->analyze($match[1]))
		{
			$ret = $match[2];
		}

		return $ret;
	}

	/**
	 * preg_match callback to process each match
	 */
	private function processElapsed($match)
	{
		return $this->analyzeTime($match[1], true);
	}

	/**
	 * /**
	 * preg_match callback to process each match
	 */
	private function processRemaining($match)
	{
		return $this->analyzeTime($match[1], false);
	}

	/**
	 * Analyzes a filter statement and decides if it's true or not
	 *
	 * @return boolean
	 */
	private function analyze($statement)
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

					$expr   = trim($item);
					$result = $this->isTrue($expr);
					$ret    = $ret && ($negate ? !$result : $result);
				}
			}
		}

		return $ret;
	}

	private function analyzeTime($expr, $isElapsed = true)
	{
		$expression = '';
		$paremPos   = strrpos($expr, ',');

		if ($paremPos !== false)
		{
			$level      = $this->getId(substr($expr, 0, $paremPos));
			$expression = substr($expr, $paremPos);
		}
		else
		{
			$level = $this->getId($expr);
		}

		// No level? No joy.
		if ($level <= 0)
		{
			return 0;
		}

		// Level not in array? No joy.
		if (!array_key_exists($level, $this->levelElapsed))
		{
			return 0;
		}

		// Parse the time expression
		$addRemove = 0;

		if (!empty($expression))
		{
			$expression = trim($expression, ', ');
			$expression = str_replace(' ', '', $expression);
			$addRemove  = (int) $expression;
		}

		if ($isElapsed)
		{
			if (!array_key_exists($level, $this->levelElapsed))
			{
				$result = 0;
			}
			else
			{
				$result = $this->levelElapsed[ $level ];
			}
		}
		else
		{
			if (!array_key_exists($level, $this->levelRemaining))
			{
				$result = 0;
			}
			else
			{
				$result = $this->levelRemaining[ $level ];
			}
		}

		$result += $addRemove;

		return $result;
	}

}
