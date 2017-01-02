<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var \Akeeba\Subscriptions\Site\View\Level\Html $this */

$this->getContainer()->platform->importPlugin('akeebasubs');
$jResponse = $this->getContainer()->platform->runPlugins('onSubscriptionFormPrepaymentRender', [
				$this->userparams,
				array_merge($this->cache, ['subscriptionlevel' => $this->item->akeebasubs_level_id])
			]);

if (!is_array($jResponse) || empty($jResponse)) return;
?>
@repeatable('customField', $field)
<?php
$field['isValid']  = array_key_exists('isValid', $field) ? $field['isValid'] : true;

$customField_class = '';
$classValidLabel   = '';
$classInvalidLabel = '';

if ($this->apply_validation == 'true')
{
	$customField_class = $field['isValid'] ? '' : 'has-error';
	$classValidLabel   = $field['isValid'] ? '' : 'hidden';
	$classInvalidLabel = !$field['isValid'] ? '' : 'hidden';
}
?>
<div class="form-group {{{$customField_class}}}">
	<label for="{{{$field['id']}}}" class="control-label col-sm-4 hidden">
		{{$field['label']}}
	</label>

	<div class="col-sm-8">
		{{$field['elementHTML']}}
	</div>
</div>
@endRepeatable

@foreach($jResponse as $customFields)
	@if (is_array($customFields) && !empty($customFields))
		@foreach($customFields as $field)
			@yieldRepeatable('customField', $field)
		@endforeach
	@endif
@endforeach
