<?php
/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\Model\Levels;
use Akeeba\Subscriptions\Admin\Model\Subscriptions;

// Make sure ATS is installed and activated
JLoader::import('joomla.application.component.helper');
if (!JComponentHelper::isEnabled('com_ats', true))
{
	return;
}

class plgAkeebasubsAtscredits extends \Akeeba\Subscriptions\Admin\PluginAbstracts\AkeebasubsBase
{
	/** @var array Levels to number of credits added mapping */
	private $credits = array();

	/**
	 * Public constructor
	 *
	 * @param   object &$subject   The object to observe
	 * @param   array  $config     An optional associative array of configuration settings.
	 *                             Recognized key values include 'name', 'group', 'params', 'language'
	 *                             (this list is not meant to be comprehensive).
	 */
	public function __construct(& $subject, $config = array())
	{
		// Include FOF. Required for ATS.
		if (!defined('FOF30_INCLUDED'))
		{
			@include_once(JPATH_LIBRARIES . '/fof30/include.php');
		}

		parent::__construct($subject, $config);

		$this->loadPluginConfiguration();
	}

	/**
	 * Loads the configuration parameters of this plugin from all of the
	 * subscription levels available.
	 */
	private function loadPluginConfiguration()
	{
		$this->credits = array();

		/** @var Levels $model */
		$model = $this->container->factory->model('Levels')->tmpInstance();

		$levels = $model->get(true);

		if ($levels->count())
		{
			/** @var Levels $level */
			foreach ($levels as $level)
			{
				if (isset($level->params['atscredits_credits']))
				{
					$this->credits[$level->akeebasubs_level_id] = $level->params['atscredits_credits'];
				}
			}
		}
	}

	/**
	 * Called whenever the administrator asks to refresh integration status.
	 *
	 * @param $user_id int The Joomla! user ID to refresh information for.
	 */
	public function onAKUserRefresh($user_id)
	{
		// Make sure we're configured
		if (empty($this->credits))
		{
			return;
		}

		// Get all of the user's subscriptions
		/** @var Subscriptions $subscriptionsModel */
		$subscriptionsModel = $this->container->factory->model('Subscriptions')->tmpInstance();

		$subscriptions = $subscriptionsModel
			->user_id($user_id)
			->get(true);

		// Make sure there are subscriptions set for the user
		if (!$subscriptions->count())
		{
			return;
		}

        // Let's register the autoloader for ATS component
        $ats_container = \FOF30\Container\Container::getInstance('com_ats');

		// Get credit information for the user
		if (!class_exists('Akeeba\TicketSystem\Admin\Helper\Credits'))
		{
			return;
		}

		$userCreditAnalysis = \Akeeba\TicketSystem\Admin\Helper\Credits::creditsLeft($user_id, false);

		// Get all #__ats_credittransactions entries
        $transModel = $ats_container->factory->model('CreditTransactions')->tmpInstance();
		$atsCreditEntries = $transModel
			->user_id($user_id)
			->type('akeebasubs')
			->get(true);

		// Create a map of #__ats_credittransactions per subscription ID
		$creditTransactions = array();

		if (count($atsCreditEntries))
		{
			foreach ($atsCreditEntries as $ce)
			{
				$temp = [
					'id'      => $ce->ats_credittransaction_id,
					'value'   => $ce->value,
					'enabled' => $ce->enabled,
					'used'    => 0
				];

				if (array_key_exists($ce->ats_credittransaction_id, $userCreditAnalysis['charges']))
				{
					$temp['used'] = $userCreditAnalysis['charges'][$ce->ats_credittransaction_id];
				}

				$creditTransactions[$ce->unique_id] = $temp;
				unset($temp);
			}
		}

		unset($atsCreditEntries, $userCreditAnalysis);

		// Walk through all subscriptions
		/** @var Subscriptions $sub */
		foreach ($subscriptions as $sub)
		{
			// Does this subscription level exist in $this->credits?
			if (!array_key_exists($sub->akeebasubs_level_id, $this->credits))
			{
				return;
			}

			$value = $this->credits[$sub->akeebasubs_level_id];

			// Do we have an #__ats_credittransactions record for it?
			$hasTransaction = array_key_exists($sub->akeebasubs_subscription_id, $creditTransactions);

			// Is it active or paid and with a start date in the future?
			$jPublishUp = $this->container->platform->getDate($sub->publish_up);
			$jNow = $this->container->platform->getDate();

			$enabled = $sub->enabled
				|| (($sub->getFieldValue('state') == 'C') && ($jPublishUp->toUnix() > $jNow->toUnix()));

			if ($enabled)
			{
				if (!$hasTransaction)
				{
					// Create a new transaction
					$data = array(
						'user_id'          => $user_id,
						'transaction_date' => $sub->created_on,
						'type'             => 'akeebasubs',
						'unique_id'        => $sub->akeebasubs_subscription_id,
						'value'            => $value
					);

                    $transModel->reset();
					$transModel->save($data);
				}
				else
				{
					// Check how many credits are left, based on the current worth of the subscription
					$transaction = $creditTransactions[$sub->akeebasubs_subscription_id];

					$data = array();

					if ($value != $transaction['value'])
					{
						$data['value'] = $value;
					}

					if (!$transaction['enabled'])
					{
						$data['enabled'] = 1;
					}

					if (!empty($data))
					{
                        $transModel->reset();
						$collection = $transModel
							->type('akeebasubs')
							->unique_id($sub->akeebasubs_subscription_id)
                            ->limit(1)
							->get();

                        if($collection)
                        {
                            $transModel = $collection->first();
                        }

						$transModel->save($data);
					}
				}
			}
			// Otherwise it's an expired or unpaid subscription with an #__ats_credittransactions record
			elseif ($hasTransaction)
			{
				// Disable the record
				$data = array(
					'enabled' => 0
				);

                $transModel->reset();
                $collection = $transModel
                    ->type('akeebasubs')
                    ->unique_id($sub->akeebasubs_subscription_id)
                    ->limit(1)
                    ->get();

                if($collection)
                {
                    $transModel = $collection->first();
                }

                $transModel->save($data);
			}
		} // end foreach subscription
	}
}