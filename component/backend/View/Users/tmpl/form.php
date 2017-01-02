<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

JHtml::_('behavior.tooltip');
JHtml::_('behavior.modal');

$userEditorLink = 'index.php?option=com_users&task=user.edit&id=' . $this->item->user_id;

if($this->item->user_id): ?>
<div class="row-fluid">
	<div class="span12">
		<a class="btn btn-inverse" href="<?php echo $userEditorLink?>">
			<span class="icon-pencil icon-white"></span>
			<?php echo JText::_('COM_AKEEBASUBS_USER_EDITTHISINJUSERMANAGER')?>
		</a>
	</div>
</div>
<?php endif; ?>

<?php echo $this->getRenderedForm(); ?>