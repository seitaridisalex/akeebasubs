<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use Akeeba\Subscriptions\Site\Model\Upgrades;

class UpgradeExpiredDiscount extends Base
{
	/**
	 * Get the discount from applying an upgrade rule for expired subscriptions
	 *
	 * Uses
	 * 		BasePrice
	 *
	 * @return  array  upgrade_id, value, combine
	 */
	protected function getValidationResult()
	{
		$ret = [
			'upgrade_id'	=> null,
			'value'			=> 0.00,
		    'combine'       => false
		];

		$state = $this->state;

		// Check that we do have a user (if there's no logged in user, we have
		// no subscription information, ergo upgrades are not applicable!)
		$user_id = $this->jUser->id;

		if (empty($user_id))
		{
			return $ret;
		}

		// Get applicable auto-rules
		/** @var Upgrades $upgradesModel */
		$upgradesModel = $this->container->factory->model('Upgrades')->tmpInstance();

		$autoRules = $upgradesModel
			->to_id($state->id)
			->enabled(1)
			->expired(1)
			->get(true);

		if (!$autoRules->count())
		{
			return $ret;
		}

		// Get the user's list of paid but no longer active (therefore: expired) subscriptions
		/** @var Subscriptions $subscriptionsModel */
		$subscriptionsModel = $this->container->factory->model('Subscriptions')->tmpInstance();

		$subscriptions = $subscriptionsModel
			->user_id($user_id)
			->enabled(0)
			->paystate('C')
			->get(true);

		// No expired subscriptions, so no discount
		if (!$subscriptions->count())
		{
			return $ret;
		}

		$subs = array();
		$uNow = time();

		$subPayments = array();

		foreach ($subscriptions as $subscription)
		{
			$uTo = $this->container->platform->getDate($subscription->publish_down)->toUnix();
			$age = $uNow - $uTo;
			$subs[$subscription->akeebasubs_level_id] = $age;

			$uOn = $this->container->platform->getDate($subscription->created_on);

			if (!array_key_exists($subscription->akeebasubs_level_id, $subPayments))
			{
				$subPayments[$subscription->akeebasubs_level_id] = array(
					'value' => $subscription->net_amount,
					'on'    => $uOn,
				);
			}
			else
			{
				$oldOn = $subPayments[$subscription->akeebasubs_level_id]['on'];

				if ($oldOn < $uOn)
				{
					$subPayments[$subscription->akeebasubs_level_id] = array(
						'value' => $subscription->net_amount,
						'on'    => $uOn,
					);
				}
			}
		}

		// Get the current subscription level's price
		$basePriceStructure = $this->factory->getValidator('BasePrice')->execute();
		$basePrice = $basePriceStructure['basePrice'];

		if ($basePrice <= 0.001)
		{
			return $ret;
		}

		// Remove any rules that do not apply
		foreach ($autoRules as $i => $rule)
		{
			if (
				// Make sure there is an active subscription in the From level
				!(array_key_exists($rule->from_id, $subs))
				// Make sure the min/max presence is respected
				|| ($subs[$rule->from_id] < ($rule->min_presence * 86400))
				|| ($subs[$rule->from_id] > ($rule->max_presence * 86400))
				// If From and To levels are different, make sure there is no active subscription in the To level yet
				|| ($rule->to_id != $rule->from_id && array_key_exists($rule->to_id, $subs))
			)
			{
				unset($autoRules[$i]);
			}
		}

		// First add add all combined rules
		foreach ($autoRules as $i => $rule)
		{
			if (!$rule->combine)
			{
				continue;
			}

			switch ($rule->type)
			{
				case 'value':
					$ret['value'] += $rule->value;
					$ret['upgrade_id'] = $rule->akeebasubs_upgrade_id;
					$ret['combine'] = true;

					break;

				default:
				case 'percent':
					$newDiscount = $basePrice * (float)$rule->value / 100.00;
					$ret['value'] += $newDiscount;
					$ret['upgrade_id'] = $rule->akeebasubs_upgrade_id;
					$ret['combine'] = true;

					break;

				case 'lastpercent':
					if (!array_key_exists($rule->from_id, $subPayments))
					{
						$lastNet = 0.00;
					}
					else
					{
						$lastNet = $subPayments[$rule->from_id]['value'];
					}

					$newDiscount = (float)$lastNet * (float)$rule->value / 100.00;
					$ret['value'] += $newDiscount;
					$ret['upgrade_id'] = $rule->akeebasubs_upgrade_id;
					$ret['combine'] = true;

					break;
			}

			unset($autoRules[$i]);
		}

		// Then check all non-combined rules if they give a higher discount
		foreach ($autoRules as $rule)
		{
			if ($rule->combine)
			{
				continue;
			}

			switch ($rule->type)
			{
				case 'value':
					if ($rule->value > $ret['value'])
					{
						$ret['value'] = $rule->value;
						$ret['upgrade_id'] = $rule->akeebasubs_upgrade_id;
						$ret['combine'] = false;
					}
					break;

				default:
				case 'percent':
					$newDiscount = $basePrice * (float)$rule->value / 100.00;

					if ($newDiscount > $ret['value'])
					{
						$ret['value'] = $newDiscount;
						$ret['upgrade_id'] = $rule->akeebasubs_upgrade_id;
						$ret['combine'] = false;
					}
					break;

				case 'lastpercent':
					if (!array_key_exists($rule->from_id, $subPayments))
					{
						$lastNet = 0.00;
					}
					else
					{
						$lastNet = $subPayments[$rule->from_id]['value'];
					}

					$newDiscount = (float)$lastNet * (float)$rule->value / 100.00;

					if ($newDiscount > $ret['value'])
					{
						$ret['value'] = $newDiscount;
						$ret['upgrade_id'] = $rule->akeebasubs_upgrade_id;
						$ret['combine'] = false;
					}
					break;
			}
		}

		return $ret;
	}

}