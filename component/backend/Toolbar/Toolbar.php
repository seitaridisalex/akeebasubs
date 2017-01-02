<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Toolbar;

use FOF30\Inflector\Inflector;
use JToolbarHelper;
use JText;

defined('_JEXEC') or die;

class Toolbar extends \FOF30\Toolbar\Toolbar
{
	/**
	 * Renders the submenu (toolbar links) for all defined views of this component
	 *
	 * @return  void
	 */
	public function renderSubmenu()
	{
		$views = array(
			'ControlPanel',
			'COM_AKEEBASUBS_MAINMENU_SETUP'    => array(
				'Levels',
				'LevelGroups',
				'Relations',
				'Upgrades',
				'TaxConfig',
				'TaxRules',
				'States',
				'EmailTemplates',
				'BlockRules',
			),
			'Subscriptions',
			'COM_AKEEBASUBS_MAINMENU_COUPONS'  => array(
				'Coupons',
				'APICoupons'
			),
			'COM_AKEEBASUBS_MAINMENU_TOOLS'    => array(
				'Import',
				'Users'
			),
			'Reports',
			'COM_AKEEBASUBS_MAINMENU_INVOICES' => array(
				'Invoices',
				'CreditNotes',
				'InvoiceTemplates',
				'CreditNoteTemplates',
			),
		);

		foreach ($views as $label => $view)
		{
			if (!is_array($view))
			{
				$this->addSubmenuLink($view);
				continue;
			}

			$label = \JText::_($label);
			$this->appendLink($label, '', false);

			foreach ($view as $v)
			{
				$this->addSubmenuLink($v, $label);
			}
		}
	}

	public function onControlPanels()
	{
		$this->renderSubmenu();

		$option = $this->container->componentName;

		JToolBarHelper::title(JText::_(strtoupper($option)), str_replace('com_', '', $option));

		JToolBarHelper::preferences($option);
	}

	public function onMakeCoupons()
	{
		$option = $this->container->componentName;

		$subtitle_key = $option . '_TITLE_MAKECOUPONS';
		JToolBarHelper::title(
			JText::_($option) . ' &ndash; <small>' . JText::_($subtitle_key) . '</small>',
			str_replace('com_', '', $option));
		JToolBarHelper::back('JTOOLBAR_BACK', 'index.php?option=com_akeebasubs&view=Coupons');

		$this->renderSubmenu();
	}

