<?php defined('_JEXEC') or die();
/** @var plgAkeebasubsAcymailing $this */
/** @var \Akeeba\Subscriptions\Site\Model\Levels $level */

$params = $level->params;
 ?>
<div class="row-fluid">
	<div class="span12">
        <div class="control-group">
            <label for="params_reseller_company_url" class="control-label">
                <?php echo JText::_('PLG_AKEEBASUBS_RESELLER_COMPANYURL_LABEL'); ?>
            </label>
            <div class="controls">
                <input type="text" name="params[reseller_company_url]" class="input-xlarge" id="params_reseller_company_url" value="<?php echo isset($params['reseller_company_url'])? $params['reseller_company_url'] : ''; ?>"/>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_RESELLER_COMPANYURL_DESC') ?>
				</span>
            </div>
        </div>
		<div class="control-group">
			<label for="params_reseller_api_key" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_RESELLER_APIKEY_LABEL'); ?>
			</label>
			<div class="controls">
				<input type="text" name="params[reseller_api_key]" class="input-medium" id="params_reseller_api_key" value="<?php echo isset($params['reseller_api_key'])? $params['reseller_api_key'] : ''; ?>"/>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_RESELLER_APIKEY_DESCR') ?>
				</span>
			</div>
		</div>
        <div class="control-group">
            <label for="params_reseller_api_pwd" class="control-label">
                <?php echo JText::_('PLG_AKEEBASUBS_RESELLER_APIPWD_LABEL'); ?>
            </label>
            <div class="controls">
                <input type="password" name="params[reseller_api_pwd]" class="input-medium" id="params_reseller_api_pwd" value="<?php echo isset($params['reseller_api_pwd'])? $params['reseller_api_pwd'] : ''; ?>"/>
                <span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_RESELLER_APIPWD_DESCR') ?>
				</span>
            </div>
        </div>
        <div class="control-group">
            <label for="params_reseller_notify_emails" class="control-label">
                <?php echo JText::_('PLG_AKEEBASUBS_RESELLER_NOTIFYEMAILS_LABEL'); ?>
            </label>
            <div class="controls">
                <input type="text" name="params[reseller_notify_emails]" class="input-xxlarge" id="params_reseller_notify_emails" value="<?php echo isset($params['reseller_notify_emails'])? $params['reseller_notify_emails'] : ''; ?>"/>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_RESELLER_NOTIFYEMAILS_DESC') ?>
				</span>
            </div>
        </div>
        <div class="control-group">
            <label for="params_reseller_frontend_label" class="control-label">
                <?php echo JText::_('PLG_AKEEBASUBS_RESELLER_FRONTEND_LABEL_LABEL'); ?>
            </label>
            <div class="controls">
                <input type="text" name="params[reseller_frontend_label]" class="input-xlarge" id="params_reseller_frontend_label" value="<?php echo isset($params['reseller_frontend_label'])? htmlentities($params['reseller_frontend_label']) : ''; ?>"/>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_RESELLER_FRONTEND_LABEL_DESC') ?>
				</span>
            </div>
        </div>
        <div class="control-group">
            <label for="params_reseller_frontend_format" class="control-label">
                <?php echo JText::_('PLG_AKEEBASUBS_RESELLER_FRONTEND_FORMAT_LABEL'); ?>
            </label>
            <div class="controls">
                <input type="text" name="params[reseller_frontend_format]" class="input-xlarge" id="params_reseller_frontend_format" value="<?php echo isset($params['reseller_frontend_format'])? htmlentities($params['reseller_frontend_format']) : ''; ?>"/>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_RESELLER_FRONTEND_FORMAT_DESC') ?>
				</span>
            </div>
        </div>
        <div class="control-group">
            <label for="params_reseller_coupon_link" class="control-label">
                <?php echo JText::_('PLG_AKEEBASUBS_RESELLER_COUPON_LINK_LABEL'); ?>
            </label>
            <div class="controls">
                <input type="text" name="params[reseller_coupon_link]" class="input-xxlarge" id="params_reseller_coupon_link" value="<?php echo isset($params['reseller_coupon_link'])? $params['reseller_coupon_link'] : ''; ?>"/>
                <span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_RESELLER_COUPON_LINK_DESC') ?>
				</span>
            </div>
        </div>
	</div>
</div>