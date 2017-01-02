<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\View\ControlPanel;

use Akeeba\Subscriptions\Admin\Model\ControlPanel;
use JComponentHelper;
use JFactory;
use JText;

defined('_JEXEC') or die;

class Html extends \FOF30\View\DataView\Html
{
	protected function onBeforeMain($tpl = null)
	{
		/** @var ControlPanel $model */
		$model = $this->getModel();

		$this->hasGeoIPPlugin = $model->hasGeoIPPlugin();
		$this->geoIPPluginNeedsUpdate = $model->GeoIPDBNeedsUpdate();

		$this->akeebaCommonDatePHP = JFactory::getDate('2015-08-14 00:00:00', 'GMT')->format(JText::_('DATE_FORMAT_LC1'));
		$this->akeebaCommonDateObsolescence = JFactory::getDate('2016-05-14 00:00:00', 'GMT')->format(JText::_('DATE_FORMAT_LC1'));

		$this->wizardstep = (int)JComponentHelper::getParams('com_akeebasubs')->get('wizardstep', 1);
	}
}