<?php

/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */
defined('_JEXEC') or die();

include_once JPATH_LIBRARIES . '/fof30/include.php';

use \FOF30\Inflector\Inflector;

function AkeebasubsBuildRoute(&$query)
{
	$segments = array();

	// Default view
	$default = 'Levels';

	// We need to find out if the menu item link has a view param
	if (array_key_exists('Itemid', $query))
	{
		$menu = JFactory::getApplication()->getMenu()->getItem($query['Itemid']);

		if (!is_object($menu))
		{
			$menuquery = array();
		}
		else
		{
			parse_str(str_replace('index.php?', '', $menu->link), $menuquery); // remove "index.php?" and parse
		}
	}
	else
	{
		$menuquery = array();
	}

	// Add the view
	$newView = array_key_exists('view', $query) ? $query['view'] :
		(array_key_exists('view', $menuquery) ? $menuquery['view'] : $default);

	$newView = ucfirst($newView);

	if ($newView == 'Level')
	{
		$newView = 'New';
	}
	elseif ($newView == 'Message')
	{
		if (!array_key_exists('layout', $query))
		{
			$query['layout'] = 'order';
		}

		if ($query['layout'] == 'order')
		{
			$newView = 'ThankYou';
		}
		else
		{
			$newView = 'Cancelled';
		}

		unset($query['layout']);
	}
	elseif (($newView == 'Userinfo') || ($newView == 'UserInfo'))
	{
		$newView = 'UserInfo';

		if (!array_key_exists('layout', $query))
		{
			unset($query['layout']);
		}
	}

	$segments[] = strtolower($newView);
	unset($query['view']);

	// Add the slug
	if ($newView != 'userinfo')
	{
		$container = \FOF30\Container\Container::getInstance('com_akeebasubs');

		if (array_key_exists('slug', $query) && ($container->inflector->isSingular($segments[0]) || ($segments[0] == 'new')))
		{
			$segments[1] = $query['slug'];
			unset($query['slug']);
		}
		elseif (array_key_exists('id', $query) && ($segments[0] == 'subscription'))
		{
			$segments[1] = $query['id'];
			unset($query['id']);
		}
	}

	return $segments;
}

function AkeebasubsParseRoute($segments)
{
	// accepted views:
	$views = array('new', 'thankyou', 'cancelled', 'level', 'levels', 'message', 'subscribe', 'subscription', 'subscriptions', 'callback', 'validate', 'userinfo', 'invoices', 'invoice');

	// accepted layouts:
	$layoutsAccepted = array(
		'Messages' => array('order', 'cancel'),
		'Invoice' => array('item')
	);

	// default view
	$default = 'levels';

	$mObject = JFactory::getApplication()->getMenu()->getActive();
	$menu = is_object($mObject) ? $mObject->query : array();

	// circumvent the auto-segment decoding
	$segments = str_replace(':', '-', $segments);

	$vars = array();

	// if there's no view, but the menu item has view info, we use that
	if (count($segments))
	{
		if (!in_array($segments[0], $views))
		{
			$vars['view'] = array_key_exists('view', $menu) ? $menu['view'] : $default;
		}
		else
		{
			$vars['view'] = array_shift($segments);
		}

		switch ($vars['view'])
		{
			case 'New':
			case 'new':
				$vars['view'] = 'Level';
				$vars['task'] = 'read';
				break;

			case 'Invoices':
			case 'invoices':
				$vars['view'] = 'Invoices';
				$vars['layout'] = 'default';
				break;

			case 'Invoice':
			case 'invoice':
				$vars['view'] = 'Invoice';
				$vars['task'] = 'read';
				$vars['layout'] = 'item';
				break;

			case 'thankyou':
			case 'Thankyou':
			case 'ThankYou':
				$vars['view'] = 'Messages';
				$vars['task'] = 'thankyou';
				$vars['layout'] = 'order';
				break;

			case 'cancelled':
			case 'Cancelled':
				$vars['view'] = 'Messages';
				$vars['task'] = 'cancel';
				$vars['layout'] = 'cancel';
				break;

			case 'userinfo':
			case 'Userinfo':
			case 'UserInfo':
				$vars['view'] = 'UserInfo';
				$vars['task'] = 'read';
				$vars['layout'] = 'default';
				break;
		}

		array_push($segments, $vars['view']);

		if (array_key_exists('layout', $vars))
		{
			array_unshift($segments, $vars['layout']);
		}

		$layouts = array_key_exists($vars['view'], $layoutsAccepted) ? $layoutsAccepted[$vars['view']] : array();

		if (!in_array($segments[0], $layouts))
		{
			$vars['layout'] = array_key_exists('layout', $menu) ? $menu['layout'] : 'default';
		}
		else
		{
			$vars['layout'] = array_shift($segments);
		}

		// if we are in a singular view, the next item is the slug, unless we are in the userinfo view
		$container = \FOF30\Container\Container::getInstance('com_akeebasubs');

		if ($container->inflector->isSingular($vars['view']) && ($vars['view'] != 'UserInfo'))
		{
			if (in_array($vars['view'], array('Subscription', 'Invoice')))
			{
				$vars['id'] = array_shift($segments);
			}
			else
			{
				$vars['slug'] = array_shift($segments);
			}
		}
	}

	return $vars;
}