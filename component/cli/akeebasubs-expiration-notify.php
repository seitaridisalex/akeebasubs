<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2016 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  --
 *
 *  Command-line script to schedule the expiration notification emails
 */
// Define ourselves as a parent file
define('_JEXEC', 1);
// Required by the CMS
define('DS', DIRECTORY_SEPARATOR);

use FOF30\Container\Container;
use Akeeba\Subscriptions\Admin\Model\Levels;
use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use Akeeba\Subscriptions\Admin\Helper\Email;

$minphp = '5.4.0';
if (version_compare(PHP_VERSION, $minphp, 'lt'))
{
	$curversion = PHP_VERSION;
	$bindir = PHP_BINDIR;
	echo <<< ENDWARNING
================================================================================
WARNING! Incompatible PHP version $curversion
================================================================================

This CRON script must be run using PHP version $minphp or later. Your server is
currently using a much older version which would cause this script to crash. As
a result we have aborted execution of the script. Please contact your host and
ask them for the correct path to the PHP CLI binary for PHP $minphp or later, then
edit your CRON job and replace your current path to PHP with the one your host
gave you.

For your information, the current PHP version information is as follows.

PATH:    $bindir
VERSION: $curversion

Further clarifications:

1. There is absolutely no possible way that you are receiving this warning in
   error. We are using the PHP_VERSION constant to detect the PHP version you
   are currently using. This is what PHP itself reports as its own version. It
   simply cannot lie.

2. Even though your *site* may be running in a higher PHP version that the one
   reported above, your CRON scripts will most likely not be running under it.
   This has to do with the fact that your site DOES NOT run under the command
   line and there are different executable files (binaries) for the web and
   command line versions of PHP.

3. Please note that you MUST NOT ask us for support about this error. We cannot
   possibly know the correct path to the PHP CLI binary as we have not set up
   your server. Your host must know and give that information.

4. The latest published versions of PHP can be found at http://www.php.net/
   Any older version is considered insecure and must NOT be used on a live
   server. If your server uses a much older version of PHP than that please
   notify them that their servers are insecure and in need of an update.

This script will now terminate. Goodbye.

ENDWARNING;
	die();
}

// Load system defines
if (file_exists(__DIR__ . '/defines.php'))
{
	require_once __DIR__ . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	$path = rtrim(__DIR__, DIRECTORY_SEPARATOR);
	$rpos = strrpos($path, DIRECTORY_SEPARATOR);
	$path = substr($path, 0, $rpos);
	define('JPATH_BASE', $path);
	require_once JPATH_BASE . '/includes/defines.php';
}

// Load the rest of the necessary files
if (file_exists(JPATH_LIBRARIES . '/import.legacy.php'))
{
	require_once JPATH_LIBRARIES . '/import.legacy.php';
}
else
{
	require_once JPATH_LIBRARIES . '/import.php';
}
require_once JPATH_LIBRARIES . '/cms.php';

// You can't fix stupid… but you can try working around it
if( (!function_exists('json_encode')) || (!function_exists('json_decode')) )
{
	require_once JPATH_ADMINISTRATOR . '/components/com_akeebasubs/helpers/jsonlib.php';
}

JLoader::import('joomla.application.cli');
JLoader::import('joomla.application.component.helper');

if (version_compare(JVERSION, '3.4.9999', 'ge'))
{
	// Joomla! 3.5 and later does not load the configuration.php unless you explicitly tell it to.
	JFactory::getConfig(JPATH_CONFIGURATION . '/configuration.php');
}

/**
 * Akeeba Subscriptions expiration notification CLI app
 */
