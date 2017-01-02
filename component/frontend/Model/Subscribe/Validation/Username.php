<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Site\Model\JoomlaUsers;
use Akeeba\Subscriptions\Site\Model\Subscribe;

/**
 * Validates the username
 *
 * @package Akeeba\Subscriptions\Site\Model\Subscribe\Validation
 */
class Username extends Base
{
	/**
	 * Validates the username.
	 *
	 * @return  object  Has keys 'username' and 'password'.
	 */
	public function getValidationResult()
	{
		$username = trim($this->state->username);
		$myUser   = $this->jUser;

		// No username specified, that's invalid
		if (empty($username))
		{
			return false;
		}

		/** @var JoomlaUsers $userModel */
		$userModel = $this->container->factory->model('JoomlaUsers')->tmpInstance();
		$user      = $userModel->username([
			'value'  => $username,
			'method' => 'exact'
		])->firstOrNew();

		// I am a logged in user. We only have to check that they don't try to change the username by manipulating
		// the form.
		if (!$myUser->guest)
		{
			return ($user->username == $myUser->username);
		}

		// No existing user
		if (empty($user->username))
		{
			return true;
		}

		// If it's a blocked user, we should allow reusing the username;
		// this would be a user who tried to subscribe, closed the payment
		// window and came back to re-register. However, if the activation
		// field is empty, this is a manually blocked user and should
		// not be allowed to subscribe again.
		if ($user->block && !empty($user->activation))
		{
			return true;
		}

		// Otherwise it's an existing user and we cannot reuse this username
		return false;
	}
}