<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Form\Field;

use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use FOF30\Form\Field\Text;
use JText;

defined('_JEXEC') or die;

class SubscriptionDiscount extends Text
{
	public function getRepeatable()
	{
		/** @var Subscriptions $subscription */
		$subscription = $this->item;

		if ($subscription->akeebasubs_coupon_id)
		{
			$title = JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_DISCOUNT_COUPON');
			$discountTitle = empty($subscription->coupon->title) ? '&mdash;&mdash;&mdash;' : $subscription->coupon->title;

			return <<< HTML
<span class="akeebasubs-subscription-discount-coupon" title="$title">
	<span class="discount-icon"></span>
	$discountTitle
</span>
HTML;
		}

		if ($subscription->akeebasubs_upgrade_id)
		{
			$title = JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_DISCOUNT_UPGRADE');

			$discountTitle = empty($subscription->upgrade->title) ? '&mdash;&mdash;&mdash;' : $subscription->upgrade->title;

			return <<< HTML
<span class="akeebasubs-subscription-discount-upgrade" title="$title">
	<span class="discount-icon"></span>
	$discountTitle
</span>
HTML;
		}

		$discountTitle = JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_DISCOUNT_NONE');

		return <<< HTML
<span class="akeebasubs-subscription-discount-none">
	$discountTitle
</span>
HTML;

	}
}