<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

$fieldName = (string)$fieldElement['name'];

echo \Akeeba\Subscriptions\Admin\Helper\Select::paymentmethods(
	$fieldName . '[]',
	$fieldValue,
	array(
		'id' => $fieldName,
		'multiple' => 'multiple',
		'always_dropdown' => 1,
		'default_option' => 1
	)
) ?>