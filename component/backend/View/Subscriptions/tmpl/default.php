<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

/** @var \FOF30\View\DataView\Form $this */

// Protect from unauthorized access
defined('_JEXEC') or die();

$this->addJavascriptFile('media://com_akeebasubs/js/blockui.js');

JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('dropdown.init');
JHtml::_('formbehavior.chosen', 'select');

echo $this->getRenderedForm();

?>

<div id="refreshMessage" style="display:none">
	<h3><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_SUBREFRESH_TITLE');?></h3>
	<p><img id="asriSpinner" src="<?php echo JURI::base()?>../media/com_akeebasubs/images/throbber.gif" align="center" /></p>
	<p><span id="asriPercent">0</span><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_SUBREFRESH_PROGRESS')?></p>
</div>

<script type="text/javascript">
	var akeebasubs_token = "<?php echo $this->container->platform->getToken(true);?>";

	(function($) {
		$(document).ready(function(){
			$('#toolbar-subrefresh').click(akeebasubs_refresh_integrations);
		});
	})(akeeba.jQuery);
</script>
