<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Site\Model\JoomlaUsers;

class Email extends Base
{
	/**
	 * Validate the Email field
	 *
	 * @return  bool
	 */
	protected function getValidationResult()
	{
		$email = trim($this->state->email);

		if (empty($email))
		{
			return false;
		}

		// Get all users with a similar email address
		/** @var JoomlaUsers $usersModel */
		$usersModel = $this->container->factory->model('JoomlaUsers')->tmpInstance();
		$list       = $usersModel
			->email($email)
			->get(true);

		foreach ($list as $item)
		{
			// This is not the email you're looking for
			if ($item->email != $email)
			{
				continue;
			}

			// This is the currently loaded user. Of course we can have the same email address.
			if ($item->id == $this->jUser->id)
			{
				continue;
			}

			// Email belongs to a non-blocked user; this is not allowed.
			if (!$item->block)
			{
				return false;
			}

			// Email belongs to a blocked user. Allow reusing it,
			// if the user is not activated yet. The idea is that
			// a newly created user is blocked and has the activation
			// field filled in. This is a user who failed to complete
			// his subscription. If the validation field is empty, it
			// is a user blocked by the administrator who should not
			// be able to subscribe again!
			if (empty($item->activation))
			{
				return false;
			}
		}

		// Validate the email format
		return $this->isValidEmailAddress($email);
	}

	/**
	 * Is this string a valid-looking email address conforming to the appropriate RFCs?
	 *
	 * @param   string $email The email string to check
	 *
	 * @return  bool  True if it's a valid email string
	 */
	private function isValidEmailAddress($email)
	{
		$isValid = true;
		$atIndex = strrpos($email, "@");

		// There is no @ sign, it can't be a valid email address
		if ($atIndex === false)
		{
			return false;
		}

		$domain    = substr($email, $atIndex + 1);
		$local     = substr($email, 0, $atIndex);
		$localLen  = strlen($local);
		$domainLen = strlen($domain);

		// Local part length exceeded
		if ($localLen < 1 || $localLen > 64)
		{
			return false;
		}

		// Domain part length exceeded
		if ($domainLen < 1 || $domainLen > 255)
		{
			return false;
		}

		// Local part starts or ends with '.'
		if ($local[0] == '.' || $local[ $localLen - 1 ] == '.')
		{
			return false;
		}

		// Local part has two consecutive dots
		if (preg_match('/\\.\\./', $local))
		{
			$isValid = false;
		}

		// Domain part has two consecutive dots
		if (preg_match('/\\.\\./', $domain))
		{
			$isValid = false;
		}


		// Commented out because it is likely to fail on shared hosts
		/**
		// Domain not found in DNS
		if (!(checkdnsrr($domain, "MX") || checkdnsrr($domain, "A")))
		{

			return false;
		}
		/**/

		return true;
	}

}