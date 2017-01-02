<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use \Akeeba\Subscriptions\Admin\Helper\Select;

JHtml::_('formbehavior.chosen');

/** @var \FOF30\View\View $this */

$optionsMonths = array(
	JHtml::_('select.option', 1, JText::_('JANUARY')),
	JHtml::_('select.option', 2, JText::_('FEBRUARY')),
	JHtml::_('select.option', 3, JText::_('MARCH')),
	JHtml::_('select.option', 4, JText::_('APRIL')),
	JHtml::_('select.option', 5, JText::_('MAY')),
	JHtml::_('select.option', 6, JText::_('JUNE')),
	JHtml::_('select.option', 7, JText::_('JULY')),
	JHtml::_('select.option', 8, JText::_('AUGUST')),
	JHtml::_('select.option', 9, JText::_('SEPTEMBER')),
	JHtml::_('select.option', 10, JText::_('OCTOBER')),
	JHtml::_('select.option', 11, JText::_('NOVEMBER')),
	JHtml::_('select.option', 12, JText::_('DECEMBER')),
);

$optionsYears = array();
$nextYear = intval(gmdate('Y')) + 1;

for ($year = 2010; $year <= $nextYear; $year++)
{
	$optionsYears[] = JHtml::_('select.option', $year, $year);
}

$printUrl = JURI::getInstance();
$printUrl->setVar('tmpl', 'component');
$template_id = isset($params['template_id']) ? $params['template_id'] : null;
?>

<form action="index.php" method="get" name="invoiceControlsForm" class="form form-horizontal">
	<input type="hidden" name="option" value="com_akeebasubs">
	<input type="hidden" name="view" value="Reports">
	<input type="hidden" name="task" value="<?php echo $this->input->getCmd('task', 'invoices') ?>">

	<?php echo JHtml::_('select.genericlist', $optionsMonths, 'month', array(
		'onchange' => 'document.forms.invoiceControlsForm.submit()'
	), 'value', 'text', $params['month']) ?>
	<?php echo JHtml::_('select.genericlist', $optionsYears, 'year', array(
		'onchange' => 'document.forms.invoiceControlsForm.submit()'
	), 'value', 'text', $params['year']) ?>
	<?php
		echo Select::invoicetemplates(
			'template_id[]',
			$template_id,
			array('multiple' => 'true', 'size' => 5, 'class' => 'advancedSelect')
		)
	?>

	<button class="btn btn-primary">
		<span class="icon icon-search"></span>
		<?php echo JText::_('COM_AKEEBASUBS_REPORTS_INVOICES_BTN_LOAD')?>
	</button>

	<a href="<?php echo $printUrl ?>" class="btn btn-primary" target="_blank">
		<span class="icon icon-print"></span>
		<?php echo JText::_('COM_AKEEBASUBS_REPORTS_INVOICES_BTN_PRINT'); ?>
	</a>
</form>