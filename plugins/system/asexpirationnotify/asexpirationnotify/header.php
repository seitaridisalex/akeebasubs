<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id: header.php 218 2011-06-02 10:45:00Z nikosdion $
 */

defined('_JEXEC') or die();

/**
 * Our main element class
 */
class JFormFieldHeader extends JFormField
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'header';

	function fetchElement($name, $value, &$node, $control_name)
	{
		return '<hr/>';
	}
	
	function getInput()
	{
		return '';
	}
}