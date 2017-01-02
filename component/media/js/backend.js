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

var akeebasubs_ri_offset = 0;
var akeebasubs_ri_limit = 0;
var akeebasubs_ri_total = 0;
var akeebasubs_ri_done = 0;

function akeebasubs_refresh_integrations()
{
	akeebasubs_ri_start();
}

function akeebasubs_ri_start()
{
	akeebasubs_ri_offset = 0;
	akeebasubs_ri_total = 1;
	akeebasubs_ri_done = 0;
	
	(function($) {
		$('#asriPercent').text('0');
		$('#asriSpinner').show();
		$.blockUI({message: $('#refreshMessage'), fadeOut: 2000});
		akeebasubs_ri_step();
	})(akeeba.jQuery);
}

function akeebasubs_ri_step()
{
	(function($) {
		$.ajax({
			type: 'GET',
			url: 'index.php?option=com_akeebasubs&view=SubscriptionRefresh',
			data: {
				'task'				: 'process',
				'format'			: 'raw',
				'forceoffset'		: akeebasubs_ri_offset,
				'forcelimit'		: 100,
				'refresh'			: 1,
				'_token'			: akeebasubs_token
			},
			dataType: 'json',
			success: function(msg, textStatus, xhr) {
				akeebasubs_ri_total = msg.total;
				akeebasubs_ri_done += msg.processed;
				akeebasubs_ri_offset += msg.processed;
				
				var percentage = 0;
				if(akeebasubs_ri_total > 0) {
					percentage = 100 * akeebasubs_ri_done / akeebasubs_ri_total;
				}
				$('#asriPercent').text(parseInt(percentage + ' '));
				
				if(akeebasubs_ri_done == akeebasubs_ri_total) {
					$('#asriSpinner').hide();
					window.location = 'index.php?option=com_akeebasubs&view=Subscriptions';
				}
				
				if( (msg.processed == 0) || (akeebasubs_ri_done == akeebasubs_ri_total) ) {
					$.unblockUI();
				} else {
					akeebasubs_ri_step();
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				$.unblockUI();
			}
		});		
		
	})(akeeba.jQuery);
}

// Courtesy of php.js
function empty (mixed_var) {
    var key;

    if (mixed_var === "" || mixed_var === 0 || mixed_var === "0" || mixed_var === null || mixed_var === false || typeof mixed_var === 'undefined') {
        return true;
    }

    if (typeof mixed_var == 'object') {
        for (key in mixed_var) {
            return false;
        }
        return true;
    }

    return false;
}

/**
 * Adds a function to the validation fetch queue
 */
function addToValidationFetchQueue(myfunction)
{
	// Really does nothing, it's here to avoid JS errors
}

/**
 * Adds a function to the validation queue
 */
function addToValidationQueue(myfunction)
{
	// Really does nothing, it's here to avoid JS errors
}

/**
 * Adds a function to the sub validation fetch queue
 */
function addToSubValidationFetchQueue(myfunction)
{
	// Really does nothing, it's here to avoid JS errors
}

/**
 * Adds a function to the sub validation queue
 */
function addToSubValidationQueue(myfunction)
{
	// Really does nothing, it's here to avoid JS errors
}