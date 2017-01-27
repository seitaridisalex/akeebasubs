/**
 * @package        akeebasubs
 * @copyright    Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

/**
 * Setup (required for Joomla! 3)
 */
if (typeof(akeeba) == 'undefined')
{
	var akeeba = {};
}
if (typeof(akeeba.jQuery) == 'undefined')
{
	akeeba.jQuery = window.jQuery.noConflict();
}

var akeebasubs_eu_configuration = {
	"BE": ["Belgium", "BE", 21],
	"BG": ["Bulgaria", "BG", 20],
	"CZ": ["Czech Rebulic", "CZ", 21],
	"DK": ["Denmark", "DK", 25],
	"DE": ["Germany", "DE", 19],
	"EE": ["Estonia", "EE", 20],
	"GR": ["Greece", "EL", 24],
	"ES": ["Spain", "ES", 21],
	"FR": ["France", "FR", 20],
	"HR": ["Croatia", "HR", 25],
	"IE": ["Ireland", "IE", 23],
	"IT": ["Italy", "IT", 22],
	"CY": ["Cyprus", "CY", 19],
	"LV": ["Latvia", "LV", 21],
	"LT": ["Lithuania", "LT", 21],
	"LU": ["Luxembourg", "LU", 17],
	"HU": ["Hungary", "HU", 27],
	"MT": ["Malta", "MT", 18],
	"NL": ["Netherlands", "NL", 21],
	"AT": ["Austria", "AT", 20],
	"PL": ["Poland", "PL", 23],
	"PT": ["Portugal", "PT", 23],
	"RO": ["Romania", "RO", 19],
	"SI": ["Slovenia", "SI", 22],
	"SK": ["Slovakia", "SK", 20],
	"FI": ["Finland", "FI", 24],
	"SE": ["Sweden", "SE", 25],
	"GB": ["United Kingdom", "GB", 20],
	"MC": ["Monaco", "FR", 20],
	"IM": ["Isle of Man", "GB", 20]
};

var akeebasubs_business_state = '';
var akeebasubs_isbusiness = false;
var akeebasubs_blocked_gui = false;
var akeebasubs_run_validation_after_unblock = false;
var akeebasubs_cached_response = false;
var akeebasubs_valid_form = true;
var akeebasubs_validation_fetch_queue = [];
var akeebasubs_validation_queue = [];
var akeebasubs_sub_validation_fetch_queue = [];
var akeebasubs_sub_validation_queue = [];
var akeebasubs_level_id = 0;
var akeebasubs_submit_after_validation = false;
var akeebasubs_noneuvat = false;
var akeebasubs_apply_validation = false;
var akeebasubs_form_specifier = 'signupForm';

function cacheSubmitAction(e)
{
	(function ($)
	{
		e.preventDefault();
		akeebasubs_submit_after_validation = true;
		$('#subscribenow').attr('disabled', 'disabled');
	})(akeeba.jQuery);
}

function blockInterface()
{
	(function ($)
	{
		$('#subscribenow').click(cacheSubmitAction);
		$('.ui-disable-spinner').show();
		//$('#subscribenow').attr('disabled','disabled');
		akeebasubs_blocked_gui = true;
	})(akeeba.jQuery);
}

function enableInterface()
{
	(function ($)
	{
		$('#subscribenow').unbind('click');
		$('.ui-disable-spinner').hide();
		$('#subscribenow').removeAttr('disabled');
		akeebasubs_blocked_gui = false;
		if (akeebasubs_run_validation_after_unblock)
		{
			akeebasubs_run_validation_after_unblock = false;
			validateBusiness();
		}
		else
		{
			if (akeebasubs_submit_after_validation)
			{
				akeebasubs_submit_after_validation = false;
				setTimeout("(function($) {$('#subscribenow').click()})(akeeba.jQuery);", 100);
			}
		}
	})(akeeba.jQuery);
}

/**
 * Runs a form validation with the server and returns validation and, optionally,
 * price analysis information. If a callback_function is specified, it will be called
 * if the form is valid.
 * @param callback_function
 * @return
 */
