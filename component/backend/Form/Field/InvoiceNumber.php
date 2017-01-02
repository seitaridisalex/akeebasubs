<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Form\Field;

use Akeeba\Subscriptions\Admin\Model\Invoices;
use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use FOF30\Form\Field\Text;

defined('_JEXEC') or die;

class InvoiceNumber extends Text
{
	public function getRepeatable()
	{
		static $invoicetemplates = null;

		if (is_null($invoicetemplates))
		{
			/** @var Invoices $model */
			$model = $this->form->getModel();

			$invoicetemplates = $model->getInvoiceTemplateNames();
		}

		$canIssueCreditNote = ($this->item->extension == 'akeebasubs') &&
			is_object($this->item->subscription) &&
			($this->item->subscription instanceof Subscriptions) &&
			($this->item->subscription->state == 'X') &&
			!is_object($this->item->creditNote) &&
			is_object($this->item->template->creditNoteTemplate);

		$hasCreditNote = !$canIssueCreditNote &&
			($this->item->extension == 'akeebasubs') &&
			is_object($this->item->subscription) &&
			($this->item->subscription instanceof Subscriptions) &&
			($this->item->subscription->state == 'X') &&
			is_object($this->item->creditNote);

		$value = '';

		if (
			($this->item->extension == 'akeebasubs')
			&& array_key_exists($this->item->akeebasubs_invoicetemplate_id, $invoicetemplates)
		)
		{
			$value .= '<span class="label label-info">' . $invoicetemplates[$this->item->akeebasubs_invoicetemplate_id]->title . '</span> ';
		}

		if ($canIssueCreditNote)
		{
			$value .= '<span class="label label-important">';
		}
		elseif ($hasCreditNote)
		{
			$value .= '<span class="label label-inverse">';
		}

		if (!empty($this->item->display_number))
		{
			$value .= htmlentities($this->item->display_number, ENT_COMPAT, 'UTF-8');
		}
		else
		{
			$value .= htmlentities($this->item->invoice_no, ENT_COMPAT, 'UTF-8');
		}

		if ($canIssueCreditNote || $hasCreditNote)
		{
			$value .= '</span>';
		}

		return $value;
	}
}