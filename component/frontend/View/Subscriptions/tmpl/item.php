<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var \Akeeba\Subscriptions\Site\View\Subscriptions\Html $this */

Use Akeeba\Subscriptions\Admin\Helper\Format;
use Akeeba\Subscriptions\Admin\Helper\Validator;

JLoader::import('joomla.utilities.date');
JLoader::import('joomla.plugin.helper');

$this->getContainer()->platform->importPlugin('akeebasubs');

$app        = JFactory::getApplication();
$jPublishUp = $this->getContainer()->platform->getDate($this->item->publish_up);
$goBackURL  = JRoute::_('index.php?option=com_akeebasubs&view=subscriptions');

if ($this->returnURL)
{
	$goBackURL  = $this->returnURL;
}
?>

<div id="akeebasubs">

<table class="table table-striped">
	<tr>
		<td class="subscription-label"><?php echo JText::_('COM_AKEEBASUBS_COMMON_ID')?></td>
		<td class="subscription-info">
			<strong><?php echo sprintf('%05u', $this->item->akeebasubs_subscription_id)?></strong>
		</td>
	</tr>
	<tr>
		<td class="subscription-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_USER')?></td>
		<td class="subscription-info">
			<strong><?php echo JFactory::getUser($this->item->user_id)->username?></strong>
			(<em><?php echo JFactory::getUser($this->item->user_id)->name?></em>)
		</td>
	</tr>
	<tr>
		<td class="subscription-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_LEVEL')?></td>
		<td class="subscription-info">
			<?php echo $this->item->level->title ?>
		</td>
	</tr>
	<tr>
		<td class="subscription-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISH_UP')?></td>
		<td class="subscription-info">
			<?php echo Format::date($this->item->publish_up) ?>
		</td>
	</tr>
	<tr>
		<td class="subscription-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISH_DOWN')?></td>
		<td class="subscription-info">
			<?php echo Format::date($this->item->publish_down) ?>
		</td>
	</tr>
	<tr>
		<td class="subscription-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_ENABLED')?></td>
		<td class="subscription-info">
			<?php if($this->item->enabled):?>
				<span class="icon icon-ok" title="<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_ENABLED_ACTIVE') ?>"></span>
			<?php elseif($jPublishUp->toUnix() >= time()):?>
				<span class="icon icon-time" title="<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_ENABLED_PENDING') ?>"></span>
			<?php else:?>
				<span class="icon icon-ban-circle" title="<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_ENABLED_INACTIVE') ?>"></span>
			<?php endif;?>
		</td>
	</tr>
	<tr>
		<td class="subscription-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_STATE')?></td>
		<td class="subscription-info"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE_'.$this->item->getFieldValue('state', 'N'))?></td>
	</tr>
	<tr>
		<td class="subscription-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_AMOUNT_PAID')?></td>
		<td class="subscription-info">
			<?php if($this->container->params->get('currencypos','before') == 'before'): ?>
			<?php echo $this->container->params->get('currencysymbol','€')?>
			<?php endif; ?>
			<?php echo sprintf('%2.02F',$this->item->gross_amount)?>
			<?php if($this->container->params->get('currencypos','before') == 'after'): ?>
			<?php echo $this->container->params->get('currencysymbol','€')?>
			<?php endif; ?>
		</td>
	</tr>
	<tr>
		<td class="subscription-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_SUBSCRIBED_ON')?></td>
		<td class="subscription-info">
			<?php echo Format::date($this->item->created_on) ?>
		</td>
	</tr>
</table>

<?php
$args      = array(
				array(
					'useredit'          => true,
					'subscriptionlevel' => $this->item->akeebasubs_level_id,
					'subcustom'         => $this->item->params
				)
			 );

$jResponse = $this->getContainer()->platform->runPlugins('onSubscriptionFormRenderPerSubFields', $args);
@ob_start();
if(is_array($jResponse) && !empty($jResponse)) foreach($jResponse as $customFields):
	if(is_array($customFields) && !empty($customFields)) foreach($customFields as $field):

		$customField_class = '';
		$validationClass   = '';

		if(array_key_exists('isValid', $field)) {
			$customField_class = $field['isValid'] ? (array_key_exists('validLabel', $field) ? 'success' : '') : 'error';
		}

		if(array_key_exists('validationClass', $field)){
			$validationClass = $field['validationClass'];
		}

		?>
		<div class="control-group <?php echo $customField_class ?>">
			<label for="<?php echo $field['id']?>" class="control-label">
				<?php echo $field['label']?>
			</label>
			<div class="controls">
				<?php echo $field['elementHTML']?>
				<?php if(array_key_exists('validLabel', $field)):?>
					<span id="<?php echo $field['id']?>_valid" class="help-inline <?php echo $validationClass ?>"
					      style="<?php if(!$field['isValid'] || !$applyValidationBoolean):?>display:none<?php endif?>">
					  <?php echo $field['validLabel']?>
					</span>
				<?php endif;?>
				<?php if(array_key_exists('invalidLabel', $field)):?>
					<span id="<?php echo $field['id']?>_invalid" class="help-inline <?php echo $validationClass ?>"
					      style="<?php if($field['isValid'] || !$applyValidationBoolean):?>display:none<?php endif?>">
					  <?php echo $field['invalidLabel']?>
					</span>
				<?php endif;?>
			</div>
		</div>

	<?php
	endforeach;
endforeach;
$subfieldsHTML = trim(@ob_get_clean());
if(!empty($subfieldsHTML)):
	// Do I have to inject any plugin validators?
	Validator::deployValidator();
?>
	<fieldset>
		<legend><?php echo JText::_('COM_AKEEBASUBS_LEVEL_PERSUBFIELDS')?></legend>
		<form class="form form-horizontal" method="post" action="<?php echo JRoute::_('index.php?option=com_akeebasubs&view=subscriptions&task=save', false)?>">
		<?php echo $subfieldsHTML ?>
			<button type="submit" class="btn btn-primary pull-right">
				<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_BUTTON_UPDATE') ?>
			</button>

			<input type="hidden" name="akeebasubs_subscription_id" value="<?php echo $this->item->akeebasubs_subscription_id?>"/>
			<input type="hidden" name="<?php echo JSession::getFormToken()?>" value="1" />
		</form>
	</fieldset>
<?php endif; ?>

<div class="akeebasubs-goback">
	<p>
		<a class="btn btn-large btn-primary" href="<?php echo $goBackURL; ?>">
			<span class="icon-white icon-arrow-left"></span>
			<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_TITLE')?>
		</a>
	</p>
</div>

	<?php // Subscription ID, so it's available for JS stuff ?>
	<input type="hidden" id="akeebasubs_subscription_id" value="<?php echo $this->item->akeebasubs_subscription_id?>"/>
	<input type="hidden" id="akeebasubs_level_id" value="<?php echo $this->item->akeebasubs_level_id?>"/>
</div>