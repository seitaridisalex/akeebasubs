<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Form\Field;

use FOF30\Form\Field\Text;
use SimpleXMLElement;

defined('_JEXEC') or die;

class MoneyEntry extends Text
{
	public function setup(SimpleXMLElement $element, $value, $group = null)
	{
		$x = parent::setup($element, $value, $group);

		static $currencyPosition = null;
		static $currencySymbol = null;

		if (is_null($currencyPosition))
		{
			$currencyPosition = $this->form->getContainer()->params->get('currencypos', 'before');
			$currencySymbol = $this->form->getContainer()->params->get('currencysymbol', '€');
		}

		if ($currencyPosition == 'before')
		{
			$this->form->setFieldAttribute($this->fieldname, 'prepend_text', $currencySymbol);
		}
		else
		{
			$this->form->setFieldAttribute($this->fieldname, 'append_text', $currencySymbol);
		}

		return $x;
	}
}