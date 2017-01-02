<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Controller;

defined('_JEXEC') or die;

use FOF30\Container\Container;
use FOF30\Controller\DataController;

class Level extends DataController
{
	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		// Fake tasks
		$this->registerTask('recurringpublish', 'noop');
		$this->registerTask('recurringunpublish', 'noop');
	}

	/**
	 * Do nothing.
	 *
	 * @param bool $cacheable
	 */
	public function noop($cacheable = false)
	{
		//recurringunpublish
		$url = 'index.php?option=com_akeebasubs&view=Levels';

		$this->setRedirect($url);
	}

	/**
	 * Since I have an "id" filter its state is set after editing an item, causing browse issues.
	 */
	protected function onBeforeBrowse()
	{
		$this->getModel()->blacklistFilters(['created_on', 'created_by']);
		$this->getModel()->setState('id', []);
	}
}