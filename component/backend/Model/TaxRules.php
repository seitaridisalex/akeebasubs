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
 * Model for taxation rules
 *
 * Fields:
 *
 * @property  int     $akeebasubs_taxrule_id
 * @property  string  $country
 * @property  string  $state
 * @property  string  $city
 * @property  bool    $vies
 * @property  float   $taxrate
 * @property  int     $akeebasubs_level_id
 *
 * Filters:
 *
 * @method  $this  akeebasubs_taxrule_id()  akeebasubs_taxrule_id(int $v)
 * @method  $this  country()                country(string $v)
 * @method  $this  state()                  state(string $v)
 * @method  $this  city()                   city(string $v)
 * @method  $this  vies()                   vies(bool $v)
 * @method  $this  taxrate()                taxrate(float $v)
 * @method  $this  akeebasubs_level_id()    akeebasubs_level_id(int $v)
 * @method  $this  enabled()                enabled(bool $v)
 * @method  $this  ordering()               ordering(int $v)
 * @method  $this  created_on()             created_on(string $v)
 * @method  $this  created_by()             created_by(int $v)
 * @method  $this  modified_on()            modified_on(string $v)
 * @method  $this  modified_by()            modified_by(int $v)
 * @method  $this  locked_on()              locked_on(string $v)
 * @method  $this  locked_by()              locked_by(int $v)
 *
 */
class TaxRules extends DataModel
{
	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		// Always load the Filters behaviour
		$this->addBehaviour('Filters');

		// Some NOT NULL fields should be allowed to be set to an empty string, therefore have to be skipped by check()
		$this->fieldsSkipChecks = ['country', 'akeebasubs_level_id', 'ordering'];
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
		$db = $this->getDbo();

		$search = $this->getState('search', null, 'string');

		if ($search)
		{
			$search = '%' . $search . '%';
			$query->where($db->qn('city') . ' LIKE ' . $db->q($search));
		}
	}
}