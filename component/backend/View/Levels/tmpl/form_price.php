<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

$fieldName = (string) $fieldElement['name'];
$currencyPosition = $this->container->params->get('currencypos','before');
$currencySymbol = $this->container->params->get('currencysymbol','€');


?>
<div
	class="input-<?php echo ($currencyPosition == 'before') ? 'prepend' : 'append' ?>">
	<?php if ($currencyPosition == 'before'): ?>
		<span class="add-on">
			<?php echo $currencySymbol ?>
		</span>
	<?php endif; ?>
	<input type="text" size="15" id="<?php echo $fieldName ?>" name="<?php echo $fieldName ?>" value="<?php echo $fieldValue ?>"
		   style="float: none"/>
	<?php if ($currencyPosition == 'after'): ?>
		<span class="add-on">
			<?php echo $currencySymbol ?>
		</span>
	<?php endif; ?>
</div>