class AkeebaSubscriptionsExpirationNotifyApp extends JApplicationCli
{
	/**
	 * JApplicationCli didn't want to run on PHP CGI. I have my way of becoming
	 * VERY convincing. Now obey your true master, you petty class!
	 *
	 * @param JInputCli $input
	 * @param JRegistry $config
	 * @param JDispatcher $dispatcher
	 */
	public function __construct(JInputCli $input = null, JRegistry $config = null, JDispatcher $dispatcher = null)
	{
		// Close the application if we are not executed from the command line, Akeeba style (allow for PHP CGI)
		if (array_key_exists('REQUEST_METHOD', $_SERVER))
		{
			die('You are not supposed to access this script from the web. You have to run it from the command line. If you don\'t understand what this means, you must not try to use this file before reading the documentation. Thank you.');
		}

		$cgiMode = false;

		if (!defined('STDOUT') || !defined('STDIN') || !isset($_SERVER['argv']))
		{
			$cgiMode = true;
		}

		// If a input object is given use it.
		if ($input instanceof JInput)
		{
			$this->input = $input;
		}
		// Create the input based on the application logic.
		else
		{
			if (class_exists('JInput'))
			{
				if ($cgiMode)
				{
					$query = "";
					if (!empty($_GET))
					{
						foreach ($_GET as $k => $v)
						{
							$query .= " $k";
							if ($v != "")
							{
								$query .= "=$v";
							}
						}
					}
					$query	 = ltrim($query);
					$argv	 = explode(' ', $query);
					$argc	 = count($argv);

					$_SERVER['argv'] = $argv;
				}

				if (class_exists('JInputCLI'))
				{
					$this->input = new JInputCLI();
				}
				else
				{
					$this->input = new JInputCli();
				}
			}
		}

		// If a config object is given use it.
		if ($config instanceof JRegistry)
		{
			$this->config = $config;
		}
		// Instantiate a new configuration object.
		else
		{
			$this->config = new JRegistry;
		}

		// If a dispatcher object is given use it.
		if ($dispatcher instanceof JDispatcher)
		{
			$this->dispatcher = $dispatcher;
		}
		// Create the dispatcher based on the application logic.
		else
		{
			$this->loadDispatcher();
		}

		// Load the configuration object.
		$this->loadConfiguration($this->fetchConfigurationData());

		// Set the execution datetime and timestamp;
		$this->set('execution.datetime', gmdate('Y-m-d H:i:s'));
		$this->set('execution.timestamp', time());

		// Set the current directory.
		$this->set('cwd', getcwd());

		// Work around Joomla! 3.4.7's JSession bug
		if (version_compare(JVERSION, '3.4.7', 'eq'))
		{
			JFactory::getSession()->restart();
		}
	}

	/**
	 * The main entry point of the application
	 */
	public function execute()
	{
		// Set all errors to output the messages to the console, in order to
		// avoid infinite loops in JError ;)
		restore_error_handler();
		JError::setErrorHandling(E_ERROR, 'die');
		JError::setErrorHandling(E_WARNING, 'echo');
		JError::setErrorHandling(E_NOTICE, 'echo');

		// Required by Joomla!
		JLoader::import('joomla.environment.request');

		// Set the root path to Akeeba Subscriptions
		define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_akeebasubs');

		// Allow inclusion of Joomla! files
		if (!defined('_JEXEC'))
			define('_JEXEC', 1);

		// Load FOF
		if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
		{
			die('FOF 3.0 is not installed');
		}

		// Load the version.php file
		include_once JPATH_COMPONENT_ADMINISTRATOR . '/version.php';

		// Load language strings
		JFactory::getLanguage()->load('plg_system_asexpirationnotify', JPATH_ADMINISTRATOR, null, true, true);

		// Display banner
		$year			 = gmdate('Y');
		$phpversion		 = PHP_VERSION;
		$phpenvironment	 = PHP_SAPI;
		$phpos			 = PHP_OS;

		$this->out("Akeeba Subscriptions Expiration Notification Emails CLI " . AKEEBASUBS_VERSION . " (" . AKEEBASUBS_DATE . ")");
		$this->out("Copyright (C) 2010-$year Nicholas K. Dionysopoulos");
		$this->out(str_repeat('-', 79));
		$this->out("Akeeba Subscriptions is Free Software, distributed under the terms of the GNU General");
		$this->out("Public License version 3 or, at your option, any later version.");
		$this->out("This program comes with ABSOLUTELY NO WARRANTY as per sections 15 & 16 of the");
		$this->out("license. See http://www.gnu.org/licenses/gpl-3.0.html for details.");
		$this->out(str_repeat('-', 79));
		$this->out("You are using PHP $phpversion ($phpenvironment)");
		$this->out("");

		// Unset time limits
		$safe_mode = true;
		if (function_exists('ini_get'))
		{
			$safe_mode = ini_get('safe_mode');
		}
		if (!$safe_mode && function_exists('set_time_limit'))
		{
			$this->out("Unsetting time limit restrictions");
			@set_time_limit(0);
		}

		// ===== START

		$this->sendNotifications();

		// ===== END

		$this->out("Peak memory usage: " . $this->peakMemUsage());
	}

