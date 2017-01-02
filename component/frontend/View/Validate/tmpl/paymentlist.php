<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Helper\Select;

echo Select::paymentmethods(
    'paymentmethod',
    $this->input->getString('paymentmethod', ''),
    array(
        'id' 		=> 'paymentmethod',
        'level_id' 	=> $this->input->getInt('id', 0),
        'country'  	=> $this->input->getString('country', '')
    )
);