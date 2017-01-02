<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\View\Levels;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Helper\Forex;
use Akeeba\Subscriptions\Site\Model\Levels;
use Akeeba\Subscriptions\Site\Model\Subscriptions;
use Akeeba\Subscriptions\Site\Model\TaxHelper;

class Html extends \FOF30\View\DataView\Html
{
	/**
	 * List of subscription IDs of the current user
	 *
	 * @var  int[]
	 */
	public $subIDs = [];

	/**
	 * Should I include VAT in the front-end display?
	 *
	 * @var  bool
	 */
	public $showVat = false;

	/**
	 * The tax helper model
	 *
	 * @var  TaxHelper
	 */
	public $taxModel;

	/**
	 * Tax defining parameters, fetched from the tax helper model
	 *
	 * @var  array
	 */
	public $taxParams = [];

	/**
	 * Should I include sign-up fees in the displayed prices?
	 *
	 * @var  int
	 */
	public $includeSignup = 0;

	/**
	 * Should I include discounts in the displayed prices?
	 *
	 * @var  bool
	 */
	public $includeDiscount = false;

	/**
	 * Should I render prices of 0 as "FREE"?
	 *
	 * @var  bool
	 */
	public $renderAsFree = false;

	/**
	 * Should I display price conversions when the user's selected country's currency is other than the shop's currency?
	 *
	 * @var bool
	 */
	public $showLocalPrices = false;

	/**
	 * Exchange rate in use
	 *
	 * @var float
	 */
	public $exchangeRate = 1.00;

	/**
	 * Local currency code, e.g. EUR
	 *
	 * @var string
	 */
	public $localCurrency = '';

	/**
	 * Local currency symbol, e.g. €
	 *
	 * @var string
	 */
	public $localSymbol = '';

	/**
	 * Country used for foreign currency display
	 *
	 * @var string
	 */
	public $country = '';

	/**
	 * Should I display notices about
	 *
	 * @var bool
	 */
	public $showNotices = true;

	/**
	 * Cache of pricing information per subscription level, required to cut down on queries in the Strappy layout.
	 *
	 * @var  object[]
	 */
	protected $pricingInformationCache = [];

	public function applyViewConfiguration()
	{
// Cache subscription IDs of this user
		$subIDs = array();
		$user   = \JFactory::getUser();

		if ($user->id)
		{
			/** @var Subscriptions $mysubs */
			$mysubs = $this->container->factory->model('Subscriptions')->tmpInstance();
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

		$this->subIDs = $subIDs;

		// Should I show VAT?
		$this->showVat = $this->container->params->get('showvat', 0);

		// Cache tax parameters
		/** @var \Akeeba\Subscriptions\Site\Model\TaxHelper $taxModel */
		$this->taxModel  = $this->getContainer()->factory->model('TaxHelper')->tmpInstance();
		$this->taxParams = $this->taxModel->getTaxDefiningParameters();

		// Should I include sign-up one time fees?
		$this->includeSignup = $this->container->params->get('includesignup', 2);

		// Should I include discounts? (only valid if it's a logged in user)
		$this->includeDiscount = false;

		if (!$user->guest)
		{
			$this->includeDiscount = $this->container->params->get('includediscount', 0);
		}

		// Should I render zero prices as "FREE"?
		$this->renderAsFree = $this->container->params->get('renderasfree', 0);

		// Should I display prices in local currency based on country selection?
		$this->showLocalPrices = $this->container->params->get('showlocalprices', 1);

		// Update the rates and check the conversion rate for this display
		if ($this->showLocalPrices)
		{
			$this->country = $this->taxParams['country'];
			Forex::updateRates(false, $this->container);
			$temp                = Forex::convertToLocal($this->taxParams['country'], 1.00, $this->container);
			$this->exchangeRate  = $temp['rate'];
			$this->localCurrency = $temp['currency'];
			$this->localSymbol   = $temp['symbol'];
		}

		// Do not show foreign exchange conversions unless the exchange rate is different than unity
		if (abs($this->exchangeRate - 1.00) <= 0.000001)
		{
			$this->showLocalPrices = false;
		}
	}

	/**
	 * Executes before rendering the page for the Browse task.
	 */
	protected function onBeforeBrowse()
	{
		$this->applyViewConfiguration();

		parent::onBeforeBrowse();
	}

	/**
	 * Returns the pricing information for a subscription level. Used by the view templates to avoid code duplication.
	 *
	 * @param   \Akeeba\Subscriptions\Site\Model\Levels  $level  The subscription level
	 *
	 * @return  object
	 */
	public function getLevelPriceInformation(Levels $level)
	{
		$levelKey = $level->getId() . '-' . $level->slug;

		if (isset($this->pricingInformationCache[$levelKey]))
		{
			return $this->pricingInformationCache[$levelKey];
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

		if (!in_array($level->akeebasubs_level_id, $this->subIDs) && ($this->includeSignup != 0))
		{
			$signupFee = (float)$level->signupfee;
		}

		if ($this->showVat)
		{
			$vatRule = $this->taxModel->getTaxRule(
				$level->akeebasubs_level_id, $this->taxParams['country'], $this->taxParams['state'],
				$this->taxParams['city'], $this->taxParams['vies']
			);

			$vatMultiplier = (100 + (float)$vatRule->taxrate) / 100;
		}

		$preDiscount = max($levelPrice, 0.0);

		if ($this->includeDiscount)
		{
			/** @var \Akeeba\Subscriptions\Site\Model\Subscribe $subscribeModel */
			$subscribeModel = $this->getContainer()->factory->model('Subscribe')->savestate(0);
			$subscribeModel->setState('id', $level->akeebasubs_level_id);
			$subscribeModel->setState('slug', $level->slug);
			$subValidation = $subscribeModel->getValidation(true);
			$discount = $subValidation->price->discount;
			$levelPrice = $level->price - $discount;
		}

		if ($this->includeSignup == 1)
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

		$this->pricingInformationCache[$levelKey] = (object)[
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

		return $this->pricingInformationCache[$levelKey];
	}

	public function toLocalCurrency($rawPrice)
	{
		static $currencyPosition = null;

		if (is_null($currencyPosition))
		{
			$currencyPosition = $this->container->params->get('currencypos','before');
		}

		$convertedPriceInfo = Forex::convertToLocal($this->country, $rawPrice);

		$result = sprintf('%0.2f', $convertedPriceInfo['value']);

		if ($currencyPosition == 'before')
		{
			$result = $convertedPriceInfo['symbol'] . $result;
		}
		else
		{
			$result .= $convertedPriceInfo['symbol'];
		}

		return $result;
	}
}