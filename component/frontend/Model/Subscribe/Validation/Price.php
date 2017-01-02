<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Helper\EUVATInfo;
use Akeeba\Subscriptions\Site\Model\TaxRules;
use Akeeba\Subscriptions\Site\Model\TaxHelper;

class Price extends Base
{
	/**
	 * Return the pricing information.
	 *
	 * Uses
	 * 		BasePrice
	 * 		CouponDiscount
	 * 		BestAutomaticDiscount
	 * 		PersonalInformation
	 *
	 * @return  array
	 */
	protected function getValidationResult()
	{
		$basePriceStructure = $this->factory->getValidator('BasePrice')->execute();
		$netPrice = $basePriceStructure['basePrice'];

		$couponStructure = $this->factory->getValidator('CouponDiscount')->execute();
		$couponDiscount = $couponStructure['value'];

		// Automatic discount (upgrade rules, subscription level relations) validation
		$discountStructure = $this->factory->getValidator('BestAutomaticDiscount')->execute();
		$autoDiscount = $discountStructure['discount'];

		// Should I use the coupon code or the automatic discount?
		$useCoupon = false;
		$useAuto = true;

		if ($couponStructure['valid'])
		{
			if ($autoDiscount > $couponDiscount)
			{
				$useCoupon = false;
				$useAuto = true;
				$couponStructure['coupon_id'] = null;
			}
			else
			{
				$useAuto = false;
				$useCoupon = true;
				$discountStructure['upgrade_id'] = null;
			}
		}

		$discount = $useCoupon ? $couponDiscount : $autoDiscount;
		$couponid = is_null($couponStructure['coupon_id']) ? 0 : $couponStructure['coupon_id'];
		$upgradeid = is_null($discountStructure['upgrade_id']) ? 0 : $discountStructure['upgrade_id'];

		if ($discount < 0.001)
		{
			$useCoupon = false;
			$useAuto = false;
		}

		// Note: do not reset the oldsup and expiration fields. Subscription level relations must not be bound
		// to the discount.

		// Get the applicable tax rule
		$taxRule = $this->getTaxRule();

		// Calculate the base price minimising rounding errors
		$basePrice = 0.01 * (100 * $netPrice - 100 * $discount);

		if ($basePrice < 0.01)
		{
			$basePrice = 0;
		}

		// Calculate the tax amount minimising rounding errors
		$taxAmount = 0.01 * ($taxRule->taxrate * $basePrice);

		// Calculate the gross amount minimising rounding errors
		$grossAmount = 0.01 * (100 * $basePrice + 100 * $taxAmount);

		// Calculate the recurring amount, if necessary
		$recurringAmount = 0;

		// Sign-up fee
		$signUp = $basePriceStructure['signUp'];

		if ($basePriceStructure['isRecurring'])
		{
			$discountFactor = $discount / $netPrice;
			$recurringAmount = $basePriceStructure['levelNet'] * (1.0 - $discountFactor);

			// Now we need to add the tax rate
			$taxAmountRec = 0.01 * ($taxRule->taxrate * $recurringAmount);
			$recurringAmount = 0.01 * (100 * $recurringAmount + 100 * $taxAmountRec);
		}

		$result = array(
			'net'        => sprintf('%1.02F', round($netPrice, 2)),
			'realnet'    => sprintf('%1.02F', round($basePriceStructure['levelNet'], 2)),
			'signup'     => sprintf('%1.02F', round($signUp, 2)),
			'discount'   => sprintf('%1.02F', round($discount, 2)),
			'taxrate'    => sprintf('%1.02F', (float)$taxRule->taxrate),
			'tax'        => sprintf('%1.02F', round($taxAmount, 2)),
			'gross'      => sprintf('%1.02F', round($grossAmount, 2)),
			'recurring'  => sprintf('%1.02F', round($recurringAmount, 2)),
			'usecoupon'  => $useCoupon ? 1 : 0,
			'useauto'    => $useAuto ? 1 : 0,
			'couponid'   => $couponid,
			'upgradeid'  => $upgradeid,
			'oldsub'     => $discountStructure['oldsub'],
			'allsubs'    => $discountStructure['allsubs'],
			'expiration' => $discountStructure['expiration'],
			'taxrule_id' => $taxRule->id,
			'tax_match'  => $taxRule->match,
			'tax_fuzzy'  => $taxRule->fuzzy,
		);

		return $result;
	}

	/**
	 * Gets the applicable tax rule based on the state variables
	 *
	 * @return  TaxRules  The applicable tax rule
	 */
	private function getTaxRule()
	{
		// Do we have a VIES registered VAT number?
		$validation = $this->factory->getValidator('PersonalInformation')->execute();
		$isVIES = $validation['vatnumber'] && EUVATInfo::isEUVATCountry($this->state->country);

		// If we have a non-empty VAT number in an EU country make sure that this VAT number is really
		// VIES registered. This works around some session issues which may end up reporting a VAT
		// field consisting of spaces as a valid VAT number.
		if ($isVIES)
		{
			$isVIES = EUVATInfo::isVIESValidVATNumber($this->state->country, $this->state->vatnumber);
		}

		/** @var TaxHelper $taxModel */
		$taxModel = $this->container->factory->model('TaxHelper')->tmpInstance();

		return $taxModel->getTaxRule(
			$this->state->id, $this->state->country, $this->state->state, $this->state->city, $isVIES
		);
	}
}