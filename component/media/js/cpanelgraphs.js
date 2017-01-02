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

var akeebasubs_cpanel_graph_from = "";
var akeebasubs_cpanel_graph_to = "";
var akeebasubs_cpanel_graph_level = "";

var akeebasubs_cpanel_graph_salesPoints = [];
var akeebasubs_cpanel_graph_subsPoints = [];
var akeebasubs_cpanel_graph_levelsPoints = [];

var akeebasubs_cpanel_graph_plot1 = null;
var akeebasubs_cpanel_graph_plot2 = null;

function akeebasubs_cpanel_graphs_load()
{
	// Get the From date
	akeebasubs_cpanel_graph_from = document.getElementById('akeebasubs_graph_datepicker').value;
	akeebasubs_cpanel_graph_to = document.getElementById('akeebasubs_graph_todatepicker').value;
	akeebasubs_cpanel_graph_level = document.getElementById('akeebasubs_graph_level_id').value * 1;
	
	// Calculate the To date
	if (akeebasubs_cpanel_graph_to == '')
	{
		var thatDay = new Date(akeebasubs_cpanel_graph_from);
		thatDay = new Date(thatDay.getTime() + 30*86400000);
		akeebasubs_cpanel_graph_to = thatDay.getUTCFullYear()+'-'+(thatDay.getUTCMonth()+1)+'-'+thatDay.getUTCDate();
	}

	// Clear the data arrays
	akeebasubs_cpanel_graph_salesPoints = [];
	akeebasubs_cpanel_graph_subsPoints = [];
	akeebasubs_cpanel_graph_levelsPoints = [];

	// Remove the charts and show the spinners
	(function($) {
		$('#aklevelschart').empty();
		$('#aklevelschart').hide();
		akeebasubs_cpanel_graph_plot2 = null;
		$('#aksaleschart').empty();
		$('#aksaleschart').hide();
		akeebasubs_cpanel_graph_plot1 = null;
		
		$('#akthrobber').show();
		$('#akthrobber2').show();
	})(akeeba.jQuery);
	
	akeebasubs_load_sales();
}

function akeebasubs_load_sales()
{
	(function($) {
		var url = "index.php?option=com_akeebasubs&view=SubscriptionStatistics&since="+akeebasubs_cpanel_graph_from+"&until="+akeebasubs_cpanel_graph_to+"&groupbydate=1&paystate=C&nozero=1&savestate=0&format=json";
		if (akeebasubs_cpanel_graph_level > 0)
		{
			url += '&level=' + akeebasubs_cpanel_graph_level;
		}
		$.getJSON(url, function(data){
			$.each(data, function(index, item){
				akeebasubs_cpanel_graph_salesPoints.push([item.date, parseInt(item.net * 100) * 1 / 100]);
				akeebasubs_cpanel_graph_subsPoints.push([item.date, item.subs * 1]);
			});
			$('#akthrobber').hide();
			$('#aksaleschart').show();
			if(akeebasubs_cpanel_graph_salesPoints.length == 0) {
				$('#aksaleschart-nodata').show();
				return;
			}
			akeebasubs_render_sales();
			akeebasubs_load_levels();
		});
	})(akeeba.jQuery);
}

function akeebasubs_load_levels()
{
	(function($) {
		var url = "index.php?option=com_akeebasubs&view=SubscriptionStatistics&since="+akeebasubs_cpanel_graph_from+"&until="+akeebasubs_cpanel_graph_to+"&groupbylevel=1&paystate=C&nozero=1&savestate=0&format=json";
		if (akeebasubs_cpanel_graph_level > 0)
		{
			url += '&level=' + akeebasubs_cpanel_graph_level;
		}
		$.getJSON(url, function(data){
			$.each(data, function(index, item){
				akeebasubs_cpanel_graph_levelsPoints.push([item.title, parseInt(item.net * 100) * 1 / 100]);
			});
			$('#akthrobber2').hide();
			$('#aklevelschart').show();
			if(akeebasubs_cpanel_graph_levelsPoints.length == 0) {
				$('#aklevelschart-nodata').show();
				return;
			}
			akeebasubs_render_levels();
		});
	})(akeeba.jQuery);
}

function akeebasubs_render_sales()
{
	(function($) {
		$.jqplot.config.enablePlugins = true;
		akeebasubs_cpanel_graph_plot1 = $.jqplot('aksaleschart', [akeebasubs_cpanel_graph_subsPoints, akeebasubs_cpanel_graph_salesPoints], {
			show: true,
			axes:{
				xaxis:{renderer:$.jqplot.DateAxisRenderer,tickInterval:'1 week'},
				yaxis:{min: 0,tickOptions:{formatString:'%.2f'}},
				y2axis:{min: 0,tickOptions:{formatString:'%u'}}
			},
			series:[ 
				{
					yaxis: 'y2axis',
					lineWidth:1,
					renderer:$.jqplot.BarRenderer,
					rendererOptions:{barPadding: 0, barMargin: 0, barWidth: 5, shadowDepth: 0, varyBarColor: 0},
					markerOptions: {
						style:'none'
					},
					color: '#aae0aa'
				},
				{
					lineWidth:3,
					markerOptions:{
						style:'filledCircle',
						size:8
					},
					renderer: $.jqplot.hermiteSplineRenderer,
					rendererOptions:{steps: 60, tension: 0.6}
				}
			],
			highlighter: {
				show: true,
				sizeAdjust: 7.5
			},
			axesDefaults:{useSeriesColor: true}
		}).replot();
	})(akeeba.jQuery);
}

function akeebasubs_render_levels()
{
	(function($) {
		$.jqplot.config.enablePlugins = true;
		akeebasubs_cpanel_graph_plot2 = $.jqplot('aklevelschart', [akeebasubs_cpanel_graph_levelsPoints], {
			show: true,
			highlighter: {
				show: true,
				formatString: '%s: %0.2f',
				tooltipLocation: 'sw',
				useAxesFormatters: false
			},
			seriesDefaults: {
				renderer: jQuery.jqplot.PieRenderer, 
				rendererOptions: {
					showDataLabels: true,
					dataLabels: 'value'
				},
				markerOptions: {
					style:'none'
				}
			},
			legend: {show:true, location: 'e'}
		}).replot();
	})(akeeba.jQuery);
}