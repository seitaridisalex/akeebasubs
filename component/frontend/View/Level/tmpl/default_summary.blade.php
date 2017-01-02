<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var \Akeeba\Subscriptions\Site\View\Level\Html $this */

use Akeeba\Subscriptions\Admin\Helper\Image;
use Akeeba\Subscriptions\Admin\Helper\Message;

use Akeeba\Subscriptions\Admin\Helper\Select;

$requireCoupon       = $this->cparams->reqcoupon;

$paymentMethodsCount = count(Select::paymentmethods('paymentmethod', '', ['id'              => 'paymentmethod',
																		  'level_id'        => $this->item->akeebasubs_level_id,
																		  'return_raw_list' => 1]));
$hidePaymentMethod   =
		(($paymentMethodsCount <= 1) && $this->cparams->hidelonepaymentoption) || ($this->validation->price->gross < 0.01);

?>
{{-- SUBSCRIPTION LEVEL DESCRIPTION --}}
<div>
	@jhtml('content.prepare', Message::processLanguage($this->item->description))
</div>

<hr />

{{-- PRICE INFORMATION SUMMARY AREA --}}
@unless($this->validation->price->net < 0.01)
<div id="akeebasubs-sum-original-container" style="display: none">
	<div class="col-xs-6" id="akeebasubs-original-label">
		@lang('COM_AKEEBASUBS_LEVEL_SUM_ORIGINALLY')
	</div>

	<div class="col-xs-6" id="akeebasubs-sum-original-field">
		<del>
		@if ($this->cparams->currencypos == 'before')
			<span class="akeebasubs-level-price-currency">{{{ $this->cparams->currencysymbol }}}</span>
		@endif
		<span class="akeebasubs-level-price" id="akeebasubs-sum-original">0.00</span>
		@if ($this->cparams->currencypos == 'after')
			<span class="akeebasubs-level-price-currency">{{{ $this->cparams->currencysymbol }}}</span>
		@endif
		</del>
	</div>
	<div class="clearfix"></div>
</div>
<div id="akeebasubs-sum-discount-container" style="display: none">
	<div class="col-xs-6" id="akeebasubs-discount-label">
		@lang('COM_AKEEBASUBS_LEVEL_SUM_DISCOUNT')
	</div>

	<div class="col-xs-6" id="akeebasubs-sum-discount-field">
		@if ($this->cparams->currencypos == 'before')
			<span class="akeebasubs-level-price-currency">{{{ $this->cparams->currencysymbol }}}</span>
		@endif
		– <span class="akeebasubs-level-price" id="akeebasubs-sum-discount">0</span>
		@if ($this->cparams->currencypos == 'after')
			<span class="akeebasubs-level-price-currency">{{{ $this->cparams->currencysymbol }}}</span>
		@endif
	</div>
	<div class="clearfix"></div>
</div>
<div id="akeebasubs-sum-container">
	<div class="col-xs-6" id="akeebasubs-sum-label">
		@lang('COM_AKEEBASUBS_LEVEL_SUM_TOTAL')
	</div>

	<div class="col-xs-6" id="akeebasubs-sum-price">
	<span class="label label-success">
		@if ($this->cparams->currencypos == 'before')
			<span class="akeebasubs-level-price-currency">{{{ $this->cparams->currencysymbol }}}</span>
		@endif
		<span class="akeebasubs-level-price" id="akeebasubs-sum-total">{{{ $this->validation->price->gross }}}</span>
		@if ($this->cparams->currencypos == 'after')
			<span class="akeebasubs-level-price-currency">{{{ $this->cparams->currencysymbol }}}</span>
		@endif
	</span>
	</div>
	<div class="clearfix"></div>
</div>

<div id="akeebasubs-vat-container" style="display:{{ ($this->validation->price->taxrate > 0) ? 'block' : 'none' }}">
	<div class="col-xs-6 pull-right" id="akeebasubs-sum-vat-container">
		@lang('COM_AKEEBASUBS_LEVEL_SUM_VAT') <span id="akeebasubs-sum-vat-percent">{{{$this->validation->price->taxrate}}}</span>%
	</div>
	<div class="clearfix"></div>
</div>

<noscript>
	<div class="alert alert-warning">
		<h5>
			<span class="glyphicon glyphicon-alert"></span>
			@lang('COM_AKEEBASUBS_LEVEL_ERR_NOJS_HEADER')
		</h5>
		<p>
			@lang('COM_AKEEBASUBS_LEVEL_SUM_NOSCRIPT')
		</p>
	</div>
</noscript>

<hr />
@endunless

{{-- COUPON CODE--}}
@if ($requireCoupon || ($this->validation->price->net > 0))
	<h3>
		@lang('COM_AKEEBASUBS_LEVEL_FIELD_COUPON')
	</h3>
	<div class="input-group">
		<input type="text" class="form-control" name="coupon" id="coupon"
			   placeholder="@lang('COM_AKEEBASUBS_LEVEL_FIELD_COUPON')"
			   value="{{{$this->cache['coupon']}}}"/>
		<span class="input-group-btn">
			<button class="btn btn-default" type="button" onclick="validateBusiness()">
				@lang('COM_AKEEBASUBS_LEVEL_FIELD_COUPON_VALIDATE')
			</button>
		</span>
	</div>
@endif

{{-- CUSTOM FIELDS --}}
<div>
	<h3>@lang('COM_AKEEBASUBS_LEVEL_SUBSCRIBE')</h3>

	@include('site:com_akeebasubs/Level/default_prepayment')
</div>

{{-- PAYMENT METHODS --}}
<div id="paymentmethod-container" class="{{$hidePaymentMethod ? 'hidden' : ''}}">
	<div class="form-group">
		<label for="paymentmethod" class="control-label sr-only">
			@lang('COM_AKEEBASUBS_LEVEL_FIELD_METHOD')
		</label>

		<div id="paymentlist-container" class="col-xs-12">
			<?php
            $country = $this->getFieldValue('country', ['XX']);

			/** @var \Akeeba\Subscriptions\Site\Model\PaymentMethods $paymentMethods */
			$paymentMethods = $this->getContainer()->factory->model('PaymentMethods')->tmpInstance();
			$defaultPayment = $paymentMethods->getLastPaymentPlugin(JFactory::getUser()->id, $country);

			echo Select::paymentmethods(
					'paymentmethod',
					$defaultPayment,
					array(
							'id'       => 'paymentmethod',
							'level_id' => $this->item->akeebasubs_level_id,
							'country'  => $country
					)
			) ?>
		</div>
	</div>
</div>

{{-- SUBSCRIBE BUTTON --}}
<div class="well">
	<img class="ui-disable-spinner pull-left" src="{{{JUri::base()}}}media/com_akeebasubs/images/throbber.gif"
		 style="display: none"/>
	<button id="subscribenow" class="btn btn-large btn-primary" type="submit"
			style="display:block;margin:auto">
		@lang('COM_AKEEBASUBS_LEVEL_BUTTON_SUBSCRIBE')
	</button>
</div>