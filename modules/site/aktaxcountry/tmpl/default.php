<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  string  $default_option */

JHtml::_('formbehavior.chosen', '#mod_aktaxcountry_country');

$postURL = JURI::current();

if (JFactory::getApplication()->input->getCmd('option', 'com_akeebasubs') != 'com_akeebasubs')
{
	$postURL = JRoute::_('index.php?option=com_akeebasubs&view=Levels');
}

$prompt = trim($prompt);

switch ($prompt)
{
	case '':
		$prompt = JText::_('MOD_AKTAXCOUNTRY_LBL_PROMPT');
		break;

	case '-':
		$prompt = '';
		break;
}

?>
<form action="<?php echo $postURL ?>" method="POST" class="form-inline pull-right" id="mod_aktaxcountry_form">
	<?php if (!empty($prompt)): ?>
		<label for="mod_aktaxcountry_country">
			<small><?php echo JText::_('MOD_AKTAXCOUNTRY_LBL_PROMPT'); ?></small>
		</label>
	<?php endif; ?>
	<?php echo JHtml::_('select.genericlist', $options, 'mod_aktaxcountry_country', array(
		'onchange' => 'document.forms.mod_aktaxcountry_form.submit()'
	), 'value', 'text', $default_option); ?>
</form>
<div class="clearfix"></div>