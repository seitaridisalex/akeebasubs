<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;

defined('_JEXEC') or die;

class Name extends Base
{
	/**
	 * Validate the Name field
	 *
	 * @return  bool
	 */
	protected function getValidationResult()
	{
		$name = trim($this->state->name);

		if (empty($name))
		{
			return false;
		}

		// The name must contain AT LEAST two parts (name/surname) separated by a space
		$nameParts = explode(" ", $name);

		return count($nameParts) >= 2;
	}

}