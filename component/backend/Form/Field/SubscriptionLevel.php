<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Form\Field;

use Akeeba\Subscriptions\Admin\Helper\Image;
use FOF30\Container\Container;
use FOF30\Form\Field\GenericList;
use FOF30\Form\Field\Model;
use FOF30\Model\DataModel;
use JHtml;
use JText;

defined('_JEXEC') or die;

class SubscriptionLevel extends Model
{
	/**
	 * Get the rendering of this field type for a repeatable (grid) display,
	 * e.g. in a view listing many item (typically a "browse" task)
	 *
	 * @since 2.0
	 *
	 * @return  string  The field HTML
	 */
	public function getRepeatable()
	{
		// Get field parameters
		$class					= $this->class ? $this->class : $this->id;
		$format_string			= $this->element['format'] ? (string) $this->element['format'] : '';
		$link_url				= $this->element['url'] ? (string) $this->element['url'] : '';
		$empty_replacement		= $this->element['empty_replacement'] ? (string) $this->element['empty_replacement'] : '';

		if ($link_url && ($this->item instanceof DataModel))
		{
			$link_url = $this->parseFieldTags($link_url);
		}
		else
		{
			$link_url = false;
		}

		if ($this->element['empty_replacement'])
		{
			$empty_replacement = (string) $this->element['empty_replacement'];
		}

		$value = GenericList::getOptionName($this->getOptions(), $this->value);

		$images = $this->getOptions(false, true);
		$image = '';

		if (isset($images[$this->value]))
		{
			$image = $images[$this->value];
		}

		// Get the (optionally formatted) value
		if (!empty($empty_replacement) && empty($value))
		{
			$value = JText::_($empty_replacement);
		}

		if (empty($format_string))
		{
			$value = htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
		}
		else
		{
			$value = sprintf($format_string, $value);
		}

		// Create the HTML
		$html = '<span class="editlinktip hasTip ' . $class . '" title="' .
		        htmlspecialchars($value) . '::' . JText::_('COM_AKEEBASUBS_SUBSCRIPTION_LEVEL_EDIT_TOOLTIP') .
		        '">';

		$html .= '<img src="' . Image::getURL($image) . '" width="32" height="32" class="sublevelpic" />';

		if ($link_url)
		{
			$html .= '<a href="' . $link_url . '">';
		}

		$html .= $value;

		if ($link_url)
		{
			$html .= '</a>';
		}

		$html .= '</span>';

		return $html;

	}

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */
	protected function getOptions($forceReset = false, $images = false)
	{
		static $loadedOptions = array();
		static $loadedImageMap = array();

		$myFormKey = $this->form->getName();

		if ($forceReset && isset($loadedOptions[$myFormKey]))
		{
			unset($loadedOptions[$myFormKey]);
		}

		if (!isset($loadedOptions[$myFormKey]))
		{
			$options = array();
			$imageMap = array();

			// Initialize some field attributes.
			$key = 'akeebasubs_level_id';
			$value = 'title';
			$nonePlaceholder = (string) $this->element['none'];

			if (!empty($nonePlaceholder))
			{
				$options[] = JHtml::_('select.option', JText::_($nonePlaceholder), null);
			}

			$container = $this->form->getContainer();
			$model = $container->factory->model('Levels')->tmpInstance();

			// Process state variables
			/** @var \SimpleXMLElement $stateoption */
			foreach ($this->element->children() as $stateoption)
			{
				// Only add <option /> elements.
				if ($stateoption->getName() != 'state')
				{
					continue;
				}

				$stateKey = (string) $stateoption['key'];
				$stateValue = (string) $stateoption;

				$model->setState($stateKey, $stateValue);
			}

			// Set the query and get the result list.
			$items = $model->get(true);

			// Build the field options.
			if (!empty($items))
			{
				foreach ($items as $item)
				{
					$options[] = JHtml::_('select.option', $item->$key, JText::_($item->$value));
					$imageMap[$item->$key] = $item->image;
				}
			}

			// Merge any additional options in the XML definition.
			$options = array_merge(parent::getOptions(), $options);

			$loadedOptions[$myFormKey] = $options;
			$loadedImageMap[$myFormKey] = $imageMap;
		}

		return $images ? $loadedImageMap[$myFormKey] : $loadedOptions[$myFormKey];
	}
}