function validateForm(callback_function)
{
	if (akeebasubs_blocked_gui)
	{
		akeebasubs_run_validation_after_unblock = true;
		return;
	}

	(function ($)
	{
		var paymentMethod = $('input[name=paymentmethod]:checked').val();

		if (paymentMethod == null)
		{
			var paymentMethod = $('select[name=paymentmethod]').val();
		}

		var $couponField = $("#coupon");
		var couponValue  = ($couponField.length > 0) ? $couponField.val() : '';

		var data = {
			'action':        'read',
			'id':            akeebasubs_level_id,
			'username':      $('#username').val(),
			'name':          $('#name').val(),
			'email':         $('#email').val(),
			'email2':        $('#email2').val(),
			'address1':      $('#address1').val(),
			'address2':      $('#address2').val(),
			'country':       $('#' + akeebasubs_form_specifier + ' select[name$="country"]').val(),
			'state':         $('#' + akeebasubs_form_specifier + ' select[name$="state"]').val(),
			'city':          $('#city').val(),
			'zip':           $('#zip').val(),
			'isbusiness':    $('#isbusiness').val(),
			'businessname':  $('#businessname').val(),
			'occupation':    $('#occupation').val(),
			'vatnumber':     $('#vatnumber').val(),
			'coupon':        couponValue,
			'paymentmethod': paymentMethod,
			'custom':        {},
			'subcustom':     {}
		};

		if ($('#password'))
		{
			data.password = $('#password').val();
			data.password2 = $('#password2').val();
		}

		// Fetch the custom fields
		$.each(akeebasubs_validation_fetch_queue, function (index, function_name)
		{
			var result = function_name();
			if ((result !== null) && (typeof result == 'object'))
			{
				// Merge the result with the data object
				$.extend(data.custom, result);
			}
		});

		// Fetch the per-subscription custom fields
		$.each(akeebasubs_sub_validation_fetch_queue, function (index, function_name)
		{
			var result = function_name();
			if ((result !== null) && (typeof result == 'object'))
			{
				// Merge the result with the data object
				$.extend(data.subcustom, result);
			}
		});

		blockInterface();

		$.ajax({
			type:     'POST',
			url: akeebasubs_validate_url + '?option=com_akeebasubs&view=Validate&format=json',
			data:     data,
			dataType: 'json',
			success:  function (msg, textStatus, xhr)
			{
				if (msg.validation)
				{
					msg.validation.custom_validation = msg.custom_validation;
					msg.validation.custom_valid = msg.custom_valid;
					msg.validation.subcustom_validation = msg.subscription_custom_validation;
					msg.validation.subcustom_valid = msg.subscription_custom_valid;
					applyValidation(msg.validation, callback_function);
				}
				if (msg.price)
				{
					applyPrice(msg.price);
				}
				enableInterface();
			},
			error:    function (jqXHR, textStatus, errorThrown)
			{
				enableInterface();
			}
		});

		// Fetch list of payment methods
		if (akeebasubs_form_specifier == 'signupForm')
		{
			$.ajax({
				type:     'POST',
				url:      akeebasubs_validate_url + '?option=com_akeebasubs&view=Validate&task=getpayment&format=json',
				data:     data,
				dataType: 'text',
				success:  function (result)
						  {
							  var html = /###(\{.*?\})###/.exec(result);

							  if (html && html[1] !== 'undefined' && html[1].html !== 'undefined')
							  {
								  // Before building the new payment list, let's save the select method, so I can select it again
								  var cur_method = $('input[name="paymentmethod"]:checked').val();
								  $('#paymentlist-container').html(JSON.parse(html[1]).html);
								  $('input[name="paymentmethod"][value="' + cur_method + '"]').prop('checked', true);

								  if (html[1].states !== 'undefined')
								  {
									  $('#akeebasubs-statescontainer').html(JSON.parse(html[1]).states)
								  }
							  }

							  enableInterface();
						  },
				error:    function ()
						  {
							  enableInterface();
						  }
			});
		}

	})(akeeba.jQuery);
}

/**
 * Validates the password fields
 * @return
 */
