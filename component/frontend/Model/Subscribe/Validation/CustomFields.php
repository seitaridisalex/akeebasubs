<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;

defined('_JEXEC') or die;

/**
 * Runs the validation events of the per user custom field plugins
 */
class CustomFields extends Base
{
	/**
	 * Runs the validation events of the loaded akeebasubs plugins
	 *
	 * @return  mixed
	 */
	public function getValidationResult()
	{
		$this->container->platform->importPlugin('akeebasubs');

		// Get the results from the custom validation
		$response = (object)[
			'custom_validation' => [],
			'custom_valid' => true
		];

		$jResponse = $this->container->platform->runPlugins('onValidate', [&$this->state]);

		if (is_array($jResponse) && !empty($jResponse))
		{
			foreach ($jResponse as $pluginResponse)
			{
				if (!is_array($pluginResponse))
				{
					continue;
				}

				if (!array_key_exists('valid', $pluginResponse))
				{
					continue;
				}

				if (!array_key_exists('custom_validation', $pluginResponse))
				{
					continue;
				}

				if (!is_array($pluginResponse['custom_validation']))
				{
					continue;
				}

				$response->custom_valid = $response->custom_valid && $pluginResponse['valid'];
				$response->custom_validation = array_merge(
					$response->custom_validation,
					$pluginResponse['custom_validation']
				);
			}
		}

		return $response;
	}
}