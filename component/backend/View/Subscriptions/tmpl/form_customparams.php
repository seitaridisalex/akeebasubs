<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var  \Akeeba\Subscriptions\Admin\Model\Subscriptions  $model */

$model->getContainer()->platform->importPlugin('akeebasubs');

$jResponse = $model->getContainer()->platform->runPlugins(
	'onSubscriptionFormRenderPerSubFields',
	array(
		array(
			'subscriptionlevel' => $model->akeebasubs_level_id,
			'subcustom'=> $model->params
		)
	)
);

if(!is_array($jResponse) || empty($jResponse))
{
	return;
}

foreach($jResponse as $customFields):
	if (!is_array($customFields) || empty($customFields))
	{
		continue;
	}

	foreach($customFields as $field):?>

		<div class="control-group">
			<label for="<?php echo $field['id']?>" class="control-label">
				<?php echo $field['label']?>
			</label>
			<div class="controls">
				<?php echo $field['elementHTML']?>
			</div>
		</div>

	<?php endforeach; ?>
<?php endforeach;?>