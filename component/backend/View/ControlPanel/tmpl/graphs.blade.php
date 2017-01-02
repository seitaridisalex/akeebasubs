<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use \Akeeba\Subscriptions\Admin\Helper\Select;

$graphDayFrom = gmdate('Y-m-d', time() - 30 * 24 * 3600);
$graphDayTo = gmdate('Y-m-d', time());


$js = <<< JS
akeebasubs_cpanel_graph_from = "$graphDayFrom";

(function($) {
    $(document).ready(function(){
        akeebasubs_cpanel_graphs_load();

        $('#akeebasubs_graph_reload').click(function(e) {
            akeebasubs_cpanel_graphs_load();
        })
    });
})(akeeba.jQuery);

JS;
?>

@section('graphs')

    @css('media://com_akeebasubs/css/jquery.jqplot.min.css')
    @js('media://com_akeebasubs/js/excanvas.min.js')
    @js('media://com_akeebasubs/js/jquery.jqplot.min.js')
    @js('media://com_akeebasubs/js/jqplot.highlighter.min.js')
    @js('media://com_akeebasubs/js/jqplot.dateAxisRenderer.min.js')
    @js('media://com_akeebasubs/js/jqplot.barRenderer.min.js')
    @js('media://com_akeebasubs/js/jqplot.pieRenderer.min.js')
    @js('media://com_akeebasubs/js/jqplot.hermite.js')
    @js('media://com_akeebasubs/js/cpanelgraphs.js')

    <div class="well well-small">
        <div class="form form-inline">
            @jhtml('calendar', $graphDayFrom, 'akeebasubs_graph_datepicker', 'akeebasubs_graph_datepicker')

            @jhtml('calendar', $graphDayTo, 'akeebasubs_graph_todatepicker', 'akeebasubs_graph_todatepicker')
            {{ Select::subscriptionlevels(0, 'akeebasubs_graph_level_id', array('class'=>'input-small')) }}
            <button class="btn btn-primary btn-mini" id="akeebasubs_graph_reload" onclick="return false">
                <span class="icon icon-white icon-reload"></span>
                @lang('COM_AKEEBASUBS_DASHBOARD_RELOADGRAPHS')
            </button>
        </div>
    </div>

    <h3>
        @lang('COM_AKEEBASUBS_DASHBOARD_SALES')
    </h3>
    <div id="aksaleschart">
        <img src="@media('media://com_akeebasubs/images/throbber.gif')" id="akthrobber" />
        <p id="aksaleschart-nodata" style="display:none">
            @lang('COM_AKEEBASUBS_DASHBOARD_STATS_NODATA')
        </p>
    </div>

    <div style="clear: both;">&nbsp;</div>

    <h3>
        @lang('COM_AKEEBASUBS_DASHBOARD_LEVELSTATS')
    </h3>

    <div id="aklevelschart">
        <img src="@media('media://com_akeebasubs/images/throbber.gif')" id="akthrobber2" />
        <p id="aklevelschart-nodata" style="display:none">
            @lang('COM_AKEEBASUBS_DASHBOARD_STATS_NODATA')
        </p>
    </div>

    @inlineJs($js)

@stop