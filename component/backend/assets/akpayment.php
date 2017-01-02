<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

$backtrace_array = debug_backtrace();
$filename = $backtrace_array[0]['file'];
$folder = dirname($filename);
$altFolder = basename($filename) . '.bak';

@ob_end_flush();

?>
<html>
<head>
	<title>WARNING: An incompatible Akeeba Subscriptions plugin has been detected</title>
</head>
<body>
<div style="border: thick solid red; margin: 1em; padding: 2em; background: #999900">
	<h1 style="font-size: 38pt; font-weight: bold; padding-bottom: 1em;">
		WARNING: Incompatible Akeeba Subscriptions plugin detected
	</h1>

	<p>
		An outdated plugin was found in <code><?php echo $filename ?></code> which is incompatible with Akeeba Subscriptions 5. Please disable this plugin, either from the back-end of your site or by renaming the <code><?php echo $folder ?></code> folder to <code><?php echo $altFolder ?></code> for this message to disappear. After refactoring the code of this plugin for Akeeba Subscriptions 5 you can re-enable it.
	</p>

	<p>
		<b>EXTREMELY IMPORTANT</b>: Do NOT ask Akeeba Ltd for support regarding this message. None will be provided and
		your request will be deleted without a reply. Support for Akeeba Subscriptions was terminated on November 2013.
		We regret to inform you that due to lack of available time we will not be available for any bespoke
		projects. We additionally regret to inform you that we cannot recommend a developer for this task.
	</p>
</div>
</body>
</html>
<?php die; ?>