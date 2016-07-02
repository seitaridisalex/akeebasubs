<?php defined('_JEXEC') or die(); ?>

<h3><?php echo JText::_('PLG_AKPAYMENT_PAYMILLDSS3_FORM_HEADER') ?></h3>

<div id="payment-errors" class="alert alert-error" style="display: none;"></div>

<form id="payment-form"
      style="display: none;" method="post"
      action="<?php echo $data->url ?>"
      class="form form-horizontal">
	<input type="hidden" name="currency" id="paymilldss3_currency" value="<?php echo $data->currency ?>"/>
	<input type="hidden" name="amount" id="paymilldss3_amount" value="<?php echo $data->amount ?>"/>
	<input type="hidden" name="description" id="paymilldss3_description" value="<?php echo $data->description ?>"/>
	<input type="hidden" name="token" id="paymilldss3_token"/>

	<div id="paymilldss3-credit-card-fields">
	</div>

	<div class="control-group">
		<label for="pay" class="control-label" style="width:190px; margin-right:20px;">
		</label>

		<div class="controls">
			<a href="#payment-form" id="payment-button" class="btn">
				<?php echo JText::_('PLG_AKPAYMENT_PAYMILLDSS3_FORM_PAYBUTTON') ?>
			</a>
		</div>
	</div>
	<div class="alert alert-warning" id="paymill-warn-noreload" style="display: none;">
		<?php echo JText::_('PLG_AKPAYMENT_PAYMILLDSS3_WARN_NORELOAD') ?>
	</div>
</form>

<script type="text/javascript">
	var akeebasubs_paymill_clicked = false;

	(function ($)
	{
		$(document).ready(function(){
			AkeebaSubs.PayMillDss3.initialize();
		})
	})(akeeba.jQuery);

</script>