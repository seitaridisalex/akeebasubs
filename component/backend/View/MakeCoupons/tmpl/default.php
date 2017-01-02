<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

use \Akeeba\Subscriptions\Admin\Helper\Select;

/** @var \Akeeba\Subscriptions\Admin\View\MakeCoupons\Html $this */
/** @var \Akeeba\Subscriptions\Admin\Model\MakeCoupons $model */

$model = $this->getModel();
$subscriptions = $model->getState('subscriptions', '');

if (is_array($subscriptions))
{
	$subscriptions = implode(',', $subscriptions);
}
?>

<?php if ($this->coupons): ?>
	<fieldset>
		<legend><?php echo JText::_('COM_AKEEBASUBS_MAKECOUPONS_COUPONS_LABEL') ?></legend>
		<table class="table table-striped" width="100%">
			<?php foreach ($this->coupons as $coupon): ?>
				<tr>
					<td>
						<?php echo $coupon ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
	</fieldset>
<?php endif; ?>

<form action="index.php" method="post" name="adminForm" id="adminForm">
	<input type="hidden" name="option" value="com_akeebasubs"/>
	<input type="hidden" name="view" value="MakeCoupons"/>
	<input type="hidden" id="task" name="task" value="generate"/>
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken(); ?>" value="1"/>

	<fieldset>
		<legend><?php echo JText::_('COM_AKEEBASUBS_MAKECOUPONS_GENERATE_LABEL') ?></legend>

		<div class="form-horizontal">
			<div class="control-group">
				<label class="control-label"
				       for="title"><?php echo JText::_('COM_AKEEBASUBS_COUPON_FIELD_TITLE') ?></label>

				<div class="controls">
					<input type="text" id="title" name="title" class="input-medium"
					       value="<?php echo $model->getState('title', '') ?>"/>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label"
				       for="prefix"><?php echo JText::_('COM_AKEEBASUBS_MAKECOUPONS_PREFIX_LABEL') ?></label>

				<div class="controls">
					<input type="text" id="prefix" name="prefix" class="input-medium"
					       value="<?php echo $model->getState('prefix', '') ?>"/>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label"
				       for="quantity"><?php echo JText::_('COM_AKEEBASUBS_MAKECOUPONS_QUANTITY_LABEL') ?></label>

				<div class="controls">
					<input type="text" id="quantity" name="quantity" class="input-small"
					       value="<?php echo $model->getState('quantity', 5) ?>"/>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label"
				       for="type"><?php echo JText::_('COM_AKEEBASUBS_COUPON_FIELD_TYPE') ?></label>

				<div class="controls">
					<?php echo Select::coupontypes('type', $model->getState('type', 'percent')) ?>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label"
				       for="value"><?php echo JText::_('COM_AKEEBASUBS_COUPON_FIELD_VALUE') ?></label>

				<div class="controls">
					<input type="text" id="value" name="value" class="input-small"
					       value="<?php echo $model->getState('value', 100) ?>"/>
				</div>
			</div>

			<div class="control-group">
				<label for="subscriptions"
				       class="control-label"><?php echo JText::_('COM_AKEEBASUBS_COUPON_FIELD_SUBSCRIPTIONS'); ?></label>

				<div class="controls">
					<?php echo Select::levels('subscriptions[]', empty($subscriptions) ? '-1' : explode(',', $subscriptions), array(
						'multiple' => 'multiple',
						'size'     => 3
					)) ?>
				</div>
			</div>

			<div class="control-group">
				<label for="userhits" class="control-label">
					<?php echo JText::_('COM_AKEEBASUBS_COUPON_FIELD_USERHITSLIMIT'); ?>
				</label>

				<div class="controls">
					<input type="text" size="5" id="userhits" name="userhits"
					       value="<?php echo $model->getState('userhits', 1) ?>"/>
				</div>
			</div>

			<div class="control-group">
				<label for="hits" class="control-label">
					<?php echo JText::_('COM_AKEEBASUBS_COUPON_FIELD_HITSLIMIT'); ?>
				</label>

				<div class="controls">
					<input type="text" size="5" id="hits" name="hits"
					       value="<?php echo $model->getState('hits', 0) ?>"/>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label"
				       for="expiration"><?php echo JText::_('COM_AKEEBASUBS_COUPON_PUBLISH_DOWN') ?></label>

				<div class="controls">
			<span class="akeebasubs-nofloat-input">
				<?php echo JHTML::_('calendar', $model->getState('expiration', ''), 'expiration', 'expiration'); ?>
			</span>
				</div>
			</div>

			<div class="form-actions">
				<button class="btn btn-primary btn-large">
					<i class="icon icon-cog icon-white"></i>
					<?php echo JText::_('COM_AKEEBASUBS_MAKECOUPONS_RUN_LABEL') ?>
				</button>
				<a href="index.php?option=com_akeebasubs&view=coupons" class="btn">
					<i class="icon icon-arrow-left"></i>
					<?php echo JText::_('COM_AKEEBASUBS_MAKECOUPONS_BACK_LABEL') ?>
				</a>
			</div>

		</div>

	</fieldset>

</form>