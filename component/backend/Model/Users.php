<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Model;

defined('_JEXEC') or die;

use FOF30\Container\Container;
use FOF30\Model\DataModel;
use JLoader;

/**
 * Model class for Akeeba Subscriptions user data
 *
 * @property  int		$akeebasubs_user_id
 * @property  int		$user_id
 * @property  int		$isbusiness
 * @property  string	$businessname
 * @property  string	$occupation
 * @property  string	$vatnumber
 * @property  int		$viesregistered
 * @property  string	$taxauthority
 * @property  string	$address1
 * @property  string	$address2
 * @property  string	$city
 * @property  string	$state
 * @property  string	$zip
 * @property  string	$country
 * @property  array		$params
 * @property  string	$notes
 * @property  int		$needs_logout
 *
 * @method  $this  akeebasubs_user_id()  akeebasubs_user_id(int $v)
 * @method  $this  user_id()             user_id(int $v)
 * @method  $this  isbusiness()          isbusiness(bool $v)
 * @method  $this  businessname()        businessname(string $v)
 * @method  $this  occupation()          occupation(string $v)
 * @method  $this  vatnumber()           vatnumber(string $v)
 * @method  $this  viesregistered()      viesregistered(bool $v)
 * @method  $this  taxauthority()        taxauthority(string $v)
 * @method  $this  address1()            address1(string $v)
 * @method  $this  address2()            address2(string $v)
 * @method  $this  city()                city(string $v)
 * @method  $this  state()               state(string $v)
 * @method  $this  zip()                 zip(string $v)
 * @method  $this  country()             country(string $v)
 * @method  $this  notes()               notes(string $v)
 * @method  $this  needs_logout()        needs_logout(bool $v)
 * @method  $this  block()               block(bool $v)
 * @method  $this  username()            username(string $v)
 * @method  $this  name()                name(string $v)
 * @method  $this  email()               email(string $v)
 * @method  $this  search()              search(string $v)
 *
 * @property-read  JoomlaUsers		$user
 * @property-read  Subscriptions[]  $subscriptions
 */
class Users extends DataModel
{
	use Mixin\JsonData;

	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		// Always load the Filters behaviour
		$this->addBehaviour('Filters');
		$this->addBehaviour('RelationFilters');

		$this->hasOne('user', 'JoomlaUsers', 'user_id', 'id');
		$this->hasMany('subscriptions', 'Subscriptions', 'user_id', 'user_id');
		$this->with(['user']);

