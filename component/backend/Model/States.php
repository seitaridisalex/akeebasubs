<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Model;

defined('_JEXEC') or die;

use FOF30\Container\Container;
use FOF30\Model\DataModel;

/**
 * Model for states / prefectures / geographic areas of countries
 *
 * Fields:
 *
 * @property  int     $akeebasubs_state_id
 * @property  string  $country
 * @property  string  $state
 * @property  string  $label
 *
 * Filters:
 *
 * @method  $this  akeebasubs_state_id()  akeebasubs_state_id(int $v)
 * @method  $this  country()              country(string $v)
 * @method  $this  state()                state(string $v)
 * @method  $this  label()                label(string $v)
 * @method  $this  enabled()              enabled(bool $v)
 * @method  $this  created_on()           created_on(string $v)
 * @method  $this  created_by()           created_by(int $v)
 * @method  $this  modified_on()          modified_on(string $v)
 * @method  $this  modified_by()          modified_by(int $v)
 * @method  $this  locked_on()            locked_on(string $v)
 * @method  $this  locked_by()            locked_by(int $v)
 * @method  $this  orderByLabels()		  orderByLabels(int $v)
 *
 */
class States extends DataModel
{
	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		// Always load the Filters behaviour
		$this->addBehaviour('Filters');

		// Some NOT NULL fields should be allowed to be set to an empty string, therefore have to be skipped by check()
		$this->fieldsSkipChecks = ['country', 'akeebasubs_level_id'];
	}

	/**
	 * Build the SELECT query for returning records. Overridden to apply custom filters.
	 *
	 * @param   \JDatabaseQuery  $query           The query being built
	 * @param   bool             $overrideLimits  Should I be overriding the limit state (limitstart & limit)?
	 *
	 * @return  void
	 */
	public function onAfterBuildQuery(\JDatabaseQuery $query, $overrideLimits = false)
	{
		if ($this->getState('orderByLabels'))
		{
			$query->clear('order');
			$query->order('country ASC, label ASC');
		}
	}
}