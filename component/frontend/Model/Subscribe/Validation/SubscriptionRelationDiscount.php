<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Site\Model\Subscriptions;
use Akeeba\Subscriptions\Site\Model\Relations;

class SubscriptionRelationDiscount extends Base
{
	/**
	 * Get the information for applying a subscription level relation discount rule
	 *
	 * Uses:
	 * 		BasePrice
	 * 		BestUpgradeDiscount
	 *
	 * @return  array  discount, relation, oldsub, allsubs
	 */
	protected function getValidationResult()
	{
		$state = $this->state;

		// Initialise the return array
		$ret = [
			'discount' => 0,
			'relation' => null,
			'oldsub'   => null,
			'allsubs'  => [],
		];

		$combinedReturn = [
			'discount' => 0,
			'relation' => null,
			'oldsub'   => null,
			'allsubs'  => [],
		];

		if ($this->jUser->guest)
		{
			return $combinedReturn;
		}

		// Get applicable relation rules

		/** @var Relations $relModel */
		$relModel = $this->container->factory->model('Relations')->tmpInstance();

		$autoRules = $relModel
			->target_level_id($state->id)
			->enabled(1)
			->limit(0)
			->limitstart(0)
			->get(true);

		if (!$autoRules->count())
		{
			// No point continuing if we don't have any rules, right?
			return $ret;
		}

		// Get the current subscription level's price
		$basePriceStructure = $this->factory->getValidator('BasePrice')->execute();
		$basePrice = $basePriceStructure['basePrice'];

		// Get the discount from upgrade rules
		$upgradeRule = $this->factory->getValidator('BestUpgradeDiscount')->execute();
		$autoDiscount = $upgradeRule['value'];

		/** @var Relations $rule */
		foreach ($autoRules as $rule)
		{
			// Get all of the user's paid subscriptions with an expiration date
			// in the future in the source_level_id of the rule.
			$jNow = $this->container->platform->getDate();
			$user_id = $this->jUser->id;

			/** @var Subscriptions $subscriptionsModel */
			$subscriptionsModel = $this->container->factory->model('Subscriptions')->tmpInstance();

			$subscriptions = $subscriptionsModel
				->level($rule->source_level_id)
				->user_id($user_id)
				->expires_from($jNow->toSql())
				->paystate('C')
				->filter_order('publish_down')
				->filter_order('ASC')
				->get(true);

			if (!$subscriptions->count())
			{
				// If there are no subscriptions on this level don't bother.
				continue;
			}

			$allsubs = array();

			foreach ($subscriptions as $sub)
			{
				$allsubs[] = $sub->akeebasubs_subscription_id;
			}

			reset($allsubs);

			switch ($rule->mode)
			{
				// Rules-based discount.
				default:
				case 'rules':
					$discount = $autoDiscount;
					break;

				// Fixed discount
				case 'fixed':
					if ($rule->type == 'value')
					{
						$discount = (float)$rule->amount;
					}
					else
					{
						$discount = $basePrice * (float)$rule->amount / 100;
					}
					break;

				// Flexible subscriptions
				case 'flexi':
					// Translate period to days
					switch ($rule->flex_uom)
					{
						default:
						case 'd':
							$modifier = 1;
							break;

						case 'w':
							$modifier = 7;
							break;

						case 'm':
							$modifier = 30;
							break;

						case 'y':
							$modifier = 365;
							break;
					}

					$modifier = $modifier * 86400; // translate to seconds

					$period = $rule->flex_period;

					// Calculate presence
					$remaining_seconds = 0;
					$now = time();

					/** @var Subscriptions $sub */
					foreach ($subscriptions as $sub)
					{
						if (($rule->flex_timecalculation == 'current') && !$sub->enabled)
						{
							continue;
						}

						$from = $this->container->platform->getDate($sub->publish_up)->toUnix();
						$to = $this->container->platform->getDate($sub->publish_down)->toUnix();

						// If the subscription is expired it doesn't count as remaining time
						if ($to < $now)
						{
							continue;
						}

						if ($from > $now)
						{
							$remaining_seconds += $to - $from;
						}
						else
						{
							$remaining_seconds += $to - $now;
						}
					}

					$remaining = $remaining_seconds / $modifier;

					// Check for low threshold
					if (($rule->low_threshold > 0) && ($remaining <= $rule->low_threshold))
					{
						$discount = $rule->low_amount;
					}
					// Check for high threshold
					elseif (($rule->high_threshold > 0) && ($remaining >= $rule->high_threshold))
					{
						$discount = $rule->high_amount;
					}
					else
					// Calculate discount based on presence
					{
						// Round the quantised presence
						switch ($rule->time_rounding)
						{
							case 'floor':
								$remaining = floor($remaining / $period);
								break;

							case 'ceil':
								$remaining = ceil($remaining / $period);
								break;

							case 'round':
								$remaining = round($remaining / $period);
								break;
						}

						$discount = $rule->flex_amount * $remaining;
					}

					// Translate percentages to net values
					if ($rule->type == 'percent')
					{
						$discount = $basePrice * (float)$discount / 100;
					}

					break;
			}

			// Combined rule. Add to, um, the combined rules return array
			if ($rule->combine)
			{
				$combinedReturn['discount'] += $discount;
				$combinedReturn['relation'] = clone $rule;
				$combinedReturn['allsubs'] = array_merge($combinedReturn['allsubs'], $allsubs);
				$combinedReturn['allsubs'] = array_unique($combinedReturn['allsubs']);

				foreach ($subscriptions as $sub)
				{
					// Loop until we find an enabled subscription
					if (!$sub->enabled)
					{
						continue;
					}

					// Use that subscription and beat it
					$combinedReturn['oldsub'] = $sub->akeebasubs_subscription_id;
					break;
				}
			}
			elseif ($discount > $ret['discount'])
			// If the current discount is greater than what we already have, use it
			{
				$ret['discount'] = $discount;
				$ret['relation'] = clone $rule;
				$ret['allsubs'] = $allsubs;

				foreach ($subscriptions as $sub)
				{
					// Loop until we find an enabled subscription
					if (!$sub->enabled)
					{
						continue;
					}

					// Use that subscription and beat it
					$ret['oldsub'] = $sub->akeebasubs_subscription_id;
					break;
				}
			}
		}

		// Finally, check if the combined rule trumps the currently selected
		// rule. If it does, use it instead of the regular return array.
		if (
			($combinedReturn['discount'] > $ret['discount']) ||
			(($ret['discount'] <= 0.01) && !is_null($combinedReturn['relation']))
		)
		{
			$ret = $combinedReturn;
		}

		return $ret;
	}

}