function validatePassword()
{
	(function ($)
	{
		if (!$('#password'))
		{
			return;
		}
		var password = $('#password').val();
		var password2 = $('#password2').val();

		$('#password_invalid').hide();
		$('#password2_invalid').hide();

		if (!akeebasubs_apply_validation)
		{
			if ((password == '') && (password2 == ''))
			{
				$('#password').parents('div.form-group').removeClass('error has-error');
				$('#password2').parents('div.form-group').removeClass('error has-error');
				return;
			}
		}

		if (password == '')
		{
			$('#password').parents('div.form-group').addClass('error has-error');
			$('#password2').parents('div.form-group').removeClass('error has-error');
			$('#password_invalid').show();
			akeebasubs_valid_form = false;
		}
		else
		{
			$('#password').parents('div.form-group').removeClass('error has-error');
			if (password2 != password)
			{
				$('#password2').parents('div.form-group').addClass('error has-error');
				$('#password2_invalid').show();
				akeebasubs_valid_form = false;
			}
			else
			{
				$('#password2').parents('div.form-group').removeClass('error has-error');
			}
		}
	})(akeeba.jQuery);
}

/**
 * Validates the (real) name
 * @return
 */
function validateName()
{
	(function ($)
	{
		$('#name_empty').hide();
		var name = $('#name').val();

		$('#name').parents('div.form-group').removeClass('error has-error');
		if (!akeebasubs_apply_validation)
		{
			return;
		}

		var invalidName = false;
		if (name == '')
		{
			invalidName = true;
		}
		/**
		 else {
			name = ltrim(rtrim(name, " "), " ");
			var nameParts = name.split(' ');
			if(nameParts.length < 2) invalidName = true;
		}
		 **/

		if (invalidName)
		{
			$('#name').parents('div.form-group').addClass('error has-error');
			$('#name_empty').show();
			akeebasubs_valid_form = false;
			return;
		}
		else
		{
			$('#name').parents('div.form-group').removeClass('error has-error');
		}
	})(akeeba.jQuery);
}

/**
 * DHTML email validation script. Courtesy of SmartWebby.com (http://www.smartwebby.com/dhtml/)
 */

function echeck(str)
{
	var at = "@";
	var dot = ".";
	var lat = str.indexOf(at);
	var lstr = str.length;
	var ldot = str.indexOf(dot);
	if (str.indexOf(at) == -1)
	{
		return false;
	}

	if (str.indexOf(at) == -1 || str.indexOf(at) == 0 || str.indexOf(at) == lstr)
	{
		return false;
	}

	if (str.indexOf(dot) == -1 || str.indexOf(dot) == 0 || str.indexOf(dot) == lstr)
	{
		return false;
	}

	if (str.indexOf(at, (lat + 1)) != -1)
	{
		return false;
	}

	if (str.substring(lat - 1, lat) == dot || str.substring(lat + 1, lat + 2) == dot)
	{
		return false;
	}

	if (str.indexOf(dot, (lat + 2)) == -1)
	{
		return false;
	}

	if (str.indexOf(" ") != -1)
	{
		return false;
	}

	return true;
}

/**
 * Validates the email address
 * @return
 */
function validateEmail()
{
	(function ($)
	{
		$('#email_empty').hide();
		$('#email_invalid').hide();
		$('#email2_invalid').hide();
		$('#email').parents('div.form-group').removeClass('error has-error');
		$('#email2').parents('div.form-group').removeClass('error has-error');
		var email = $('#email').val();
		var email2 = $('#email2').val();

		if (!akeebasubs_apply_validation)
		{
			return;
		}

		if ((email == '') && (email2 == ''))
		{
			$('#email').parents('div.form-group').removeClass('error has-error');
			$('#email2').parents('div.form-group').removeClass('error has-error');
			$('#email_empty').hide();
			$('#email_invalid').hide();
			$('#email2_invalid').hide();
			return;
		}

		if (email == '')
		{
			$('#email').parents('div.form-group').addClass('error has-error');
			$('#email_empty').show();
			akeebasubs_valid_form = false;
			return;
		}
		else if (!echeck(email))
		{
			$('#email').parents('div.form-group').addClass('error has-error');
			$('#email_invalid').show();
			akeebasubs_valid_form = false;
			return;
		}
		else
		{
			validateForm();
		}
	})(akeeba.jQuery);
}

