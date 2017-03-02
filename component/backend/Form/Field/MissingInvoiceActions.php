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

class MissingInvoiceActions extends Text
{
	/** @var Invoices */
	public $item = null;

	public function getRepeatable()
	{
		$returnURL = base64_encode(\JUri::getInstance()->toString());

		$html = '<a href="index.php?option=com_akeebasubs&view=Invoices&task=generateForSubscription&id=' .
			htmlentities($this->item->akeebasubs_subscription_id, ENT_COMPAT, 'UTF-8') . '&returnurl=' . $returnURL .
			'" class="btn btn-mini btn-success" title="' .
			JText::_('COM_AKEEBASUBS_INVOICES_ACTION_REGENERATE') .
			'"><span class="icon icon-refresh icon-white"></span>' .
			JText::_('COM_AKEEBASUBS_INVOICES_ACTION_REGENERATE')
			. '</a>'
			. "\n";

		return $html;
	}
}