	protected function sendNotifications()
	{
		// Get today's date
		JLoader::import('joomla.utilities.date');
		$jNow = new JDate();
		$now  = $jNow->toUnix();

		// Get and loop all subscription levels
		/** @var Levels $levelsModel */
		$levelsModel = Container::getInstance('com_akeebasubs')->factory->model('Levels')->tmpInstance();
		$levels = $levelsModel
			->enabled(1)
			->get(true);

		/** @var Levels $level */
		foreach ($levels as $level)
		{
			$this->out("Checking for subscriptions in the \"{$level->title}\" subscription level");
			// Load the notification thresholds and make sure they are sorted correctly!
			$notify1     = $level->notify1;
			$notify2     = $level->notify2;
			$notifyAfter = $level->notifyafter;

			if ($notify2 > $notify1)
			{
				$tmp     = $notify2;
				$notify2 = $notify1;
				$notify1 = $tmp;
			}

			// Make sure we are asked to notify users, at all!
			if (($notify1 <= 0) && ($notify2 <= 0))
			{
				$this->out("\t!! This level specifies the users should not be notified");
				continue;
			}

			// Get the subscriptions expiring within the next $notify1 days for
			// users which we have not contacted yet.
			$jFrom = new JDate($now + 1);
			$jTo   = new JDate($now + $notify1 * 24 * 3600);

			/** @var Subscriptions $subsModel */
			$subsModel = Container::getInstance('com_akeebasubs')->factory->model('Subscriptions')->tmpInstance();

			$subs1 = $subsModel->getClone()
			                   ->contact_flag(0)
			                   ->level($level->akeebasubs_level_id)
			                   ->enabled(1)
			                   ->expires_from($jFrom->toSql())
			                   ->expires_to($jTo->toSql())
			                   ->get(true);

			// Get the subscriptions expiring within the next $notify2 days for
			// users which we have contacted only once
			$subs2 = array();

			if ($notify2 > 0)
			{
				$jFrom = new JDate($now + 1);
				$jTo   = new JDate($now + $notify2 * 24 * 3600);

				$subs2 = $subsModel->getClone()
				                   ->contact_flag(1)
				                   ->level($level->akeebasubs_level_id)
				                   ->enabled(1)
				                   ->expires_from($jFrom->toSql())
				                   ->expires_to($jTo->toSql())
				                   ->get(true);
			}

			// Get the subscriptions expired $notifyAfter days ago
			$subs3 = array();

			if ($notifyAfter > 0)
			{
				// Get all subscriptions expired $notifyAfter + 2 to $notifyAfter days ago. So, if $notifyAfter is 30
				// it will get all subscriptions expired 30 to 32 days ago. This allows us to send emails if the plugin
				// is triggered at least once every two days. Any site with less traffic than that required for the
				// plugin to be triggered every 48 hours doesn't need our software, it needs better marketing to get
				// some users!
				$jFrom = new JDate($now - ($notifyAfter + 2) * 24 * 3600);
				$jTo   = new JDate($now - $notifyAfter * 24 * 3600);

				$subs3 = $subsModel->getClone()
				                   ->level($level->akeebasubs_level_id)
				                   ->enabled(0)
				                   ->expires_from($jFrom->toSql())
				                   ->expires_to($jTo->toSql())
				                   ->get(true);
			}

			// If there are no subscriptions, bail out
			$subs1count = is_object($subs1) ? $subs1->count() : 0;
			$subs2count = is_object($subs2) ? $subs2->count() : 0;
			$subs3count = is_object($subs3) ? $subs3->count() : 0;

			if (($subs1count + $subs2count + $subs3count) == 0)
			{
				$this->out("\tNo subscriptions to notify were found in this level");
				continue;
			}

			// Check is some of those subscriptions have been renewed. If so, set their contactFlag to 2
			$realSubs = array();

			$this->out("\tProcessing notifications");

			foreach (array($subs1, $subs2, $subs3) as $subs)
			{
				/** @var Subscriptions $sub */
				foreach ($subs as $sub)
				{
					// Skip the subscription if the contact_flag is already 3
					if ($sub->contact_flag == 3)
					{
						continue;
					}

					// Get the user and level, load similar subscriptions with start date after this subscription's expiry date
					$renewals = $subsModel->getClone()
					                      ->enabled(1)
					                      ->user_id($sub->user_id)
					                      ->level($sub->akeebasubs_level_id)
					                      ->publish_up($sub->publish_down)
					                      ->get(true);

					if ($renewals->count())
					{
						// The user has already renewed. Don't send him an email; just update the row
						$subsModel->getClone()
						          ->find($sub->akeebasubs_subscription_id)
						          ->save([
							          'contact_flag' => 3
						          ]);
					}
					else
					{
						// No renewals found. Let's nag our user.
						$realSubs[] = $sub;
					}
				}
			}

			// If there are no subscriptions, bail out
			if (empty($realSubs))
			{
				continue;
			}

			// Loop through subscriptions and send out emails, checking for timeout
			$jNow = new JDate();
			$mNow = $jNow->toSql();

			/** @var Subscriptions $sub */
			foreach ($realSubs as $sub)
			{
				// Is it the first or the second contact?
				if ($sub->enabled && ($sub->contact_flag == 0))
				{
					// First contact
					$data = array(
						'contact_flag'  => 1,
						'first_contact' => $mNow
					);
					$result = $this->sendEmail($sub, 'first');
				}
				elseif ($sub->enabled && ($sub->contact_flag == 1))
				{
					// Second and final contact
					$data = array(
						'contact_flag'   => 2,
						'second_contact' => $mNow
					);
					$result = $this->sendEmail($sub, 'second');
				}
				elseif (!$sub->enabled)
				{
					$data = array(
						'contact_flag'  => 3,
						'after_contact' => $mNow
					);
					$result = $this->sendEmail($sub, 'after');
				}
				else
				{
					continue;
				}

				if ($result)
				{
					$this->out("\t\t#{$sub->akeebasubs_subscription_id} SENT");

					$table = $subsModel->getClone();
					$table->find($sub->akeebasubs_subscription_id);
					$table->setState('_dontNotify', true);
					$table->save($data);
				}
				else
				{
					$this->out("\t\t#{$sub->akeebasubs_subscription_id} FAILED");
				}
			}
		}
	}

