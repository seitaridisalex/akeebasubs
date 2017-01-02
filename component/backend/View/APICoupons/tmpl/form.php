<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var \FOF30\View\DataView\Form $this */

\JHtml::_('jquery.framework', true);
\JHtml::_('formbehavior.chosen', 'select');

$this->addJavascriptInline(<<< JS

jQuery(document).ready(function()
{
	function akeebasubsOnUsageLimitsChange()
	{
		var value = jQuery('#usage_limits').val();

		if(value == 1)
		{
			jQuery("#creation_limit").show();
			jQuery("#subscription_limit").hide().val("0");
			jQuery("#value_limit").hide().val("0");
		}
		else if(value == 2)
		{
			jQuery("#creation_limit").hide().val("0");
			jQuery("#subscription_limit").show();
			jQuery("#value_limit").hide().val("0");
		}
		else
		{
			jQuery("#creation_limit").hide().val("0");
			jQuery("#subscription_limit").hide().val("0");
			jQuery("#value_limit").show();
		}
	}

	jQuery("#usage_limits").change(akeebasubsOnUsageLimitsChange);

	akeebasubsOnUsageLimitsChange();
})

JS

);
?>
<?php if ($this->item->akeebasubs_apicoupon_id > 0):
	$createURL = JUri::root() . 'index.php?option=com_akeebasubs&view=APICoupons&task=create&key=' .
		urlencode($this->item->key) . '&pwd=' . urlencode($this->item->password) .
		'&format=json';

    $limitsURL = JUri::root() . 'index.php?option=com_akeebasubs&view=APICoupons&task=getlimits&key=' .
        urlencode($this->item->key) . '&pwd=' . urlencode($this->item->password) .
        '&format=json';
	?>
	<div class="alert alert-info">
		<div><?php echo JText::sprintf('COM_AKEEBASUBS_APICOUPONS_INFO_URL', $createURL); ?></div>
        <div><?php echo JText::sprintf('COM_AKEEBASUBS_APICOUPONS_LIMITS_URL', $limitsURL); ?></div>
	</div>
<?php endif; ?>

<?php echo $this->getRenderedForm(); ?>