function validateAddress()
{
	(function ($)
	{
		var address = $('#address1').val();
		var country = $('#' + akeebasubs_form_specifier + ' select[name$="country"]').val();
		var state = $('#' + akeebasubs_form_specifier + ' select[name$="state"]').val();
		var city = $('#city').val();
		var zip = $('#zip').val();

		var hasErrors = false;

		if (!akeebasubs_apply_validation)
		{
			$('#address1').parents('div.form-group').removeClass('error has-error');
			$('#country').parents('div.form-group').removeClass('error has-error');
			$('#city').parents('div.form-group').removeClass('error has-error');
			$('#state').parents('div.form-group').removeClass('error has-error');
			$('#zip').parents('div.form-group').removeClass('error has-error');

			$('#address1_empty').hide();
			$('#country_empty').hide();
			$('#city_empty').hide();
			$('#state_empty').hide();
			$('#zip_empty').hide();

			return;
		}


		$('#address1').parents('div.form-group').removeClass('error has-error');
		if (address == '')
		{
			$('#address1').parents('div.form-group').addClass('error has-error');
			$('#address1_empty').show();
			hasErrors = true;
		}
		else
		{
			$('#address1_empty').hide();
		}

		$('#country').parents('div.form-group').removeClass('error has-error');
		if (country == '')
		{
			$('#country').parents('div.form-group').addClass('error has-error');
			$('#country_empty').show();
			hasErrors = true;
		}
		else
		{
			$('#country_empty').hide();
			// If that's an EU country, show and update the VAT field
			if ($('#vatfields'))
			{
				$('#vatfields').hide();

				if (akeebasubs_noneuvat)
				{
					$('#vatfields').show();
					$('#vatcountry').text('');
				}

				Object.keys(akeebasubs_eu_configuration).forEach(function(key){
					if (key == country)
					{
						$('#vatfields').show();

						var ccode = akeebasubs_eu_configuration[key][1];
						$('#vatcountry').text(ccode);

					}
				});
			}
		}

		$('#city').parents('div.form-group').removeClass('error has-error');
		if (city == '')
		{
			$('#city').parents('div.form-group').addClass('error has-error');
			$('#city_empty').show();
			hasErrors = true;
		}
		else
		{
			$('#city_empty').hide();
		}

		$('#zip').parents('div.form-group').removeClass('error has-error');
		if (zip == '')
		{
			$('#zip').parents('div.form-group').addClass('error has-error');
			$('#zip_empty').show();
			hasErrors = true;
		}
		else
		{
			$('#zip_empty').hide();
		}


		if (hasErrors)
		{
			akeebasubs_valid_form = false;
			return;
		}

	})(akeeba.jQuery);
}

/**
 * Validates the business registration information and runs a price fetch
 * @return
 */
function validateBusiness()
{
	(function ($)
	{
		// Do I have to show the business fields?
		if ($('#isbusiness').val() == 1)
		{
			$('#businessfields').show();
		}
		else
		{
			$('#businessfields').hide();
			// If it's not a business validation, chain an address validation
			if (akeebasubs_blocked_gui)
			{
				akeebasubs_run_validation_after_unblock = true;
				return;
			}
			else
			{
				akeebasubs_valid_form = true;
				validateForm();
			}
			return;
		}

		// Do I have to show VAT fields?
		var country = $('#' + akeebasubs_form_specifier + ' select[name$="country"]').val();
		$('#vatfields').hide();

		if (akeebasubs_noneuvat)
		{
			$('#vatfields').css('display', 'block');
			$('#vatcountry').text('');
		}

		Object.keys(akeebasubs_eu_configuration).forEach(function(key){
			if (key == country)
			{
				$('#vatfields').css('display', 'block');

				var ccode = akeebasubs_eu_configuration[key][1];
				$('#vatcountry').text(ccode);

			}
		});

		// Make sure we don't do business validation / price check unless something's changed
		var vatnumber = '';
		if ($('#vatnumber'))
		{
			vatnumber = $('#vatnumber').val();
		}

		var data = {
			country:      $('#' + akeebasubs_form_specifier + ' select[name$="country"]').val(),
			state:        $('#' + akeebasubs_form_specifier + ' select[name$="state"]').val(),
			city:         $('#city').val(),
			zip:          $('#zip').val(),
			isbusiness:   $('#isbusiness').val(),
			businessname: $('#businessname').val(),
			occupation:   $('#occupation').val(),
			vatnumber:    vatnumber,
			coupon:       ($("#coupon").length > 0) ? $('#coupon').val() : ''
		};

		var hash = '';
		for (key in data)
		{
			hash += '|' + key + '|' + data[key];
		}
		hash += '|';

		if (akeebasubs_business_state == hash)
		{
			if (akeebasubs_isbusiness)
			{
				return;
			}
			akeebasubs_isbusiness = true;
		}

		akeebasubs_business_state = hash;

		validateForm();
	})(akeeba.jQuery);
}

