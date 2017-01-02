<?php
/**
 * @package      akeebasubs
 * @copyright    Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license      GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 * @version      $Id$
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
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

if (JFactory::getUser()->guest)
{
	echo '&nbsp;';
}
else
{
	/** @var \Akeeba\Subscriptions\Site\Model\Subscriptions $subsModel */
	$subsModel = $container->factory->model('Subscriptions')->tmpInstance();
	$list = $subsModel
		->user_id(JFactory::getUser()->id)
		->enabled(1)
		->get(true);

	if (!$list->count())
	{
		echo "&nbsp;";

		return;
	}

	JLoader::import('joomla.utilities.date');

	$expires = 0;
	$regex   = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';

	/** @var \Akeeba\Subscriptions\Site\Model\Subscriptions $s */
	foreach ($list as $s)
	{
		if (!preg_match($regex, $s->publish_down))
		{
			$s->publish_down = '2037-01-01';
		}

		$ed = new JDate($s->publish_down);
		$ex = $ed->toUnix();

		if ($ex > $expires)
		{
			$expires = $ex;
		}
	}

	$ed = new JDate($expires);
	echo JText::sprintf('MOD_AKSEXPIRES_EXPIRESON', $ed->format(JText::_('DATE_FORMAT_LC1'), true));
}