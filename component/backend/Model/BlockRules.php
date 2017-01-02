<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Model;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Site\Model\Subscribe\StateData;
use FOF30\Container\Container;
use FOF30\Model\DataModel;
use FOF30\Utils\Ip;

/**
 * Model class for subscription blocking rules
 *
 * @property int    $akeebasubs_blockrule_id
 * @property string $username
 * @property string $name
 * @property string $email
 * @property string $iprange
 *
 * @method $this akeebasubs_blockrule_id() akeebasubs_blockrule_id(int $v)
 * @method $this username() username(string $v)
 * @method $this name() name(string $v)
 * @method $this email() email(string $v)
 * @method $this iprange() iprange(string $v)
 */
class BlockRules extends DataModel
{
	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		$this->addBehaviour('Filters');
	}

	/**
	 * Checks if the current user is blocked, i.e. they are not allowed to subscribe to this site.
	 *
	 * @param   StateData  $state  The state of the subscriptions model
	 *
	 * @return  boolean  True if the user is blocked
	 */
	public function isBlocked(StateData $state)
	{
		// Get block rules
		$this->setState('enabled', 1);
		$activeBlockRules = $this->get(true);

		if (!$activeBlockRules->count())
		{
			return false;
		}

		$userIp = Ip::getIp();

		foreach ($activeBlockRules as $rule)
		{
			$hit   = false;
			$match = true;

			if ($rule->username)
			{
				$pattern = strtolower($rule->username);
				$string  = strtolower($state->username);
				$hit     = true;
				$match   = $match && fnmatch($pattern, $string);
			}

			if ($rule->name)
			{
				$pattern = strtolower($rule->name);
				$string  = strtolower($state->name);
				$hit     = true;
				$match   = $match && fnmatch($pattern, $string);
			}

			if ($rule->email)
			{
				$pattern = strtolower($rule->email);
				$string  = strtolower($state->email);
				$hit     = true;
				$match   = $match && (strripos($state->email, $rule->email) === 0);
			}

			if ($rule->iprange)
			{
				$pattern = strtolower($rule->iprange);
				$hit     = true;
				$match   = $match && Ip::IPinList($userIp, $pattern);
			}

			if ($hit && $match)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Check the data for validity.
	 *
	 * @return  static  Self, for chaining
	 *
	 * @throws \RuntimeException  When the data bound to this record is invalid
	 */
	public function check()
	{
		$result = true;

		$this->username = trim($this->username);
		$this->name     = trim($this->name);
		$this->email    = trim($this->email);
		$this->iprange  = trim($this->iprange);

		if (empty($this->username) && empty($this->name) && empty($this->email) && empty($this->iprange))
		{
			throw new \RuntimeException(\JText::_('COM_AKEEBASUBS_BLOCKRULE_ERR_ALLEMPTY'));
		}

		return $this;
	}
}