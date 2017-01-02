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
use JText;

defined('_JEXEC') or die;

class InvoiceActions extends Text
{
	/** @var Invoices */
	public $item = null;

	public function getRepeatable()
	{
		static $extensions = null;

		if (is_null($extensions))
		{
			/** @var Invoices $model */
			$model = $this->form->getModel();

			$extensions = $model->getExtensions();
		}

		$html = '';

		$canIssueCreditNote = ($this->item->extension == 'akeebasubs') &&
			is_object($this->item->subscription) &&
			($this->item->subscription instanceof Subscriptions) &&
			($this->item->subscription->state == 'X') &&
			!is_object($this->item->creditNote) &&
			is_object($this->item->template->creditNoteTemplate);

		$canShowCreditNote = !$canIssueCreditNote &&
			($this->item->extension == 'akeebasubs') &&
			is_object($this->item->subscription) &&
			($this->item->subscription instanceof Subscriptions) &&
			($this->item->subscription->state == 'X') &&
			is_object($this->item->creditNote);

		if ($this->item->extension == 'akeebasubs')
		{
			$html .= '<a href="index.php?option=com_akeebasubs&view=Invoices&task=read&id=' .
				htmlspecialchars($this->item->akeebasubs_subscription_id, ENT_COMPAT, 'UTF-8') .
				'&tmpl=component" class="btn btn-info btn-small modal" rel="{handler: \'iframe\', size: {x: 800, y: 500}}" title="' .
				JText::_('COM_AKEEBASUBS_INVOICES_ACTION_PREVIEW') . '"><span class="icon icon-file icon-white"></span></a>' .
				"\n";
			$html .= '<a href="index.php?option=com_akeebasubs&view=Invoices&task=download&id=' .
				htmlspecialchars($this->item->akeebasubs_subscription_id, ENT_COMPAT, 'UTF-8') .
				'" class="btn btn-primary btn-small" title="' .
				JText::_('COM_AKEEBASUBS_INVOICES_ACTION_DOWNLOAD')
				. '"><span class="icon icon-download icon-white"></span></a>' . "\n";
			$html .= '<a href="index.php?option=com_akeebasubs&view=Invoices&task=send&id=' .
				htmlentities($this->item->akeebasubs_subscription_id, ENT_COMPAT, 'UTF-8') .
				'" class="btn btn-success btn-small" title="' .
				JText::_('COM_AKEEBASUBS_INVOICES_ACTION_RESEND') .
				'"><span class="icon icon-envelope icon-white"></span></a>'
				. "\n";

			$db = \JFactory::getDbo();
			if (empty($this->item->sent_on) || ($this->item->sent_on == $db->getNullDate()))
			{
				$html .= '<span class="label"><span class="icon icon-white icon-warning"></span>' .
					JText::_('COM_AKEEBASUBS_INVOICES_LBL_NOTSENT') . '</span>' . "\n";
			}
			else
			{
				$html .= '<span class="label label-success"><span class="icon icon-white icon-ok"></span>' .
					JText::_('COM_AKEEBASUBS_INVOICES_LBL_SENT') . '</span>' . "\n";
			}

			$html .= '<a href="index.php?option=com_akeebasubs&view=Invoices&task=generate&id=' .
				htmlentities($this->item->akeebasubs_subscription_id, ENT_COMPAT, 'UTF-8') .
				'" class="btn btn-mini btn-warning" title="' .
				JText::_('COM_AKEEBASUBS_INVOICES_ACTION_REGENERATE') .
				'"><span class="icon icon-refresh icon-white"></span></a>'
				. "\n";

			if ($canIssueCreditNote)
			{
				$html .= '<br/><br/><a href="index.php?option=com_akeebasubs&view=CreditNotes&task=generate&id=' .
					htmlentities($this->item->akeebasubs_subscription_id, ENT_COMPAT, 'UTF-8') .
					'&returnurl=' . htmlentities(base64_encode('index.php?option=com_akeebasubs&view=Invoices')) .
					'" class="btn btn-small btn-danger" title="' .
					JText::_('COM_AKEEBASUBS_CREDITNOTES_ACTION_REGENERATE') .
					'"><span class="icon icon-cancel icon-white"></span>' .
					JText::_('COM_AKEEBASUBS_CREDITNOTES_ACTION_REGENERATE') . '</a>'
					. "\n";
			}
			elseif ($canShowCreditNote)
			{
				$html .= '<br/><br/><a href="index.php?option=com_akeebasubs&view=CreditNotes&task=download&id=' .
					htmlentities($this->item->akeebasubs_subscription_id, ENT_COMPAT, 'UTF-8') .
					'" class="btn btn-small btn-info" title="' .
					JText::_('COM_AKEEBASUBS_CREDITNOTES_ACTION_DOWNLOAD') .
					'"><span class="icon icon-download icon-white"></span>' .
					JText::_('COM_AKEEBASUBS_CREDITNOTES_ACTION_DOWNLOAD') . '</a>'
					. "\n";
			}
		}
		elseif(array_key_exists($this->item->extension, $extensions))
		{
			$html .= '<a class="btn" href="' .
				sprintf($extensions[$this->item->extension]['backendurl'], $this->item->invoice_no) .
				'"><span class="icon icon-share-alt"></span>' .
				JText::_('COM_AKEEBASUBS_INVOICES_LBL_OPENEXTERNAL') .
				'</a>' . "\n";
		}
		else
		{
			$html .= '<span class="label">' . JText::_('COM_AKEEBASUBS_INVOICES_LBL_NOACTIONS') . '</span>' . "\n";
		}

		return $html;
	}
}