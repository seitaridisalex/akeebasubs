<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

/** @var \Akeeba\Subscriptions\Site\View\Level\Html $this */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\Helper\Select;

$akeebasubs_subscription_level = isset($this->item) ? $this->item->akeebasubs_level_id : null;
$apply_validation              = isset($this->apply_validation) ? ($this->apply_validation == 'true') : true;
$field_data = [
        'name'         => $this->getFieldValue('name'),
        'email'        => $this->getFieldValue('email'),
        'email2'       => $this->getFieldValue('email2'),
        'address1'     => $this->getFieldValue('address1'),
        'address2'     => $this->getFieldValue('address2'),
        'city'         => $this->getFieldValue('city'),
        'state'        => $this->getFieldValue('state'),
        'zip'          => $this->getFieldValue('zip'),
        'country'      => $this->getFieldValue('country', ['XX']),
        'businessname' => $this->getFieldValue('businessname'),
        'occupation'   => $this->getFieldValue('occupation'),
        'vatnumber'    => $this->getFieldValue('vatnumber'),
];

$group_classes                 = [
	'username'     => '',
	'password'     => '',
	'password2'    => '',
	'name'         => $this->validation->validation->name ? '' : 'has-error',
	'email'        => $this->validation->validation->email ? '' : 'has-error',
	'email2'       => $this->validation->validation->email2 ? '' : 'has-error',
	'address1'     => $this->validation->validation->address1 ? '' : 'has-error',
	'city'         => $this->validation->validation->city ? '' : 'has-error',
	'state'        => $this->validation->validation->state ? '' : 'has-error',
	'zip'          => $this->validation->validation->zip ? '' : 'has-error',
	'country'      => $this->validation->validation->country ? '' : 'has-error',
	'businessname' => $this->validation->validation->businessname ? '' : 'has-error',
	'occupation'   => !empty($field_data['occupation']) ? '' : 'has-error',
	'vatnumber'    => $this->validation->validation->vatnumber ? '' : 'warning has-warning',
];

if ($this->container->platform->getUser()->guest)
{
	$group_classes['username']  = (!$apply_validation || $this->validation->validation->username) ? 'has-success' : 'has-error';
	$group_classes['password']  = !$this->cache['password'] ? 'has-error' : '';
	$group_classes['password2'] =
		(!$this->cache['password2'] || ($this->cache['password2'] != $this->cache['password'])) ? 'has-error' :
			'';
}

$isBusiness = $this->getFieldValue('isbusiness');
?>
@js('media://com_akeebasubs/js/signup.js')
@js('media://com_akeebasubs/js/autosubmit.js')

