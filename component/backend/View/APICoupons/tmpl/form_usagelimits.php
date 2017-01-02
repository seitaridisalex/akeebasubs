<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

$selected = $model->creation_limit ? 1 : ($model->subscription_limit ? 2 : 3);
echo \Akeeba\Subscriptions\Admin\Helper\Select::apicouponLimits('usage_limits', $selected) ?>

<input type="text" style="width: 50px; display:none" id="creation_limit" name="creation_limit"
	   value="<?php echo $this->escape($model->creation_limit) ?>"/>
<input type="text" style="width: 50px; display:none" id="subscription_limit"
	   name="subscription_limit"
	   value="<?php echo $this->escape($model->subscription_limit) ?>"/>
<input type="text" style="width: 50px; display:none" id="value_limit" name="value_limit"
	   value="<?php echo $this->escape($model->value_limit) ?>"/>