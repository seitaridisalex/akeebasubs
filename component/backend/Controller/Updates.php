<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Controller;

defined('_JEXEC') or die;

use FOF30\Container\Container;
use FOF30\Controller\Controller;

class Updates extends Controller
{
	use Mixin\PredefinedTaskList;

	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		$this->setPredefinedTaskList(['force']);
		$this->cacheableTasks = array();
	}

	public function force($cachable = false, $urlparams = false, $tpl = null)
	{
		/** @var  $model \Akeeba\Subscriptions\Admin\Model\Updates */
		$model = $this->getModel();

		$model->getUpdates(true);

		$url = 'index.php?option=com_akeebasubs';
		$msg = \JText::_('AKEEBA_COMMON_UPDATE_INFORMATION_RELOADED');
		$this->setRedirect($url, $msg);
	}
}