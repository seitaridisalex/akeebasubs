/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */
/**
 * Setup (required for Joomla! 3)
 */
if(typeof(akeeba) == 'undefined') {
	var akeeba = {};
}
if(typeof(akeeba.jQuery) == 'undefined') {
	akeeba.jQuery = window.jQuery.noConflict();
}

(function($) {
	$(window).load(function(){
		if($('#paymentForm')) {
			$('#paymentForm').submit();
		}
	});
})(akeeba.jQuery);
