<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Form\Field;

use FOF30\Form\Field\Text;

defined('_JEXEC') or die;

class Discount extends Text
{
	public function getRepeatable()
	{
		// Initialise
		$class            = $this->id;
		$typeField        = 'type';
		$classValue       = 'akeebasubs-coupon-discount-value';
		$classPercent     = 'akeebasubs-coupon-discount-percent';
		$classLastPercent = 'akeebasubs-coupon-discount-lastpercent';

		// Get field parameters
		if ($this->element['class'])
		{
			$class = (string) $this->element['class'];
		}

		if ($this->element['type_field'])
		{
			$typeField = (string) $this->element['type_field'];
		}

		if ($this->element['class_value'])
		{
			$classValue = (string) $this->element['class_value'];
		}

		if ($this->element['class_percent'])
		{
			$classPercent = (string) $this->element['class_percent'];
		}

		if ($this->element['class_lastpercent'])
		{
			$classLastPercent = (string) $this->element['class_lastpercent'];
		}

		$type = $this->item->$typeField;

		// Start the HTML output
		$extraClass = ($type == 'value') ? $classValue : $classPercent;
		$extraClass .= ($type == 'lastpercent') ? ' ' . $classLastPercent : '';

		$html = '<span class="' . $class . ' ' . $extraClass . '">';

		// Case 1: Value discount
		if ($type == 'value')
		{
			$currencyPos = $this->form->getContainer()->params->get('currencypos', 'before');
			$currencySymbol = $this->form->getContainer()->params->get('currencysymbol', '€');

			if ($currencyPos == 'before')
			{
				$html .= $currencySymbol;
			}

			$html .= ' ' . sprintf('%02.02f', (float) $this->value) . ' ';

			if ($currencyPos == 'after')
			{
				$html .= $currencySymbol;
			}
		}
		else
		{
			$html .= sprintf('%2.2f', (float) $this->value) . ' %';
		}

		// End the HTML output
		$html .= '</span>';

		return $html;
	}
}