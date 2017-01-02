<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Site\Model\Subscriptions;
use Akeeba\Subscriptions\Site\Model\Upgrades;

class UpgradeDiscount extends Base
{
	/**
	 * Get the discount from applying an upgrade rule
	 *
	 * Uses
	 * 		BasePrice
	 *
	 * @return  array  upgrade_id, value, combine
	 */
	protected function getValidationResult()
	{
		// Initialise the response
		$ret = [
			'upgrade_id'	=> null,
			'value'			=> 0.0,
		    'combine'       => false,
		];

		// Check that we do have a user (if there's no logged in user, we have
		// no subscription information, ergo upgrades are not applicable!)
		$user_id = $this->jUser->id;

		if (empty($user_id))
		{
			return $ret;
		}

		// Get the state variables
		$state = $this->state;

		// Get the current subscription base price
		$basePriceStructure = $this->factory->getValidator('BasePrice')->execute();
		$basePrice = $basePriceStructure['basePrice'];

		// If this is a free subscription we don't have a discount.
		if ($basePrice <= 0.001)
		{
			return $ret;
		}

		// Get applicable auto-rules
		/** @var Upgrades $upgradesModel */
		$upgradesModel = $this->container->factory->model('Upgrades')->tmpInstance();
		$autoRules = $upgradesModel
			->to_id($state->id)
			->enabled(1)
			->expired(0)
			->get(true);

		// No rules found? No discount.
		if (!$autoRules->count())
		{
			return $ret;
		}

		// Get the user's list of subscriptions
		/** @var Subscriptions $subscriptionsModel */
		$subscriptionsModel = $this->container->factory->model('Subscriptions')->tmpInstance();
		$subscriptions = $subscriptionsModel
			->user_id($user_id)
			->enabled(1)
			->get(true);

		// No subscriptions? No discount: you can't upgrade when you don't have an active subscription.
		if (!$subscriptions->count())
		{
			return $ret;
		}

		$subs = array();
		$uNow = time();

		$subPayments = array();

		foreach ($subscriptions as $subscription)
		{
			$uFrom = $this->container->platform->getDate($subscription->publish_up)->toUnix();
			$presence = $uNow - $uFrom;
			$subs[$subscription->akeebasubs_level_id] = $presence;

			$uOn = $this->container->platform->getDate($subscription->created_on)->toUnix();

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

		// First add all combined rules
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