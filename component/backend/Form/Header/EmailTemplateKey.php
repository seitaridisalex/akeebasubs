<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Form\Header;

use Akeeba\Subscriptions\Admin\Helper\Email;
use FOF30\Form\Header\Selectable;

defined('_JEXEC') or die;

class EmailTemplateKey extends Selectable
{
	protected function getOptions()
	{
		static $options = null;

		if (is_null($options))
		{
			$options = Email::getEmailKeys(1);

			if (empty($options))
			{
				$options = array();
			}
		}

		reset($options);

		return $options;
	}
}