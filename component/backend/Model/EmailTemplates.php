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
 * Model Akeeba\Subscriptions\Admin\Model\EmailTemplates
 *
 * Fields:
 *
 * @property  int     $akeebasubs_emailtemplate_id
 * @property  string  $key
 * @property  int     $subscription_level_id
 * @property  string  $subject
 * @property  string  $body
 * @property  string  $language
 *
 * Filters:
 *
 * @method  $this  akeebasubs_emailtemplate_id()  akeebasubs_emailtemplate_id(int $v)
 * @method  $this  key()                          key(string $v)
 * @method  $this  subscription_level_id()        subscription_level_id(int $v)
 * @method  $this  subject()                      subject(string $v)
 * @method  $this  body()                         body(string $v)
 * @method  $this  language()                     language(string $v)
 * @method  $this  enabled()                      enabled(bool $v)
 * @method  $this  ordering()                     ordering(int $v)
 * @method  $this  created_on()                   created_on(string $v)
 * @method  $this  created_by()                   created_by(int $v)
 * @method  $this  modified_on()                  modified_on(string $v)
 * @method  $this  modified_by()                  modified_by(int $v)
 * @method  $this  locked_on()                    locked_on(string $v)
 * @method  $this  locked_by()                    locked_by(int $v)
 *
 **/class EmailTemplates extends DataModel
{
	/**
	 * Overrides the constructor to add the Filters behaviour
	 *
	 * @param Container $container
	 * @param array     $config
	 */
	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		$this->addBehaviour('Filters');
	}

	/**
	 * Unpublish the newly copied item
	 *
	 * @param EmailTemplates $copy
	 */
	protected function onAfterCopy(EmailTemplates $copy)
	{
		// Unpublish the newly copied item
		if ($copy->enabled)
		{
			$this->publish(0);
		}
	}
}