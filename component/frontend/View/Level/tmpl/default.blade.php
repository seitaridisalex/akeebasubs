<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\Helper\Select;

/** @var \Akeeba\Subscriptions\Site\View\Level\Html $this */

$script = <<<JS

akeebasubs_level_id = {$this->item->akeebasubs_level_id};

JS;
$this->addJavascriptInline($script);

?>

<div id="akeebasubs">

	{{-- "Do Not Track" warning --}}
	@include('site:com_akeebasubs/Level/default_donottrack')

	{{-- Module position 'akeebasubscriptionsheader' --}}
	@modules('akeebasubscriptionsheader')

	<div class="clearfix"></div>

	{{-- Warning when Javascript is disabled --}}
	<noscript>
		<div class="alert alert-warning">
			<h3>
				<span class="glyphicon glyphicon-alert"></span>
				@lang('COM_AKEEBASUBS_LEVEL_ERR_NOJS_HEADER')
			</h3>
			<p>@lang('COM_AKEEBASUBS_LEVEL_ERR_NOJS_BODY')</p>
			<p>
				<a href="http://enable-javascript.com" class="btn btn-primary" target="_blank">
					<span class="glyphicon glyphicon-info-sign"></span>
					@lang('COM_AKEEBASUBS_LEVEL_ERR_NOJS_MOREINFO')
				</a>
			</p>
		</div>
	</noscript>

	<form
		action="@route('index.php?option=com_akeebasubs&view=Subscribe&layout=default&slug=' . $this->input->getString('slug', ''))"
		method="post"
		id="signupForm" class="form form-horizontal">
		<input type="hidden" name="@token()" value="1"/>

		<div class="col-sm-12 col-md-6">
			{{-- ACCOUNT COLUMN --}}
			<div id="akeebasubs-panel-account" class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">
						@lang('COM_AKEEBASUBS_LEVEL_LBL_ACCOUNTHEADER')
					</h3>
				</div>
				<div class="panel-body">
					@include('site:com_akeebasubs/Level/default_fields')
				</div>
			</div>
		</div>
		<div class="col-sm-12 col-md-6">
			{{-- ORDER COLUMN --}}
			<div id="akeebasubs-panel-order" class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">
						@lang('COM_AKEEBASUBS_LEVEL_LBL_YOURORDER')
						<span class="label label-default label-inverse">{{{$this->item->title}}}</span>
					</h3>
				</div>
				<div class="panel-body">
					@include('site:com_akeebasubs/Level/default_summary')
				</div>
			</div>
		</div>

		<div class="clearfix"></div>
	</form>

	{{-- Module position 'akeebasubscriptionsfooter' --}}
	@modules('akeebasubscriptionsfooter')

	<div class="clearfix"></div>
</div>

<?php
$aks_msg_error_overall = JText::_('COM_AKEEBASUBS_LEVEL_ERR_JSVALIDATIONOVERALL', true);
$script                = <<<JS

akeebasubs_apply_validation = {$this->apply_validation};

akeeba.jQuery(document).ready(function(){
	validatePassword();
	validateName();
	validateEmail();
	validateAddress();
	validateBusiness();
	validateForm();
});

function onSignupFormSubmit()
{
	if (akeebasubs_valid_form == false) {
		alert('$aks_msg_error_overall');
	}

	return akeebasubs_valid_form;
}

JS;
$this->addJavascriptInline($script);