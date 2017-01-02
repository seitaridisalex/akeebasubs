<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Helper\EUVATInfo;
use Akeeba\Subscriptions\Site\Model\TaxRules;
use Akeeba\Subscriptions\Site\Model\Users;

class Business extends Base
{
	/**
	 * Validate the business fields: business name, activity, VAT number
	 *
	 * @return  array
	 */
	protected function getValidationResult()
	{
		$ret = [
			'businessname'	=> false,
			'occupation'	=> false,
			'vatnumber'		=> false,
			'novatrequired' => false
		];

		// Get some state data
		$country = trim($this->state->country);
		$vatNumber = trim($this->state->vatnumber);
		$businessName = trim($this->state->businessname);
		$businessActivity = trim($this->state->occupation);
		$isBusiness = (bool)$this->state->isbusiness;

		// If this is not a business registration (or we're not supposed to collect personal information) we have
		// to return.
		if (!$isBusiness)
		{
			$ret['businessname'] = true;
			$ret['occupation'] = true;

			return $ret;
		}

		// Otherwise make sure the business name and activity are not empty
		$ret['businessname'] = !empty($businessName);
		$ret['occupation'] = !empty($businessActivity);

		// Fix the VAT number's format
		$vatCheckResult = EUVATInfo::checkVATFormat($country, $vatNumber);
		$this->state->vatnumber = '';

		if ($vatCheckResult->valid)
		{
			$this->state->vatnumber = $vatCheckResult->vatnumber;
		}

		// Is this an EU VAT taxable country
		$isEUVATCountry = EUVATInfo::isEUVATCountry($country);

		// Is this an extra-EU country?
		if (!$isEUVATCountry)
		{
			$ret['novatrequired'] = true;

			return $ret;
		}

		// If the country has two rules with VIES enabled/disabled and a non-zero VAT,
		// we will skip VIES validation. We'll also skip validation if there are no
		// rules for this country (the default tax rate will be applied)

		/** @var TaxRules $taxRulesModel */
		$taxRulesModel = $this->container->factory->model('TaxRules')->tmpInstance();

		// First try loading the rules for this level
		$taxRules = $taxRulesModel
			->enabled(1)
			->akeebasubs_level_id($this->state->id)
			->country($this->state->country)
			->filter_order('ordering')
			->filter_order_Dir('ASC')
			->get(true);

		// If this level has no rules try the "All levels" rules
		if (!$taxRules->count())
		{
			$taxRules = $taxRulesModel
				->enabled(1)
				->akeebasubs_level_id(0)
				->country($this->state->country)
				->filter_order('ordering')
				->filter_order_Dir('ASC')
				->get(true);
		}

		$catchRules = 0;
		$lastVies = null;

		if ($taxRules->count())
		{
			/** @var TaxRules $rule */
			foreach ($taxRules as $rule)
			{
				// Note: You can't use $rule->state since it returns the model state
				$rule_state = $rule->getFieldValue('state', null);

				if (empty($rule_state) && empty($rule->city) && $rule->taxrate && ($lastVies != $rule->vies))
				{
					$catchRules++;
					$lastVies = $rule->vies;
				}
			}
		}

		$mustCheck = ($catchRules < 2) && ($catchRules > 0);

		// If I don't need to check the VAT number, return
		if (!$mustCheck)
		{
			$ret['novatrequired'] = true;

			return $ret;
		}

		// Since I'm required to check the VAT number, keep a note
		$ret['novatrequired'] = false;

		// Can I use cached result? In order to do so...
		$useCachedResult = false;

		// ...I have to be logged in...
		if (!$this->jUser->guest)
		{
			// ...and I must have my viesregistered flag set to 2
			// and my VAT number must match the saved record.
			/** @var Users $subsUsersModel */
			$subsUsersModel = $this->container->factory->model('Users')->tmpInstance();

			$userparams = $subsUsersModel
				->getMergedData($this->jUser->id);

			if (($userparams->viesregistered == 2) && ($userparams->vatnumber == $vatNumber))
			{
				$useCachedResult = true;
			}
		}

		if ($useCachedResult)
		{
			// Use the cached VIES validation result
			$ret['vatnumber'] = true;

			return $ret;
		}

		// No, check the VAT number against the VIES web service
		$ret['vatnumber'] = EUVATInfo::isVIESValidVATNumber($country, $vatNumber);

		return $ret;
	}
}