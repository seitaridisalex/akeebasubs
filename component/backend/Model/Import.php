<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Model;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Helper\Format;
use FOF30\Date\Date;
use FOF30\Model\DataModel;
use FOF30\Model\Model;
use JApplicationHelper;
use JFactory;
use JLoader;
use JText;
use JUserHelper;

class Import extends Model
{
	/**
	 * Column mapping for imported data
	 *
	 * @var array
	 */
	protected $columnMap   = array();

	/**
	 * Current csv row we're going to import
	 *
	 * @var array
	 */
	protected $currentData = array();

	/**
	 * Parses a CSV file, importing every row as a new user
	 *
	 * @param   string  $file            Uploaded file
	 * @param   string  $fieldDelimiter  Fields separator, such as ";" or ","
	 * @param   string  $fieldQuotes     Field quotes such as " or '
	 *
	 * @return  int  The number of imported users.
	 */
	public function import($file, $fieldDelimiter, $fieldQuotes)
	{
		$result     = 0;
		$i          = 0;
        $errors     = array();

		if (!$file)
		{
			throw new \RuntimeException(JText::_('COM_AKEEBASUBS_USERS_IMPORT_ERR_FILE'));
		}

		// At the moment I don't need the $fieldQuotes, it seems that fgetcsv works with or without it
		//list($field, ) = $this->decodeDelimiterOptions($delimiter);

		$handle = fopen($file, 'r');

		while (true)
		{
			// Read the next line
			$line = '';

			while (!feof($handle) && (strpos($line, "\n") === false) && (strpos($line, "\r") === false))
			{
				$line .= fgets($handle, 65536);
			}

			// Past EOF and no data read? Break.
			if (empty($line) && feof($handle))
			{
				break;
			}

			// Did we read more than one line?
			if (!in_array(substr($line, -1), array("\r", "\n")))
			{
				// Get the position of linefeed and carriage return characters in the line read
				$posLF = strpos($line, "\n");
				$posCR = strpos($line, "\r");

				// Determine line ending
				if (($posCR !== false) && ($posLF !== false))
				{
					// We have both \r and \n. Are they strung together?
					if ($posLF - $posCR == 1)
					{
						// Yes. Windows/DOS line termination.
						$searchCharacter = "\r\n";
					}
					else
					{
						// Nope. It's either Mac OS Classic or UNIX. Which one?
						if ($posCR < $posLF)
						{
							// Mac OS Classic
							$searchCharacter = "\r";
						}
						else
						{
							// UNIX
							$searchCharacter = "\n";
						}
					}
				}
				elseif ($posCR !== false)
				{
					$searchCharacter = "\r";
				}
				elseif ($posLF !== false)
				{
					$searchCharacter = "\n";
				}
				else
				{
					$searchCharacter = null;
				}

				// Roll back the file
				if (!is_null($searchCharacter))
				{
					$pos = strpos($line, $searchCharacter);
					$rollback = strlen($line) - strpos($line, $searchCharacter);
					fseek($handle, -$rollback + strlen($searchCharacter), SEEK_CUR);
					// And chop the line
					$line = substr($line, 0, $pos);
				}
			}

			// Handle DOS and Mac OS classic line breaks
			$line = str_replace("\r\n", "\n", $line);
			$line = str_replace("\r", "\n", $line);
			$line = trim($line);

			if (empty($line))
			{
				continue;
			}

			// I have to use this weird structure because if an user passes an empty char as field enclosure
			// str_getcsv will return false, so I have to omit it, forcing PHP to use the function default one
			if($fieldQuotes)
			{
				$this->currentData = str_getcsv($line, $fieldDelimiter, $fieldQuotes);
			}
			else
			{
				$this->currentData = str_getcsv($line, $fieldDelimiter);
			}

			if($this->currentData === false)
			{
				break;
			}

			$i++;

			// Skip first line, there are headers in the file, so let's map them and then continue
			if($i == 1)
			{
				$this->readColumns();
				continue;
			}

			$this->performDataMapping();

			// Perform integrity checks on current line (required fields, existing subscription etc etc)
			$check = $this->performImportChecks();

			if (!$check)
			{
				$errors[] = JText::sprintf('COM_AKEEBASUBS_USERS_IMPORT_ERR_LINE', $i);

                continue;
			}

			if (!($userid = $this->importClient()))
			{
                $errors[] = JText::sprintf('COM_AKEEBASUBS_USERS_IMPORT_ERR_LINE', $i);

                continue;
			}

			if (!$this->importSubscription($userid))
			{
                $errors[] = JText::sprintf('COM_AKEEBASUBS_USERS_IMPORT_ERR_LINE', $i);

				continue;
			}

			$result++;
		}

		fclose($handle);

        // Did I had any errors?
        if($errors)
        {
            throw new \RuntimeException(implode("<br/>", $errors));
        }

		return $result;
	}

