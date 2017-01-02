<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\View\Subscriptions;

use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use FOF30\Container\Container;
use FOF30\Model\DataModel;
use FOF30\View\DataView\Csv as BaseCsvView;
use FOF30\View\Exception\AccessForbidden;

defined('_JEXEC') or die;

class Csv extends BaseCsvView
{
	/**
	 * Overrides the default method to execute and display a template script.
	 * Instead of loadTemplate is uses loadAnyTemplate.
	 *
	 * @param   string $tpl The name of the template file to parse
	 *
	 * @return  boolean  True on success
	 *
	 * @throws  \Exception  When the layout file is not found
	 */
	public function display($tpl = null)
	{
		$eventName = 'onBefore' . ucfirst($this->doTask);
		$this->triggerEvent($eventName, array($tpl));

		// Load the model
		/** @var DataModel $model */
		$model = $this->getModel();

		$items = $model->with(['user', 'juser'])->get();
		$this->items = $items;

		$platform = $this->container->platform;
		$document = $platform->getDocument();

		if ($document instanceof \JDocument)
		{
			$document->setMimeEncoding('text/csv');
		}

		$platform->setHeader('Pragma', 'public');
		$platform->setHeader('Expires', '0');
		$platform->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
		$platform->setHeader('Cache-Control', 'public', false);
		$platform->setHeader('Content-Description', 'File Transfer');
		$platform->setHeader('Content-Disposition', 'attachment; filename="' . $this->csvFilename . '"');

		if (is_null($tpl))
		{
			$tpl = 'csv';
		}

		$hasFailed = false;

		try
		{
			$result = $this->loadTemplate($tpl, true);

			if ($result instanceof \Exception)
			{
				$hasFailed = true;
			}
		}
		catch (\Exception $e)
		{
			$hasFailed = true;
		}

		if (!$hasFailed)
		{
			echo $result;
		}
		else
		{
			// Default CSV behaviour in case the template isn't there!
			if (count($items) === 0)
			{
				throw new AccessForbidden;
			}

			$item    = $items->last();
			$keys    = $item->getData();
			$keys    = array_keys($keys);

			$extraKeys = [
				'isbusiness', 'businessname', 'occupation', 'vatnumber', 'viesregistered', 'address1', 'address2',
				'city', 'state', 'zip', 'country'
			];

			$userKeys = [
				'name', 'username', 'email'
			];

			reset($items);

			if ($this->csvHeader)
			{
				$csv = array();

				foreach (array_merge($keys, $extraKeys, $userKeys) as $k)
				{
					$k = str_replace('"', '""', $k);
					$k = '"' . $k . '"';

					$csv[] = $k;
				}

				echo implode(",", $csv) . "\r\n";
			}

			/** @var Subscriptions $item */
			foreach ($items as $item)
			{
				$csv  = array();
				$data = [];

				foreach ($keys as $k)
				{
					$data[$k] = $item->$k;
				}

				foreach ($extraKeys as $k)
				{
					$data[$k] = $item->user->$k;
				}

				foreach ($userKeys as $k)
				{
					$data[$k] = $item->juser->$k;
				}

				foreach ($data as $k => $v)
				{
					if (is_array($v))
					{
						$v = 'Array';
					}
					elseif (is_object($v))
					{
						$v = 'Object';
					}

					$v = str_replace('"', '""', $v);
					$v = str_replace("\r", '\\r', $v);
					$v = str_replace("\n", '\\n', $v);
					$v = '"' . $v . '"';

					$csv[] = $v;
				}

				echo implode(",", $csv) . "\r\n";
			}
		}

		$eventName = 'onAfter' . ucfirst($this->doTask);
		$this->triggerEvent($eventName, array($tpl));

		return true;
	}
}