function validateIsNotBusiness(e)
{
	(function ($)
	{
		$('#businessfields').hide();
		akeebasubs_cached_response.businessname = true;
		akeebasubs_cached_response.novatrequired = true;
		applyValidation(akeebasubs_cached_response);
		akeebasubs_isbusiness = false;
	})(akeeba.jQuery);
}

function onIsBusinessClick(e)
{
	(function ($) {
		var isBusiness = $('#isbusiness').val() == 1;

		if (isBusiness)
		{
			validateBusiness();

			return;
		}

		validateIsNotBusiness();
	})(akeeba.jQuery);
}

function applyValidation(response, callback)
{
	akeebasubs_cached_response = response;

	(function ($)
	{
		akeebasubs_valid_form = true;
		if (akeebasubs_apply_validation)
		{
			$('#username').parents('div.form-group').removeClass('error has-error');

			if ($('#username_invalid'))
			{
				if (response.username)
				{
					$('#username_invalid').hide();
				}
				else
				{
					if (!$('#username').attr('disabled') || $('#username').attr('disabled') != 'disabled')
					{
						akeebasubs_valid_form = false;
					}

					$('#username_invalid').hide();

					if (true || ($('#username').val() != ''))
					{
						$('#username').parents('div.form-group').addClass('error has-error');
						$('#username_invalid').show();
					}
				}
			}

			$('#name').parents('div.form-group').removeClass('error has-error');
			if (response.name)
			{
				$('#name_empty').hide();
			}
			else
			{
				$('#name').parents('div.form-group').addClass('error has-error');
				akeebasubs_valid_form = false;
				$('#name_empty').show();
			}

			$('#email').parents('div.form-group').removeClass('error has-error');
			if (response.email)
			{
				$('#email_invalid').hide();
			}
			else
			{
				$('#email').parents('div.form-group').addClass('error has-error');
				akeebasubs_valid_form = false;
				$('#email_invalid').show();
			}

			$('#email2').parents('div.form-group').removeClass('error has-error');
			if (response.email2)
			{
				$('#email2_invalid').hide();
			}
			else
			{
				$('#email2').parents('div.form-group').addClass('error has-error');
				akeebasubs_valid_form = false;
				$('#email2_invalid').show();
			}

			$('#address1').parents('div.form-group').removeClass('error has-error');
			if (response.address1)
			{
				$('#address1_empty').hide();
			}
			else
			{
				$('#address1').parents('div.form-group').addClass('error has-error');
				akeebasubs_valid_form = false;
				$('#address1_empty').show();
			}

			$('#country').parents('div.form-group').removeClass('error has-error');
			if (response.country)
			{
				$('#country_empty').hide();
			}
			else
			{
				akeebasubs_valid_form = false;
				$('#country').parents('div.form-group').addClass('error has-error');
				$('#country_empty').show();
			}

			$('#state').parents('div.form-group').removeClass('error has-error');
			if (response.state)
			{
				$('#state_empty').hide();
			}
			else
			{
				$('#state').parents('div.form-group').addClass('error has-error');
				if ($('#state_empty').css('display') != 'none')
				{
					akeebasubs_valid_form = false;
				}
				$('#state_empty').show();
			}

			$('#city').parents('div.form-group').removeClass('error has-error');
			if (response.city)
			{
				$('#city_empty').hide();
			}
			else
			{
				$('#city').parents('div.form-group').addClass('error has-error');
				akeebasubs_valid_form = false;
				$('#city_empty').show();
			}

			$('#zip').parents('div.form-group').removeClass('error has-error');
			if (response.zip)
			{
				$('#zip_empty').hide();
			}
			else
			{
				$('#zip').parents('div.form-group').addClass('error has-error');
				akeebasubs_valid_form = false;
				$('#zip_empty').show();
			}

			$('#businessname').parents('div.form-group').removeClass('error has-error');
			if (response.businessname)
			{
				$('#businessname_empty').hide();
			}
			else
			{
				$('#businessname').parents('div.form-group').addClass('error has-error');
				if ($('#isbusiness').val() == 1)
				{
					akeebasubs_valid_form = false;
				}
				$('#businessname_empty').show();
			}

			$('#occupation').parents('div.form-group').removeClass('error has-error');
			if ($('#occupation').val())
			{
				$('#occupation_empty').hide();
			}
			else
			{
				$('#occupation').parents('div.form-group').addClass('error has-error');
				if ($('#isbusiness').val() == 1)
				{
					akeebasubs_valid_form = false;
				}
				$('#occupation_empty').show();
			}
		}
		else
		{
			// Apply validation is false
			$('#businessname').parents('div.form-group').removeClass('error has-error');
			$('#occupation').parents('div.form-group').removeClass('error has-error');
			$('#businessname_empty').hide();
			$('#occupation_empty').hide();
		}

		$('#vatnumber').parents('div.form-group').removeClass('warning has-warning');
		if (response.vatnumber)
		{
			$('#vat-status-invalid').hide();
		}
		else
		{
			$('#vatnumber').parents('div.form-group').addClass('warning has-warning');
			$('#vat-status-invalid').show();
		}

		if (response.novatrequired)
		{
			$('#vatnumber').parents('div.form-group').removeClass('warning has-warning');
			$('#vat-status-invalid').hide();
		}

		// Finally, apply the custom validation
		$.each(akeebasubs_validation_queue, function (index, function_name)
		{
			var isValid = function_name(response);
			akeebasubs_valid_form = akeebasubs_valid_form & isValid;
		});
		$.each(akeebasubs_sub_validation_queue, function (index, function_name)
		{
			var isValid = function_name(response);
			akeebasubs_valid_form = akeebasubs_valid_form & isValid;
		});

		if (!akeebasubs_apply_validation)
		{
			akeebasubs_valid_form = true;
		}

		if (akeebasubs_apply_validation)
		{
			if (akeebasubs_valid_form)
			{
				$('#subscribenow').addClass('btn-success').removeClass('btn-warning btn-primary')
			}
			else
			{
				$('#subscribenow').removeClass('btn-success btn-primary').addClass('btn-warning')
			}
		}

	})(akeeba.jQuery);
}

