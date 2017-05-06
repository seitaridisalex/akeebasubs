<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Form\Field;

use Akeeba\Subscriptions\Admin\Model\Coupons;
use FOF30\Form\Field\TextArea;
use JFactory;
use JText;

defined('_JEXEC') or die;

/**
 * Renders the limits imposed on Coupon entries
 */
class CouponNotes extends TextArea
{
    public function getInput()
    {
        /** @var Coupons $item */
        $item   = $this->form->getModel();
        $params = $item->params;

        $this->value = '';

        if(isset($params['notes']))
        {
            $this->value = $params['notes'];
        }

        return parent::getInput();
    }

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
		$limits = array();

		if ($this->item->user)
		{
			$limits[] = JText::_('COM_AKEEBASUBS_COUPONS_LIMITS_USERS') . ' (' . $this->container->platform->getUser($this->item->user)->username . ')';
		}

		if ($this->item->email)
		{
			$limits[] = JText::_('COM_AKEEBASUBS_COUPONS_LIMITS_EMAIL') . ' (' . $this->item->email . ')';
		}

		if (count($this->item->subscriptions))
		{
			$limits[] = JText::_('COM_AKEEBASUBS_COUPONS_LIMITS_LEVELS');
		}

		if ($this->item->hitslimit)
		{
			$limits[] = JText::_('COM_AKEEBASUBS_COUPONS_LIMITS_HITS');
		}

		if ($this->item->userhits)
		{
			$limits[] = JText::_('COM_AKEEBASUBS_COUPONS_LIMITS_USERHITS');
		}

		$strLimits = implode(', ', $limits);

		return $strLimits;
	}
}
