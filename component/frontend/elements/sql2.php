<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die();

/**
 * Our main element class, creating a multi-select list out of an SQL statement
 */
class JFormFieldSQL2 extends JFormField
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'SQL2';
	
	function getInput()
	{
		$db			= JFactory::getDBO();
		$db->setQuery($this->element['query']);
		$nodes = $db->loadObjectList();
		$key = ($this->element['key_field'] ? $this->element['key_field'] : 'value');
		$val = ($this->element['value_field'] ? $this->element['value_field'] : $this->name);
		$defaultOption = array(
			(object)array((string)$key=>'',(string)$val=>JText::_('COM_AKEEBASUBS_SELECT_GENERIC'))
		);
		$nodes = array_merge($defaultOption, $nodes);
		return JHTML::_('select.genericlist',  $nodes, $this->name.'[]', 'multiple="multiple"', $key, $val, $this->value, $this->id);
	}
}