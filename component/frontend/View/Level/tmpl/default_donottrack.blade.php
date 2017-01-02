<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

?>
{{-- "Do Not Track" warning --}}
@if($this->dnt && $this->cparams->warndnt)
	<div class="alert alert-warning" style="text-align: center">
		@lang('COM_AKEEBASUBS_DNT_WARNING')
	</div>
@endif