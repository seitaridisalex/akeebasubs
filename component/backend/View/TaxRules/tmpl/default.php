<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();
?>
<div class="row-fluid j-toggle-main j-toggle-transition span12">
	<div class="span12">
		<a href="index.php?option=com_akeebasubs&view=TaxConfigs" class="btn btn-primary">
			<i class="icon-white icon-wand"></i>
			<?php echo JText::_('COM_AKEEBASUBS_TITLE_TAXCONFIGS') ?>
		</a>
	</div>
</div>
<?php
echo $this->getRenderedForm();