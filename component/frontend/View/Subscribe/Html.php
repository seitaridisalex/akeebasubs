<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\View\Subscribe;

defined('_JEXEC') or die;

use FOF30\View\View;

class Html extends View
{
	/**
	 * The subscription form, created by the payment plugin
	 *
	 * @var  string
	 */
	public $form = '';

	/**
	 * Runs before displaying the view
	 *
	 * @param   string  $tpl  Ignored.
	 *
	 * @throws  \Exception
	 */
	public function onBeforeSubscribe($tpl = null)
	{
		\JFactory::getApplication()->setHeader('X-Cache-Control', 'False', true);
	}
}