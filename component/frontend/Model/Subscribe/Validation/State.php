<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Helper\Select;

class State extends Base
{
	/**
	 * Validates the State field (part of address)
	 *
	 * @return  bool
	 */
	protected function getValidationResult()
	{
		// Get the country and state
		$country = trim($this->state->country);
		$state = trim($this->state->state);

		// No country specified? We can't proceed.
		if (empty($country))
		{
			return false;
		}

		// We need to translate the country code to the country name (this is how Select::$states is keyed)
		$country = Select::formatCountry($country);

		// If the selected country has no states we set the state to "N/A" (code: empty string)
		if (!array_key_exists($country, Select::$states))
		{
			$this->state->state = '';

			return true;
		}

		// Is the selected state one of the valid states for this country?
		if (array_key_exists($state, Select::$states[$country]))
		{
			return true;
		}

		$this->state->state = '';

		return false;
	}

}