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

class Coupon extends Base
{
	/**
	 * Validates the Coupon field. Returns an array:
	 * couponFound	True if we found a coupon code. If this is true and valid is false the coupon was found but its
	 * 				conditions did not validate
	 * coupon		The validated Coupons object, or null if there is no valid coupon found
	 * valid		The coupon is valid
	 *
	 * @return  array  See above
	 */
	protected function getValidationResult()
	{
		$ret = [
			'valid' => false,
			'couponFound' => false,
			'coupon' => null
		];

		$couponCode = trim($this->state->coupon);

		// No coupon specified
		if (empty($couponCode))
		{
			return $ret;
		}

		/** @var Coupons $couponsModel */
		$couponsModel = $this->container->factory->model('Coupons')->tmpInstance();

		// Try to load the coupon
		try
		{
			/** @var Coupons $coupon */
			$coupon = $couponsModel
				->coupon(strtoupper($couponCode))
				->firstOrFail();

			if (empty($coupon->akeebasubs_coupon_id))
			{
				$coupon = null;
			}
		}
		catch (\Exception $e)
		{
			$coupon = null;
		}

		// The coupon was not found
		if (is_null($coupon))
		{
			return $ret;
		}

		// Coupon found
		$ret['couponFound'] = true;
		$ret['coupon'] = $coupon;

		// The coupon is invalid unless it's enabled, we've definitely found a match and all coupon conditions apply
		$valid = false;

		if ($coupon->enabled && (strtoupper($coupon->coupon) == strtoupper($couponCode)))
		{
			// Check validity period
			$jFrom = $this->container->platform->getDate($coupon->publish_up);
			$jTo = $this->container->platform->getDate($coupon->publish_down);
			$jNow = $this->container->platform->getDate();

			$valid = ($jNow->toUnix() >= $jFrom->toUnix()) && ($jNow->toUnix() <= $jTo->toUnix());

			// Check levels list
			if ($valid && !empty($coupon->subscriptions))
			{
				$levels = $coupon->subscriptions;

				if (!is_array($levels))
				{
					$levels = explode(',', $coupon->subscriptions);
				}

				$valid = in_array($this->state->id, $levels);
			}

			// Check user
			if ($valid && $coupon->user)
			{
				$user_id = $this->jUser->id;
				$valid = $user_id == $coupon->user;
			}

			// Check email
			if ($valid && $coupon->email)
			{
				$valid = $this->state->email == $coupon->email;
			}

			// Check user group levels
			if ($valid && !empty($coupon->usergroups))
			{
				$groups = $coupon->usergroups;

				if (!is_array($groups))
				{
					$groups = explode(',', $coupon->usergroups);
				}

				$ugroups = $this->jUser->getAuthorisedGroups();
				$valid = 0;

				foreach ($ugroups as $ugroup)
				{
					if (in_array($ugroup, $groups))
					{
						$valid = true;

						break;
					}
				}
			}

			// Check hits limit
			if ($valid && ($coupon->hitslimit > 0))
			{
				/** @var Subscriptions $subscriptionsModel */
				$subscriptionsModel = $this->container->factory->model('Subscriptions')->tmpInstance();

				// Get the real coupon hits
				$hits = $subscriptionsModel
					->coupon_id($coupon->akeebasubs_coupon_id)
					->paystate('C')
					->limit(0)
					->limitstart(0)
					->count();

				$valid = $hits < $coupon->hitslimit;

				if (($coupon->hits != $hits) || ($hits >= $coupon->hitslimit))
				{
					$coupon->hits = $hits;
					$coupon->enabled = $hits < $coupon->hitslimit;
					$coupon->store();
				}
			}

			// Check user hits limit
			if ($valid && $coupon->userhits && !$this->jUser->guest)
			{
				$user_id = $this->jUser->id;

				// How many subscriptions with a paystate of C,P for this user
				// are using this coupon code?
				/** @var Subscriptions $subscriptionsModel */
				$subscriptionsModel = $this->container->factory->model('Subscriptions')->tmpInstance();

				$hits = $subscriptionsModel
					->coupon_id($coupon->akeebasubs_coupon_id)
					->paystate(['C', 'P'])
					->user_id($user_id)
					->limit(0)
					->limitstart(0)
					->count();

				$valid = $hits < $coupon->userhits;
			}
		}

		$ret['valid'] = $valid;

		if (!$ret['valid'])
		{
			$ret['coupon'] = null;
		}

		return $ret;
	}

}