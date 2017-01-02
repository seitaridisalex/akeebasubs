<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Form\Field;

use FOF30\Form\Field\Text;
use JText;

defined('_JEXEC') or die;

class PaymentStatus extends Text
{
	public function getRepeatable()
	{
		$state = $this->value;
		$stateLower = strtolower($state);
		$stateLabel = htmlspecialchars(JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE_' . $this->value));

		$processor = htmlspecialchars($this->item->processor);
		$processorKey = htmlspecialchars($this->item->processor_key);

		return <<< HTML
<span class="akeebasubs-payment akeebasubs-payment-$stateLower hasTip"
	title="$stateLabel::$processor &bull; $processorKey">
</span>

<span class="akeebasubs-subscription-processor">
	$processor
</span>
HTML;

	}
}