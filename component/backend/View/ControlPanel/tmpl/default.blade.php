<?php
/**
 * package   AkeebaSubs
 * copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
?>

{{-- Include external sections. Do note how you can include sub-templates in one order and compile them in a completely
different order using @yield later on! --}}
@include('admin:com_akeebasubs/ControlPanel/phpversion')
@include('admin:com_akeebasubs/ControlPanel/graphs')
@include('admin:com_akeebasubs/ControlPanel/stats')
{{-- Note: Passing $this->wizardstep is not necessary, I just use it to demonstrate how you can pass forced parameters
to Blade sub-templates --}}
@include('admin:com_akeebasubs/ControlPanel/wizard', ['wizardstep' => $this->wizardstep])
{{-- Note: I don't pass $this->hasGeoIPPlugin and $this->geoIPPluginNeedsUpdate. This demonstrates how Blade
subtemplates can view their parent's variables automatically. --}}
@include('admin:com_akeebasubs/ControlPanel/geoip')

{{-- Compile the output. Do note that I don't need to wrap it in a section. Content outside a section is yielded
immediately. Alternatively I could wrap this in a @section/@show block or even @section/@stop and use @yield to
render it. --}}
@yield('phpVersionWarning', '')

<div id="updateNotice"></div>

@if ($this->wizardstep < 6)
    @yield('wizard', '')
@endif

@yield('geoip', '')

<div class="row-fluid">
    <div id="cpanel" class="span6">
        @yield('graphs', '')
    </div>
    <div id="cpanel" class="span6">
        @yield('stats', '')

        <div style="clear: both;">&nbsp;</div>

        @modules('akeebasubscriptionsstats')

        <div style="clear: both;">&nbsp;</div>

        @include('admin:com_akeebasubs/ControlPanel/quickicons')
        @yield('quickicons', '')
    </div>
</div>

<div class="row-fluid footer">
    <div class="span12">
        @include('admin:com_akeebasubs/ControlPanel/footer')
        @yield('footer')
    </div>
</div>

<script type="text/javascript">
    (function($) {
        $(document).ready(function(){
            $.ajax('index.php?option=com_akeebasubs&view=ControlPanel&task=updateinfo&tmpl=component', {
                success: function(msg, textStatus, jqXHR)
                {
                    // Get rid of junk before and after data
                    var match = msg.match(/###([\s\S]*?)###/);
                    data = match[1];

                    if (data.length)
                    {
                        $('#updateNotice').html(data);
                    }
                }
            })
        });
    })(akeeba.jQuery);
</script>