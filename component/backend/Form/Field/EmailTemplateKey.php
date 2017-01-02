<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Form\Field;

use Akeeba\Subscriptions\Admin\Helper\Email;
use FOF30\Form\Field\GenericList;

defined('_JEXEC') or die;

class EmailTemplateKey extends GenericList
{
	protected $isRepeatable = false;

	protected function getOptions()
	{
		static $options = null;

		if (is_null($options))
		{
			$mode = $this->isRepeatable ? 2 : 1;
			$options = Email::getEmailKeys($mode);

			if (empty($options))
			{
				$options = array();
			}
		}

		reset($options);

		return $options;
	}

	public function getRepeatable()
	{
		$this->isRepeatable = true;

		return parent::getRepeatable();
	}
}