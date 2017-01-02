<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\View\Subscriptions;

use Akeeba\Subscriptions\Site\Model\Invoices;
use Akeeba\Subscriptions\Site\Model\Levels;
use Akeeba\Subscriptions\Site\Model\Subscriptions;
use JUri;

defined('_JEXEC') or die;

class Html extends \FOF30\View\DataView\Html
{
	public $returnURL = '';

	protected function onBeforeBrowse()
	{
		parent::onBeforeBrowse();

		// Get all levels and all active levels

		/** @var Levels $levelsModel */
		$levelsModel = $this->container->factory->model('Levels')->tmpInstance();

		$rawActiveLevels = $levelsModel
			->get(true);

		$activeLevels = array();
		$allLevels = array();
		$ppList = array();

		// Let's get all the enabled plugins

		$this->container->platform->importPlugin('akpayment');
		$tempList = $this->container->platform->runPlugins('onAKPaymentGetIdentity', []);

		// Remove a level for better handling
		foreach ($tempList as $tempPlugin)
		{
			$keys = array_keys($tempPlugin);
			$name = array_pop($keys);
			$ppList[$name] = array_pop($tempPlugin);
		}

		if ($rawActiveLevels->count())
		{
			/** @var Levels $l */
			foreach ($rawActiveLevels as $l)
			{
				$allLevels[$l->akeebasubs_level_id] = $l;

				if ($l->enabled)
				{
					$activeLevels[] = $l->akeebasubs_level_id;
				}
			}
		}

		// Get subscription and subscription level IDs, sort subscriptions
		// based on status
		$subIDs = array();
		$subscription_ids = array();
		$sortTable = array(
			'active'  => array(),
			'waiting' => array(),
			'pending' => array(),
			'expired' => array(),
		);

		if ($this->items->count())
		{
			\JLoader::import('joomla.utilities.date');

			/** @var Subscriptions $sub */
			foreach ($this->items as $sub)
			{
				$id = $sub->akeebasubs_subscription_id;

				$subIDs[] = $id;
				$subscription_ids[] = $id;

				$sub->addKnownField('allow_renew', 0, 'tinyint(1)');

				// Propagate the info the the sub can be cancelled
				if (isset($ppList[$sub->processor]))
				{
					$sub->allow_renew = $ppList[$sub->processor]->recurringCancellation;
				}
				else
				{
					$sub->allow_renew = true;
				}

				$jd = $this->container->platform->getDate($sub->publish_up);

				if ($sub->enabled)
				{
					$sortTable['active'][] = $id;
				}
				elseif (($sub->state == 'C') && ($jd->toUnix() >= time()))
				{
					$sortTable['waiting'][] = $id;
				}
				elseif ($sub->state == 'P')
				{
					$sortTable['pending'][] = $id;
				}
				else
				{
					$sortTable['expired'][] = $id;
				}
			}
		}

		$subIDs = array_unique($subIDs);

		// Get invoices data
		$invoices = array();

		/** @var Invoices $invoicesModel */
		$invoicesModel = $this->container->factory->model('Invoices')->tmpInstance();

		if (!empty($subscription_ids))
		{
			$rawInvoices = $invoicesModel
				->subids($subscription_ids)
				->get(true);

			if ($rawInvoices->count())
			{
				/** @var Invoices $rawInvoice */
				foreach ($rawInvoices as $rawInvoice)
				{
					$invoices[$rawInvoice->akeebasubs_subscription_id] = $rawInvoice;
				}
			}
		}

		// Get invoicing extensions
		$extensions = $invoicesModel->getExtensions();
		
		// Should I add a returl URL to the "View details" button?
		$returnURL = $this->input->getBase64('returnurl', null);

		if ($returnURL)
		{
			$this->returnURL = base64_decode($returnURL);
		}

		if ($this->input->getBool('includereturnurl', false))
		{
			$this->returnURL = JUri::getInstance()->toString();
		}

		// Assign variables
		$this->activeLevels = $activeLevels;
		$this->allLevels = $allLevels;
		$this->subIDs = $subIDs;
		$this->invoices = $invoices;
		$this->extensions = $extensions;
		$this->sortTable = $sortTable;
	}

	protected function onBeforeRead()
	{
		parent::onBeforeRead();

		$returnURL = $this->input->getBase64('returnurl', null);

		if ($returnURL)
		{
			$this->returnURL = base64_decode($returnURL);
		}
	}


}