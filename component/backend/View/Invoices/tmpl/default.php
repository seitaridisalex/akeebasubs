<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHtml::_('behavior.modal', 'a.modal');

$nullDate = \JFactory::getDbo()->getNullDate();
$extensions = $this->getModel()->getExtensions();
?>

<?php if(empty($extensions)): ?>
<div class="alert alert-error">
	<p>
	<?php echo JText::_('COM_AKEEBASUBS_INVOICES_MSG_EXTENSIONS_NONE'); ?>
	</p>
</div>
<?php else: ?>
<div class="alert alert-info">
	<p>
		<?php echo JText::_('COM_AKEEBASUBS_INVOICES_MSG_EXTENSIONS_SOME'); ?>
	</p>

	<ul>
	<?php foreach ($extensions as $key => $extension): ?>
		<li><?php echo $extension['title'] ?></li>
	<?php endforeach; ?>
	</ul>
<?php if(count($extensions) > 1): ?>
	<p><strong>
		<?php echo JText::_('COM_AKEEBASUBS_INVOICES_MSG_EXTENSIONS_MULTIPLE'); ?>
	</strong></p>
<?php endif; ?>
</div>
<?php endif; ?>

<?php echo $this->getRenderedForm();