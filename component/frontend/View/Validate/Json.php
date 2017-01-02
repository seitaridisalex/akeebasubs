<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\View\Validate;

defined('_JEXEC') or die;

class Json extends \FOF30\View\DataView\Json
{
    protected function onBeforeGetpayment($tpl = null)
    {
        $this->setLayout('paymentlist');
        $paymentHtml = $this->loadTemplate($tpl, true);

        $this->setLayout('statelist');
        $stateHtml = $this->loadTemplate($tpl, true);

        echo '###'.json_encode([
                'html'   => $paymentHtml,
                'states' => $stateHtml,
            ]).'###';
    }
}