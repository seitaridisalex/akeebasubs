<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;

defined('_JEXEC') or die;

class PersonalInformation extends Base
{
	/**
	 * Get the validation results for all personal information.
	 *
	 * Uses:
	 * 		Name
	 * 		Email
	 * 		Country
	 * 		State
	 * 		Business
	 * 		Coupon
	 *
	 * @return  bool
	 */
	protected function getValidationResult()
	{
		$state = $this->state;

		$requireCoupon = $this->container->params->get('reqcoupon', 0) ? true : false;

		// 1. Basic checks
		$ret = array(
			'name'          => !empty($state->name),
			'email'         => !empty($state->email),
			'email2'        => !empty($state->email2) && ($state->email == $state->email2),
			'address1'      => !empty($state->address1),
			'country'       => !empty($state->country),
			'state'         => !empty($state->state),
			'city'          => !empty($state->city),
			'zip'           => !empty($state->zip),
			'businessname'  => !empty($state->businessname),
			'occupation'    => !empty($state->occupation),
			'vatnumber'     => !empty($state->vatnumber),
			'novatrequired' => true,
			'coupon'        => !empty($state->coupon),
		);

		$ret['rawDataForDebug'] = (array)$state;

		// Name validation
		$ret['name'] = $this->factory->getValidator('Name')->execute();

		// Email validation
		$ret['email'] = $this->factory->getValidator('Email')->execute();

		// 2. Country validation
		$ret['country'] = $this->factory->getValidator('Country')->execute();

		// 3. State validation
		$ret['state'] = $this->factory->getValidator('State')->execute();

		// 4. Business validation
		$businessValidation   = $this->factory->getValidator('Business')->execute();
		$ret['businessname']  = $businessValidation['businessname'];
		$ret['occupation']    = $businessValidation['occupation'];
		$ret['vatnumber']     = $businessValidation['vatnumber'];
		$ret['novatrequired'] = $businessValidation['novatrequired'];

		// After the business validation $this->state->vatnumber contains the reformatted VAT number
		$this->container->factory->model('Subscribe')->setState('vatnumber', $state->vatnumber);

		// 5. Coupon validation
		$couponValidation = $this->factory->getValidator('Coupon')->execute();
		$ret['coupon']    = $couponValidation['valid'];

		// If the coupon is invalid because the coupon is not found and we are NOT required to have a valid coupon to
		// subscribe to this level we need to report the coupon as valid, preventing validation errors. The idea is that
		// –unless you're required to present a valid coupon to subscribe– leaving the coupon field blank or typing
		// garbage / a not applicable coupon should not prevent you from subscribing. In fact, that would be the
		// typical case for most sites.
		if (!$ret['coupon'] && !$requireCoupon && !$couponValidation['couponFound'])
		{
			$ret['coupon'] = true;
		}

		return $ret;
	}
}