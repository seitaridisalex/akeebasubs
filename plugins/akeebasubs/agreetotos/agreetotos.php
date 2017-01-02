<?php
/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

/**
 * This plugin renders an Agree to Terms of Service field
 */
class plgAkeebasubsAgreetotos extends JPlugin
{
	function onSubscriptionFormPrepaymentRender($userparams, $cache)
	{
		JHtml::_('bootstrap.popover');

		// Load the language
		$lang = JFactory::getLanguage();
		$lang->load('plg_akeebasubs_agreetotos', JPATH_ADMINISTRATOR, 'en-GB', true);
		$lang->load('plg_akeebasubs_agreetotos', JPATH_ADMINISTRATOR, null, true);

		$cachechoice = $this->params->get('cachechoice', 0);

		// Init the fields array which will be returned
		$fields = array();

		// ----- AGREE TO TOS FIELD -----
		// Get the current setting (or 0 if none)
		if (array_key_exists('agreetotos', $cache['custom']))
		{
			if ($cachechoice)
			{
				$current = $cache['custom']['agreetotos'];
			}
			else
			{
				$current = '';
			}
		}
		else
		{
			if (!is_object($userparams->params))
			{
				$current = '';
			}
			else
			{
				if ($cachechoice)
				{
					$current = property_exists($userparams->params, 'agreetotos') ? $userparams->params->agreetotos : 0;
				}
				else
				{
					$current = '';
				}
			}
		}

		// Setup the field
		$url = $this->params->get('tosurl', '');

		if (empty($url))
		{
			$urlField = JText::_('PLG_AKEEBASUBS_AGREETOTOS_TOS_LABEL');
		}
		else
		{
			$text = JText::_('PLG_AKEEBASUBS_AGREETOTOS_TOS_LABEL');
			$urlField = '<a href="javascript:return false;" onclick="window.open(\'' . $url . '\',\'toswindow\',\'width=640,height=480,resizable=yes,scrollbars=yes,toolbar=no,location=no,directories=no,status=no,menubar=no\');">' . $text . '</a>';
		}

		// Setup the field's HTML
		$checked = $current ? 'checked="checked"' : '';
		$labelText = JText::sprintf('PLG_AKEEBASUBS_AGREETOTOS_AGREE_LABEL', $urlField);
		$extraText = JText::sprintf('PLG_AKEEBASUBS_AGREETOTOS_TOS_INFO_LABEL', JText::_('PLG_AKEEBASUBS_AGREETOTOS_TOS_LABEL'));
		$labelText2 = strip_tags($labelText);
		$html = <<<HTML
<label class="checkbox">
	<input type="checkbox" name="custom[agreetotos]" id="agreetotos" $checked />
	<span class="glyphicon glyphicon-info-sign hasPopover" title="$labelText2" data-content="$extraText"></span>
	$labelText
</label>
HTML;

		$field = array(
			'id'           => 'agreetotos',
			'label'        => '* ',
			'elementHTML'  => $html,
			'isValid'      => $current != 0
		);
		// Add the field to the return output
		$fields[] = $field;

		// ----- ADD THE JAVASCRIPT -----
		$javascript = <<<JS

(function($) {
	$(document).ready(function(){
		// Tell Akeeba Subscriptions how to fetch the extra field data
		addToValidationFetchQueue(plg_akeebasubs_agreetotos_fetch);
		// Tell Akeeba Subscriptions how to validate the extra field data
		addToValidationQueue(plg_akeebasubs_agreetotos_validate);
		
		// Immediate validation of the field
		if (akeebasubs_apply_validation)
		{
			$('#agreetotos').change(function(e){
				if($('#agreetotos').is(':checked')) {
					$('#agreetotos').parents('div.form-group').removeClass('has-error');
				} else {
					$('#agreetotos').parents('div.form-group').addClass('has-error');
				}
			});
		}
	});
})(akeeba.jQuery);

function plg_akeebasubs_agreetotos_fetch()
{
	var result = {};

	(function($) {
		result.agreetotos = $('#agreetotos').is(':checked') ? 1 : 0;
	})(akeeba.jQuery);

	return result;
}

function plg_akeebasubs_agreetotos_validate(response)
{
    var thisIsValid = true;

	(function($) {
		$('#agreetotos').parents('div.form-group').removeClass('has-error');

		if (!akeebasubs_apply_validation)
		{
			thisIsValid = true;
			return;
		}

		if(response.custom_validation.agreetotos || $('#agreetotos').is(':checked')) {
			thisIsValid = true;
		} else {
			$('#agreetotos').parents('div.form-group').addClass('has-error');
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

		if (!array_key_exists('agreetotos', $custom))
		{
			$custom['agreetotos'] = 0;
		}

		$custom['agreetotos'] = ($custom['agreetotos'] === 'on') ? 1 : 0;

		$response['custom_validation']['agreetotos'] = ($custom['agreetotos'] != 0) ? 1 : 0;
		$response['valid'] = $response['custom_validation']['agreetotos'] ? 1 : 0;

		return $response;
	}
}