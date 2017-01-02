<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Site\Model\Coupons;
use Akeeba\Subscriptions\Site\Model\Subscriptions;

class CouponDiscount extends Base
{
	/**
	 * Get the discount from applying a coupon
	 *
	 * Uses:
	 * 		BasePrice
	 * 		Coupon
	 *
	 * @return  array
	 */
	protected function getValidationResult()
	{
		$ret = [
			'valid' => false,
			'couponFound' => false,
			'value' => 0.0,
			'coupon_id' => null
		];

		// Get the coupon validation results
		$couponValidation = $this->factory->getValidator('Coupon')->execute();

		$ret['valid'] = $couponValidation['valid'];
		$ret['couponFound'] = $couponValidation['couponFound'];

		// No valid coupon, no coupon discount
		if (!$ret['valid'])
		{
			return $ret;
		}

		/** @var Coupons $coupon */
		$coupon = $couponValidation['coupon'];
		$ret['coupon_id'] = $coupon->akeebasubs_coupon_id;

		// Double check we really do have a coupon
		if (!is_object($coupon))
		{
			return $ret;
		}

		// Get the base price of the subscription
		$basePriceStructure = $this->factory->getValidator('BasePrice')->execute();
		$basePrice = $basePriceStructure['basePrice'];

		// Initialise the coupon discount value
		$ret['value'] = 0.0;

		// Get the actual coupon discount amount
		switch ($coupon->type)
		{
			case 'value':
				$ret['value'] = (float)$coupon->value;

				if ($ret['value'] > $basePrice)
				{
					$ret['value'] = $basePrice;
				}

				if ($ret['value'] <= 0.001)
				{
					$ret['value'] = 0.0;
				}
				break;

			case 'percent':
				$multiplier = (float)$coupon->value / 100.0;

				if ($multiplier <= 0.001)
				{
					$multiplier = 0.0;
				}

				if ($multiplier > 0.999)
				{
					$multiplier = 1.0;
				}

				$ret['value'] = $multiplier * $basePrice;
				break;

			case 'lastpercent':
				$subPayments = $this->getLatestPayments();

				if (!array_key_exists($this->state->id, $subPayments))
				{
					$lastNet = 0.00;
				}
				else
				{
					$lastNet = $subPayments[$this->state->id]['value'];
				}

				$multiplier = (float)$coupon->value / 100.0;

				if ($multiplier <= 0.001)
				{
					$multiplier = 0.0;
				}

				if ($multiplier > 0.999)
				{
					$multiplier = 1.0;
				}

				$ret['value'] = (float)$lastNet * $multiplier;

				break;
		}

		return $ret;
	}

	/**
	 * Get the latest payments of the user
	 *
	 * @return  array
	 */
	private function getLatestPayments()
	{
		$user = $this->jUser;

		// Not logged in? No last payment.
		if ($user->guest)
		{
			return [];
		}

		$user_id = $user->id;

		// Get the user's list of subscriptions
		/** @var Subscriptions $subscriptionsModel */
		$subscriptionsModel = $this->container->factory->model('Subscriptions')->tmpInstance();
		$subscriptions = $subscriptionsModel
			->user_id($user_id)
			->paystate('C')
			->get(true);

		// No subscriptions? Last payment is 0.
		if (!$subscriptions->count())
		{
			return [];
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

		return $subPayments;
	}
}