function applyPrice(response)
{
	(function ($)
	{
		var $sumTotalField = $('#akeebasubs-sum-total');

		if ($sumTotalField.length > 0)
		{
			var vatContainer = $('#akeebasubs-vat-container');
			vatContainer.hide();

			$sumTotalField.text(response.gross);
			$('#akeebasubs-sum-vat-percent').html(response.taxrate);

			if (response.taxrate > 0)
			{
				vatContainer.show();
			}

			var $discountFieldContainer = $('#akeebasubs-sum-discount-container');
			var $originalFieldContainer = $('#akeebasubs-sum-original-container');

			if ($discountFieldContainer.length)
			{
				$discountFieldContainer.hide();

				if ($originalFieldContainer.length)
				{
					$originalFieldContainer.hide();
				}

				var $discountField = $('#akeebasubs-sum-discount');
				var $originalField = $('#akeebasubs-sum-original');

				if ((response.discount > 0) && $discountField.length)
				{
					$discountFieldContainer.show();

					if ($originalFieldContainer.length)
					{
						$originalFieldContainer.show();
					}

					var discountWithVAT = response.discount * (1.00 + (response.taxrate / 100.00));
					$discountField.html(discountWithVAT.toFixed(2));

					if ($originalField.length)
					{
						var originalWithVAT = response.net * (1.00 + (response.taxrate / 100.00));
						$originalField.html(originalWithVAT.toFixed(2));
					}
				}
			}

			if (response.gross * 1 <= 0)
			{
				$('#paymentmethod-container').hide();
			}
			else
			{
				$('#paymentmethod-container').css('display', 'inline');
			}
		}

		var $couponField = $("#coupon");

		if ($couponField.length)
		{
			var couponValue  = ($couponField.length > 0) ? $couponField.val() : '';
			$couponField.removeClass('coupon-valid coupon-invalid');

			if (couponValue)
			{
				var couponClass = (response.couponid > 0) ? 'coupon-valid' : 'coupon-invalid';
				$couponField.addClass(couponClass);
			}
		}

	})(akeeba.jQuery);
}