<div class="form form-horizontal akeebasubs-signup-fields">

	@if ($this->container->platform->getUser()->guest)
	<h3>@lang('COM_AKEEBASUBS_LEVEL_USERACCOUNT')</h3>

	{{-- Login button --}}
	<div id="akeebasubs-level-login">
		<div class="col-sm-8 pull-right">
			<a href="@route('index.php?option=com_users&return=' . base64_encode(JUri::getInstance()->toString())))"
			   class="btn btn-primary" rel="nofollow,noindex">
				<span class="glyphicon glyphicon-log-in"></span>
				@lang('COM_AKEEBASUBS_LEVEL_BTN_LOGINIFALERADY')
			</a>
		</div>
		<div class="clearfix"></div>
	</div>

	{{-- Username --}}
	<div class="form-group {{$group_classes['username']}}">
		<label for="username" class="control-label col-sm-4">
			@lang('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME')
		</label>

		<div class="col-sm-8">
			<input type="text" class="form-control" name="username" id="username"
				   value="{{{$this->cache['username']}}}"/>
			<p id="username_invalid" class="help-block"
			   <?php if (strpos($group_classes['username'], 'error') === false): ?>style="display:none"<?php endif ?>>
				@lang('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME_INVALID')
			</p>
		</div>
	</div>

	{{-- Password --}}
	<div class="form-group {{$group_classes['password']}}">
		<label for="password" class="control-label col-sm-4">
			@lang('COM_AKEEBASUBS_LEVEL_FIELD_PASSWORD')
		</label>

		<div class="col-sm-8">
			<input type="password" class="form-control" name="password" id="password"
				   value="{{{$this->cache['password']}}}"/>
			<p id="password_invalid" class="help-block"
			   style="<?php if (strpos($group_classes['password'], 'error') === false): ?>display:none<?php endif; ?>">
				@lang('COM_AKEEBASUBS_LEVEL_ERR_PASSWORD_EMPTY')
			</p>
		</div>
	</div>

	{{-- Password (repeat) --}}
	<div class="form-group {{$group_classes['password2']}}">
		<label for="password2" class="control-label col-sm-4">
			@lang('COM_AKEEBASUBS_LEVEL_FIELD_PASSWORD2')
		</label>

		<div class="col-sm-8">
			<input type="password" class="form-control" name="password2" id="password2"
				   value="{{{$this->cache['password2']}}}"/>
			<p id="password2_invalid" class="help-block"
			   style="<?php if (strpos($group_classes['password2'], 'error') === false): ?>display:none<?php endif; ?>">
				@lang('COM_AKEEBASUBS_LEVEL_ERR_PASSWORD2')
			</p>
		</div>
	</div>
	@endif

	@unless($this->container->platform->getUser()->guest)
	{{-- Username (STATIC DISPLAY) --}}
	<div class="form-group">
		<label for="username" class="control-label col-sm-4">
			@lang('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME')
		</label>

		<div class="col-sm-8">
			<input type="text" class="form-control" name="username" id="username" disabled="disabled"
				   value="{{{$this->userparams->username}}}"/>
		</div>
	</div>
	@endunless

	{{-- Email --}}
	<div class="form-group {{$group_classes['email']}}">
		<label for="email" class="control-label col-sm-4">
			@lang('COM_AKEEBASUBS_LEVEL_FIELD_EMAIL')
		</label>

		<div class="col-sm-8">
			<input type="text" class="form-control" name="email" id="email"
				   value="{{{$field_data['email']}}}"/>
			<p id="email_invalid" class="help-block"
			   <?php if (strpos($group_classes['email'], 'error') === false): ?>style="display:none"<?php endif ?>>
				@lang('COM_AKEEBASUBS_LEVEL_ERR_EMAIL')
			</p>
		</div>
	</div>

	{{-- Email (repeat) --}}
	<div class="form-group {{$group_classes['email2']}}">
		<label for="email2" class="control-label col-sm-4">
			@lang('COM_AKEEBASUBS_LEVEL_FIELD_EMAIL2')
		</label>

		<div class="col-sm-8">
			<input type="text" class="form-control" name="email2" id="email2"
				   value="{{{$field_data['email2']}}}"/>
			<p id="email2_invalid" class="help-block"
			   <?php if (strpos($group_classes['email2'], 'error') === false): ?>style="display:none"<?php endif ?>>
				@lang('COM_AKEEBASUBS_LEVEL_ERR_EMAIL2')
			</p>
		</div>
	</div>

	<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_COUNTRYINFO') ?></h3>

	<div class="alert alert-default">
		<span class="glyphicon glyphicon-info-sign"></span>
		@lang('COM_AKEEBASUBS_LEVEL_COUNTRYINFO_HELP')
	</div>

	{{-- Country --}}
	<div class="form-group {{$group_classes['country']}}">
		<label for="country" class="control-label col-sm-4">
			@lang('COM_AKEEBASUBS_LEVEL_FIELD_COUNTRY')
		</label>

		<div class="col-sm-8">
			{{Select::countries($field_data['country'], 'country', array('class' => 'form-control'))}}
			<p id="country_empty" class="help-block"
			   <?php if (strpos($group_classes['country'], 'error') === false): ?>style="display:none"<?php endif ?>>
				@lang('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')
			</p>
		</div>
	</div>

	{{-- State --}}
	<div class="form-group {{$group_classes['state']}}" id="stateField">
		<label for="state" class="control-label col-sm-4">
			@lang('COM_AKEEBASUBS_LEVEL_FIELD_STATE')
		</label>

		<div class="col-sm-8">
			<div id="akeebasubs-statescontainer">
				{{Select::states($field_data['state'], 'state', array('class' => 'form-control', 'country' => $field_data['country']))}}
			</div>
			<p id="state_empty" class="help-block"
			   <?php if (strpos($group_classes['city'], 'error') === false): ?>style="display:none"<?php endif ?>>
				@lang('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')
			</p>
		</div>
	</div>


	<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_INVOICINGPREFS') ?></h3>

	<div class="alert alert-default">
		<span class="glyphicon glyphicon-info-sign"></span>
		@lang('COM_AKEEBASUBS_LEVEL_INVOICINGPREFS_HELP')
	</div>

	{{-- Full name --}}
	<div class="form-group {{{$group_classes['name']}}}">
		<label for="name" class="control-label col-sm-4">
			@lang('COM_AKEEBASUBS_LEVEL_FIELD_NAME')
		</label>

		<div class="col-sm-8">
			<input type="text" class="form-control" name="name" id="name"
				   value="{{{$field_data['name']}}}"/>
			<p id="name_empty" class="help-block"
			   <?php if (strpos($group_classes['name'], 'error') === false): ?>style="display:none"<?php endif ?>>
				@lang('COM_AKEEBASUBS_LEVEL_ERR_NAME_INVALID')
			</p>
		</div>
	</div>

	{{-- Purchasing as a company --}}
	<div class="form-group">
		<label for="isbusiness" class="control-label col-sm-4">
			@lang('COM_AKEEBASUBS_LEVEL_FIELD_ISBUSINESS')
		</label>
<span class="col-sm-6 col-md-3 col-lg-2">
	<?php echo JHtml::_('select.genericlist', [
			JHtml::_('select.option', '0', JText::_('JNO')),
			JHtml::_('select.option', '1', JText::_('JYES'))
	], 'isbusiness', ['class' => 'form-control'], 'value', 'text', $isBusiness, 'isbusiness'); ?>
