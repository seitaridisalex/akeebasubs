<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Helper;

use FOF30\Container\Container;
use FOF30\Date\Date;
use FOF30\Model\DataModel;
use JLoader;
use JText;

defined('_JEXEC') or die;

/**
 * A helper class for formatting data for display
 */
abstract class Format
{
	/**
	 * Format a date for display
	 *
	 * @param   string  $date    The date to format
	 * @param   string  $format  The format string, default is whatever you specified in the component options
	 *
	 * @return string
	 */
	public static function date($date, $format = null)
	{
		JLoader::import('joomla.utilities.date');

		$jDate = new Date($date);

		if (empty($format))
		{
			$format = self::getContainer()->params->get('dateformat', 'Y-m-d H:i');
			$format = str_replace('%', '', $format);
		}

		return $jDate->format($format, true);
	}

	/**
	 * Check if the given string is a valid date
	 *
	 * @param   string  $date  Date as string
	 *
	 * @return  bool|Date  False on failure, JDate if successful
	 */
	public static function checkDateFormat($date)
	{
		JLoader::import('joomla.utilities.date');
		$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';

		if (!preg_match($regex, $date))
		{
			return false;
		}

		return new Date($date);
	}

	/**
	 * Returns the human readable subscription level title based on the numeric subscription level ID given in $id
	 *
	 * @param   int  $id  The subscription level ID
	 *
	 * @return  string  The subscription level title, or three em-dashes if it's unknown
	 */
	public static function formatLevel($id)
	{
		static $levels;

		if (empty($levels))
		{
			/** @var DataModel $levelsModel */
			$levelsModel = Container::getInstance('com_akeebasubs')->factory
				->model('Levels')->tmpInstance();

			$rawlevels = $levelsModel
				->filter_order('ordering')
				->filter_order_Dir('ASC')
				->get(true);

			$levels = array();

			if (!empty($rawlevels))
			{
				foreach ($rawlevels as $rawlevel)
				{
					$levels[ $rawlevel->akeebasubs_level_id ] = $rawlevel->title;
				}
			}
		}

		if (array_key_exists($id, $levels))
		{
			return $levels[ $id ];
		}
		else
		{
			return '&mdash;&mdash;&mdash;';
		}
	}

	/**
	 * Returns the human readable subscription level group title based on the numeric subscription level group ID given
	 * in $id.
	 *
	 * @param   int  $id  The subscription level ID
	 *
	 * @return  string  The subscription level title, or three em-dashes if it's unknown
	 */
	public static function formatLevelgroup($id)
	{
		static $levelGroupsMap;

		if (empty($levelGroupsMap))
		{
			/** @var DataModel $levelsModel */
			$levelGroupsModel = Container::getInstance('com_akeebasubs')->factory
				->model('LevelGroups')->tmpInstance();

			$levelGroupsList = $levelGroupsModel
				->get(true);

			if (!empty($levelGroupsList))
			{
				foreach ($levelGroupsList as $levelGroup)
				{
					$levelGroupsMap[ $levelGroup->akeebasubs_levelgroup_id ] = $levelGroup->title;
				}
			}
			else
			{
				$levelGroupsMap = array();
			}
		}

		if (array_key_exists($id, $levelGroupsMap))
		{
			return $levelGroupsMap[ $id ];
		}
		else
		{
			return JText::_('COM_AKEEBASUBS_SELECT_LEVELGROUP');
		}
	}

	/**
	 * Format a list of subscription levels, as used in invoice templates
	 *
	 * @param   string|array  $ids  An array or a comma-separated list of IDs
	 *
	 * @return  string
	 */
	public static function formatInvTempLevels($ids)
	{
		if (empty($ids))
		{
			return JText::_('COM_AKEEBASUBS_COMMON_LEVEL_ALL');
		}
		if (empty($ids))
		{
			return JText::_('COM_AKEEBASUBS_COMMON_LEVEL_NONE');
		}

		if (!is_array($ids))
		{
			$ids = explode(',', $ids);
		}

		static $levels;

		if (empty($levels))
		{
			$levelsList = Container::getInstance('com_akeebasubs')->factory->model('Levels')->tmpInstance()->get(true);

			if (!empty($levelsList))
			{
				foreach ($levelsList as $level)
				{
					$levels[ $level->akeebasubs_level_id ] = $level->title;
				}
			}

			$levels[ - 1 ] = JText::_('COM_AKEEBASUBS_COMMON_LEVEL_NONE');
			$levels[0]     = JText::_('COM_AKEEBASUBS_COMMON_LEVEL_ALL');
		}

		$ret = array();

		foreach ($ids as $id)
		{
			if (array_key_exists($id, $levels))
			{
				$ret[] = $levels[ $id ];
			}
			else
			{
				$ret[] = '&mdash;';
			}
		}

		return implode(', ', $ret);
	}

	/**
	 * Return the human readable name of the invoicing extension
	 *
	 * @param   string  $extension  The invoicing extension code
	 *
	 * @return  string
	 */
	public static function formatInvoicingExtension($extension)
	{
		static $map = null;

		if (is_null($map))
		{
			/** @var Akeeba\Subscriptions\Admin\Model\Invoices $model */
			$model = Container::getInstance('com_akeebasubs')->factory
				->model('Invoices')->tmpInstance();

			$map = $model->getExtensions(2);
		}

		if (array_key_exists($extension, $map))
		{
			return $map[ $extension ];
		}
		else
		{
			return $extension;
		}
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