	/**
	 * Imports the user, creating if there isn't and updating the AS user table.
	 *
	 * @return  int  Joomla user_id if successful, otherwise false
	 */
	protected function importClient()
	{
		static $cache = array(
			'email'	=> array(),
			'user'	=> array(),
		);

		// No email? Get out
		if(!$this->getCsvData('email'))
		{
			return false;
		}

		$factory = $this->container->factory;

		/** @var JoomlaUsers $joomlaUser */
		$joomlaUser = $factory->model('JoomlaUsers')->tmpInstance();
		/** @var DataModel $subsUser */
		$subsUser = $factory->model('Users')->tmpInstance()->addBehaviour('Filters');

		$userId = 0;
		$email = $this->getCsvData('email');
		$username = $this->getCsvData('username');

		if (!empty($cache['email']))
		{
			if (array_key_exists($email, $cache['email']))
			{
				$userId = $cache['email'][$email]->id;
			}
		}

		if (($userId == 0) && !empty($cache['user']))
		{
			if (array_key_exists($username, $cache['user']))
			{
				$userId = $cache['user'][$username]->id;
			}
		}

		if ($userId == 0)
		{
			// Maybe the user does exist?

			try
			{
				if ($joomlaUser->reset()->email($email)->count())
				{
					$userId = $joomlaUser->getId();
				}
				elseif ($joomlaUser->reset()->username($username)->count())
				{
					$userId = $joomlaUser->getId();
				}
			}
			catch (\RuntimeException $e)
			{
				$userId = 0;
			}

			if (!empty($userId))
			{
				$cache['email'][$email] = $userId;
				$cache['user'][$username] = $userId;
			}
		}

		// No user? Let's create it
		if($userId == 0)
		{
			$params = array(
				'name'			=> $this->getCsvData('name'),
				'username'		=> $username,
				'email'			=> $email,
				'password'		=> $this->getCsvData('password'),
				'password2'		=> $this->getCsvData('password')
			);

			// Error while creating the user
			$userId = $joomlaUser->createNewUser($params);

			if (!$userId)
			{
				return false;
			}

			// Cache this user
			$cache['email'][$email] = $userId;
			$cache['user'][$username] = $userId;
		}

		// Ok, in a way or in another I have the Joomla user. Now it's time to update AS user
		$subsUser->reset();

		// Let's try loading it using Joomla id. Using the table object will assure me that I'll automatically update/create the user
		$subsUser->setState('user_id', $userId);
		$subsUser->firstOrNew();

		$updates = array(
			'user_id'			=> (int) $userId,
			'isbusiness'		=> (int) $this->getCsvData('isbusiness'),
			'businessname'		=> $this->getCsvData('businessname'),
			'occupation'		=> $this->getCsvData('occupation'),
			'vatnumber'			=> $this->getCsvData('vatnumber'),
			'viesregistered'	=> (int) $this->getCsvData('viesregistered'),
			'address1'			=> $this->getCsvData('address1'),
			'address2'			=> $this->getCsvData('address2'),
			'city'				=> $this->getCsvData('city'),
			'state'				=> $this->getCsvData('state'),
			'zip'				=> $this->getCsvData('zip'),
			'country'			=> $this->getCsvData('country'),
		);

		if (!$subsUser->save($updates))
		{
			return false;
		}

		return $userId;
	}

