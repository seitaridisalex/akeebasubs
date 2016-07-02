<?php defined('_JEXEC') or die();?>

<h3><?php echo JText::_('PLG_AKPAYMENT_PAYMILL_FORM_HEADER') ?></h3>

<div id="payment-errors" class="alert alert-error" style="display: none;"></div>
<?php
/*
 * 2013-01-31 nicholas: I moved those fields outside the form because we MUST
 * NOT submit the credit card information back to the site. The whole point of
 * using the bridge JS to get a token is exactly that. Not having the CC info
 * reach our server allows us to do transactions even from servers which are not
 * certified for PCI Compliance. This is a matter of transaction security.
 */
?>
<div class="form-horizontal" id="ccform">
	<div class="control-group" id="control-group-card-holder">
		<label for="card-holder" class="control-label" style="width:190px; margin-right:20px;">
			<?php echo JText::_('PLG_AKPAYMENT_PAYMILL_FORM_CARDHOLDER') ?>
		</label>
		<div class="controls">
			<input type="text" name="card-holder" id="card-holder" class="input-large" value="<?php echo $data->carholder ?>" />
		</div>
	</div>
	<div class="control-group" id="control-group-card-number">
		<label for="card-number" class="control-label" style="width:190px; margin-right:20px;">
			<?php echo JText::_('PLG_AKPAYMENT_PAYMILL_FORM_CC') ?>
		</label>
		<div class="controls">
			<input type="text" name="card-number" id="card-number" class="input-large" />
		</div>
	</div>
	<div class="control-group" id="control-group-card-expiry">
		<label for="card-expiry" class="control-label" style="width:190px; margin-right:20px;">
			<?php echo JText::_('PLG_AKPAYMENT_PAYMILL_FORM_EXPDATE') ?>
		</label>
		<div class="controls">
			<?php echo $this->selectMonth() ?><span> / </span><?php echo $this->selectYear() ?>
		</div>
	</div>
	<div class="control-group" id="control-group-card-cvc">
		<label for="card-cvc" class="control-label" style="width:190px; margin-right:20px;">
			<?php echo JText::_('PLG_AKPAYMENT_PAYMILL_FORM_CVC') ?>
		</label>
		<div class="controls">
			<input type="text" name="card-cvc" id="card-cvc" class="input-mini" />
		</div>
	</div>

	<div class="control-group">
		<label for="pay" class="control-label" style="width:190px; margin-right:20px;">
		</label>
		<div class="controls">
			<a href="#ccform" id="payment-button" class="btn">
				<?php echo JText::_('PLG_AKPAYMENT_PAYMILL_FORM_PAYBUTTON') ?>
			</a>
		</div>
	</div>
	<div class="alert alert-warning" id="paymill-warn-noreload" style="display: none;">
		<?php echo JText::_('PLG_AKPAYMENT_PAYMILL_WARN_NORELOAD') ?>
	</div>
</div>

<form id="payment-form" action="<?php echo $data->url ?>" method="post" class="form form-horizontal">
	<input type="hidden" name="currency" id="currency" value="<?php echo $data->currency ?>" />
	<input type="hidden" name="amount" id="amount" value="<?php echo $data->amount ?>" />
	<input type="hidden" name="description" id="description" value="<?php echo $data->description ?>" />
	<input type="hidden" name="token" id="token" />
</form>

<script type="text/javascript">
var akeebasubs_paymill_clicked = false;

