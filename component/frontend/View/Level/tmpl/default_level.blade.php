<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\Helper\Image;
use Akeeba\Subscriptions\Admin\Helper\Message;
?>

<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">
			@lang('COM_AKEEBASUBS_LEVEL_LBL_YOURORDER')
			<span class="label label-default label-inverse">{{{$this->item->title}}}</span>
		</h3>
	</div>
	<div class="panel-body">
		@jhtml('content.prepare', Message::processLanguage($this->item->description))
	</div>
</div>