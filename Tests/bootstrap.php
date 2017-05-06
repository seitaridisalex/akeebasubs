<?php
/**
 * @package    AkeebaSubs
 * @subpackage Tests
 * @copyright  Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */

if (!file_exists(__DIR__ . '/../vendor/autoload.php'))
{
	die('You need to install Composer and run `composer install` before running the tests.');
}

define('_JEXEC', 1);

// Maximise error reporting.
ini_set('zend.ze1_compatibility_mode', '0');
error_reporting(E_ALL & ~E_STRICT);
ini_set('display_errors', 1);

// Timezone fix; avoids errors printed out by PHP 5.3.3+
if (function_exists('date_default_timezone_get') && function_exists('date_default_timezone_set'))
{
	if (function_exists('error_reporting'))
	{
		$oldLevel = error_reporting(0);
	}
	$serverTimezone = @date_default_timezone_get();
	if (empty($serverTimezone) || !is_string($serverTimezone))
	{
		$serverTimezone = 'UTC';
	}
	if (function_exists('error_reporting'))
	{
		error_reporting($oldLevel);
	}
	@date_default_timezone_set($serverTimezone);
}

require_once 'config.php';

$siteroot = $akeebasubsTestConfig ['site_root'];

if (file_exists($siteroot . '/defines.php'))
{
	include_once $siteroot . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', $siteroot);

	require_once JPATH_BASE . '/includes/defines.php';
}

if (!defined('JPATH_TESTS'))
{
	define('JPATH_TESTS', realpath(__DIR__ . '/..'));
}

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/import.legacy.php';
require_once JPATH_LIBRARIES . '/cms.php';

// This is required to force Joomla! to read the correct configuration.php file...
$config = JFactory::getConfig(JPATH_SITE . '/configuration.php');

// Load FOF's autoloader
require_once JPATH_LIBRARIES . '/fof30/include.php';

\FOF30\Autoloader\Autoloader::getInstance()->addMap('Akeeba\\Subscriptions\\Tests\\', __DIR__);

// Work around Joomla! 3.7's Session package being utterly borked
$session    = JFactory::getSession();
$dispatcher = new JEventDispatcher();
$session    = JFactory::getSession();
$input      = new JInputCli();
$session->initialise($input, $dispatcher);
$session->start();

// Perform the master setup
\Akeeba\Subscriptions\Tests\Stubs\CommonSetup::masterSetup();