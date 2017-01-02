<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Form\Field;

use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use Akeeba\Subscriptions\Admin\Helper\Format;
use FOF30\Form\Field\Text;

defined('_JEXEC') or die;

class PublishRangeDates extends Text
{
	public function getRepeatable()
	{
		/** @var Subscriptions $subscription */
		$subscription = $this->item;

		$publish_up = Format::date($subscription->publish_up, 'Y-m-d H:i');
		$publish_down = Format::date($subscription->publish_down, 'Y-m-d H:i');

		return <<< HTML
<div class="akeebasubs-susbcription-publishup">
	$publish_up
</div>
<div class="akeebasubs-susbcription-publishdown">
	$publish_down
</div>
HTML;

	}
}