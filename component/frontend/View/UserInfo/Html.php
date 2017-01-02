<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\View\UserInfo;

use FOF30\View\View;

defined('_JEXEC') or die;

class Html extends View
{
    /**
     * Get the value of a field from the session cache. If it's empty use the value from the user parameters cache.
     *
     * @param   string  $fieldName    The name of the field
     * @param   array   $emptyValues  A list of values considered to be "empty" for the purposes of this method
     *
     * @return  mixed  The field value
     */
    public function getFieldValue($fieldName, array $emptyValues = [])
    {
        $cacheValue = null;
        $userparamsValue = null;

        if (isset($this->cache[$fieldName]))
        {
            $cacheValue = $this->cache[$fieldName];
        }

        if (isset($this->userparams->{$fieldName}))
        {
            $userparamsValue = $this->userparams->{$fieldName};
        }

        if (is_null($cacheValue))
        {
            return $userparamsValue;
        }

        if (!empty($emptyValues) && in_array($cacheValue, $emptyValues))
        {
            return $userparamsValue;
        }

        return $cacheValue;
    }
}