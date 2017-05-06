<?php
/**
 * @package     Akeeba\Subscriptions\Admin\Helper
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Akeeba\Subscriptions\Admin\Helper;

use Akeeba\Subscriptions\Site\Model\Levels;
use Akeeba\Subscriptions\Site\Model\Subscriptions;
use FOF30\Container\Container;

abstract class Price
{
	/**
	 * The component's container
	 *
	 * @var   Container
	 */
	protected static $container;

	/**
	 * Returns the component's container
	 *
	 * @return  Container
	 */
	protected static function getContainer()
	{
		if (is_null(self::$container))
		{
			self::$container = Container::getInstance('com_akeebasubs');
		}

		return self::$container;
	}

	/**
	 * Cache of pricing information per subscription level, required to cut down on queries in the Strappy layout.
	 *
	 * @var  object[]
	 */
	protected static $pricingInformationCache = [];

	/**
	 * The subscription level IDs for which the currently logged in user has an active subscription.
	 *
	 * @var   array
	 */
	protected static $subIDs = null;

	/**
	 * Parameters for calculating and displaying prices
	 *
	 * @var   \stdClass
	 */
	protected static $pricingParameters = null;

	/**
	 * Returns the subscription level IDs for which the currently logged in user has an active subscription. Used for
	 * calculating sign-up fees.
	 *
	 * @return  int[]
	 */
	public static function getSubIDs()
	{
		if (is_null(self::$subIDs))
		{
			$container = self::getContainer();
			$subIDs    = array();
			$user      = $container->platform->getUser();

			if ($user->id)
			{
				/** @var Subscriptions $mysubs */
				$mysubs = $container->factory->model('Subscriptions')->tmpInstance();
				$mysubs
					->user_id($user->id)
					->paystate('C')
					->get(true);

				if (!empty($mysubs))
				{
					foreach ($mysubs as $sub)
					{
						$subIDs[] = $sub->akeebasubs_level_id;
					}
				}

				$subIDs = array_unique($subIDs);
			}

			self::$subIDs = $subIDs;
		}

		return self::$subIDs;
	}

	/**
	 * Get the price calculation parameters
	 *
	 * @return  \stdClass
	 */
	public static function getPricingParameters()
	{
		if (is_null(self::$pricingParameters))
		{
			$container = self::getContainer();
			/** @var \Akeeba\Subscriptions\Site\Model\TaxHelper $taxModel */
			$taxModel  = self::getContainer()->factory->model('TaxHelper')->tmpInstance();
			$user = $container->platform->getUser();
			$showLocalPrices = $container->params->get('showlocalprices', 1);
			$taxParams  = $taxModel->getTaxDefiningParameters();
			$exchangeRate = 1.00;
			$country = '';
			$localCurrency = '';
			$localSymbol = '';

			if ($showLocalPrices)
			{
				$country = $taxParams['country'];
				Forex::updateRates(false, $container);
				$temp                = Forex::convertToLocal($taxParams['country'], 1.00, $container);
				$exchangeRate  = $temp['rate'];
				$localCurrency = $temp['currency'];
				$localSymbol   = $temp['symbol'];

				// Do not show foreign exchange conversions unless the exchange rate is different than unity
				if (abs($exchangeRate - 1.00) <= 0.000001)
				{
					$showLocalPrices = false;
				}
			}

			self::$pricingParameters = (object) [
				'taxModel'         => $taxModel,
				'showVat'          => $container->params->get('showvat', 0),
				'taxParams'        => $taxParams,
				'includeSignup'    => $container->params->get('includesignup', 2),
				'includeDiscount'  => $user->guest ? false : $container->params->get('includediscount', 0),
				'renderAsFree'     => $container->params->get('renderasfree', 0),
				'showLocalPrices'  => $showLocalPrices,
				'country'          => $country,
				'exchangeRate'     => $exchangeRate,
				'localCurrency'    => $localCurrency,
				'localSymbol'      => $localSymbol,
				'currencyPosition' => $container->params->get('currencypos', 'before'),
				'currency'         => $container->params->get('currency', 'EUR'),
				'currencySymbol'   => $container->params->get('currencysymbol', '€'),
			];
		}

		return self::$pricingParameters;
	}

	/**
	 * Returns the pricing information for a subscription level. Used by the view templates to avoid code duplication.
	 *
	 * @param   \Akeeba\Subscriptions\Site\Model\Levels  $level  The subscription level
	 *
	 * @return  object
	 */
	public static function getLevelPriceInformation(Levels $level)
	{
		$levelKey = $level->getId() . '-' . $level->slug;

		if (isset(self::$pricingInformationCache[$levelKey]))
		{
			return self::$pricingInformationCache[$levelKey];
		}

		$signupFee = 0;
		$discount = 0;
		$levelPrice = $level->price;
		$vatMultiplier = 1.0;
		$vatRule = (object)[
			'match'   => 0,    // How many parameters matched exactly
			'fuzzy'   => 0,    // How many parameters matched fuzzily
			'taxrate' => 0, // Tax rate in percentage points (e.g. 12.3 means 12.3% tax)
			'id'      => 0, // The ID of the tax rule in effect
		];

		$subIDs = self::getSubIDs();
		$params = self::getPricingParameters();

		if (!in_array($level->akeebasubs_level_id, $subIDs) && ($params->includeSignup != 0))
		{
			$signupFee = (float)$level->signupfee;
		}

		if ($params->showVat)
		{
			$vatRule = $params->taxModel->getTaxRule(
				$level->akeebasubs_level_id, $params->taxParams['country'], $params->taxParams['state'],
				$params->taxParams['city'], $params->taxParams['vies']
			);

			$vatMultiplier = (100 + (float)$vatRule->taxrate) / 100;
		}

		$preDiscount = max($levelPrice, 0.0);

		if ($params->includeDiscount)
		{
			/** @var \Akeeba\Subscriptions\Site\Model\Subscribe $subscribeModel */
			$subscribeModel = self::getContainer()->factory->model('Subscribe')->savestate(0);
			$subscribeModel->setState('id', $level->akeebasubs_level_id);
			$subscribeModel->setState('slug', $level->slug);
			$subValidation = $subscribeModel->getValidation(true);
			$discount = $subValidation->price->discount;
			$levelPrice = $level->price - $discount;
		}

		if ($params->includeSignup == 1)
		{
			if (($levelPrice + $signupFee) < 0)
			{
				$levelPrice = -$signupFee;
			}

			$priceForFormatting = ($levelPrice + $signupFee) * $vatMultiplier;
			$formattedPrice     = sprintf('%1.02F', $priceForFormatting);
			$preDiscount        = max(($preDiscount + $signupFee) * $vatMultiplier, 0);
			$levelPrice += $signupFee;
		}
		else
		{
			if ($levelPrice < 0)
			{
				$levelPrice = 0;
			}

			$priceForFormatting = ($levelPrice) * $vatMultiplier;
			$formattedPrice = sprintf('%1.02F', $priceForFormatting);
			$preDiscount = $preDiscount * $vatMultiplier;
		}

		$dotpos = strpos($formattedPrice, '.');
		$price_integer = substr($formattedPrice, 0, $dotpos);
		$price_fractional = substr($formattedPrice, $dotpos + 1);

		$formattedPriceSU = sprintf('%1.02F', $signupFee * $vatMultiplier);
		$dotposSU = strpos($formattedPriceSU, '.');
		$price_integerSU = substr($formattedPriceSU, 0, $dotposSU);
		$price_fractionalSU = substr($formattedPriceSU, $dotposSU + 1);

		$formattedPriceD = sprintf('%1.02F', $discount);
		$dotposD = strpos($formattedPriceD, '.');
		$price_integerD = substr($formattedPriceD, 0, $dotposD);
		$price_fractionalD = substr($formattedPriceD, $dotposD + 1);

		$formattedPreDiscount = sprintf('%1.02F', $preDiscount);
		$dotposPD = strpos($formattedPreDiscount, '.');
		$price_integerPD = substr($formattedPreDiscount, 0, $dotposPD);
		$price_fractionalPD = substr($formattedPreDiscount, $dotposPD + 1);

		self::$pricingInformationCache[$levelKey] = (object)[
			'vatRule'              => $vatRule,
			'vatMultiplier'        => $vatMultiplier,
			'levelPrice'           => $levelPrice,

			'discount'             => $discount,
			'discountFormatted'    => $formattedPriceD,
			'discountInteger'      => $price_integerD,
			'discountFractional'   => $price_fractionalD,

			'prediscount'             => $preDiscount,
			'prediscountFormatted'    => $formattedPreDiscount,
			'prediscountInteger'      => $price_integerPD,
			'prediscountFractional'   => $price_fractionalPD,

			'formattedPrice'       => $formattedPrice,
			'priceInteger'         => $price_integer,
			'priceFractional'      => $price_fractional,
			'formattedPriceSignup' => $formattedPriceSU,
			'priceForFormatting'   => $priceForFormatting,

			'signupFee'            => $signupFee,
			'signupInteger'        => $price_integerSU,
			'signupFractional'     => $price_fractionalSU,
		];

		return self::$pricingInformationCache[$levelKey];
	}

	/**
	 * Convert the price to the local currency, using the currency exchange rate, and return it formatted with the
	 * local currency symbol.
	 *
	 * @param   float  $rawPrice  The raw price
	 *
	 * @return  string
	 */
	public static function toLocalCurrency($rawPrice)
	{
		$params = self::getPricingParameters();
		$currencyPosition = $params->currencyPosition;

		$convertedPriceInfo = Forex::convertToLocal($params->country, $rawPrice);

		$result = sprintf('%0.2f', $convertedPriceInfo['value']);

		if ($currencyPosition == 'before')
		{
			return $convertedPriceInfo['symbol'] . $result;
		}

		return $result . $convertedPriceInfo['symbol'];
	}

}