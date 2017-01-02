<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;

defined('_JEXEC') or die;

/**
 * Runs the validation events of the per subscription custom field plugins
 *
 * @package Akeeba\Subscriptions\Site\Model\Subscribe\Validation
 */
class SubscriptionCustomFields extends Base
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
			'subscription_custom_validation' => [],
			'subscription_custom_valid' => true
		];

		$jResponse = $this->container->platform->runPlugins('onValidatePerSubscription', [&$this->state]);

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

				if (!array_key_exists('subscription_custom_validation', $pluginResponse))
				{
					continue;
				}

				if (!is_array($pluginResponse['subscription_custom_validation']))
				{
					continue;
				}

				$response->subscription_custom_valid = $response->subscription_custom_valid && $pluginResponse['valid'];
				$response->subscription_custom_validation = array_merge(
					$response->subscription_custom_validation,
					$pluginResponse['subscription_custom_validation']
				);
			}
		}

		return $response;
	}
}