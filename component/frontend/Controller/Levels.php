<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Controller\Mixin;
use Akeeba\Subscriptions\Site\Model\Subscribe as SubscribeModel;
use Akeeba\Subscriptions\Site\Model\TaxHelper;
use Akeeba\Subscriptions\Site\Model\Users;
use FOF30\Container\Container;
use FOF30\Controller\DataController;
use JFactory;

class Levels extends DataController
{
	use Mixin\PredefinedTaskList;

	/**
	 * Overridden. Limit the tasks we're allowed to execute.
	 *
	 * @param   Container  $container
	 * @param   array      $config
	 */
	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		$this->predefinedTaskList = ['browse', 'read'];

		if ($this->input->getBool('caching', true))
		{
			$this->cacheableTasks = ['browse'];
		}
		else
		{
			$this->cacheableTasks = [];
		}
	}

	/**
	 * Runs before the browse task
	 *
	 * @throws \Exception
	 */
	public function onBeforeBrowse()
	{
		$params = \JFactory::getApplication()->getPageParameters();

		$ids = $params->get('ids', '');

		if (is_array($ids) && !empty($ids))
		{
			$checkIds = implode(',', $ids);

			if (($checkIds === '0') || $checkIds === '')
			{
				$ids = '';
			}
		}
		else
		{
			$ids = '';
		}

		// Working around Progressive Caching
		$appInput = \JFactory::getApplication()->input;

		if (!empty($ids))
		{
			$appInput->set('ids', $ids);
			$appInput->set('_x_userid', $this->container->platform->getUser()->id);
		}

		/** @var TaxHelper $taxHelper */
		$taxHelper = $this->getModel('TaxHelper');
		$taxParameters = $taxHelper->getTaxDefiningParameters();
		$appInput->set('_akeebasubs_taxParameters', $taxParameters);

		$this->registerUrlParams(array(
			'ids'                       => 'ARRAY',
			'_akeebasubs_taxParameters' => 'ARRAY',
			'no_clear'                  => 'BOOL',
			'_x_userid'                 => 'INT',
			'coupon'                    => 'STRING'
		));

		// Save a possible coupon code in the session
		$coupon = $this->input->getString('coupon');

		if (!empty($coupon))
		{
			$this->container->platform->setSessionVar('coupon', $coupon, 'com_akeebasubs');
		}

		// Are we told to hide notices?
		if (!$this->input->getBool('shownotices', true))
		{
			$view = $this->getView();
			$view->showNotices = false;
		}

		// Continue parsing page options
		/** @var \Akeeba\Subscriptions\Site\Model\Levels $model */
		$model = $this->getModel();
		$noClear = $this->input->getBool('no_clear', false);

		if (!$noClear)
		{
			$model
				->clearState()
				->savestate(0)
				->setIgnoreRequest(1)
				->limit(0)
				->limitstart(0)
				->enabled(1)
				->only_once(1)
				->filter_order('ordering')
				->filter_order_Dir('ASC');

			if (!empty($ids))
			{
				$model->id($ids);
			}
		}

		$model->access_user_id($this->container->platform->getUser()->id);
	}

	/**
	 * Runs before the read task
	 *
	 * @return bool
	 */
	public function onBeforeRead()
	{
		// Fetch the subscription slug from page parameters
		$params = \JFactory::getApplication()->getPageParameters();
		$pageslug = $params->get('slug', '');
		$slug = $this->input->getString('slug', null);

		if ($pageslug)
		{
			$slug = $pageslug;
			$this->input->set('slug', $slug);
		}

		/** @var \Akeeba\Subscriptions\Site\Model\Levels $model */
		$model = $this->getModel();

		$this->getIDsFromRequest($model, true);
		$model->access_user_id($this->container->platform->getUser()->id);
		$id = $model->getId();

		if (!$id && $slug)
		{
			// Note: do note replace $item with $model or read() won't see the loaded record because of how references
			// work in PHP.
			$item = $model
				->id(0)
				->slug([
					'method' => 'exact',
					'value' => $slug
				])
				->firstOrNew();

			$id = $item->getId();
		}

		// Make sure the level exists
		if ($id == 0)
		{
			return false;
		}

		// The level exists, load it.
		$model->find($id);

		// Working around Progressive Caching
		\JFactory::getApplication()->input->set('slug', $slug);
		\JFactory::getApplication()->input->set('id', $id);

		$this->registerUrlParams(array(
			'slug' => 'STRING',
			'id'   => 'INT',
		));

		// Make sure the level is published
		if (!$model->enabled)
		{
			return false;
		}

		// Check for "Forbid renewal" conditions
		if ($model->only_once)
		{
			$levels = $model->getClone()->savestate(false)->setIgnoreRequest(true)->clearState()->reset(true, true);
			$levels
				->slug([
					'method' => 'exact',
					'value' => $model->slug
				])
				->only_once(1)
				->get(true);

			if (!$levels->count())
			{
				// User trying to renew a level which is marked as only_once
				if ($model->renew_url)
				{
					$this->container->platform->redirect($model->renew_url);
				}

				return false;
			}
		}

        /** @var \Akeeba\Subscriptions\Site\View\Level\Html $view */
		$view = $this->getView();

		// Get the user model and load the user data
		/** @var Users $usersModel */
		$usersModel = $this->getModel('Users');
		$userparams = $usersModel
			->getMergedData($this->container->platform->getUser()->id);

		$view->userparams = $userparams;

		// Load any cached user supplied information
		/** @var SubscribeModel $vModel */
		$vModel = $this->getModel('Subscribe');
		$vModel->slug($slug)->id($id);

		// Should we use the coupon code saved in the session?
		$sessionCoupon = $this->container->platform->getSessionVar('coupon', null, 'com_akeebasubs');
		$inputCoupon = $this->input->getString('coupon');

		if (empty($inputCoupon) && !empty($sessionCoupon))
		{
			$vModel->coupon($sessionCoupon);
			$this->container->platform->setSessionVar('coupon', null, 'com_akeebasubs');
		}

		$cache = (array)($vModel->getData());

		if ($cache['firstrun'])
		{
			foreach ($cache as $k => $v)
			{
				if (empty($v))
				{
					if (property_exists($userparams, $k))
					{
						$cache[$k] = $userparams->$k;
					}
				}
			}
		}

		$view->cache = (array)$cache;
		$view->validation = $vModel->getValidation();

		return true;
	}

	/**
	 * Registers page-identifying parameters to the application object. This is used by the Joomla! caching system to
	 * get the unique identifier of a page and decide its caching status (cached, not cached, cache expired).
	 *
	 * @param array $urlparams
	 */
	protected function registerUrlParams($urlparams = array())
	{
		$app = \JFactory::getApplication();

		$registeredurlparams = null;

		if (!empty($app->registeredurlparams))
		{
			$registeredurlparams = $app->registeredurlparams;
		}
		else
		{
			$registeredurlparams = new \stdClass;
		}

		foreach ($urlparams as $key => $value)
		{
			// Add your safe url parameters with variable type as value {@see JFilterInput::clean()}.
			$registeredurlparams->$key = $value;
		}

		$app->registeredurlparams = $registeredurlparams;
	}
}