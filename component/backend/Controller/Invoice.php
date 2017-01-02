<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Controller;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use FOF30\Container\Container;
use FOF30\Controller\DataController;
use FOF30\Controller\Exception\ItemNotFound;
use FOF30\View\Exception\AccessForbidden;

class Invoice extends DataController
{
	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		$this->cacheableTasks = [];
	}

	public function onBeforeRead()
	{
		// Load the model
		/** @var \Akeeba\Subscriptions\Admin\Model\Invoices $model */
		$model = $this->getModel();

		// If there is no record loaded, try loading a record based on the id passed in the input object
		if (!$model->getId())
		{
			$ids = $this->getIDsFromRequest($model, true);

			if ($model->getId() != reset($ids))
			{
				$key = strtoupper($this->container->componentName . '_ERR_' . $model->getName() . '_NOTFOUND');
				throw new ItemNotFound(\JText::_($key), 404);
			}
		}

		// Check that this is the item's owner or an administrator
		$user = $this->container->platform->getUser();
		$sub  = $model->subscription;

		if (!$this->checkACL('core.manage') && ($sub->user_id != $user->id))
		{
			throw new AccessForbidden;
		}
	}

	public function download()
	{
		// Load the model
		/** @var \Akeeba\Subscriptions\Admin\Model\Invoices $model */
		$model = $this->getModel();

		// If there is no record loaded, try loading a record based on the id passed in the input object
		if (!$model->getId())
		{
			$ids = $this->getIDsFromRequest($model, true);

			if ($model->getId() != reset($ids))
			{
				$key = strtoupper($this->container->componentName . '_ERR_' . $model->getName() . '_NOTFOUND');
				throw new ItemNotFound(\JText::_($key), 404);
			}
		}

		// Check that this is the item's owner or an administrator
		$user = $this->container->platform->getUser();
		$sub  = $model->subscription;

		if (!$this->checkACL('core.manage') && ($sub->user_id != $user->id))
		{
			throw new AccessForbidden;
		}

		// Make sure we have a PDF file or try to generate one
		\JLoader::import('joomla.filesystem.file');

		// In previous versions of Akeeba Subscriptions we're storing everything inside a single folder
		$old_path = JPATH_ADMINISTRATOR . '/components/com_akeebasubs/invoices/';
		// While in new ones every year/month has its own folder
		$year_path = $model->getInvoicePath();

		$filename   = $model->filename;
		$saved_path = '';

		foreach (array($old_path, $year_path) as $path)
		{
			if (\JFile::exists($path . $filename))
			{
				$saved_path = $path;
				break;
			}
		}

		// Our invoice wasn't in any directory? Well, let's create it
		if (!$saved_path)
		{
			$filename   = $model->createPDF();
			$saved_path = $model->getInvoicePath();

			if ($filename == false)
			{
				$key = strtoupper($this->container->componentName . '_ERR_' . $model->getName() . '_NOTFOUND');
				throw new ItemNotFound(\JText::_($key), 404);
			}
		}

		// Clear any existing data
		while (@ob_end_clean())
		{
			;
		}

		// Fix IE bugs
		if (empty($model->display_number))
		{
			$basename = 'invoice_' . $model->invoice_no;
		}
		else
		{
			$basename = $model->display_number;
		}

		// Add extension
		$basename .= '.pdf';

		if (isset($_SERVER['HTTP_USER_AGENT']) && strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE'))
		{
			$header_file = preg_replace('/\./', '%2e', $filename, substr_count($basename, '.') - 1);

			if (ini_get('zlib.output_compression'))
			{
				ini_set('zlib.output_compression', 'Off');
			}
		}
		else
		{
			$header_file = $basename;
		}

		// Get the PDF file's data
		@clearstatcache();

		$fileData = @file_get_contents($saved_path . $filename);

		// Disable caching
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: public", false);

		// Send MIME headers
		header("Content-Description: File Transfer");
		header('Content-Type: application/pdf');
		header("Accept-Ranges: bytes");
		header('Content-Disposition: attachment; filename="' . $header_file . '"');
		header('Content-Transfer-Encoding: binary');
		header('Connection: close');

		error_reporting(0);

		if (!ini_get('safe_mode'))
		{
			set_time_limit(0);
		}

		echo $fileData;

		$this->container->platform->closeApplication();
	}

	public function send()
	{
		// Load the model
		/** @var \Akeeba\Subscriptions\Admin\Model\Invoices $model */
		$model = $this->getModel();

		// If there is no record loaded, try loading a record based on the id passed in the input object
		if (!$model->getId())
		{
			$ids = $this->getIDsFromRequest($model, true);

			if ($model->getId() != reset($ids))
			{
				$key = strtoupper($this->container->componentName . '_ERR_' . $model->getName() . '_NOTFOUND');
				throw new ItemNotFound(\JText::_($key), 404);
			}
		}

		// Check that this is an administrator
		if (!$this->checkACL('core.manage'))
		{
			throw new AccessForbidden;
		}

		// Email the PDF file
		$sub    = $model->subscription;
		$status = ($model->emailPDF($sub) === true);

		// Post-action redirection
		if ($customURL = $this->input->get('returnurl', '', 'string'))
		{
			$customURL = base64_decode($customURL);
		}

		$url = !empty($customURL) ? $customURL : 'index.php?option=com_akeebasubs&view=Invoices';

		if (!$status)
		{
			$this->setRedirect($url, \JText::_('COM_AKEEBASUBS_INVOICES_MSG_NOTSENT'), 'error');
		}
		else
		{
			$this->setRedirect($url, \JText::_('COM_AKEEBASUBS_INVOICES_MSG_SENT'));
		}
	}

	public function generate()
	{
		// Load the model
		/** @var \Akeeba\Subscriptions\Admin\Model\Invoices $model */
		$model = $this->getModel();

		// If there is no record loaded, try loading a record based on the id passed in the input object
		if (!$model->getId())
		{
			$ids = $this->getIDsFromRequest($model, true);

			if ($model->getId() != reset($ids))
			{
				$key = strtoupper($this->container->componentName . '_ERR_' . $model->getName() . '_NOTFOUND');
				throw new ItemNotFound(\JText::_($key), 404);
			}
		}

		// Check that this is an administrator
		if (!$this->checkACL('core.manage'))
		{
			throw new AccessForbidden;
		}

		// (Re-)generate the invoice
		$sub = $model->subscription;

		$status = ($model->createInvoice($sub) === true);

		// Post-action redirection
		if ($customURL = $this->input->get('returnurl', '', 'string'))
		{
			$customURL = base64_decode($customURL);
		}

		$url = !empty($customURL) ? $customURL : 'index.php?option=com_akeebasubs&view=Invoices';

		if ($status === false)
		{
			$this->setRedirect($url, \JText::_('COM_AKEEBASUBS_INVOICES_MSG_NOTGENERATED'), 'error');
		}
		else
		{
			$this->setRedirect($url, \JText::_('COM_AKEEBASUBS_INVOICES_MSG_GENERATED'));
		}
	}

	public function generateForSubscription()
	{
		// Check that this is an administrator
		if (!$this->checkACL('core.manage'))
		{
			throw new AccessForbidden;
		}

		$id = $this->input->getInt('id');

		/** @var Subscriptions $sub */
		$sub = $this->container->factory->model('Subscriptions')->tmpInstance();
		$sub->findOrFail($id);

		/** @var \Akeeba\Subscriptions\Admin\Model\Invoices $model */
		$model = $this->getModel();

		$status = ($model->createInvoice($sub) === true);

		// Post-action redirection
		if ($customURL = $this->input->get('returnurl', '', 'string'))
		{
			$customURL = base64_decode($customURL);
		}

		$url = !empty($customURL) ? $customURL : 'index.php?option=com_akeebasubs&view=Invoices';

		if ($status === false)
		{
			$this->setRedirect($url, \JText::_('COM_AKEEBASUBS_INVOICES_MSG_NOTGENERATED'), 'error');
		}
		else
		{
			$this->setRedirect($url, \JText::_('COM_AKEEBASUBS_INVOICES_MSG_GENERATED'));
		}
	}
}