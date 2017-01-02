<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Helper;

use FOF30\Container\Container;
use JUri;

defined('_JEXEC') or die;

/**
 * A helper class for getting the URL to subscription level images
 */
abstract class Image
{
	/**
	 * Get the absolute subscription level image URL when given a partial path. The partial path is a relative path to
	 * either the site root (that's how Joomla! 3 stores it) or the imagedir directory (that's how Joomla! 2.5 used to
	 * store it). We'll figure out which one it is and return the correct URL.
	 *
	 * @param   string  $filename  The relative path
	 *
	 * @return  string  The asbolute URL to the image file
	 */
	public static function getURL($filename)
	{
		// Get the base site URL
		$url = JURI::base();
		$url = rtrim($url, '/');

		// Take into account relative URL for administrator
		$container = Container::getInstance('com_akeebasubs');

		if ($container->platform->isBackend())
		{
			$url .= '/..';
		}

		$imagePath = trim(self::getContainer()->params->get('imagedir', 'images/'), '/');

		// Where is the image (we search for images in J! 2.5 compatible paths to cater for migrated sites)?
		$testJ30 = JPATH_SITE . '/' . $filename;

		if (@file_exists($testJ30))
		{
			return $url . '/' . $filename;
		}
		else
		{
			return $url . '/' . $imagePath . '/' . $filename;
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
