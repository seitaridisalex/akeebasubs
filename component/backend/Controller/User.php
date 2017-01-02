<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Controller;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Model\Users;
use FOF30\Controller\DataController;

class User extends DataController
{
	protected function onBeforeEdit()
	{
		$user_id = $this->input->getInt('user_id', 0);

		// Try to load a record based on the Joomla! user ID
		if ($user_id)
		{
			/** @var Users $model */
			$model = $this->getModel()->savestate(false);
			$item = $model->user_id($user_id)->firstOrNew();
			$model->bind($item->getData());

			// If the record was not found, try to create a new one
			if (!$model->getId())
			{
				$url = 'index.php?option=com_akeebasubs&view=User';
				$this->setRedirect($url);
				$this->redirect();
			}
		}
	}
}