/**
 * Adds a function to the validation fetch queue
 */
function addToValidationFetchQueue(myfunction)
{
	if (typeof myfunction != 'function')
	{
		return false;
	}
	akeebasubs_validation_fetch_queue.push(myfunction);
}

/**
 * Adds a function to the validation queue
 */
function addToValidationQueue(myfunction)
{
	if (typeof myfunction != 'function')
	{
		return false;
	}
	akeebasubs_validation_queue.push(myfunction);
}

/**
 * Adds a function to the per-subscription validation fetch queue
 */
function addToSubValidationFetchQueue(myfunction)
{
	if (typeof myfunction != 'function')
	{
		return false;
	}
	akeebasubs_sub_validation_fetch_queue.push(myfunction);
}

/**
 * Adds a function to the per-subscription validation queue
 */
function addToSubValidationQueue(myfunction)
{
	if (typeof myfunction != 'function')
	{
		return false;
	}
	akeebasubs_sub_validation_queue.push(myfunction);
}


(function ($)
{
	$(document).ready(function ()
	{
		if (jQuery('#userinfoForm').length)
		{
			akeebasubs_form_specifier = 'userinfoForm';
		}

		$('#username').blur(validateForm);
		if ($('#password'))
		{
			$('#password').blur(validatePassword);
			$('#password2').blur(validatePassword);
		}
		$('#name').blur(validateName);
		$('#email').blur(validateEmail);
		$('#email2').blur(validateEmail);
		$('#address1').blur(validateAddress);
		$('#city').blur(validateBusiness);
		$('#zip').blur(validateBusiness);
		$('#businessname').blur(validateBusiness);
		$('#vatnumber').blur(validateBusiness);

		$('#' + akeebasubs_form_specifier + ' select[name$="country"]').change(validateBusiness);
		$('#' + akeebasubs_form_specifier + ' select[name$="state"]').change(validateBusiness);
		$('#' + akeebasubs_form_specifier + ' select[name$="isbusiness"]').change(onIsBusinessClick);

		if ($('#coupon').length > 0)
		{
			$('#coupon').blur(validateBusiness);
		}
		// Attach onBlur events to custom fields
		$('#' + akeebasubs_form_specifier + ' *[name]').filter(function (index)
		{
			if ($(this).is('input'))
			{
				return $(this).attr('name').substr(0, 7) == 'custom[';
			}
			return false;
		}).blur(validateForm);
		$('#' + akeebasubs_form_specifier + ' *[name]').filter(function (index)
		{
			if ($(this).is('input'))
			{
				return $(this).attr('name').substr(0, 10) == 'subcustom[';
			}
			return false;
		}).blur(validateForm);
		// Attach onChange events to custom checkboxes
		$('#' + akeebasubs_form_specifier + ' *[name]').filter(function (index)
		{
			if ($(this).attr('type') == 'checkbox')
			{
				return true;
			}
			if ($(this).attr('type') == 'radio')
			{
				return true;
			}
			if ($(this).is('select'))
			{
				return true;
			}
			return false;
		}).change(validateForm);

		setTimeout('onIsBusinessClick();', 1500);

		// Disable form submit when ENTER is hit in the coupon field
		$('input#coupon').keypress(function (e)
		{
			if (e.which == 13)
			{
				validateForm();
				return false;
			}
		});

		validateEmail();
		validateForm();
	});
})(akeeba.jQuery);

/**
 (function($) {
})(akeeba.jQuery);
 /**/

function rtrim(str, charlist)
{
	charlist = !charlist ? ' \\s\u00A0' : (charlist + '').replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '\\$1');
	var re = new RegExp('[' + charlist + ']+$', 'g');
	return (str + '').replace(re, '');
}

function ltrim(str, charlist)
{
	charlist = !charlist ? ' \\s\u00A0' : (charlist + '').replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '$1');
	var re = new RegExp('^[' + charlist + ']+', 'g');
	return (str + '').replace(re, '');
}
