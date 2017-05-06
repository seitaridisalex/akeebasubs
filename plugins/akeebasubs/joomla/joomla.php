<?php
/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

/**
 * Akeeba Subscriptions - Joomla! User Groups integration
 *
 * Adds and removes users to Joomla! user groups when the subscription state changes
 */
class plgAkeebasubsJoomla extends \Akeeba\Subscriptions\Admin\PluginAbstracts\AkeebasubsBase
{
	/**
	 * Public constructor
	 *
	 * @param object $subject
	 * @param array  $config
	 */
	public function __construct(& $subject, $config = array())
	{
		$config['templatePath'] = dirname(__FILE__);
		$config['name']         = 'joomla';

		parent::__construct($subject, $config);
	}

	/**
	 * Called whenever the administrator asks to refresh integration status.
	 *
	 * @param   int $user_id The Joomla! user ID to refresh information for.
	 *
	 * @return  void
	 */
	public function onAKUserRefresh($user_id)
	{
		// Load groups
		$addGroups    = array();
		$removeGroups = array();
		$this->loadUserGroups($user_id, $addGroups, $removeGroups);

		if (empty($addGroups) && empty($removeGroups))
		{
			return;
		}

		// Get DB connection
		$db = $this->container->db;

		// Add to Joomla! groups
		if (!empty($addGroups))
		{
			// 1. Delete existing assignments
			$groupSet = array();

			foreach ($addGroups as $group)
			{
				$groupSet[] = $db->q($group);
			}

			$query = $db->getQuery(true)
			            ->delete($db->qn('#__user_usergroup_map'))
			            ->where($db->qn('user_id') . ' = ' . $user_id)
			            ->where($db->qn('group_id') . ' IN (' . implode(', ', $groupSet) . ')');

			$db->setQuery($query);
			$db->execute();

			// 2. Add new assignments
			$query = $db->getQuery(true)
			            ->insert($db->qn('#__user_usergroup_map'))
			            ->columns(array(
				            $db->qn('user_id'),
				            $db->qn('group_id'),
			            ));

			foreach ($addGroups as $group)
			{
				$query->values($db->q($user_id) . ', ' . $db->q($group));
			}

			$db->setQuery($query);
			$db->execute();
		}

		// Remove from Joomla! groups
		if (!empty($removeGroups))
		{
			$query    = $db->getQuery(true)
			               ->delete($db->qn('#__user_usergroup_map'))
			               ->where($db->qn('user_id') . ' = ' . $db->q($user_id));

			$groupSet = array();

			foreach ($removeGroups as $group)
			{
				$groupSet[] = $db->q($group);
			}

			$query->where($db->qn('group_id') . ' IN (' . implode(', ', $groupSet) . ')');
			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Used by the template to render selection fields
	 *
	 * @param   \Akeeba\Subscriptions\Admin\Model\Levels  $level  The subscription level
	 * @param   string                                    $type   add or remove
	 *
	 * @return  string  The HTML for the drop-down field
	 */
	protected function getSelectField(\Akeeba\Subscriptions\Admin\Model\Levels $level, $type)
	{
		if (!in_array($type, ['add', 'remove']))
		{
			return '';
		}

		$key = "joomla_{$type}groups";

		if (isset($level->params[$key]))
		{
			$groupList = $level->params[$key];
		}
		else
		{
			$groupList = array();
		}

		return JHtml::_('access.usergroup', "params[$key][]", $groupList, array(
			'multiple' => 'multiple',
			'size'     => 8,
			'class'    => 'input-large'
		), false);
	}
}