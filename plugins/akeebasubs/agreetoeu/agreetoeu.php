<?php
/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

/**
 * A plugin which creates two extra fields for conformance to EU directives regarding consumer protection and VAT.
 */
class plgAkeebasubsAgreetoeu extends JPlugin
{
	function onSubscriptionFormPrepaymentRender($userparams, $cache)
	{
		// Load the language
		$lang = JFactory::getLanguage();
		$lang->load('plg_akeebasubs_agreetoeu', JPATH_ADMINISTRATOR, 'en-GB', true);
		$lang->load('plg_akeebasubs_agreetoeu', JPATH_ADMINISTRATOR, null, true);

		// Init the fields array which will be returned
		$fields = array();

		// ----- CONFIRM BEING INFORMED FIELD -----
		// Setup the combobox parameters
		$labelText = JText::_('PLG_AKEEBASUBS_AGREETOEU_CONFIRM_INFORMED_LABEL');
		$extraText = JText::_('PLG_AKEEBASUBS_AGREETOEU_CONFIRM_INFORMED_DESC');
		$html = <<<HTML
<label class="checkbox">
	<input type="checkbox" name="custom[confirm_informed]" id="confirm_informed" />
	<span class="glyphicon glyphicon-info-sign hasPopover" title="$labelText" data-content="$extraText"></span>
	$labelText
</label>
HTML;

		// Setup the field
		$field = array(
			'id'           => 'confirm_informed',
			'label'        => '* ',
			'elementHTML'  => $html,
			'isValid'      => false
		);
		// Add the field to the return output
		$fields[] = $field;

		// ----- CONFIRM POSTAL ADDRESS FIELD -----
		// Setup the combobox parameters
		$labelText = JText::_('PLG_AKEEBASUBS_AGREETOEU_CONFIRM_POSTAL_LABEL');
		$extraText = JText::_('PLG_AKEEBASUBS_AGREETOEU_CONFIRM_POSTAL_DESC');
		$html = <<<HTML
<label class="checkbox">
	<input type="checkbox" name="custom[confirm_postal]" id="confirm_postal" />
	<span class="glyphicon glyphicon-info-sign hasPopover" title="$labelText" data-content="$extraText"></span>
	$labelText
</label>
HTML;

		// Setup the field
		$field = array(
			'id'           => 'confirm_postal',
			'label'        => '* ',
			'elementHTML'  => $html,
			'isValid'      => false
		);
		// Add the field to the return output
		$fields[] = $field;

		// ----- CONFIRM RIGHT TO WITHDRAWAL FIELD -----
		// Setup the combobox parameters
		$labelText = JText::_('PLG_AKEEBASUBS_AGREETOEU_CONFIRM_WITHDRAWAL_LABEL');
		$extraText = JText::_('PLG_AKEEBASUBS_AGREETOEU_CONFIRM_WITHDRAWAL_DESC');
		$html = <<<HTML
<label class="checkbox">
	<input type="checkbox" name="custom[confirm_withdrawal]" id="confirm_withdrawal" />
	<span class="glyphicon glyphicon-info-sign hasPopover" title="$labelText" data-content="$extraText"></span>
	$labelText
</label>
HTML;

		// Setup the field
		$field = array(
			'id'           => 'confirm_withdrawal',
			'label'        => '* ',
			'elementHTML'  => $html,
			'isValid'      => false
		);

		// Add the field to the return output
		$fields[] = $field;

		// ----- ADD THE JAVASCRIPT -----
		$javascript = <<<JS

(function($) {
	$(document).ready(function(){
		// Tell Akeeba Subscriptions how to fetch the extra field data
		addToValidationFetchQueue(plg_akeebasubs_agreetoeu_fetch);
		// Tell Akeeba Subscriptions how to validate the extra field data
		addToValidationQueue(plg_akeebasubs_agreetoeu_validate);
		
		// Immediate validation of the field
		if (akeebasubs_apply_validation)
		{
			$('#confirm_informed').change(function(e){
				if($('#confirm_informed').is(':checked')) {
					$('#confirm_informed').parents('div.form-group').removeClass('has-error');
				} else {
					$('#confirm_informed').parents('div.form-group').addClass('has-error');
				}
			});

			$('#confirm_postal').change(function(e){
				if($('#confirm_postal').is(':checked')) {
					$('#confirm_postal').parents('div.form-group').removeClass('has-error');
				} else {
					$('#confirm_postal').parents('div.form-group').addClass('has-error');
				}
			});

			$('#confirm_withdrawal').change(function(e){
				if($('#confirm_withdrawal').is(':checked')) {
					$('#confirm_withdrawal').parents('div.form-group').removeClass('has-error');
				} else {
					$('#confirm_withdrawal').parents('div.form-group').addClass('has-error');
				}
			});
		}
	});
})(akeeba.jQuery);

function plg_akeebasubs_agreetoeu_fetch()
{
	var result = {};

	(function($) {
		result.confirm_informed = $('#confirm_informed').is(':checked') ? 1 : 0;
		result.confirm_postal = $('#confirm_postal').is(':checked') ? 1 : 0;
		result.confirm_withdrawal = $('#confirm_withdrawal').is(':checked') ? 1 : 0;
	})(akeeba.jQuery);

	return result;
}

function plg_akeebasubs_agreetoeu_validate(response)
{
    var thisIsValid = true;

	(function($) {
		$('#confirm_informed').parents('div.form-group').removeClass('has-error');
		$('#confirm_postal').parents('div.form-group').removeClass('has-error');
		$('#confirm_withdrawal').parents('div.form-group').removeClass('has-error');

		if (!akeebasubs_apply_validation)
		{
			thisIsValid = true;
			return;
		}
		
		if (!response.custom_validation.confirm_informed) {
			$('#confirm_informed').parents('div.form-group').addClass('has-error');
			thisIsValid = false;
		}

		if (!response.custom_validation.confirm_postal) {
			$('#confirm_postal').parents('div.form-group').addClass('has-error');
			thisIsValid = false;
		}

		if (!response.custom_validation.confirm_withdrawal) {
			$('#confirm_withdrawal').parents('div.form-group').addClass('has-error');
			thisIsValid = false;
		}
	})(akeeba.jQuery);

	return thisIsValid;
}

JS;
		$document = JFactory::getDocument();
		$document->addScriptDeclaration($javascript);

		// ----- RETURN THE FIELDS -----
		return $fields;
	}

