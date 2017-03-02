<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\Subscriptions\Admin\Model\Invoices;

defined('_JEXEC') or die();

/** @var  \Akeeba\Subscriptions\Admin\Model\Subscriptions $model */

\JHtml::_('behavior.modal');

$invoice      = $model->invoice;
$needsInvoice = is_null($invoice) || !($invoice instanceof Invoices) || empty($invoice->display_number);
$returnURL    = 'index.php?option=com_akeebasubs&view=Subscriptions&task=edit&id=' . $model->akeebasubs_subscription_id;
?>

<h3>
	<?php echo JText::_('COM_AKEEBASUBS_MAINMENU_INVOICES'); ?>
</h3>

<?php
if ($needsInvoice): ?>
	<a
		class="btn btn-large btn-primary"
		href="index.php?option=com_akeebasubs&view=Invoices&task=generateForSubscription&id=<?php echo $model->akeebasubs_subscription_id ?>&returnurl=<?php echo base64_encode($returnURL) ?>">
		<span class="icon icon-white icon-refresh"></span>
		<?php echo JText::_('COM_AKEEBASUBS_INVOICES_ACTION_REGENERATE') ?>
	</a>
<?php else: ?>
	<h4>
		<a
			href="index.php?option=com_akeebasubs&view=Invoices&akeebasubs_subscription_id=<?php echo $model->akeebasubs_subscription_id ?>"
		    target="_blank"
		>
			#<?php echo $invoice->display_number ?>
		</a>
	</h4>
	<a
		class="btn btn-info modal"
		href="index.php?option=com_akeebasubs&view=Invoices&task=read&id=<?php echo $model->akeebasubs_subscription_id ?>&tmpl=component"
		rel="{handler: 'iframe', size: {x: 800, y: 500}}"
	>
		<span class="icon icon-file icon-white"></span>
		<?php echo JText::_('COM_AKEEBASUBS_INVOICES_ACTION_PREVIEW'); ?>
	</a>
	<a
		class="btn btn-primary"
		href="index.php?option=com_akeebasubs&view=Invoices&task=download&id=<?php echo $model->akeebasubs_subscription_id ?>"
	>
		<span class="icon icon-download icon-white"></span>
		<?php echo JText::_('COM_AKEEBASUBS_INVOICES_ACTION_DOWNLOAD'); ?>
	</a>
<?php endif; ?>