</span>
	</div>

	<div id="businessfields">
		{{-- Business name --}}
		<div class="form-group {{$group_classes['businessname']}}">
			<label for="businessname" class="control-label col-sm-4">
				@lang('COM_AKEEBASUBS_LEVEL_FIELD_BUSINESSNAME')
			</label>

			<div class="col-sm-8">
				<input type="text" class="form-control" name="businessname" id="businessname"
					   value="{{{$field_data['businessname']}}}"/>
				<p id="businessname_empty" class="help-block"
				   <?php if (strpos($group_classes['businessname'], 'error') === false): ?>style="display:none"<?php endif ?>>
					@lang('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')
				</p>
			</div>
		</div>

		{{-- Business activity --}}
		<div class="form-group {{$group_classes['occupation']}}">
			<label for="occupation" class="control-label col-sm-4">
				@lang('COM_AKEEBASUBS_LEVEL_FIELD_OCCUPATION')
			</label>

			<div class="col-sm-8">
				<input type="text" class="form-control" name="occupation" id="occupation"
					   value="{{{$field_data['occupation']}}}"/>
				<p id="occupation_empty" class="help-block"
				   <?php if (strpos($group_classes['occupation'], 'error') === false): ?>style="display:none"<?php endif ?>>
					@lang('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')
				</p>
			</div>
		</div>

		{{-- VAT Number --}}
		<div class="form-group {{$group_classes['vatnumber']}}" id="vatfields">
			<label for="vatnumber" class="control-label col-sm-4" id="vatlabel">
				@lang('COM_AKEEBASUBS_LEVEL_FIELD_VATNUMBER_ALTLABEL')
			</label>

			<div class="col-sm-8">
				<div class="input-group">
					<span class="input-group-addon" id="vatcountry">EU</span>
					<input type="text" name="vatnumber" id="vatnumber" class="form-control"
						   value="<?php echo $this->escape($field_data['vatnumber']); ?>"/>
				</div>
				<p id="vat-status-invalid" class="help-block"
				   <?php if (strpos($group_classes['vatnumber'], 'warning') === false): ?>style="display:none"<?php endif ?>>
					@lang('COM_AKEEBASUBS_LEVEL_VAT_INVALID')
				</p>
			</div>
		</div>
	</div>

	{{-- Address --}}
	<div class="form-group {{$group_classes['address1']}}">
		<label for="address1" class="control-label col-sm-4">
			@lang('COM_AKEEBASUBS_LEVEL_FIELD_ADDRESS1')
		</label>

		<div class="col-sm-8">
			<input type="text" class="form-control" name="address1" id="address1"
				   value="{{{$field_data['address1']}}}"/>
			<p id="address1_empty" class="help-block"
			   <?php if (strpos($group_classes['address1'], 'error') === false): ?>style="display:none"<?php endif ?>>
				@lang('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')
			</p>
		</div>
	</div>

	{{-- Address (cont) --}}
	<div class="form-group">
		<label for="address2" class="control-label col-sm-4">
			@lang('COM_AKEEBASUBS_LEVEL_FIELD_ADDRESS2')
		</label>

		<div class="col-sm-8">
			<input type="text" class="form-control" name="address2" id="address2"
				   value="{{{$field_data['address2']}}}"/>
		</div>
	</div>

	{{-- City --}}
	<div class="form-group {{$group_classes['city']}}">
		<label for="city" class="control-label col-sm-4">
			@lang('COM_AKEEBASUBS_LEVEL_FIELD_CITY')
		</label>

		<div class="col-sm-8">
			<input type="text" class="form-control" name="city" id="city"
				   value="{{{$field_data['city']}}}"/>
			<p id="city_empty" class="help-block"
			   <?php if (strpos($group_classes['city'], 'error') === false): ?>style="display:none"<?php endif ?>>
				@lang('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')
			</p>
		</div>
	</div>

	{{-- Zip --}}
	<div class="form-group {{$group_classes['zip']}}">
		<label for="zip" class="control-label col-sm-4">
			@lang('COM_AKEEBASUBS_LEVEL_FIELD_ZIP')
		</label>

		<div class="col-sm-8">
			<input type="text" class="form-control" name="zip" id="zip"
				   value="{{{$field_data['zip']}}}"/>
			<p id="zip_empty" class="help-block"
			   <?php if (strpos($group_classes['zip'], 'error') === false): ?>style="display:none"<?php endif ?>>
				@lang('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')
			</p>
		</div>
	</div>

	{{-- Per-subscription custom fields --}}
	@unless(is_null($akeebasubs_subscription_level))
	@include('site:com_akeebasubs/Level/default_persubscription', [
		'akeebasubs_subscription_level' => $akeebasubs_subscription_level,
		'apply_validation'              => $apply_validation
	])
	@endunless
</div>

<?php
$aks_validate_url  = JUri::base() . 'index.php';
$aks_noneuvat      = $this->container->params->get('noneuvat', 0) ? 'true' : 'false';
$script            = <<< JS

var akeebasubs_validate_url = "$aks_validate_url";
var akeebasubs_valid_form = false;
var akeebasubs_noneuvat = $aks_noneuvat;

JS;
$this->addJavascriptInline($script);
