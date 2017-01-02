<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Site\Model\Subscribe\Validation;
use FOF30\Container\Container;
use JUser;

/**
 * Factory for validator objects
 *
 * @package Akeeba\Subscriptions\Site\Model\Subscribe
 */
class ValidatorFactory
{
	/** @var   array   Already created validator objects */
	private $validators = [];

	/** @var   Container  The container */
	private $container = null;

	/** @var   JUser  The current user's object  */
	private $jUser = null;

	/** @var   StateData  The state data from the submitted form / validation request */
	private $state = null;

	/**
	 * Public constructor
	 *
	 * @param   Container  $container  The container of the component
	 * @param   StateData  $state      The state data from the submitted form / validation request
	 * @param   JUser      $jUser      The Joomla! user object of the user we're validating against
	 */
	public function __construct(Container $container, StateData $state, JUser $jUser)
	{
		$this->container = $container;
		$this->state     = $state;
		$this->jUser     = $jUser;
	}

	/**
	 * Set the state data after the factory is created
	 *
	 * @param StateData $state
	 */
	public function setStateData(StateData $state)
	{
		$this->state = $state;
	}

	/**
	 * Gets a validator object by type. If you request the same object type again the same object will be returned.
	 *
	 * @param   string  $type  The validator type
	 *
	 * @return  Validation\Base
	 *
	 * @throws  \InvalidArgumentException  If the validator type is not found
	 */
	public function getValidator($type)
	{
		$className = '\\Akeeba\\Subscriptions\\Site\\Model\\Subscribe\\Validation\\' . ucfirst($type);

		if (!class_exists($className))
		{
			throw new \InvalidArgumentException;
		}

		if (!isset($this->validators[$type]))
		{
			$this->validators[$type] = new $className($this->container, $this->state, $this, $this->jUser);
		}

		return $this->validators[$type];
	}

	/**
	 * Used in Unit Tests. Replaces or sets a validation object in the factory.
	 *
	 * @param   string          $type       The validator type to replace/set
	 * @param   Validation\Base $validator  The validator object for this type
	 *
	 * @return  void
	 */
	public function setValidator($type, $validator)
	{
		if (is_null($validator))
		{
			if (isset($this->validators[$type]))
			{
				unset ($this->validators[$type]);
			}

			return;
		}

		$this->validators[$type] = $validator;
	}

	/**
	 * Reset the validator manager. It expunges all validators from memory.
	 */
	public function reset()
	{
		$this->validators = [];
	}
}