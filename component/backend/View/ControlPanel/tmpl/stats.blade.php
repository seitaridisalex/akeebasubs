<?php
/**
 * package   AkeebaSubs
 * copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var \Akeeba\Subscriptions\Admin\View\ControlPanel\Html $this */
/** @var \Akeeba\Subscriptions\Admin\Model\SubscriptionsForStats $subs */
$subs = $this->container->factory->model('SubscriptionsForStats')->tmpInstance();
?>

@section('stats')
    <h3>
        @lang('COM_AKEEBASUBS_DASHBOARD_STATS')
    </h3>

    @repeatable('renderMoney', $moneyValue)
    <?php
    $currencyPosition = $this->container->params->get('currencypos','before');
    $currencySymbol = $this->container->params->get('currencysymbol','€');
    ?>
    @if ($currencyPosition == 'before')
        {{{ $currencySymbol }}}
    @endif

    <?php echo sprintf('%.02f', $moneyValue); ?>

    @if ($currencyPosition == 'after')
        {{{ $currencySymbol }}}
    @endif
    @endRepeatable

    <table width="100%" class="table table-striped">
        <tbody>
        <tr class="row0">
            <td width="50%">
                @lang('COM_AKEEBASUBS_DASHBOARD_STATS_LASTYEAR')
            </td>
            <td align="right" width="25%">
                {{{ $subs->clearState()->since((gmdate('Y')-1).'-01-01 00:00:00')
                             ->until((gmdate('Y')-1).'-12-31 23:59:59')
                             ->nozero(1)
                             ->nojoins(1)
                             ->paystate('C')
                             ->count() }}}
            </td>
            <td align="right" width="25%">
                <?php $money = $subs->clearState()
                                    ->since((gmdate('Y')-1).'-01-01')
                                    ->until((gmdate('Y')-1).'-12-31 23:59:59')
                                    ->moneysum(1)
                                    ->nozero(1)
                                    ->nojoins(1)
                                    ->paystate('C')
                                    ->count() ?>
                @yieldRepeatable('renderMoney', $money)
            </td>
        </tr>
        <tr class="row1">
            <td>@lang('COM_AKEEBASUBS_DASHBOARD_STATS_THISYEAR')</td>
            <td align="right">
                {{{ $subs->clearState()
                                   ->since(gmdate('Y').'-01-01')
                                   ->until(gmdate('Y').'-12-31 23:59:59')
                                   ->nozero(1)
                                   ->nojoins(1)
                                   ->paystate('C')
                                   ->count() }}}
            </td>
            <td align="right">

                <?php $money = $subs->clearState()
                                ->since(gmdate('Y').'-01-01')
								->until(gmdate('Y').'-12-31 23:59:59')
								->moneysum(1)
								->nozero(1)
								->nojoins(1)
								->paystate('C')
								->count() ?>
                @yieldRepeatable('renderMoney', $money)
            </td>
        </tr>
        <tr class="row0">
            <td>@lang('COM_AKEEBASUBS_DASHBOARD_STATS_LASTMONTH')</td>
            <td align="right">
                <?php
                $y = gmdate('Y');
                $m = gmdate('m');
                if ($m == 1)
                {
                    $m = 12;
                    $y -= 1;
                }
                else
                {
                    $m -= 1;
                }
                switch ($m)
                {
                    case 1:
                    case 3:
                    case 5:
                    case 7:
                    case 8:
                    case 10:
                    case 12:
                        $lmday = 31;
                        break;
                    case 4:
                    case 6:
                    case 9:
                    case 11:
                        $lmday = 30;
                        break;
                    case 2:
                        if (!($y % 4) && ($y % 400))
                        {
                            $lmday = 29;
                        }
                        else
                        {
                            $lmday = 28;
                        }
                }
                if ($y < 2011) $y = 2011;
                if ($m < 1) $m = 1;
                if ($lmday < 1) $lmday = 1;
                ?>
                {{{ $subs->clearState()
                                   ->since($y.'-'.$m.'-01')
                                   ->until($y.'-'.$m.'-'.$lmday.' 23:59:59')
                                   ->nozero(1)
                                   ->nojoins(1)
                                   ->paystate('C')
                                   ->count() }}}
            </td>
            <td align="right">
                <?php $money = $subs->clearState()
                         ->since($y . '-' . $m . '-01')
                         ->until($y . '-' . $m . '-' . $lmday . ' 23:59:59')
                         ->moneysum(1)
                         ->nozero(1)
                         ->nojoins(1)
                         ->paystate('C')
                         ->count()
                ?>
                @yieldRepeatable('renderMoney', $money)
            </td>
        </tr>
        <tr class="row1">
            <td>@lang('COM_AKEEBASUBS_DASHBOARD_STATS_THISMONTH')</td>
            <td align="right">
                <?php
                switch (gmdate('m'))
                {
                    case 1:
                    case 3:
                    case 5:
                    case 7:
                    case 8:
                    case 10:
                    case 12:
                        $lmday = 31;
                        break;
                    case 4:
                    case 6:
                    case 9:
                    case 11:
                        $lmday = 30;
                        break;
                    case 2:
                        $y = gmdate('Y');
                        if (!($y % 4) && ($y % 400))
                        {
                            $lmday = 29;
                        }
                        else
                        {
                            $lmday = 28;
                        }
                }
                if ($lmday < 1) $lmday = 28;
                ?>
                {{{ $subs->clearState()
                                   ->since(gmdate('Y').'-'.gmdate('m').'-01')
                                   ->until(gmdate('Y').'-'.gmdate('m').'-'.$lmday.' 23:59:59')
                                   ->nozero(1)
                                   ->nojoins(1)
                                   ->paystate('C')
                                   ->count() }}}
            </td>
            <td align="right">
                <?php $money = $subs->clearState()
                             ->since(gmdate('Y') . '-' . gmdate('m') . '-01')
                             ->until(gmdate('Y') . '-' . gmdate('m') . '-' . $lmday . ' 23:59:59')
                             ->moneysum(1)
                             ->nozero(1)
                             ->nojoins(1)
                             ->paystate('C')
                             ->count()
                ?>
                @yieldRepeatable('renderMoney', $money)
            </td>
        </tr>
        <tr class="row0">
            <td width="50%">@lang('COM_AKEEBASUBS_DASHBOARD_STATS_LAST7DAYS')</td>
            <td align="right" width="25%">
                {{{ $subs->clearState()
                                   ->since( gmdate('Y-m-d', time()-7*24*3600) )
                                   ->until( gmdate('Y-m-d') )
                                   ->nozero(1)
                                   ->nojoins(1)
                                   ->paystate('C')
                                   ->count() }}}
            </td>
            <td align="right" width="25%">
                <?php $money = $subs->clearState()
                             ->since(gmdate('Y-m-d', time() - 7 * 24 * 3600))
                             ->until(gmdate('Y-m-d'))
                             ->moneysum(1)
                             ->nozero(1)
                             ->nojoins(1)
                             ->paystate('C')
                             ->count()
                ?>
                @yieldRepeatable('renderMoney', $money)
            </td>
        </tr>
        <tr class="row1">
            <td width="50%">@lang('COM_AKEEBASUBS_DASHBOARD_STATS_YESTERDAY')</td>
            <td align="right" width="25%">
                <?php
                $date = new DateTime();
                $date->setDate(gmdate('Y'), gmdate('m'), gmdate('d'));
                $date->modify("-1 day");
                $yesterday = $date->format("Y-m-d");
                $date->modify("+1 day")
                ?>
                {{{ $subs->clearState()
                                   ->since( $yesterday )
                                   ->until( $date->format("Y-m-d") )
                                   ->nozero(1)
                                   ->nojoins(1)
                                   ->paystate('C')
                                   ->count() }}}
            </td>
            <td align="right" width="25%">
                <?php $money = $subs->clearState()
                             ->since($yesterday)
                             ->until($date->format("Y-m-d"))
                             ->moneysum(1)
                             ->nozero(1)
                             ->nojoins(1)
                             ->paystate('C')
                             ->count()
                ?>
                @yieldRepeatable('renderMoney', $money)
            </td>
        </tr>
        <tr class="row0">
            <td width="50%"><strong>@lang('COM_AKEEBASUBS_DASHBOARD_STATS_TODAY')</strong></td>
            <td align="right" width="25%">
                <strong>
                    <?php
                    $expiry = clone $date;
                    $expiry->modify('+1 day');
                    ?>
                    {{{ $subs->clearState()
                                       ->since( $date->format("Y-m-d") )
                                       ->until( $expiry->format("Y-m-d") )
                                       ->nozero(1)
                                       ->nojoins(1)
                                       ->paystate('C')
                                       ->count() }}}
                </strong>
            </td>
            <td align="right" width="25%">
                <strong>
                    <?php $money = $subs->clearState()
                                 ->since($date->format("Y-m-d"))
                                 ->until($expiry->format("Y-m-d"))
                                 ->nozero(1)
                                 ->nojoins(1)
                                 ->paystate('C')
                                 ->moneysum(1)
                                 ->count()
                    ?>
                    @yieldRepeatable('renderMoney', $money)
                </strong>
            </td>
        </tr>
        <tr class="row1">
            <?php
            switch (gmdate('m'))
            {
                case 1:
                case 3:
                case 5:
                case 7:
                case 8:
                case 10:
                case 12:
                    $lmday = 31;
                    break;
                case 4:
                case 6:
                case 9:
                case 11:
                    $lmday = 30;
                    break;
                case 2:
                    $y = gmdate('Y');
                    if (!($y % 4) && ($y % 400))
                    {
                        $lmday = 29;
                    }
                    else
                    {
                        $lmday = 28;
                    }
            }
            if ($lmday < 1) $lmday = 28;
            if ($y < 2011) $y = 2011;
            $daysin = gmdate('d');
            $numsubs = $subs->clearState()
                            ->since(gmdate('Y') . '-' . gmdate('m') . '-01')
                            ->until(gmdate('Y') . '-' . gmdate('m') . '-' . $lmday . ' 23:59:59')
                            ->nozero(1)
                            ->nojoins(1)
                            ->paystate('C')
                            ->count();
            $summoney = $subs->clearState()
                             ->since(gmdate('Y') . '-' . gmdate('m') . '-01')
                             ->until(gmdate('Y') . '-' . gmdate('m') . '-' . $lmday . ' 23:59:59')
                             ->nojoins(1)
                             ->moneysum(1)
                             ->paystate('C')
                             ->count();

            $money = $summoney / $daysin;
            ?>
            <td width="50%">
                <strong>
                    @lang('COM_AKEEBASUBS_DASHBOARD_STATS_AVERAGETHISMONTH')
                </strong>
            </td>
            <td align="right" width="25%">
                <strong><?php echo sprintf('%01.1f', $numsubs / $daysin)?><strong>
            </td>
            <td align="right" width="25%">
                <strong>
                    @yieldRepeatable('renderMoney', $money)
                </strong>
            </td>
        </tr>
        <tr class="row0">
            <td width="50%">
                <strong>
                    @lang('COM_AKEEBASUBS_DASHBOARD_STATS_PROJECTION')
                </strong>
            </td>
            <td align="right" width="25%">
                <em><?php echo sprintf('%01u', $lmday * ($numsubs / $daysin))?></em>
            </td>
            <td align="right" width="25%">
                <em>
                    <?php $money = $lmday * ($summoney / $daysin); ?>
                    @yieldRepeatable('renderMoney', $money)
                </em>
            </td>
        </tr>
        <tr class="row1">
            <td width="70%" colspan="2">
                @lang('COM_AKEEBASUBS_DASHBOARD_STATS_TOTALACTIVESUBSCRIBERS')
            </td>
            <td width="25%" align="right">
                {{{ $subs->clearState()->getActiveSubscribers() }}}
            </td>
        </tr>
        <tr class="row0">
            <td width="70%"
                colspan="2">@lang('COM_AKEEBASUBS_DASHBOARD_STATS_TOTALACTIVESUBSCRIPTIONS')</td>
            <td width="25%" align="right">
                {{{$subs->clearState()
                             ->paystate('C')
                             ->nozero(1)
                             ->nojoins(1)
                             ->enabled(1)
                             ->count() }}}
            </td>
        </tr>
        </tbody>
    </table>
@stop