	/**
	 * Sends a notification email to the user
	 *
	 * @param   Subscriptions  $row           The subscription row
	 * @param   bool           $firstContact  Is this the first time we contact the user?
	 *
	 * @return  bool
	 */
	private function sendEmail($row, $firstContact)
	{
		// Get the user object
		$user = JFactory::getUser($row->user_id);

		$type = $firstContact ? 'first' : 'second';

		// Get a preloaded mailer
		$key	 = 'plg_system_asexpirationnotify_' . $type;
		$mailer	 = Email::getPreloadedMailer($row, $key);

		if ($mailer === false)
		{
			return false;
		}

		$mailer->addRecipient($user->email);

		$result = $mailer->Send();

		if (($result instanceof Exception) || ($result === false))
		{
			return false;
		}

		return true;
	}

	function memUsage()
	{
		if (function_exists('memory_get_usage'))
		{
			$size	 = memory_get_usage();
			$unit	 = array('b', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb');
			return @round($size / pow(1024, ($i		 = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
		}
		else
		{
			return "(unknown)";
		}
	}

	function peakMemUsage()
	{
		if (function_exists('memory_get_peak_usage'))
		{
			$size	 = memory_get_peak_usage();
			$unit	 = array('b', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb');
			return @round($size / pow(1024, ($i		 = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
		}
		else
		{
			return "(unknown)";
		}
	}

}
JApplicationCli::getInstance('AkeebaSubscriptionsExpirationNotifyApp')->execute();
