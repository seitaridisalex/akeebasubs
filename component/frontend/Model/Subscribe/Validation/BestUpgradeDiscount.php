<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;

defined('_JEXEC') or die;

class BestUpgradeDiscount extends Base
{
	/**
	 * Get the maximum discount from applying upgrade rules for active and expired subscriptions
	 *
	 * Uses:
	 * 		UpgradeDiscount
	 * 		UpgradeExpiredDiscount
	 *
	 * @return  array  upgrade_id, value
	 */
	protected function getValidationResult()
	{
		// Get the automatic discount based on upgrade rules for active and expired subscriptions
		$upgradeRule = $this->factory->getValidator('UpgradeDiscount')->execute();
		$upgradeExpiredRule = $this->factory->getValidator('UpgradeExpiredDiscount')->execute();

		// If both rules are supposed to be combined with other rules we return the combined discount
		if ($upgradeRule['combine'] && $upgradeExpiredRule['combine'])
		{
			// We have to choose which rule ID to report. We will report the one which would give the highest discount
			// by itself. This is just for statistics collection. It has no impact on the discount itself.

			if ($upgradeRule['value'] >= $upgradeExpiredRule['value'])
			{
				$upgradeRule['value'] += $upgradeExpiredRule['value'];

				return $upgradeRule;
			}

			$upgradeExpiredRule['value'] += $upgradeRule['value'];

			return $upgradeExpiredRule;
		}

		// If either or both rules are not set up to be combined we will only return the maximum discount
		if ($upgradeRule['value'] >= $upgradeExpiredRule['value'])
		{
			return $upgradeRule;
		}

		return $upgradeExpiredRule;
	}

}