function PaymillResponseHandler(error, result)
{
	(function($) {
		$('#ccform .control-group').removeClass('error');
		if (error) {
			if (error.apierror == '3internal_server_error')
			{
				$('#payment-errors').html('<?php echo JText::_('PLG_AKPAYMENT_PAYMILL_FORM_3INTERNAL_SERVER_ERROR') ?>');
			}
			else if (error.apierror == 'internal_server_error')
			{
				$('#payment-errors').html('<?php echo JText::_('PLG_AKPAYMENT_PAYMILL_FORM_3INTERNAL_SERVER_ERROR') ?>');
			}
			else if (error.apierror == 'invalid_public_key')
			{
				$('#payment-errors').html('<?php echo JText::_('PLG_AKPAYMENT_PAYMILL_FORM_INVALID_PUBLIC_KEY') ?>');
			}
			else if (error.apierror == '3ds_cancelled')
			{
				$('#payment-errors').html('<?php echo JText::_('PLG_AKPAYMENT_PAYMILL_FORM_3DS_CANCELLED') ?>');
			}
			else if (error.apierror == 'field_invalid_card_number')
			{
				$('#payment-errors').html('<?php echo JText::_('PLG_AKPAYMENT_PAYMILL_FORM_INVALID_CARD_NUMBER') ?>');
				$('#control-group-card-number').addClass('error');
			}
			else if (error.apierror == 'field_invalid_card_exp_year')
			{
				$('#payment-errors').html('<?php echo JText::_('PLG_AKPAYMENT_PAYMILL_FORM_INVALID_EXP_YEAR') ?>');
				$('#control-group-card-expiry').addClass('error');
			}
			else if (error.apierror == 'field_invalid_card_exp_month')
			{
				$('#payment-errors').html('<?php echo JText::_('PLG_AKPAYMENT_PAYMILL_FORM_INVALID_EXP_MONTH') ?>');
				$('#control-group-card-expiry').addClass('error');
			}
			else if (error.apierror == 'field_invalid_card_exp')
			{
				$('#payment-errors').html('<?php echo JText::_('PLG_AKPAYMENT_PAYMILL_FORM_INVALID_CARD_EXP') ?>');
				$('#control-group-card-expiry').addClass('error');
			}
			else if (error.apierror == 'field_invalid_card_cvc')
			{
				$('#payment-errors').html('<?php echo JText::_('PLG_AKPAYMENT_PAYMILL_FORM_INVALID_CARD_CVC') ?>');
				$('#control-group-card-cvc').addClass('error');
			}
			else if (error.apierror == 'field_invalid_card_holder')
			{
				$('#payment-errors').html('<?php echo JText::_('PLG_AKPAYMENT_PAYMILL_FORM_INVALID_CARD_HOLDER') ?>');
				$('#control-group-card-holder').addClass('error');
			}
			else
			{
				$('#payment-errors').html('<?php JText::_('PLG_AKPAYMENT_PAYMILL_FORM_UNKNOWN_ERROR') ?>');
			}

			$('#payment-errors').css('display', 'block');
			$('#payment-button').removeAttr('disabled');
			$('#paymill-warn-noreload').hide('fast');
			$('#token').val('');

			akeebasubs_paymill_clicked = false;
		}
		else
		{
			$('#payment-errors').css('display', 'none');
			var token = result.token;
			$('#token').val(token);

			$('#payment-form').submit();
		}
	})(akeeba.jQuery);
}


(function($) {
	$(document).ready(function(){
		$('#payment-button').click(function() {
			if ($('#token').val() == '')
			{
				// Prevent double click
				if (akeebasubs_paymill_clicked)
				{
					return false;
				}

				akeebasubs_paymill_clicked = true;

				// Disable the button
				$('#payment-button').attr('disabled', 'disabled');
				$('#paymill-warn-noreload').show('fast');

				// Ask PayMill to create a token
				paymill.createToken({
					number:			$('#card-number').val(),
					exp_month:		$('#card-expiry-month').val(),
					exp_year:		$('#card-expiry-year').val(),
					cvc:			$('#card-cvc').val(),
					amount_int:		$('#amount').val(),
					currency:		$('#currency').val(),
					cardholder:		$('#card-holder').val()
				}, PaymillResponseHandler);
			}

			return false;
		})
	})
})(akeeba.jQuery);

</script>