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

class MakeCoupons extends Controller
{
	use Mixin\PredefinedTaskList;

	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		$this->registerTask('overview', 'display');
		$this->setPredefinedTaskList(['overview', 'generate']);
		$this->cacheableTasks = array();
	}

	public function generate($cachable = false, $urlparams = false, $tpl = null)
	{
		/** @var  $model Akeeba\Subscriptions\Admin\Model\MakeCoupons */
		$model = $this->getModel();

		$model->makeCoupons();

		$this->setRedirect('index.php?option=com_akeebasubs&view=MakeCoupons');
	}
}