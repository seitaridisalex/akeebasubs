/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

if(typeof(akeeba) == 'undefined') {
	var akeeba = {};
}

if(typeof(akeeba.jQuery) == 'undefined') {
	akeeba.jQuery = jQuery;
}

// Object initialisation
if (typeof AkeebaSubs == 'undefined')
{
	var AkeebaSubs = {};
}

if (typeof AkeebaSubs.PayMillDss3 == 'undefined')
{
	AkeebaSubs.PayMillDss3 = {
		translations: 		{},
		akeebaUrl:			'',
		clicked:			false
	}
}

(function($){

	/**
	 * Initializes the PayMill DSS3 payment form
	 */
	AkeebaSubs.PayMillDss3.initialize = function()
	{
		paymill.embedFrame('paymilldss3-credit-card-fields', {
			'lang': 'en'
		}, function(error){
			if (error)
			{
				try
				{
					console.log(error.apierror, error.message);
				}
				catch (e) {}

				AkeebaSubs.PayMillDss3.showError(error.message);
			}
			else
			{
				$('#payment-form').show();
				$('#payment-button').click(AkeebaSubs.PayMillDss3.onPaymentButtonClick)
			}
		})
	};

	AkeebaSubs.PayMillDss3.onPaymentButtonClick = function(e)
	{
		// Prevent multiple clicks to the payment button
		if (AkeebaSubs.PayMillDss3.clicked)
		{
			return false;
		}

		AkeebaSubs.PayMillDss3.clicked = true;

		// Disable the button
		$('#payment-button').attr('disabled', 'disabled');
		$('#paymill-warn-noreload').show();

		// Ask PayMill to create a token
		paymill.createTokenViaFrame({
			amount_int:		$('#paymilldss3_amount').val(),
			currency:		$('#paymilldss3_currency').val()
		}, AkeebaSubs.PayMillDss3.onTokenResult);

		return false;
	};

	AkeebaSubs.PayMillDss3.onTokenResult = function(error, result)
	{
		if (error)
		{
			try
			{
				console.log(error.apierror, error.message);
			}
			catch (e) {}

			AkeebaSubs.PayMillDss3.showError(error.message);

			return false;
		}

		$('#payment-errors').css('display', 'none');
		var token = result.token;
		$('#paymilldss3_token').val(token);

		$('#payment-form').submit();
	};

	/**
	 * Displays an error message
	 *
	 * @param  message  string  The error message to display
	 */
	AkeebaSubs.PayMillDss3.showError = function(message)
	{
		$('#payment-errors')
			.html(message)
			.css('display', 'block');
		$('#payment-button').removeAttr('disabled');
		$('#paymill-warn-noreload').hide();
		$('#paymilldss3_token').val('');

		AkeebaSubs.PayMillDss3.clicked = false;
	};

})(akeeba.jQuery);