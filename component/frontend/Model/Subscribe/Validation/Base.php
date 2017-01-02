<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Site\Model\Subscribe\StateData;
use Akeeba\Subscriptions\Site\Model\Subscribe\ValidatorFactory;
use FOF30\Container\Container;
use JUser;

/**
 * Base class for the Validator objects
 *
 * @package Akeeba\Subscriptions\Site\Model\Subscribe\Validation
 */
abstract class Base
{
	/** @var  Container  The Container of the component */
	protected $container = null;

	/** @var  StateData  The state data we're operating on */
	protected $state = null;

	/** @var  ValidatorFactory  The validator factory */
	protected $factory = null;

	/** @var  JUser  The current user's object  */
	protected $jUser = null;

	/** @var  mixed  The (cached) result of this validation class */
	protected $result = null;

	/**
	 * Public constructor
	 *
	 * @param   Container         $container  The container of the component
	 * @param   StateData         $state      State data to operate on
	 * @param   ValidatorFactory  $factory    The validator factory
	 * @param   JUser             $jUser      The Joomla! user object of the user we're validating against
	 */
	public function __construct(Container $container, StateData $state, ValidatorFactory $factory, JUser $jUser)
	{
		$this->container = $container;
		$this->state     = $state;
		$this->factory   = $factory;
		$this->jUser     = $jUser;
	}

	/**
	 * Resets the validation results. The next run will return fresh validation results.
	 *
	 * @return  $this  for chaining
	 */
	final public function reset()
	{
		$this->result = null;

		return $this;
	}

	/**
	 * Gets the (cached) validation results
	 *
	 * @param   bool  $force  When true we reset() before returning the validation results.
	 *
	 * @return  mixed  The (cached) validation results
	 */
	final public function execute($force = false)
	{
		if ($force)
		{
			$this->reset();
		}

		if (is_null($this->result))
		{
			$this->result = $this->getValidationResult();
		}

		return $this->result;
	}

	/**
	 * Get the validation result
	 *
	 * @return  mixed  The validation result. Do not store it to self::$result, let execute() handle it.
	 */
	abstract protected function getValidationResult();
}