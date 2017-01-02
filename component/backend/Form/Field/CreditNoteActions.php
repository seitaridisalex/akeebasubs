<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Form\Field;

use Akeeba\Subscriptions\Admin\Model\CreditNotes;
use FOF30\Form\Field\Text;
use JText;

defined('_JEXEC') or die;

class CreditNoteActions extends Text
{
	/** @var CreditNotes */
	public $item = null;

	public function getRepeatable()
	{
		$html = '';

		$html .= '<a href="index.php?option=com_akeebasubs&view=CreditNotes&task=read&id=' .
			htmlspecialchars($this->item->akeebasubs_invoice_id, ENT_COMPAT, 'UTF-8') .
			'&tmpl=component" class="btn btn-info btn-small modal" rel="{handler: \'iframe\', size: {x: 800, y: 500}}" title="' .
			JText::_('COM_AKEEBASUBS_INVOICES_ACTION_PREVIEW') . '"><span class="icon icon-file icon-white"></span></a>' .
			"\n";
		$html .= '<a href="index.php?option=com_akeebasubs&view=CreditNotes&task=download&id=' .
			htmlspecialchars($this->item->akeebasubs_invoice_id, ENT_COMPAT, 'UTF-8') .
			'" class="btn btn-primary btn-small" title="' .
			JText::_('COM_AKEEBASUBS_INVOICES_ACTION_DOWNLOAD')
			. '"><span class="icon icon-download icon-white"></span></a>' . "\n";
		$html .= '<a href="index.php?option=com_akeebasubs&view=CreditNotes&task=send&id=' .
			htmlentities($this->item->akeebasubs_invoice_id, ENT_COMPAT, 'UTF-8') .
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

		$html .= '<a href="index.php?option=com_akeebasubs&view=CreditNotes&task=generate&id=' .
			htmlentities($this->item->akeebasubs_invoice_id, ENT_COMPAT, 'UTF-8') .
			'" class="btn btn-mini btn-warning" title="' .
			JText::_('COM_AKEEBASUBS_INVOICES_ACTION_REGENERATE') .
			'"><span class="icon icon-refresh icon-white"></span></a>'
			. "\n";

		return $html;
	}
}