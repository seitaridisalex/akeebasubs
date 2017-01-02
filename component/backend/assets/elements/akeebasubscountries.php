<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die('Restricted access');

if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
{
    throw new RuntimeException('FOF 3.0 is not installed', 500);
}

JFormHelper::loadFieldClass('list');

// Let's invoke our container in order to register the autoloader
\FOF30\Container\Container::getInstance('com_akeebasubs');

class JFormFieldAkeebasubscountries extends JFormFieldList
{
    protected function getOptions()
    {
        $options = \Akeeba\Subscriptions\Admin\Helper\Select::$countries;

        // Let's remove the "no value" option
        array_shift($options);

        return $options;
    }
}