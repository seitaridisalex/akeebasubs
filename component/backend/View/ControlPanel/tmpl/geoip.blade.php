<?php
/**
 * package   AkeebaSubs
 * copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
?>

@section('geoip')
    @if (!$this->hasGeoIPPlugin)
        <div class="well">
            <h3>
                @lang('COM_AKEEBASUBS_GEOIP_LBL_GEOIPPLUGINSTATUS')
            </h3>

            <p>
                @lang('COM_AKEEBASUBS_GEOIP_LBL_GEOIPPLUGINMISSING')
            </p>

            <a class="btn btn-primary" href="https://www.akeebabackup.com/download/akgeoip.html" target="_blank">
                <span class="icon icon-white icon-download-alt"></span>
                @lang('COM_AKEEBASUBS_GEOIP_LBL_DOWNLOADGEOIPPLUGIN')
            </a>
        </div>
    @elseif ($this->geoIPPluginNeedsUpdate)
        <div class="well well-small">
            <h3>
                @lang('COM_AKEEBASUBS_GEOIP_LBL_GEOIPPLUGINEXISTS')
            </h3>

            <p>
                @lang('COM_AKEEBASUBS_GEOIP_LBL_GEOIPPLUGINCANUPDATE')
            </p>

            <a class="btn btn-small"
               href="index.php?option=com_akeebasubs&view=ControlPanel&task=updategeoip&@token()=1">
                <span class="icon icon-refresh"></span>
                @lang('COM_AKEEBASUBS_GEOIP_LBL_UPDATEGEOIPDATABASE')
            </a>
        </div>
    @endif
@stop