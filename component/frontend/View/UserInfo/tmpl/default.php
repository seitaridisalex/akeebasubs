<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var \Akeeba\Subscriptions\Site\View\UserInfo\Html $this */
?>

<div id="akeebasubs" class="userinfo">

<?php echo $this->getContainer()->template->loadPosition('akeebasubscriptionsuserinfoheader'); ?>

<noscript>
<hr/>
<h1><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_NOJS_HEADER')?></h1>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_NOJS_BODY')?></p>
<hr/>
</noscript>

<form action="<?php echo JRoute::_('index.php?option=com_akeebasubs&view=UserInfo') ?>" method="post" id="userinfoForm" >
	<input type="hidden" name="<?php echo $this->container->platform->getToken(true);?>" value="1" />
	<input type="hidden" name="task" value="save" />

	<?php echo $this->loadAnyTemplate('site:com_akeebasubs/Level/default_fields') ?>

	<div class="form-actions">
		<button class="btn btn-primary btn-large" id="update_userinfo" type="submit"><?php echo JText::_('COM_AKEEBASUBS_USERINFO_BUTTON_UPDATE_USER')?></button>
	</div>
</form>

	<?php echo $this->getContainer()->template->loadPosition('akeebasubscriptionsuserinfofooter'); ?>

</div>

<?php
$aks_msg_error_overall = JText::_('COM_AKEEBASUBS_LEVEL_ERR_JSVALIDATIONOVERALL',true);
$script = <<<JS

(function(\$) {
	\$(document).ready(function(){
		// Commented out until we can resolve some strange validation errors for some users
		// \$('#signupForm').submit(onSignupFormSubmit);
		validatePassword();
		validateName();
		validateEmail();
		validateAddress();
		validateBusiness();
	});
})(akeeba.jQuery);

function onSignupFormSubmit()
{
	if(akeebasubs_valid_form == false) {
		alert('$aks_msg_error_overall');
	}

	return akeebasubs_valid_form;
}

JS;
$this->addJavascriptInline($script);