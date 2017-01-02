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

class TaxConfig extends Controller
{
	use Mixin\PredefinedTaskList;

	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		$this->registerTask('overview', 'display');
		$this->setPredefinedTaskList(['main', 'apply']);
		$this->cacheableTasks = array();
	}

	public function apply($cachable = false, $urlparams = false, $tpl = null)
	{
		$this->csrfProtection();

		/** @var  $model \Akeeba\Subscriptions\Admin\Model\TaxConfig */
		$model = $this->getModel();

		$model->clearTaxRules();
		$model->createTaxRules();
		$model->applyComponentConfiguration();

		// Redirect back to the control panel
		$url = '';
		$returnUrl = $this->input->getBase64('returnurl', '');

		if (!empty($returnUrl))
		{
			$url = base64_decode($returnUrl);
		}
		if (empty($url))
		{
			$url = \JURI::base() . 'index.php?option=com_akeebasubs';
		}

		$this->setRedirect($url, \JText::_('COM_AKEEBASUBS_TAXCONFIGS_MSG_APPLIED'));
	}
}