	function onValidate($data)
	{
		$response = array(
			'isValid'           => true,
			'custom_validation' => array()
		);

		$custom = $data->custom;

		if (!array_key_exists('confirm_informed', $custom))
		{
			$custom['confirm_informed'] = 0;
		}

		if (!array_key_exists('confirm_postal', $custom))
		{
			$custom['confirm_postal'] = 0;
		}

		if (!array_key_exists('confirm_withdrawal', $custom))
		{
			$custom['confirm_withdrawal'] = 0;
		}

		$custom['confirm_informed'] = $this->isTruthism($custom['confirm_informed']) ? 1 : 0;
		$custom['confirm_postal'] = $this->isTruthism($custom['confirm_postal']) ? 1 : 0;
		$custom['confirm_withdrawal'] = $this->isTruthism($custom['confirm_withdrawal']) ? 1 : 0;

		$response['custom_validation']['confirm_informed'] = $custom['confirm_informed'];
		$response['custom_validation']['confirm_postal'] = $custom['confirm_postal'];
		$response['custom_validation']['confirm_withdrawal'] = $custom['confirm_withdrawal'];

		// Huh?
		$response['valid'] = $response['custom_validation']['confirm_informed'] && $response['custom_validation']['confirm_postal'] && $response['custom_validation']['confirm_withdrawal'];

		return $response;
	}

	private function isTruthism($value)
	{
		if ($value === 1) return true;

		if (in_array($value, ['on', 'checked', 'true', '1', 'yes', 1, true], true))
		{
			return true;
		}

		return false;
	}

}