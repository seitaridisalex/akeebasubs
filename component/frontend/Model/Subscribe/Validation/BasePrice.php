<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Site\Model\Levels;
use Akeeba\Subscriptions\Site\Model\Subscriptions;

class BasePrice extends Base
{
	/**
	 * Get the base price including the sign-up fee and any price modifiers set by the plugins
	 *
	 * @return  array    basePrice, net, signUp, isRecurring
	 */
	protected function getValidationResult()
	{
		$ret = [
			'levelNet'    => 0.0,
			'basePrice'   => 0.0, // Base price, including sign-up and surcharges
			'signUp'      => 0.0, // Sign-up fee applied
			'isRecurring' => false
		];

		// Get the subscription level and the default sign-up fee
		/** @var Levels $level */
		$level = $this->container->factory->model('Levels')->tmpInstance();
		$level->find($this->state->id);
		$signup_fee = $level->signupfee;
		$ret['levelNet'] = (float)$level->price;

		// If the user is already a subscriber to this level do not charge a sign-up fee
		$subIDs = array();
		$user   = $this->jUser;

		if ($user->id)
		{
			/** @var Subscriptions $subscriptionsModel */
			$subscriptionsModel = $this->container->factory->model('Subscriptions')->tmpInstance();
			$mysubs             = $subscriptionsModel
				->user_id($user->id)
				->paystate('C')
				->get(true);

			if ($mysubs->count())
			{
				foreach ($mysubs as $sub)
				{
					$subIDs[] = $sub->akeebasubs_level_id;
				}
			}

			$subIDs = array_unique($subIDs);

			if (in_array($level->akeebasubs_level_id, $subIDs))
			{
				$signup_fee = 0;
			}
		}

		// Get the default price value
		$basePrice = (float)$level->price + (float)$signup_fee;

		$ret['signUp']      = (float) $signup_fee;
		$ret['isRecurring'] = (bool) $level->recurring;

		// Net price modifiers (via plugins)
		$price_modifier = 0;

		$this->container->platform->importPlugin('akeebasubs');
		$this->container->platform->importPlugin('akpayment');

		$priceValidationData = array_merge(
			(array)$this->state, array(
				'level'    => $level,
				'netprice' => $basePrice
			)
		);

		$jResponse = $this->container->platform->runPlugins('onValidateSubscriptionPrice', array(
				(object)$priceValidationData)
		);

		if (is_array($jResponse) && !empty($jResponse))
		{
			foreach ($jResponse as $pluginResponse)
			{
				if (empty($pluginResponse))
				{
					continue;
				}

				$price_modifier += $pluginResponse;
			}
		}

		$basePrice += $price_modifier;

		$ret['basePrice'] = $basePrice;

		return $ret;
	}

}