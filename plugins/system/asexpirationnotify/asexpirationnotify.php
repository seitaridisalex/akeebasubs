<?php
/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

JLoader::import('joomla.plugin.plugin');

use FOF30\Container\Container;
use Akeeba\Subscriptions\Admin\Model\Levels;
use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use FOF30\Date\Date;

class plgSystemAsexpirationnotify extends JPlugin
{
	/**
	 * Should this plugin be allowed to run? True if FOF can be loaded and the Akeeba Subscriptions component is enabled
	 *
	 * @var  bool
	 */
	private $enabled = true;

	/**
	 * Public constructor. Overridden to load the language strings.
	 */
	public function __construct(& $subject, $config = array())
	{
		if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
		{
			$this->enabled = false;
		}

		// Do not run if Akeeba Subscriptions is not enabled
		JLoader::import('joomla.application.component.helper');

		if (!JComponentHelper::isEnabled('com_akeebasubs'))
		{
			$this->enabled = false;
		}

		if (!is_object($config['params']))
		{
			JLoader::import('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}

		parent::__construct($subject, $config);

		// Timezone fix; avoids errors printed out by PHP 5.3.3+ (thanks Yannick!)
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
	}

	/**
	 * Called when Joomla! is booting up and checks the subscriptions statuses.
	 * If a subscription is close to expiration, it sends out an email to the user.
	 */
	public function onAfterInitialise()
	{
		if (!$this->enabled)
		{
			return;
		}

		// Check if we need to run
		if (!$this->doIHaveToRun())
		{
			return;
		}

		$this->onAkeebasubsCronTask('expirationnotify');
	}

	public function onAkeebasubsCronTask($task, $options = array())
	{
		if (!$this->enabled)
		{
			return;
		}

		if ($task != 'expirationnotify')
		{
			return;
		}

		$defaultOptions = array(
			'time_limit' => 2,
		);

		$options = array_merge($defaultOptions, $options);

		\JLog::add("Starting Expiration Notify - Time limit {$options['time_limit']} seconds", \JLog::DEBUG, "akeebasubs.cron.expirationnotify");

		// Get today's date
		JLoader::import('joomla.utilities.date');
		$jNow = new Date();
		$now  = $jNow->toUnix();

		// Start the clock!
		$clockStart = microtime(true);

		// Get and loop all subscription levels
		/** @var Levels $levelsModel */
		$levelsModel = Container::getInstance('com_akeebasubs')->factory->model('Levels')->tmpInstance();
		$levels = $levelsModel
			->enabled(1)
			->get(true);

		// Update the last run info before sending any emails
		$this->setLastRunTimestamp();

		/** @var Levels $level */
		foreach ($levels as $level)
		{
			\JLog::add("Processing level " . $level->title, \JLog::DEBUG, "akeebasubs.cron.expirationnotify");

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
				continue;
			}

			// Get the subscriptions expiring within the next $notify1 days for
			// users which we have not contacted yet.
			$jFrom = new Date($now + 1);
			$jTo   = new Date($now + $notify1 * 24 * 3600);

			/** @var Subscriptions $subsModel */
			$subsModel = Container::getInstance('com_akeebasubs')->factory->model('Subscriptions')->tmpInstance();

			$subs1 = $subsModel->getClone()
				 ->contact_flag(0)
				 ->level($level->akeebasubs_level_id)
				 ->enabled(1)
				 ->expires_from($jFrom->toSql())
				 ->expires_to($jTo->toSql())
				 ->get(true);

			\JLog::add("First Notification - From " . $jFrom->format('Y/m/d H:i:s') . ' - To ' . $jTo->format('Y/m/d H:i:s') . ' - Contact flag 0 - Found ' . $subs1->count(), \JLog::DEBUG, "akeebasubs.cron.expirationnotify");

			// Get the subscriptions expiring within the next $notify2 days for
			// users which we have contacted only once
			$subs2 = array();

			if ($notify2 > 0)
			{
				$jFrom = new Date($now + 1);
				$jTo   = new Date($now + $notify2 * 24 * 3600);

				$subs2 = $subsModel->getClone()
					->contact_flag(1)
					->level($level->akeebasubs_level_id)
					->enabled(1)
					->expires_from($jFrom->toSql())
					->expires_to($jTo->toSql())
					->get(true);

				\JLog::add("Second Notification - From " . $jFrom->format('Y/m/d H:i:s') . ' - To ' . $jTo->format('Y/m/d H:i:s') . ' - Contact flag 1 - Found ' . $subs1->count(), \JLog::DEBUG, "akeebasubs.cron.expirationnotify");
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
				$jFrom = new Date($now - ($notifyAfter + 2) * 24 * 3600);
				$jTo   = new Date($now - $notifyAfter * 24 * 3600);

				$subs3 = $subsModel->getClone()
					->level($level->akeebasubs_level_id)
					->enabled(0)
					->expires_from($jFrom->toSql())
					->expires_to($jTo->toSql())
					->get(true);

				\JLog::add("After Expiration Notification - From " . $jFrom->format('Y/m/d H:i:s') . ' - To ' . $jTo->format('Y/m/d H:i:s') . ' - Contact flag 3 - Found ' . $subs1->count(), \JLog::DEBUG, "akeebasubs.cron.expirationnotify");
			}

			// If there are no subscriptions, bail out
			$subs1count = is_object($subs1) ? $subs1->count() : 0;
			$subs2count = is_object($subs2) ? $subs2->count() : 0;
			$subs3count = is_object($subs3) ? $subs3->count() : 0;

			if (($subs1count + $subs2count + $subs3count) == 0)
			{
				continue;
			}

			// Check is some of those subscriptions have been renewed. If so, set their contactFlag to 2
			$realSubs = array();

			foreach (array($subs1, $subs2, $subs3) as $subs)
			{
				/** @var Subscriptions $sub */
				foreach ($subs as $sub)
				{
					// Skip the subscription if the contact_flag is already 3
					if ($sub->contact_flag == 3)
					{
						\JLog::add("Skipping #" . $sub->akeebasubs_subscription_id . ', contact flag is 3', \JLog::INFO, "akeebasubs.cron.expirationnotify");

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
						\JLog::add("Skipping #" . $sub->akeebasubs_subscription_id . ', renewals found. Updating contact flag to 3', \JLog::INFO, "akeebasubs.cron.expirationnotify");

						// The user has already renewed. Don't send him an email; just update the row
						$subsModel->getClone()
						        ->find($sub->akeebasubs_subscription_id)
						        ->save([
							        'contact_flag' => 3
						        ]);

						// Timeout check -- Only if we did make a modification!
						$clockNow = microtime(true);
						$elapsed  = $clockNow - $clockStart;

						if (($options['time_limit'] > 0) && ($elapsed > $options['time_limit']))
						{
							return;
						}
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
			$jNow           = new Date();
			$mNow           = $jNow->toSql();
			$processedCount = 0;

			/** @var Subscriptions $sub */
			foreach ($realSubs as $sub)
			{
				$processedCount++;

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
					\JLog::add("I have no idea what to do with subscription #{$sub->akeebasubs_subscription_id}", \JLog::WARNING, "akeebasubs.cron.expirationnotify");

					continue;
				}

				if ($result)
				{
					\JLog::add("-- Updating subscription #{$sub->akeebasubs_subscription_id}, contact flag set to {$data['contact_flag']}", \JLog::DEBUG, "akeebasubs.cron.expirationnotify");
					$table = $subsModel->getClone();
					$table->find($sub->akeebasubs_subscription_id);
					$table->setState('_dontNotify', true);
					$table->save($data);
				}

				// Timeout check -- Only if we sent at least one email!
				$clockNow = microtime(true);
				$elapsed  = $clockNow - $clockStart;

				if (($options['time_limit'] > 0) && ($elapsed > $options['time_limit']))
				{
					$leftOvers = count($realSubs) - $processedCount;
					\JLog::add("I ran out of time. Number of subscriptions in queue left unprocessed: $leftOvers", \JLog::DEBUG, "akeebasubs.cron.expirationnotify");

					// Unset last run timestamp and return
					$this->setLastRunTimestamp(0);

					return;
				}
			}
		}
	}

	/**
	 * Fetches the com_akeebasubs component's parameters as a JRegistry instance
	 *
	 * @return JRegistry The component parameters
	 */
	private function getComponentParameters()
	{
		JLoader::import('joomla.registry.registry');
		$component = JComponentHelper::getComponent('com_akeebasubs');

		if ($component->params instanceof JRegistry)
		{
			$cparams = $component->params;
		}
		elseif (!empty($component->params))
		{
			$cparams = new JRegistry($component->params);
		}
		else
		{
			$cparams = new JRegistry('{}');
		}

		return $cparams;
	}

	/**
	 * "Do I have to run?" - the age old question. Let it be answered by checking the
	 * last execution timestamp, stored in the component's configuration.
	 */
	private function doIHaveToRun()
	{
		// Get the component parameters
		$componentParameters      = $this->getComponentParameters();

		// Is scheduling enabled?
		// WARNING: DO NOT USE $componentParameters HERE! THIS IS A PLUGIN PARAMETER NOT A COMPONENT PARAMETER
		$scheduling  = $this->params->get('scheduling', 1);

		if (!$scheduling)
		{
			return false;
		}

		// Find the next execution time (midnight GMT of the next day after the last time we ran the scheduling)
		$lastRunUnix = $componentParameters->get('plg_akeebasubs_asexpirationnotify_timestamp', 0);
		$dateInfo    = getdate($lastRunUnix);
		$nextRunUnix = mktime(0, 0, 0, $dateInfo['mon'], $dateInfo['mday'], $dateInfo['year']);
		$nextRunUnix += 24 * 3600;

		// Get the current time
		$now = time();

		// have we reached the next execution time?
		return ($now >= $nextRunUnix);
	}

	/**
	 * Saves the timestamp of this plugin's last run
	 */
	private function setLastRunTimestamp($timestamp = null)
	{
		$lastRun = is_null($timestamp) ? time() : $timestamp;
		$params  = $this->getComponentParameters();
		$params->set('plg_akeebasubs_asexpirationnotify_timestamp', $lastRun);

		$db = Container::getInstance('com_akeebasubs')->db;

		$data  = $params->toString('JSON');
		$query = $db->getQuery(true)
		            ->update($db->qn('#__extensions'))
		            ->set($db->qn('params') . ' = ' . $db->q($data))
		            ->where($db->qn('element') . ' = ' . $db->q('com_akeebasubs'))
		            ->where($db->qn('type') . ' = ' . $db->q('component'));
		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Notifies the component of the supported email keys by this plugin.
	 *
	 * @return  array
	 *
	 * @since 3.0
	 */
	public function onAKGetEmailKeys()
	{
		$this->loadLanguage();

		return array(
			'section' => $this->_name,
			'title'   => JText::_('PLG_SYSTEM_ASEXPIRATIONNOTIFY_EMAILSECTION'),
			'keys'    => array(
				'first'  => JText::_('PLG_SYSTEM_ASEXPIRATIONNOTIFY_EMAIL_FIRST'),
				'second' => JText::_('PLG_SYSTEM_ASEXPIRATIONNOTIFY_EMAIL_SECOND'),
				'after'  => JText::_('PLG_SYSTEM_ASEXPIRATIONNOTIFY_EMAIL_AFTER'),
			)
		);
	}

	/**
	 * Sends a notification email to the user
	 *
	 * @param   Subscriptions  $row   The subscription row
	 * @param   string         $type  Contact type (first, second, after)
	 *
	 * @return  bool
	 */
	private function sendEmail($row, $type)
	{
		$container = Container::getInstance('com_akeebasubs');

		// Get the user object
		$user = $container->platform->getUser($row->user_id);

		// Get a preloaded mailer
		$key    = 'plg_system_' . $this->_name . '_' . $type;
		$mailer = \Akeeba\Subscriptions\Admin\Helper\Email::getPreloadedMailer($row, $key);

		if ($mailer === false)
		{
			return false;
		}

		\JLog::add("Sending $type notification to #{$row->akeebasubs_subscription_id} @{$user->username} ($user->name <{$user->email}>)", \JLog::INFO, "akeebasubs.cron.expirationnotify");

		$mailer->addRecipient($user->email);
		$result = $mailer->Send();
		$mailer = null;

		return $result;
	}
}