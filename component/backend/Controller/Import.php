<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Controller;

defined('_JEXEC') or die;

use FOF30\Controller\Controller;
use JFactory;
use JText;

class Import extends Controller
{
	public function import()
	{
		$app       = JFactory::getApplication();
		/** @var \Akeeba\Subscriptions\Admin\Model\Import $model */
		$model     = $this->getModel();
		$file      = $this->input->files->get('csvfile', null, 'raw');
		$delimiter = $this->input->getInt('csvdelimiters', 0);
		$field     = $this->input->getString('field_delimiter', '');
		$enclosure = $this->input->getString('field_enclosure', '');

		if ($file['error'])
		{
			$this->setRedirect('index.php?option=com_akeebasubs&view=Import', JText::_('COM_AKEEBASUBS_IMPORT_ERR_UPLOAD'), 'error');

			return true;
		}

		if ($delimiter != - 99)
		{
			list($field, $enclosure) = $model->decodeDelimiterOptions($delimiter);
		}

		// Import ok, but maybe I have warnings (ie skipped lines)
		try
		{
			$model->import($file['tmp_name'], $field, $enclosure);
		}
		catch (\RuntimeException $e)
		{
			//Uh oh... import failed, let's inform the user why it happened
			$app->enqueueMessage(JText::sprintf('COM_AKEEBASUBS_IMPORT_FAIL', $e->getMessage()), 'error');
		}

		$this->setRedirect('index.php?option=com_akeebasubs&view=Import');
	}
}