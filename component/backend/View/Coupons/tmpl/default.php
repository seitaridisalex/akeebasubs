<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHtml::_('behavior.tooltip');
?>
<div class="row-fluid">
	<div class="span2">
		&nbsp;
	</div>
	<div class="span10 j-toggle-main j-toggle-transition">
		<a href="index.php?option=com_akeebasubs&view=MakeCoupons" class="btn btn-success">
			<i class="icon icon-cog icon-white"></i>
			<?php echo JText::_('COM_AKEEBASUBS_TITLE_MAKECOUPONS')?>
		</a>
	</div>
</div>

<?php echo $this->getRenderedForm(); ?>