		// Not NULL fields which do accept 0 values should not be part of auto-checks
		$this->fieldsSkipChecks = ['isbusiness', 'viesregistered', 'needs_logout'];
	}

	/**
	 * Run the onAKUserSaveData event on the plugins before saving a row
	 *
	 * @param   array|\stdClass  $data  Source data
	 *
	 * @return  bool
	 */
	function onBeforeSave(&$data)
	{
		$pluginData = $data;

		if (is_object($data))
		{
			if ($data instanceof DataModel)
			{
				$pluginData = $data->toArray();
			}
			else
			{
				$pluginData = (array) $data;
			}
		}

		$this->container->platform->importPlugin('akeebasubs');
		$jResponse = $this->container->platform->runPlugins('onAKUserSaveData', array(&$pluginData));

		if (in_array(false, $jResponse))
		{
			throw new \RuntimeException('Cannot save user data');
		}
	}

	/**
	 * Build the SELECT query for returning records. Overridden to apply custom filters.
	 *
	 * @param   \JDatabaseQuery  $query           The query being built
	 * @param   bool             $overrideLimits  Should I be overriding the limit state (limitstart & limit)?
	 *
	 * @return  void
	 */
	public function onAfterBuildQuery(\JDatabaseQuery $query, $overrideLimits = false)
	{
		$db = $this->getDbo();

		$username = $this->getState('username', null, 'string');

		if ($username)
		{
			$this->whereHas('user', function(\JDatabaseQuery $subQuery) use($username, $db) {
				$subQuery->where($db->qn('username') . ' LIKE ' . $db->q('%' . $username . '%'));
			});
		}

		$name = $this->getState('name', null, 'string');

		if ($name)
		{
			$this->whereHas('user', function(\JDatabaseQuery $subQuery) use($name, $db) {
				$subQuery->where($db->qn('name') . ' LIKE ' . $db->q('%' . $name . '%'));
			});
		}

		$email = $this->getState('email', null, 'string');

		if ($email)
		{
			$this->whereHas('user', function(\JDatabaseQuery $subQuery) use($email, $db) {
				$subQuery->where($db->qn('email') . ' LIKE ' . $db->q('%' . $email . '%'));
			});
		}

		$block = $this->getState('block', null, 'int');

		if (!is_null($block))
		{
			$this->whereHas('user', function(\JDatabaseQuery $subQuery) use($block, $db) {
				$subQuery->where($db->qn('block') . ' = ' . $db->q($block));
			});
		}

		$search = $this->getState('search', null, 'string');

		if ($search)
		{
			$search = '%' . $search . '%';
			$query->where(
				'(' .
				'(' . $db->qn('businessname') .
				' LIKE ' . $db->q($search) . ') OR ' .
				'(' . $db->qn('occupation') .
				' LIKE ' . $db->q($search) . ') OR ' .
				'(' . $db->qn('vatnumber') .
				' LIKE ' . $db->q($search) . ') OR ' .
				'(' . $db->qn('address1') .
				' LIKE ' . $db->q($search) . ') OR ' .
				'(' . $db->qn('address2') .
				' LIKE ' . $db->q($search) . ') OR ' .
				'(' . $db->qn('city') .
				' LIKE ' . $db->q($search) . ') OR ' .
				'(' . $db->qn('state') .
				' LIKE ' . $db->q($search) . ') OR ' .
				'(' . $db->qn('zip') .
				' LIKE ' . $db->q($search) . ')'
				. ')'
			);
		}
	}

	/**
	 * Returns the merged data from the Akeeba Subscriptions' user parameters, the Joomla! user data and the Joomla!
	 * user profile data.
	 *
	 * @param   int  $user_id  The user ID to load, null to use the alredy loaded user
	 *
	 * @return  object
	 */
	public function getMergedData($user_id = null)
	{
		if (is_null($user_id))
		{
			$user_id = $this->getState('user_id', $this->user_id);
		}

		$this->find(['user_id' => $user_id]);

		// Get a legacy data set from the user parameters
		$userRow = $this->user;

		if (empty($this->user_id) || !is_object($userRow))
		{
			/** @var JoomlaUsers $userRow */
			$userRow = $this->container->factory->model('JoomlaUsers')->tmpInstance();
			$userRow->find($user_id);
		}

		// Decode user parameters
		$params = $userRow->params;

		if (!($userRow->params instanceof \JRegistry))
		{
			JLoader::import('joomla.registry.registry');
			$params = new \JRegistry($userRow->params);
		}

		$businessname = $params->get('business_name', '');

		$nativeData = array(
			'isbusiness'     => empty($businessname) ? 0 : 1,
			'businessname'   => $params->get('business_name', ''),
			'occupation'     => $params->get('occupation', ''),
			'vatnumber'      => $params->get('vat_number', ''),
			'viesregistered' => 0,
			'taxauthority'   => '',
			'address1'       => $params->get('address', ''),
			'address2'       => $params->get('address2', ''),
			'city'           => $params->get('city', ''),
			'state'          => $params->get('state', ''),
			'zip'            => $params->get('zip', ''),
			'country'        => $params->get('country', ''),
			'params'         => array()
		);

		$userData = $userRow->toArray();
		$myData = $nativeData;

		foreach (array('name', 'username', 'email') as $key)
		{
			$myData[$key] = $userData[$key];
		}

		$myData['email2'] = $userData['email'];

		unset($userData);

		if (($user_id > 0) && ($this->user_id == $user_id))
		{
			$myData = array_merge($myData, $this->toArray());

			if (is_string($myData['params']))
			{
				$myData['params'] = json_decode($myData['params'], true);

				if (is_null($myData['params']))
				{
					$myData['params'] = array();
				}
			}
		}
		else
		{
			$taxParameters = $this->container->factory->model('TaxHelper')->tmpInstance()->getTaxDefiningParameters($myData);

			$taxData = array(
				'isbusiness' => $taxParameters['vies'] ? 1 : 0,
				'city'       => $taxParameters['city'],
				'state'      => $taxParameters['state'],
				'country'    => $taxParameters['country'],
				'params'     => array()
			);

			$myData = array_merge($myData, $taxData);
		}

		// Finally, merge data coming from the plugins. Note that the
		// plugins only run when a new subscription is in progress, not
		// every time the user data loads.
		$this->container->platform->importPlugin('akeebasubs');

		$jResponse = $this->container->platform->runPlugins('onAKUserGetData', array((object)$myData));

		if (is_array($jResponse) && !empty($jResponse))
		{
			foreach ($jResponse as $pResponse)
			{
				if (!is_array($pResponse))
				{
					continue;
				}

				if (empty($pResponse))
				{
					continue;
				}

				if (array_key_exists('params', $pResponse))
				{
					if (!empty($pResponse['params']))
					{
						foreach ($pResponse['params'] as $k => $v)
						{
							$myData['params'][$k] = $v;
						}
					}

					unset($pResponse['params']);
				}

				foreach ($pResponse as $k => $v)
				{
					if (!empty($v))
					{
						$myData[$k] = $v;
					}
				}
			}
		}

		if (!isset($myData['params']))
		{
			$myData['params'] = array();
		}

		$myData['params'] = (object)$myData['params'];

		return (object)$myData;
	}

	/**
	 * Map the 'custom' data key to params
	 *
	 * @param   array|mixed $data
	 */
	protected function onBeforeBind(&$data)
	{
		if (!is_array($data))
		{
			return;
		}

		if (array_key_exists('custom', $data))
		{
			$params = json_encode($data['custom']);
			unset($data['custom']);
			$data['params'] = $params;
		}
	}

	protected function getParamsAttribute($value)
	{
		return $this->getAttributeForJson($value);
	}

	protected function setParamsAttribute($value)
	{
		return $this->setAttributeForJson($value);
	}
}