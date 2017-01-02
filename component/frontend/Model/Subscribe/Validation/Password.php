<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Site\Model\Subscribe;

/**
 * Validates the password
 *
 * @package Akeeba\Subscriptions\Site\Model\Subscribe\Validation
 */
class Password extends Base
{
	/**
	 * Validates the password.
	 *
	 * @return  object  Has keys 'username' and 'password'.
	 */
	public function getValidationResult()
	{
		$myUser   = $this->jUser;

		// Password is always valid for logged in users (of course!)
		if (!$myUser->guest)
		{
			return true;
		}

		// If either password field is empty the password validation fails
		if (empty($this->state->password) || empty($this->state->password2))
		{
			return false;
		}

		// If the two password fields do not match the password validation fails
		if ($this->state->password != $this->state->password2)
		{
			return false;
		}

		return true;
	}
}