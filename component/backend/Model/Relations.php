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
 * Model for Subscription Level Relations
 *
 * Fields:
 *
 * @property  int     $akeebasubs_relation_id
 * @property  int     $source_level_id
 * @property  int     $target_level_id
 * @property  string  $mode
 * @property  string  $type
 * @property  float   $amount
 * @property  int     $low_threshold
 * @property  float   $low_amount
 * @property  int     $high_threshold
 * @property  float   $high_amount
 * @property  float   $flex_amount
 * @property  int     $flex_period
 * @property  string  $flex_uom
 * @property  string  $flex_timecalculation
 * @property  string  $time_rounding
 * @property  string  $expiration
 * @property  bool    $combine
 *
 * Filters:
 *
 * @method  $this  akeebasubs_relation_id()  akeebasubs_relation_id(int $v)
 * @method  $this  source_level_id()         source_level_id(int $v)
 * @method  $this  target_level_id()         target_level_id(int $v)
 * @method  $this  mode()                    mode(string $v)
 * @method  $this  type()                    type(string $v)
 * @method  $this  amount()                  amount(float $v)
 * @method  $this  low_threshold()           low_threshold(int $v)
 * @method  $this  low_amount()              low_amount(float $v)
 * @method  $this  high_threshold()          high_threshold(int $v)
 * @method  $this  high_amount()             high_amount(float $v)
 * @method  $this  flex_amount()             flex_amount(float $v)
 * @method  $this  flex_period()             flex_period(int $v)
 * @method  $this  flex_uom()                flex_uom(string $v)
 * @method  $this  flex_timecalculation()    flex_timecalculation(string $v)
 * @method  $this  time_rounding()           time_rounding(string $v)
 * @method  $this  expiration()              expiration(string $v)
 * @method  $this  combine()                 combine(bool $v)
 * @method  $this  enabled()                 enabled(bool $v)
 * @method  $this  ordering()                ordering(int $v)
 * @method  $this  created_on()              created_on(string $v)
 * @method  $this  created_by()              created_by(int $v)
 * @method  $this  modified_on()             modified_on(string $v)
 * @method  $this  modified_by()             modified_by(int $v)
 * @method  $this  locked_on()               locked_on(string $v)
 * @method  $this  locked_by()               locked_by(int $v)
 *
 */
class Relations extends DataModel
{
	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		$this->addBehaviour('Filters');

		$this->fieldsSkipChecks = ['ordering'];
	}
}