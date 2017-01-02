<?php
/**
 * package   AkeebaSubs
 * copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
?>

@section('2copromo')
    @if (JComponentHelper::getParams('com_akeebasubs')->get('show2copromo',1))
    <div class="row-fluid">
        <div class="well">
            <h3>Special offer for Akeeba Subscriptions users</h3>
            <p>
                <a href="http://2checkout.com">2Checkout.com</a> (2CO) is a worldwide leader in
                payments and e-commerce services. 2CO powers online sellers with a global
                platform of payment methods and a world-class fraud prevention service on secure
                and reliable PCI-compliant payment pages.</p>
            <p>
                2Checkout’s payments platform bundles a gateway and merchant account into one single
                offering with no need to contract with a merchant bank or manage separate agreements.
                You can accept Visa, MasterCard, AMEX, Discover, PayPal, Diner’s Club, JCB and Debit
                cards (in the U.S.) from one solution through 2Checkout’s fully secure hosted payment
                pages.  In addition, 2CO provides industry leading recurring billing services, call
                center support, full SSL certification, and the system is translatable in 15 languages
                and 26 international currencies for buyers and sellers in over 200 countries.
            </p>
            <p>
                Save now! Use promo code <strong style="color: #009900">AkeebaLoves2CO</strong> for a waiver of your first
                monthly fee (a savings of $10.99) and start selling online today! Visit
                <a href="http://www.2checkout.com">www.2checkout.com</a>, click SIGN UP NOW, complete
                the application, and then enter the code into the promo code field to take advantage
                of this special offer today!
            </p>
            <div class="form-actions">
                <a class="btn btn-success btn-large" href="https://www.2checkout.com/referral?r=akeebaloves2co">
                    <i class="icon-shopping-cart icon-white"></i> Take me to www.2checkout.com
                </a>
                <a class="btn btn-danger" href="index.php?option=com_akeebasubs&view=ControlPanel&task=hide2copromo">
                    <i class="icon-off icon-white"></i>
                    Hide this special offer
                </a>
            </div>
        </div>
    </div>
    @endif
@stop