<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Form\Field;

use Akeeba\Subscriptions\Admin\Model\APICoupons;
use FOF30\Form\Field\Text;
use JText;

defined('_JEXEC') or die;

/**
 * Renders the limits imposed on API Coupon entries
 */
class APICouponLimits extends Text
{
	/**
	 * Get the rendering of this field type for a repeatable (grid) display,
	 * e.g. in a view listing many item (typically a "browse" task)
	 *
	 * @since 2.0
	 *
	 * @return  string  The field HTML
	 */
	public function getRepeatable()
	{
        /** @var APICoupons $item */
        $item   = $this->item;
		$limits = array();

		if ($item->subscriptions)
		{
			$limits[] = JText::_('COM_AKEEBASUBS_COUPONS_LIMITS_LEVELS');
		}

		if ($item->creation_limit)
		{
			$limits[] = JText::_('COM_AKEEBASUBS_COUPONS_LIMITS_HITS');
		}

        if ($item->value_limit)
        {
            $limits[] = JText::_('COM_AKEEBASUBS_COUPONS_LIMITS_VALUE');
        }

        $strLimits = implode(', ', $limits);

        $usage = $item->getApiLimits($item->key, $item->password);

        $strLimits .= '<br/><em>'.JText::_('COM_AKEEBASUBS_COUPONS_USAGE').': '.$usage['current'].'/'.$usage['limit'].'</em>';

		return $strLimits;
	}
}
