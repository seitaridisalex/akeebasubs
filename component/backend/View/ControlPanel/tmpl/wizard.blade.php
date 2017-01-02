<?php
/**
 * package   AkeebaSubs
 * copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
?>

@section('wizard')

<div class="well">
    @if ($wizardstep == 1)

        <h2>
            @lang('COM_AKEEBASUBS_CPANEL_WIZARD_STEP1_TITLE')
        </h2>
        <p>
            @lang('COM_AKEEBASUBS_CPANEL_WIZARD_STEP1_TEXT')
        </p>

        <p>
            <a href="index.php?option=com_config&view=component&component=com_akeebasubs&path=&return={{ base64_encode(\JUri::getInstance()->toString()) }}"
               class="btn btn-primary">
                @lang('COM_AKEEBASUBS_CPANEL_WIZARD_STEP1_BUTTON')
            </a>
        </p>

    @elseif ($wizardstep == 2)

        <h2>
            @lang('COM_AKEEBASUBS_CPANEL_WIZARD_STEP2_TITLE')
        </h2>
        <p>
            @lang('COM_AKEEBASUBS_CPANEL_WIZARD_STEP2_TEXT')
        </p>
        <p>
            @lang('COM_AKEEBASUBS_CPANEL_WIZARD_STEP2_TEXTA')
        </p>
        <p>
            <a href="index.php?option=com_users&view=groups" class="btn btn-primary">
                @lang('COM_AKEEBASUBS_CPANEL_WIZARD_STEP2_BUTTONA')
            </a>
        </p>
        <p>
            @lang('COM_AKEEBASUBS_CPANEL_WIZARD_STEP2_TEXTb')
        </p>
        <p>
            <a href="index.php?option=com_users&view=levels" class="btn btn-primary">
                @lang('COM_AKEEBASUBS_CPANEL_WIZARD_STEP2_BUTTONB')
            </a>
        </p>

    @elseif ($wizardstep == 3)

        <h2>
            @lang('COM_AKEEBASUBS_CPANEL_WIZARD_STEP3_TITLE')
        </h2>
        <p>
            @lang('COM_AKEEBASUBS_CPANEL_WIZARD_STEP3_TEXT')
        </p>
        <p>
            <a href="index.php?option=com_plugins&view=plugins&filter_search=&filter_folder=akpayment" class="btn btn-primary">
                @lang('COM_AKEEBASUBS_CPANEL_WIZARD_STEP3_BUTTON')
            </a>
        </p>

    @elseif ($wizardstep == 4)

        <h2>
            @lang('COM_AKEEBASUBS_CPANEL_WIZARD_STEP4_TITLE')
        </h2>
        <p>
            @lang('COM_AKEEBASUBS_CPANEL_WIZARD_STEP4_TEXT')
        </p>
        <p>
            <a href="index.php?option=com_akeebasubs&view=Levels" class="btn btn-primary">
                @lang('COM_AKEEBASUBS_CPANEL_WIZARD_STEP4_BUTTON')
            </a>
        </p>

    @elseif ($wizardstep == 5)

        <h2>
            @lang('COM_AKEEBASUBS_CPANEL_WIZARD_STEP5_TITLE')
        </h2>
        <p>
            @lang('COM_AKEEBASUBS_CPANEL_WIZARD_STEP5_TEXT')
        </p>
        <p>
            <a href="https://www.akeebabackup.com/documentation/akeeba-subscriptions.html" class="btn btn-primary">
                @lang('COM_AKEEBASUBS_CPANEL_WIZARD_STEP5_BUTTON')
            </a>
        </p>

    @else
        {{-- No content; wizard is complete --}}
    @endif

    <div class="form-actions">
        <a href="index.php?option=com_akeebasubs&view=ControlPanel&task=wizardstep&wizardstep=<?php echo ++$wizardstep ?>" class="btn btn-success">
            <span class="icon icon-white icon-check"></span>
            @lang('COM_AKEEBASUBS_CPANEL_WIZARD_COMMON_COMPLETE')
        </a>
        <a href="index.php?option=com_akeebasubs&view=ControlPanel&task=wizardstep&wizardstep=6" class="btn btn-warning">
            <span class="icon icon-remove"></span>
            @lang('COM_AKEEBASUBS_CPANEL_WIZARD_COMMON_HIDE')
        </a>
    </div>
</div>
@stop