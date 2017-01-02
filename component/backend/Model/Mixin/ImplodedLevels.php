<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Model\Mixin;

use FOF30\Model\DataModel;

defined('_JEXEC') or die;

/**
 * Trait for dealing with imploded arrays, stored as comma-separated values
 */
trait ImplodedLevels
{
	/**
	 * Converts the array of subscription level IDs into a comma separated list after making sure that they actually do
	 * exist.
	 *
	 * @param   array  $value  The array of values
	 *
	 * @return  string  The imploded comma-separated list
	 */
	protected function setAttributeForImplodedLevels($value)
	{
		if (!empty($value))
		{
			if (is_array($value))
			{
				$subs = $value;
			}
			else
			{
				$subs = explode(',', $value);
			}
			if (empty($subs))
			{
				$value = '';
			}
			else
			{
				$subscriptions = array();

				/** @var DataModel $levelModel */
				$levelModel = $this->container->factory->model('Levels')->tmpInstance();

				foreach ($subs as $id)
				{
					try
					{
						$levelModel->reset(true, true);
						$levelModel->findOrFail($id);
						$id = $levelModel->akeebasubs_level_id;
					}
					catch (\Exception $e)
					{
						$id = null;
					}


					if (!is_null($id))
					{
						$subscriptions[] = $id;
					}
				}

				$value = implode(',', $subscriptions);
			}
		}
		else
		{
			return '';
		}

		return $value;
	}
}