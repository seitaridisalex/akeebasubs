<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Controller\Mixin;
use Akeeba\Subscriptions\Site\Model\Subscribe;
use Akeeba\Subscriptions\Site\Model\Subscriptions;
use FOF30\Container\Container;
use FOF30\Controller\Controller;
use FOF30\View\Exception\AccessForbidden;

class Callback extends Controller
{
	use Mixin\PredefinedTaskList;

	/**
	 * Overridden. Limit the tasks we're allowed to execute.
	 *
	 * @param   Container $container
	 * @param   array     $config
	 */
	public function __construct(Container $container, array $config = array())
	{
		$config['modelName'] = 'Subscribe';
		$config['csrfProtection'] = 0;

		parent::__construct($container, $config);

		$this->predefinedTaskList = ['callback', 'cancel'];

		$this->cacheableTasks = [];
	}

	/**
	 * Process a payment callback. This is called directly by the payment processing services.
	 */
	public function callback()
	{
		// Makes sure SiteGround's SuperCache doesn't cache the subscription page
		\JFactory::getApplication()->setHeader('X-Cache-Control', 'False', true);

		/** @var Subscribe $model */
		$model = $this->getModel();

		$result = $model
			->paymentmethod($this->input->getCmd('paymentmethod', 'none'))
			->runCallback();

		echo $result ? 'OK' : 'FAILED';

		$this->container->platform->closeApplication();
	}

	/**
	 * Process a recurring subscription cancellation
	 */
	public function cancel()
	{
		// Makes sure SiteGround's SuperCache doesn't cache the subscription page
		\JFactory::getApplication()->setHeader('X-Cache-Control', 'False', true);

		$msg = null;
		$type = null;

		$subid = $this->input->getInt('sid');

		// No subscription id? Let's stop here
		if (!$subid)
		{
			$url = 'index.php';
			$msg = \JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_FAILED_CANCELLING');
			$type = 'error';
		}
		else
		{
			/** @var Subscribe $model */
			$model = $this->getModel();

			/** @var Subscriptions $sub */
			$sub = $this->getModel('Subscriptions');

			$sub->find($subid);

			$level = $sub->level;

			$url = \JRoute::_('index.php?option=com_akeebasubs&view=message&slug=' . $level->slug . '&layout=cancel&subid=' . $subid);

			$result = $model
				->paymentmethod($this->input->getCmd('paymentmethod', 'none'))
				->runCancelRecurring();

			if (!$result)
			{
				$msg = \JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_FAILED_CANCELLING');
				$type = 'error';
			}
		}

		$this->setRedirect($url, $msg, $type);
	}
}