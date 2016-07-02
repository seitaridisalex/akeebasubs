<?php defined('_JEXEC') or die(); ?>
<div class="row-fluid">
	<div class="span6">
		<div class="control-group">
			<label for="params_contentpublish_publishcore" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_PUBLISHCORE_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo JHtml::_('select.booleanlist', 'params[contentpublish_publishcore]', array(), $params['contentpublish_publishcore']) ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_PUBLISHCORE_DESCRIPTION') ?>
				</span>
			</div>
		</div>
		<div class="control-group">
			<label for="params_contentpublish_publishk2" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_PUBLISHK2_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo JHtml::_('select.booleanlist', 'params[contentpublish_publishk2]', array(), $params['contentpublish_publishk2']) ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_PUBLISHK2_DESCRIPTION') ?>
				</span>
			</div>
		</div>
		<div class="control-group">
			<label for="params_contentpublish_publishsobipro" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_PUBLISHSOBIPRO_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo JHtml::_('select.booleanlist', 'params[contentpublish_publishsobipro]', array(), $params['contentpublish_publishsobipro']) ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_PUBLISHSOBIPRO_DESCRIPTION') ?>
				</span>
			</div>
		</div>
		<div class="control-group">
			<label for="params_contentpublish_publishzoo" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_PUBLISHZOO_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo JHtml::_('select.booleanlist', 'params[contentpublish_publishzoo]', array(), $params['contentpublish_publishzoo']) ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_PUBLISHZOO_DESCRIPTION') ?>
				</span>
			</div>
		</div>
		<div class="control-group">
			<label for="params_contentpublish_addgroups" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_ADDGROUPS_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo JHtml::_('select.genericlist', $zooApps, 'params[contentpublish_addgroups][]', array('multiple' => 'multiple', 'size' => 8, 'class' => 'input-large'), 'value', 'text', $params['contentpublish_addgroups']) ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_ADDGROUPS_DESCRIPTION') ?>
				</span>
			</div>
		</div>
	</div>

	<div class="span6">
		<div class="control-group">
			<label for="params_contentpublish_unpublishcore" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_UNPUBLISHCORE_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo JHtml::_('select.booleanlist', 'params[contentpublish_unpublishcore]', array(), $params['contentpublish_unpublishcore']) ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_UNPUBLISHCORE_DESCRIPTION') ?>
				</span>
			</div>
		</div>
		<div class="control-group">
			<label for="params_contentpublish_unpublishk2" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_UNPUBLISHK2_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo JHtml::_('select.booleanlist', 'params[contentpublish_unpublishk2]', array(), $params['contentpublish_unpublishk2']) ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_UNPUBLISHK2_DESCRIPTION') ?>
				</span>
			</div>
		</div>
		<div class="control-group">
			<label for="params_contentpublish_unpublishsobipro" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_UNPUBLISHSOBIPRO_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo JHtml::_('select.booleanlist', 'params[contentpublish_unpublishsobipro]', array(), $params['contentpublish_unpublishsobipro']) ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_UNPUBLISHSOBIPRO_DESCRIPTION') ?>
				</span>
			</div>
		</div>
		<div class="control-group">
			<label for="params_contentpublish_unpublishzoo" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_UNPUBLISHZOO_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo JHtml::_('select.booleanlist', 'params[contentpublish_unpublishzoo]', array(), $params['contentpublish_unpublishzoo']) ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_UNPUBLISHZOO_DESCRIPTION') ?>
				</span>
			</div>
		</div>
		<div class="control-group">
			<label for="params_contentpublish_removegroups" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_REMOVEGROUPS_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo JHtml::_('select.genericlist', $zooApps, 'params[contentpublish_removegroups][]', array('multiple' => 'multiple', 'size' => 8, 'class' => 'input-large'), 'value', 'text', $params['contentpublish_removegroups']) ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_REMOVEGROUPS_DESCRIPTION') ?>
				</span>
			</div>
		</div>
	</div>
</div>

<div class="alert alert-warning">
	<p><?php echo JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_USAGENOTE'); ?></p>
</div>