<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

?>
{{-- "Do Not Track" warning --}}
@if($this->dnt)
	<div class="alert alert-block alert-danger" style="text-align: center;font-weight: bold">
		@lang('COM_AKEEBASUBS_DNT_WARNING')
	</div>
@endif