	/**
	 * Imports a subscription for a given user, using data coming from a CSV file
	 *
	 * @param   int     $userId     Joomla user_id of the imported user
	 *
	 * @return  bool    True if successful
	 */
	protected function importSubscription($userId)
	{
		static $levelCache = array();

		JLoader::import('joomla.application.component.helper');

		if (empty($levelCache))
		{
			$levelCache = $this->container->factory
				->model('Levels')->tmpInstance()
				->createTitleLookup();
		}

		$app        = JFactory::getApplication();
		$level      = $levelCache[strtoupper($this->getCsvData('subscription_level'))];
		$publish_up = Format::checkDateFormat($this->getCsvData('publish_up'));

		if (!$publish_up)
		{
			return false;
		}

		// Publish down
		if ($this->getCsvData('publish_down'))
		{
			$publish_down = Format::checkDateFormat($this->getCsvData('publish_down'));

			if (!$publish_down)
			{
				return false;
			}
		}
		else
		{
			$temp = strtotime('+' . $level->duration . ' days', $publish_up->toUnix());
			$publish_down = new Date($temp);
		}

		// Created on
		if($this->getCsvData('created_on'))
		{
			$created_on = Format::checkDateFormat($this->getCsvData('created_on'));

			if (!$created_on)
			{
				return false;
			}
		}
		else
		{
			$created_on = clone $publish_up;
		}

		$sub = $this->container->factory->model('Subscriptions')->tmpInstance();

		$randomString = JUserHelper::genRandomPassword();
		$hash = JApplicationHelper::getHash($randomString);

		$bind['user_id']             = $userId;
		$bind['akeebasubs_level_id'] = $level->akeebasubs_level_id;
		$bind['publish_up']          = $publish_up->toSql();
		$bind['publish_down']        = $publish_down->toSql();
		$bind['enabled']             = $this->getCsvData('enabled', 1);
		$bind['processor']           = $this->getCsvData('processor', 'import');
		$bind['processor_key']       = $this->getCsvData('processor_key', md5(microtime() . $hash));
		$bind['state']               = $this->getCsvData('status', 'C');
		$bind['net_amount']          = $this->getCsvData('net_amount', 0);
		$bind['tax_amount']          = $this->getCsvData('tax_amount', 0);
		$bind['gross_amount']        = $this->getCsvData('gross_amount', (float)$bind['net_amount'] + (float)$bind['tax_amount']);
		$bind['recurring_amount']    = $this->getCsvData('recurring_amount', $bind['gross_amount']);
		$bind['tax_percent']         = $this->getCsvData('tax_percent', (100 * (float)$bind['tax_amount'] / (float)$bind['net_amount']));
		$bind['created_on']          = $created_on->toSql();
		$bind['prediscount_amount']  = $this->getCsvData('prediscount_amount', (float)$bind['gross_amount']);
		$bind['discount_amount']     = $this->getCsvData('discount_amount', 0);
		$bind['contact_flag']        = $this->getCsvData('contact_flag', 0);

		return $sub->save($bind);
	}

	/**
	 * Decodes a single value (1,2,3) to an array containing the field delimiter and enclosure
	 *
	 * @param   int     $delimiter
	 *
	 * @return  array   [0] => field delimiter, [1] => enclosure char
	 */
	public function decodeDelimiterOptions($delimiter)
	{
		switch ($delimiter)
		{
			case 1:
				return array(',', '');
				break;

			case 2:
				return array(';', '');
				break;

			default:
				return array(';', '"');
				break;
		}
	}

	/**
	 * Performs checks on current columns got from the CSV, controlling that everything is alright
	 *
	 * @return  bool  Is everything alright?
	 */
	protected function performImportChecks()
	{
		static $cache = array();

		// Required fields as: username, email, password, name, subscription_level, publish_up
		if (
			!$this->getCsvData('username') ||
			!$this->getCsvData('email') ||
			!$this->getCsvData('password') ||
		    !$this->getCsvData('name') ||
			!$this->getCsvData('subscription_level') ||
			!$this->getCsvData('publish_up'))
		{
			return false;
		}

		if (!$cache)
		{
			$cache = $this->container->factory->model('Levels')->tmpInstance()
				->createTitleLookup();
		}

		// Is the subscrption level existing?
		if (!isset($cache[ strtoupper($this->getCsvData('subscription_level')) ]))
		{
			return false;
		}

		return true;
	}

	protected function readColumns()
	{
		for ($i = 0; $i < count($this->currentData); $i++)
		{
			$this->columnMap[$this->currentData[$i]] = $i;
		}
	}

	protected function performDataMapping()
	{
		$mapping = array();

		foreach ($this->columnMap as $column => $position)
		{
			if (!$column)
            {
                continue;
            }

            // If the row is missing some columns simply skip such column
            if(!isset($this->currentData[$position]))
            {
                continue;
            }

			$mapping[$column] = $this->currentData[$position];
		}

		$this->currentData = $mapping;
	}

	protected function getCsvData($key, $default = '')
	{
		if (isset($this->currentData[$key]))
		{
			return $this->currentData[$key];
		}

		return $default;
	}

}