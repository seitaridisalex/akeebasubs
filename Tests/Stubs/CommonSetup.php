<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Stubs;

use Akeeba\Subscriptions\Admin\Model\TaxConfig;
use FOF30\Container\Container;
use JFactory;
use JUser;
use JUserHelper;

abstract class CommonSetup
{
	/** @var   array  Known users we have already created */
	protected static $users = [];

	/**
	 * Initialisation of the site for testing purposes
	 */
	public static function masterSetup()
	{
		self::importSQL();
		self::applyTaxRulesComponentConfig();
	}

	/**
	 * Get a list of pre-fabricated Joomla! users we're using throughout our tests
	 *
	 * @return  JUser[]
	 */
	public static function getUsers()
	{
		if (empty(self::$users))
		{
			// Create users afresh
			self::$users = [
				'guest'     => new JUser(),
				'user1'     => new JUser(1000),
				'user2'     => new JUser(1001),
				'user3'     => new JUser(1002),
				'business'  => new JUser(1010),
				'forcedvat' => new JUser(1011),
				'guineapig' => new JUser(1020),
			];
		}

		return self::$users;
	}

	/**
	 * Imports the Tests/_data/initialise.sql file which contains our basic testing environment
	 */
	protected static function importSQL()
	{
		// Load the raw SQL
		$fileName = __DIR__ . '/../_data/initialise.sql';
		$sql      = file_get_contents($fileName);

		// Chop it into distinct queries
		$db          = JFactory::getDbo();
		$sqlCommands = $db->splitSql($sql);

		// Start a transaction
		$db->transactionStart();

		foreach ($sqlCommands as $query)
		{
			$query = trim($query);

			if (empty($query))
			{
				continue;
			}

			try
			{
				$db->setQuery($query)->execute();
			}
			catch (\Exception $e)
			{
				echo "!! SQL import error\n\nSQL query:\n";
				echo $query . "\n\nError message:\n";
				echo $e->getMessage() . "\n\n";
				die;
			}
		}

		// Commit the transaction
		$db->transactionCommit();
	}

	/**
	 * Set up the component configuration side of the tax rules. The actual tax rules are in initialise.sql
	 */
	protected static function applyTaxRulesComponentConfig()
	{
		$container = Container::getInstance('com_akeebasubs', [
			'tempInstance' => true,
			'factoryClass' => '\\FOF30\\Factory\\SwitchFactory'
		], 'admin');
		/** @var TaxConfig $taxConfigModel */
		$taxConfigModel = new TaxConfig($container);

		foreach ([
					 'novatcalc'           => 0,
					 'akeebasubs_level_id' => 0,
					 'country'             => 'CY',
					 'taxrate'             => '19',
					 'viesreg'             => 1,
					 'showvat'             => 0,
				 ] as $key => $value)
		{
			$taxConfigModel->setState($key, $value);
		}

		$taxConfigModel->applyComponentConfiguration();
	}

	/**
	 * Delete a Joomla! user by username
	 *
	 * @param   string $username The username of the user to delete
	 *
	 * @return  void
	 */
	protected static function userDelete($username)
	{
		$userId = JUserHelper::getUserId($username);

		if ($userId == 0)
		{
			return;
		}

		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
					->delete('#__users')
					->where($db->qn('id') . ' = ' . $db->q($userId));
		$db->setQuery($query)->execute();

		$query = $db->getQuery(true)
					->delete('#__user_usergroup_map')
					->where($db->qn('user_id') . ' = ' . $db->q($userId));
		$db->setQuery($query)->execute();

		$query = $db->getQuery(true)
					->delete('#__user_profiles')
					->where($db->qn('user_id') . ' = ' . $db->q($userId));
		$db->setQuery($query)->execute();
	}

	/**
	 * Create a Joomla! user
	 *
	 * @param   array $userInfo The information of the user being created
	 *
	 * @return  JUser  The newly created user
	 */
	protected static function userCreate(array $userInfo)
	{
		$user = new JUser();
		$user->bind($userInfo);
		$user->save();

		return $user;
	}
}