<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\View\SubscriptionStatistics;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Model\SubscriptionsForStats;
use FOF30\View\DataView\Json as BaseJsonView;

class Json extends BaseJsonView
{
	/**
	 * The event which runs when we are displaying the record list JSON view
	 *
	 * @param   string  $tpl  The sub-template to use
	 */
	public function onBeforeBrowse($tpl = null)
	{
		/** @var SubscriptionsForStats $model */
		$model = $this->getModel();

		// Set the correct MIME type
		$document = $this->container->platform->getDocument();
		$document->setMimeEncoding('application/json');

		// Get the raw data
		$limitstart = $model->getState('limitstart', 0);
		$limit = $model->getState('limit', 0);
		$overrideLimits = $model->getState('overrideLimits', 0) || ($limit <= 0);

		$rawData = $model->getRawDataArray($limitstart, $limit, $overrideLimits);

		$json = json_encode($rawData);

		// JSONP support
		$callback = $this->input->get('callback', null, 'raw');

		if (!empty($callback))
		{
			echo $callback . '(' . $json . ')';
		}
		else
		{
			echo $json;
		}
	}
}