<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

JHtml::_('behavior.tooltip');
JHtml::_('behavior.modal');

$script = <<< JS
(function($) {
	$(document).ready(function(){
		akeebasubs_relations_mode_onChange();
	});
})(jQuery);

	function akeebasubs_relations_mode_onChange()
	{
		(function($) {
			var mode = $('#mode').val();
			$('#akeebasubs-relations-fixed').css('display','none');
			$('#akeebasubs-relations-flexi').css('display','none');
			if(mode == 'fixed')
			{
				$('#akeebasubs-relations-fixed').css('display','block');
			}
			else if(mode == 'flexi')
			{
				$('#akeebasubs-relations-flexi').css('display','block');
			}
		})(jQuery);
	}
JS;


$this->addJavascriptInline($script);

/** @var $this \FOF30\View\DataView\Form */

echo $this->getRenderedForm();