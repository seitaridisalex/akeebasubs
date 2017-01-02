<?php
/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

/** @var  plgAkpayment2checkout  $this */
/** @var  \Akeeba\Subscriptions\Site\Model\Subscriptions  $subscription */
/** @var  \Akeeba\Subscriptions\Site\Model\Levels  $level */
/** @var  \Akeeba\Subscriptions\Site\Model\Users  $user */
/** @var  stdClass  $data */

defined('_JEXEC') or die();

$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo $data->url ?>"  method="post" id="paymentForm">
	<input type="hidden" name="sid" value="<?php echo $data->sid?>" />
	<input type="hidden" name="cart_order_id" value="<?php echo $subscription->akeebasubs_subscription_id ?>" />
	<input type="hidden" name="total" value="<?php echo sprintf('%02.2f',$subscription->gross_amount) ?>" />

	<input type="hidden" name="c_prod_1" value="<?php echo $level->akeebasubs_level_id ?>" />
	<input type="hidden" name="c_name_1" value="<?php echo $level->title ?>" />
	<input type="hidden" name="c_description_1" value="<?php echo $level->title . ' - [ ' . $user->username . ' ]' ?>" />
	<input type="hidden" name="c_price_1" value="<?php echo sprintf('%02.2f',$subscription->gross_amount) ?>" />
	<input type="hidden" name="c_tangible_1" value="N" />

	<?php if($data->params->get('demo',0)): ?>
	<input type="hidden" name="demo" value="Y" />
	<?php endif;?>
	<input type="hidden" name="fixed" value="Y" />
	<input type="hidden" name="lang" value="<?php echo $data->params->get('lang','en') ?>" />
	<input type="hidden" name="merchant_order_id" value="<?php echo $subscription->akeebasubs_subscription_id ?>" />
	<input type="hidden" name="pay_method" value="<?php echo strtoupper($data->params->get('pay_method','cc')) ?>" />
	<?php if($data->params->get('skip_landing',0)): ?>
	<input type="hidden" name="skip_landing" value="1" />
	<?php endif; ?>
	<input type="hidden" name="id_type" value="1" />
	<input type="hidden" name="x_receipt_link_url" value="<?php echo $data->x_receipt_link_url ?>" />

	<input type="hidden" name="card_holder_name" value="<?php echo $data->name ?>" />
	<input type="hidden" name="street_address" value="<?php echo $kuser->address1 ?>">
	<input type="hidden" name="street_address2" value="<?php echo $kuser->address2 ?>">
	<input type="hidden" name="city" value="<?php echo $kuser->city ?>">
	<input type="hidden" name="state" value="<?php echo $kuser->state ?>">
	<input type="hidden" name="zip" value="<?php echo $kuser->zip ?>">
	<input type="hidden" name="country" value="<?php echo $kuser->country ?>">
	<input type="hidden" name="email" value="<?php echo $data->email ?>">

	<input class="btn" type="submit" />
</form>
</p>