<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Helper\Select;

class Country extends Base
{
	/**
	 * Validates the Country field
	 *
	 * @return  bool
	 */
	protected function getValidationResult()
	{
		// Get the country
		$country = trim($this->state->country);

		// No country specified means it's invalid
		if (empty($country))
		{
			return false;
		}

		// Otherwise make sure it's one of the allowed countries
		$countries = Select::getFilteredCountries();
		return array_key_exists($country, $countries);
	}

}