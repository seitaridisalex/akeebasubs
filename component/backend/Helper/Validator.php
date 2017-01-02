<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Helper;

use JFactory;

defined('_JEXEC') or die;

/**
 * A helper class to load the validator in the front-end Subscription view
 */
abstract class Validator
{
	protected static $selectors = array();

	public static function addSelector($selector)
	{
		self::$selectors[] = $selector;
	}

	public static function deployValidator()
	{
		if(!self::$selectors)
		{
			return;
		}

		$selectors = implode(',', self::$selectors);

		$javascript = <<<JS

akeeba.jQuery(document).ready(function(){
	akeeba.jQuery('$selectors').change(function(){
		var data = {};
		data.id = akeeba.jQuery('#akeebasubs_level_id').val();

		akeeba.jQuery.each(akeeba.jQuery('$selectors'), function(key, value){
			var element = {};
			var name = akeeba.jQuery(value).attr('name');
			element[name] = akeeba.jQuery(value).val();
			akeeba.jQuery.extend(data, element);
		});

		akeeba.jQuery.ajax('index.php?option=com_akeebasubs&view=Validate&opt=plugins&format=json',{
			type : 'POST',
			data : data,
			dataType : 'json',
			success : function(json, textStatus, jqXHR){
				var slavesubsVal = akeeba.jQuery('.slavesubsValidation');
				var i = 0;
				slavesubsVal.hide();

				akeeba.jQuery.each(json.subscription_custom_validation, function(key, value){
					var warn = akeeba.jQuery(slavesubsVal[i]);
					if(!value){
						warn.show();
						warn.closest('div.control-group').addClass('error');
					}
					else{
						warn.hide();
						warn.closest('div.control-group').removeClass('error');
					}
					i += 1;
				})
			}
		});
	})
});

JS;

		JFactory::getDocument()->addScriptDeclaration($javascript);
	}
}