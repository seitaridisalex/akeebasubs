<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

/** @var \Akeeba\Subscriptions\Site\View\Subscribe\Html $this */

defined('_JEXEC') or die();

$this->addJavascriptFile('media://com_akeebasubs/js/autosubmit.js');

?>
<?php echo $this->form ?>