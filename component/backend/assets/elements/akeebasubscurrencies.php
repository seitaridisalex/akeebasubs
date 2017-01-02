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

class JFormFieldAkeebasubscurrencies extends JFormFieldList
{
    protected function getOptions()
    {
        $db      = JFactory::getDbo();
        $options[] = array('value' => '', 'text' => ' - '.JText::_('COM_AKEEBASUBS_COMMON_SELECT').' - ');

        $query = $db->getQuery(true)
                    ->select('*')
                    ->from($db->qn('#__akeebasubs_countrycurrencies'))
                    ->order($db->qn('name').' ASC')
                    ->group($db->qn('currency'));

        $rows = $db->setQuery($query)->loadObjectList();

        foreach($rows as $row)
        {
            $options[] = array('value' => $row->currency, 'text' => $row->currency.' '.$row->name);
        }

        return $options;
    }
}