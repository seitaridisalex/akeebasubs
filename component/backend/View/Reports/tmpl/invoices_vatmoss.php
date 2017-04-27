<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

use FOF30\Date\Date;

defined('_JEXEC') or die;

/** @var \FOF30\View\View $this */

if ($this->input->getCmd('tmpl', 'html') != 'component')
{
	echo $this->loadAnyTemplate('admin:com_akeebasubs/Reports/invoices_controls', array('params' => $this->params));
}
else
{
	$this->addJavascriptInline("\n\nwindow.print();");
}

$date = new Date($this->params['year'] . '-' . $this->params['month'] . '-01');
?>

<h1>
	<?php echo JText::sprintf('COM_AKEEBASUBS_REPORTS_VATMOSS_TITLE', $date->format('F Y')); ?>
</h1>

<table width="100%" cellspacing="0" cellpadding="0" style="border-top: thin solid #c0c0c0">
	<thead>
	<tr style="background-color: black; color: white">
		<th><?php echo JText::_('COM_AKEEBASUBS_REPORTS_INVOICES_COL_NUMBER') ?></th>
		<th><?php echo JText::_('COM_AKEEBASUBS_REPORTS_INVOICES_COL_ISSUED') ?></th>
		<th><?php echo JText::_('COM_AKEEBASUBS_REPORTS_INVOICES_COL_PAYMENT') ?></th>
		<th><?php echo JText::_('COM_AKEEBASUBS_REPORTS_INVOICES_COL_VATNR') ?></th>
		<th><?php echo JText::_('COM_AKEEBASUBS_REPORTS_INVOICES_COL_NET') ?></th>
		<th><?php echo JText::_('COM_AKEEBASUBS_REPORTS_INVOICES_COL_TAXPERCENT') ?></th>
		<th><?php echo JText::_('COM_AKEEBASUBS_REPORTS_INVOICES_COL_TAXAMOUNT') ?></th>
		<th><?php echo JText::_('COM_AKEEBASUBS_REPORTS_INVOICES_COL_PAYABLE') ?></th>
	</tr>
	</thead>
	<tbody>
	<?php
	$m = 1;
	$i = 0;

	$totalAmount = 0;
	$totalTax = 0;
	$totalNet = 0;
	$totalInvoicesShown = 0;

	$lastCountry = '';
	$countryInvoicesShown = 0;
	$countryAmount = 0;
	$countryTax = 0;
	$countryNet = 0;

	foreach ($this->records as $r):
		if ((($r->country != $lastCountry) && ($lastCountry != ''))):?>
		<tr style="background-color: black; color: white; font-weight: bold;">
			<td colspan="4">
				<?php echo JText::sprintf('COM_AKEEBASUBS_REPORTS_INVOICES_LBL_COUNTRYTOTAL', \Akeeba\Subscriptions\Admin\Helper\Select::formatCountry($lastCountry), $countryInvoicesShown); ?>
			</td>
			<td align="right">
				<?php echo $countryNet ?> &euro;
			</td>
			<td></td>
			<td align="right">
				<?php echo $countryTax ?> &euro;
			</td>
			<td align="right">
				<?php echo $countryAmount ?> &euro;
			</td>
		</tr>
		<tr style="background-color: white; color: white; font-weight: bold;">
			<td colspan="8">
				&nbsp;
			</td>
		</tr>
		<?php endif;

		if ($r->country != $lastCountry):
			$lastCountry = $r->country;
			$countryInvoicesShown = 0;
			$countryAmount = 0;
			$countryTax = 0;
			$countryNet = 0;

			?>
			<tr style="background-color: #ccc;">
				<td colspan="8" style="color: black; font-weight: bold; font-size: 15pt; text-align: center; padding: 6pt">
					<?php echo \Akeeba\Subscriptions\Admin\Helper\Select::formatCountry($r->country) ?>
				</td>
			</tr>
		<?php endif;

		$m = 1 - $m;
		$i++;
		$color = $m ? '#f0f0f0' : 'white';
		$tdStyle = 'style="border-bottom: thin solid black;  border-left: thin solid #c0c0c0;"';
		$tdStyleLast = 'style="border-bottom: thin solid black;  border-left: thin solid #c0c0c0; border-right: thin solid #c0c0c0"';

		$who = $r->isbusiness ? $r->businessname : $r->name;
		$occupation = $r->isbusiness ? $r->occupation : '';

		if ($occupation)
		{
			$occupation = "<span style=\"color: green; font-size: small;\">$occupation</span><br/>";
		}

		$address = $r->address1;
		$address .= $r->address2 ? ', ' . $r->address2 : '';

		$vatnumber = '';

		if ($r->isbusiness && ($r->tax_amount == 0))
		{
			$vatnumber = $r->vatnumber;
		}

		$totalAmount += $r->gross_amount;
		$countryAmount += $r->gross_amount;
		$totalTax += $r->tax_amount;
		$countryTax += $r->tax_amount;
		$totalNet += $r->net_amount;
		$countryNet += $r->net_amount;
		$totalInvoicesShown++;
		$countryInvoicesShown++;

		$r->net_amount = sprintf('%.02f', $r->net_amount);
		$r->tax_amount = sprintf('%.02f', $r->tax_amount);
		$r->gross_amount = sprintf('%.02f', $r->gross_amount);
		$r->tax_percent = sprintf('%.02f', $r->tax_percent);
		?>
		<tr style="background-color: <?php echo $color ?>;">
			<td <?php echo $tdStyle ?> width="100">
				# <span style="font-weight: bold;"><?php echo $r->number ?></span><br/>
				<span style="padding-left: 1em"><?php echo $r->invoice_date ?></span>
			</td>
			<td <?php echo $tdStyle ?>>
				<span style="font-weight: bold;"><?php echo $who ?></span><br/>
				<?php echo $occupation ?>
				<span style="color: #333; font-size: small;">
					<?php echo $address ?> &bull; <?php echo $r->zip ?> &bull;
					<?php echo $r->city ?> &bull; <?php echo $r->country ?>
				</span><br/>
			</td>
			<td <?php echo $tdStyle ?> width="220">
					<span style="color: #333; font-size: small;">
						<?php echo $r->processor ?><br/>
						<?php echo $r->processor_key ?>
					</span>
			</td>
			<td <?php echo $tdStyle ?> width="120">
				<?php echo $vatnumber ?>
			</td>
			<td <?php echo $tdStyle ?> align="right" width="80">
				<?php echo $r->net_amount ?> &euro;&nbsp;
			</td>
			<td <?php echo $tdStyle ?> align="right" width="80">
				<?php echo $r->tax_percent ?> %&nbsp;
			</td>
			<td <?php echo $tdStyle ?> align="right" width="80">
				<?php echo $r->tax_amount ?> &euro;&nbsp;
			</td>
			<td <?php echo $tdStyle ?> align="right" width="80">
				<?php echo $r->gross_amount ?> &euro;&nbsp;
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
	<tfoot>
	<tr style="background-color: black; color: white; font-weight: bold;">
		<td colspan="4">
			<?php echo JText::sprintf('COM_AKEEBASUBS_REPORTS_INVOICES_LBL_COUNTRYTOTAL', \Akeeba\Subscriptions\Admin\Helper\Select::formatCountry($lastCountry), $countryInvoicesShown); ?>
		</td>
		<td align="right">
			<?php echo $countryNet ?> &euro;
		</td>
		<td></td>
		<td align="right">
			<?php echo $countryTax ?> &euro;
		</td>
		<td align="right">
			<?php echo $countryAmount ?> &euro;
		</td>
	</tr>
	<tr style="background-color: white; color: white; font-weight: bold;">
		<td colspan="8">
			&nbsp;
		</td>
	</tr>
	<tr style="background-color: navy; color: white; font-weight: bold;">
		<td colspan="4">
			<?php echo JText::sprintf('COM_AKEEBASUBS_REPORTS_INVOICES_LBL_GRANDTOTAL', $totalInvoicesShown); ?>
		</td>
		<td align="right">
			<?php echo $totalNet ?> &euro;
		</td>
		<td></td>
		<td align="right">
			<?php echo $totalTax ?> &euro;
		</td>
		<td align="right">
			<?php echo $totalAmount ?> &euro;
		</td>
	</tr>
	</tfoot>
</table>