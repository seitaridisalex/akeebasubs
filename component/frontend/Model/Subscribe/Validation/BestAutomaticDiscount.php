<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;

defined('_JEXEC') or die;

class BestAutomaticDiscount extends Base
{
	/**
	 * Get the maximum discount from applying any automatic discount
	 *
	 * Uses:
	 *        BestUpgradeDiscount
	 *        SubscriptionRelationDiscount
	 *
	 * @return  array  discount, expiration, allsubs, oldsub. upgrade_id
	 */
	protected function getValidationResult()
	{
		// Initialise the return value
		$ret = array(
			'discount'   => 0.00, // discount amount
			'expiration' => 'overlap', // old subscription expiration mode
			'allsubs'    => [], // all old subscription ids
			'oldsub'     => null, // old subscription id
			'upgrade_id' => null, // upgrade rule ID
		);

		// Get the automatic discount based on upgrade rules for active and expired subscriptions
		$upgradeRule       = $this->factory->getValidator('BestUpgradeDiscount')->execute();
		$ret['discount']   = $upgradeRule['value'];
		$ret['upgrade_id'] = $upgradeRule['upgrade_id'];

		// Check if we have a Subscription Level Relation discount
		$relationData = $this->factory->getValidator('SubscriptionRelationDiscount')->execute();

		// We don't have Subscription Level Relation discount; return.
		if (is_null($relationData['relation']))
		{
			return $ret;
		}

		// As long as we have an expiration method other than "overlap" pass along the subscriptions which will be
		// replaced / used to extend the subscription time
		if ($relationData['relation']->expiration != 'overlap')
		{
			$ret['expiration'] = $relationData['relation']->expiration;
			$ret['oldsub']     = $relationData['oldsub'];
			$ret['allsubs']    = $relationData['allsubs'];

			$this->_upgrade_id = null;
		}

		// No. What we really have is a relation discount which is NOT based on rules
		if ($relationData['relation']->mode != 'rules')
		{
			// Get the discount from the levels relation and make sure it's greater than the upgrade rules discount
			$relDiscount = $relationData['discount'];

			if ($relDiscount > $ret['discount'])
			{
				// Yes, it's greater than the upgrade rule-based discount. Use it.
				$ret['discount'] = $relDiscount;
				// Also remember to tell our caller that there's no upgrade discount involved, since we're using a
				// subscription level relation which is not bound to upgrade rules.
				$ret['upgrade_id'] = null;
			}
		}

		// Finally, return the structure
		return $ret;
	}

}