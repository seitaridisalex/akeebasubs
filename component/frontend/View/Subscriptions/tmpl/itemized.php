<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

use FOF30\Date\Date;

defined('_JEXEC') or die();

$subs = array();
$expired = array();
JLoader::import('joomla.utilities.date');
$jNow = new Date();

if ($this->items->count())
{
	foreach ($this->items as $subscription)
	{
		if (array_key_exists($subscription->akeebasubs_level_id, $subs))
		{
			continue;
		}

		if ($subscription->enabled)
		{
			/** @var \Akeeba\Subscriptions\Site\Model\Levels $level */
			$level = $subscription->level;
			if ($level->akeebasubs_level_id)
			{
				if ($level->akeebasubs_level_id == $subscription->akeebasubs_level_id)
				{
					$subs[$subscription->akeebasubs_level_id] = $level->title;
				}
			}
		}
		else
		{
			$jUp = new Date($subscription->publish_up);

			if ($jUp->toUnix() > $jNow->toUnix())
			{
				continue;
			}

			// Is it expired or just not activated yet?
			$expired[] = $subscription->akeebasubs_level_id;
		}
	}
} ?>

<?php if (!empty($subs)): ?>
	<ul class="akeebasubs-subscriptions-itemized-active">
		<?php foreach ($subs as $sub): ?>
			<li><?php echo $sub ?></li>
		<?php endforeach ?>
	</ul>
<?php endif; ?>

<?php if (!empty($expired)): $count = count($expired); ?>
	<span class="akeebasubs-subscriptions-itemized-expired">
<?php if ($count == 1): ?>
	<?php echo JText::_('COM_AKEEBASUBS_LEVELS_ITEMIZED_ONEEXPIREDSUB') ?>
<?php else: ?>
	<?php echo sprintf(JText::_('COM_AKEEBASUBS_LEVELS_ITEMIZED_MANYEXPIREDSUBS'), $count) ?>
<?php endif; ?>
</span>
<?php endif ?>