	public function onEmailtemplatesBrowse()
	{
		$this->onBrowse();

		JToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'JLIB_HTML_BATCH_COPY', true);
	}

	public function onEmailtemplatesAdd()
	{
		// Quick hack to mark this record as new
		$this->_isNew = true;

		$this->onAdd();
	}

	public function onEmailtemplatesEdit()
	{
		$this->onEdit();

		if (!isset($this->_isNew))
		{
			JToolBarHelper::divider();

			$options['class']   = 'envelope';
			$options['a.task']  = 'testTemplate';
			$options['a.href']  = '#';
			$options['text']    = JText::_('COM_AKEEBASUBS_EMAILTEMPLATES_TESTTEMPLATE');

			$this->addCustomBtn('test-template', $options);
		}
	}

	public function onImportsDefault()
	{
		$this->renderSubmenu();

		$option = $this->container->componentName;
		$view = 'Import';

		$subtitle_key = $option . '_TITLE_' . $view;
		JToolBarHelper::title(JText::_($option).' &ndash; <small>' .
		                      JText::_($subtitle_key) .
		                      '</small>',
			str_replace('com_', '', $option));

		JToolBarHelper::custom('import', 'download', 'download', 'COM_AKEEBASUBS_IMPORT', false);
		JToolbarHelper::divider();

		JToolBarHelper::back('JTOOLBAR_BACK', 'index.php?option=com_akeebasubs&view=ControlPanel');
	}

	public function onTaxconfigsMain()
	{
		$this->renderSubmenu();

		$option = $this->container->componentName;
		$view = 'TaxConfigs';

		$subtitle_key = $option . '_TITLE_' . $view;
		JToolBarHelper::title(JText::_($option).' &ndash; <small>' .
			JText::_($subtitle_key) .
			'</small>',
			str_replace('com_', '', $option));

		JToolBarHelper::back('JTOOLBAR_BACK', 'index.php?option=com_akeebasubs&view=TaxRules');
	}

	public function onLevelsBrowse()
	{
		$this->onBrowse();

		JToolBarHelper::divider();
		JToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'JLIB_HTML_BATCH_COPY', false);
	}

	public function onInvoiceTemplatesBrowse()
	{
		$this->onBrowse();

		JToolBarHelper::divider();
		JToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'JLIB_HTML_BATCH_COPY', false);
	}

	public function onInvoicesBrowse()
	{
		$this->renderSubmenu();

		$option = $this->container->componentName;
		$view = 'Invoices';

		$subtitle_key = $option . '_TITLE_' . $view;
		JToolBarHelper::title(JText::_($option).' &ndash; <small>' .
			JText::_($subtitle_key) .
			'</small>',
			str_replace('com_', '', $option));

		// Add toolbar buttons
		if ($this->perms->delete)
		{
			JToolBarHelper::deleteList();
		}
	}

	public function onReports()
	{
		$this->renderSubmenu();

		$option = $this->container->componentName;
		$view = 'Reports';

		$subtitle_key = $option . '_TITLE_' . $view;
		JToolBarHelper::title(JText::_($option).' &ndash; <small>' .
			JText::_($subtitle_key) .
			'</small>',
			str_replace('com_', '', $option));

		JToolBarHelper::back('JTOOLBAR_BACK', 'index.php?option=com_akeebasubs&view=ControlPanel');
	}

	public function onReportsInvoices()
	{
		$this->renderSubmenu();

		$option = $this->container->componentName;
		$view = 'Reports';

		$subtitle_key = $option . '_TITLE_' . $view;
		JToolBarHelper::title(JText::_($option).' &ndash; <small>' .
			JText::_($subtitle_key) .
			'</small>',
			str_replace('com_', '', $option));

		JToolBarHelper::back('JTOOLBAR_BACK', 'index.php?option=com_akeebasubs&view=Reports');
	}

	public function onReportsVies()
	{
		$this->onReportsInvoices();
	}

	public function onReportsVatmoss()
	{
		$this->onReportsInvoices();
	}

	public function onSubscriptionsBrowse()
	{
		$this->onBrowse();

		$bar = \JToolBar::getInstance('toolbar');

		// Add "Run Integrations"
		JToolBarHelper::divider();
		$bar->appendButton('Link', 'play', JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_SUBREFRESH'), 'javascript:akeebasubs_refresh_integrations();');

		// Add "Export to CSV"
		$link = \JUri::getInstance();
		$query = $link->getQuery(true);
		$query['format'] = 'csv';
		$query['option'] = 'com_akeebasubs';
		$query['view'] = 'subscriptions';
		$query['task'] = 'browse';
		$link->setQuery($query);

		JToolBarHelper::divider();
		$bar->appendButton('Link', 'download', JText::_('COM_AKEEBASUBS_COMMON_EXPORTCSV'), $link->toString());
	}

	/**
	 * Adds a link to the submenu (toolbar links)
	 *
	 * @param string $view   The view we're linking to
	 * @param array  $parent The parent view
	 */
	private function addSubmenuLink($view, $parent = null)
	{
		static $activeView = null;

		if (empty($activeView))
		{
			$activeView = $this->container->input->getCmd('view', 'cpanel');
		}

		if ($activeView == 'cpanels')
		{
			$activeView = 'cpanel';
		}

		$key = $this->container->componentName . '_TITLE_' . $view;

		// Exceptions to avoid introduction of a new language string
		if ($view == 'ControlPanel')
		{
			$key = $this->container->componentName . '_TITLE_CPANEL';
		}

		if (strtoupper(\JText::_($key)) == strtoupper($key))
		{
			$altView = $this->container->inflector->isPlural($view) ? $this->container->inflector->singularize($view) : $this->container->inflector->pluralize($view);
			$key2    = strtoupper($this->container->componentName) . '_TITLE_' . strtoupper($altView);

			if (strtoupper(\JText::_($key2)) == $key2)
			{
				$name = ucfirst($view);
			}
			else
			{
				$name = \JText::_($key2);
			}
		}
		else
		{
			$name = \JText::_($key);
		}

		$link = 'index.php?option=' . $this->container->componentName . '&view=' . $view;

		$active = $view == $activeView;

		$this->appendLink($name, $link, $active, null, $parent);
	}

	/**
	 * Add a custom toolbar button
	 *
	 * @param string $id      The button ID
	 * @param array  $options Button options
	 */
	protected function addCustomBtn($id, $options = array())
	{
		$options = (array) $options;
		$a_class = 'btn btn-small';
		$href    = '';
		$task    = '';
		$text    = '';
		$rel     = '';
		$target  = '';
		$other   = '';

		if (isset($options['a.class']))
		{
			$a_class .= $options['a.class'];
		}
		if (isset($options['a.href']))
		{
			$href = $options['a.href'];
		}
		if (isset($options['a.task']))
		{
			$task = $options['a.task'];
		}
		if (isset($options['a.target']))
		{
			$target = $options['a.target'];
		}
		if (isset($options['a.other']))
		{
			$other = $options['a.other'];
		}
		if (isset($options['text']))
		{
			$text = $options['text'];
		}
		if (isset($options['class']))
		{
			$class = $options['class'];
		}
		else
		{
			$class = 'default';
		}

		if (isset($options['modal']))
		{
			\JHtml::_('behavior.modal');
			$a_class .= ' modal';
			$rel = "'handler':'iframe'";
			if (is_array($options['modal']))
			{
				if (isset($options['modal']['size']['x']) && isset($options['modal']['size']['y']))
				{
					$rel .= ", 'size' : {'x' : " . $options['modal']['size']['x'] . ", 'y' : " . $options['modal']['size']['y'] . "}";
				}
			}
		}

		$html = '<a id="' . $id . '" class="' . $a_class . '" alt="' . $text . '"';

		if ($rel)
		{
			$html .= ' rel="{' . $rel . '}"';
		}
		if ($href)
		{
			$html .= ' href="' . $href . '"';
		}
		if ($task)
		{
			$html .= " onclick=\"javascript:submitbutton('" . $task . "')\"";
		}
		if ($target)
		{
			$html .= ' target="' . $target . '"';
		}
		if ($other)
		{
			$html .= ' ' . $other;
		}
		$html .= ' >';

		$html .= '<span class="icon icon-' . $class . '" title="' . $text . '" > </span>';

		$html .= $text;

		$html .= '</a>';

		$bar = \JToolBar::getInstance();
		$bar->appendButton('Custom', $html, $id);
	}
}