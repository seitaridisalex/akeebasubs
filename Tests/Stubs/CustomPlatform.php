<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Stubs;

use FOF30\Platform\Joomla\Platform;

class CustomPlatform extends Platform
{
	/** @var   array   Fake plugin event handlers. Format: eventName => callable[] */
	protected static $eventHandlers = [];

	/**
	 * Load plugins of a specific type. Obviously this seems to only be required
	 * in the Joomla! CMS.
	 *
	 * @param   string $type The type of the plugins to be loaded
	 *
	 * @see PlatformInterface::importPlugin()
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	public function importPlugin($type)
	{
		return;
	}

	/**
	 * Reset the fake event handlers
	 */
	static function resetEventHandlers()
	{
		static::$eventHandlers = [];
	}

	/**
	 * Add a fake event handler
	 *
	 * @param   string   $event    Name of the event
	 * @param   callable $handler  Event handler
	 */
	static function addEventHandler($event, callable $handler)
	{
		$event = strtolower($event);

		if (!isset(static::$eventHandlers[$event]))
		{
			static::$eventHandlers[$event] = [];
		}

		static::$eventHandlers[$event][] = $handler;
	}

	/**
	 * Execute plugins (system-level triggers) and fetch back an array with
	 * their return values.
	 *
	 * @param   string $event The event (trigger) name, e.g. onBeforeScratchMyEar
	 * @param   array  $data  A hash array of data sent to the plugins as part of the trigger
	 *
	 * @see PlatformInterface::runPlugins()
	 *
	 * @return  array  A simple array containing the results of the plugins triggered
	 *
	 * @codeCoverageIgnore
	 */
	public function runPlugins($event, $data)
	{
		$return = [];

		$event = strtolower($event);

		if (isset(static::$eventHandlers[$event]))
		{
			foreach (static::$eventHandlers[$event] as $handler)
			{
				$return[] = call_user_func_array($handler, $data);
			}
		}

		return $return;
	}
}