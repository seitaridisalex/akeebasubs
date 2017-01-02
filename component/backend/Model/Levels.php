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
use JFactory;

/**
 * Model for subscription levels
 *
 * Fields:
 *
 * @property  int       $akeebasubs_level_id
 * @property  string    $title
 * @property  string    $slug
 * @property  string    $image
 * @property  string    $description
 * @property  int       $duration
 * @property  float     $price
 * @property  float     $signupfee
 * @property  string    $ordertext
 * @property  string    $orderurl
 * @property  string    $canceltext
 * @property  string    $cancelurl
 * @property  bool      $only_once
 * @property  bool      $recurring
 * @property  bool      $forever
 * @property  int       $akeebasubs_levelgroup_id
 * @property  int       $access
 * @property  string    $fixed_date
 * @property  string[]  $payment_plugins
 * @property  string    $renew_url
 * @property  string    $content_url
 * @property  array     $params
 * @property  int       $notify1
 * @property  int       $notify2
 * @property  int       $notifyafter
 *
 * Filters:
 *
 * @method  $this  akeebasubs_level_id()       akeebasubs_level_id(int $v)
 * @method  $this  title()                     title(string $v)
 * @method  $this  slug()                      slug(string $v)
 * @method  $this  image()                     image(string $v)
 * @method  $this  description()               description(string $v)
 * @method  $this  duration()                  duration(int $v)
 * @method  $this  price()                     price(float $v)
 * @method  $this  signupfee()                 signupfee(float $v)
 * @method  $this  ordertext()                 ordertext(string $v)
 * @method  $this  orderurl()                  orderurl(string $v)
 * @method  $this  canceltext()                canceltext(string $v)
 * @method  $this  cancelurl()                 cancelurl(string $v)
 * @method  $this  only_once()                 only_once(bool $v)
 * @method  $this  recurring()                 recurring(bool $v)
 * @method  $this  forever()                   forever(bool $v)
 * @method  $this  akeebasubs_levelgroup_id()  akeebasubs_levelgroup_id(int $v)
 * @method  $this  access()                    access(int $v)
 * @method  $this  fixed_date()                fixed_date(string $v)
 * @method  $this  payment_plugins()           payment_plugins(string $v)
 * @method  $this  renew_url()                 renew_url(string $v)
 * @method  $this  content_url()               content_url(string $v)
 * @method  $this  enabled()                   enabled(bool $v)
 * @method  $this  ordering()                  ordering(int $v)
 * @method  $this  created_on()                created_on(string $v)
 * @method  $this  created_by()                created_by(int $v)
 * @method  $this  modified_on()               modified_on(string $v)
 * @method  $this  modified_by()               modified_by(int $v)
 * @method  $this  locked_on()                 locked_on(string $v)
 * @method  $this  locked_by()                 locked_by(int $v)
 * @method  $this  notify1()                   notify1(int $v)
 * @method  $this  notify2()                   notify2(int $v)
 * @method  $this  notifyafter()               notifyafter(int $v)
 * @method  $this  levelgroup()                levelgroup(int $v)
 * @method  $this  access_user_id()            access_user_id(int $v)
 * @method  $this  id()                        id(mixed $v)
 *
 */
class Levels extends DataModel
{
	use Mixin\Assertions, Mixin\ImplodedArrays, Mixin\JsonData;

	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		$this->addBehaviour('Filters');

		$this->blacklistFilters(['only_once']);
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
		$user      = \JFactory::getUser();

		$access_user_id = $this->getState('access_user_id', null);

		if (!is_null($access_user_id))
		{
			$levels = \JFactory::getUser($access_user_id)->getAuthorisedViewLevels();

			if (!empty($levels))
			{
				$levels = array_map(array($this->getDbo(), 'quote'), $levels);

				$query->where($db->qn('access') . ' IN (' . implode(',', $levels) . ')');
			}
		}

		$subIDs = array();

		$only_once = $this->getState('only_once', null);

		if ($only_once && $user->id)
		{
			/** @var Subscriptions $subscriptionsModel */
			$subscriptionsModel = $this->container->factory
				->model('Subscriptions')->tmpInstance();

			$mySubscriptions = $subscriptionsModel
				->user_id($user->id)
				->paystate('C')
				->get(true);

			if ($mySubscriptions->count())
			{
				foreach ($mySubscriptions as $sub)
				{
					$subIDs[] = $sub->akeebasubs_level_id;
				}
			}

			$subIDs = array_unique($subIDs);
		}

		if ($only_once && $user->id)
		{
			if (count($subIDs))
			{
				$query->where(
					'(' .
						'(' . $db->qn('only_once') . ' = ' . $db->q(0) . ')' .
						' OR ' .
						'(' .
							'(' . $db->qn('only_once') . ' = ' . $db->q(1) . ')'
							. ' AND ' .
							'(' . $db->qn('akeebasubs_level_id') . ' NOT IN ' . '(' . implode(',', $subIDs) . ')' . ')'
						. ')' .
					')'
				);
			}
		}

		$search = $this->getState('search', null);

		if ($search)
		{
			$search = '%' . $search . '%';
			$query->where($db->qn('description') . ' LIKE ' . $db->q($search));
		}

		// Filter by IDs
		$ids = $this->getState('id', null);

		if (is_array($ids))
		{
			$temp = '';

			foreach ($ids as $id)
			{
				$id = (int) $id;

				if ($id > 0)
				{
					$temp .= $id . ',';
				}
			}

			if (empty($temp))
			{
				$temp = ' ';
			}

			$ids = substr($temp, 0, - 1);
		}
		elseif (is_string($ids) && (strpos($ids, ',') !== false))
		{
			$ids  = explode(',', $ids);
			$temp = '';

			foreach ($ids as $id)
			{
				$id = (int) $id;

				if ($id > 0)
				{
					$temp .= $id . ',';
				}
			}

			if (empty($temp))
			{
				$temp = ' ';
			}

			$ids = substr($temp, 0, - 1);
		}
		elseif (is_numeric($ids) || is_string($ids))
		{
			$ids = (int) $ids;
		}
		else
		{
			$ids = '';
		}

		if ($ids)
		{
			$query->where($db->qn('akeebasubs_level_id') . ' IN (' . $ids . ')');
		}

		$levelgroup = $this->getState('levelgroup', null, 'int');

		if (is_numeric($levelgroup))
		{
			$query->where($db->qn('akeebasubs_levelgroup_id') . ' = ' . (int) $levelgroup);
		}

		$order = $this->getState('filter_order', 'akeebasubs_level_id', 'cmd');

		if (!in_array($order, array_keys($this->getData())))
		{
			$order = 'akeebasubs_level_id';
		}

		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order . ' ' . $dir);
	}

	public function check()
	{
		$result = true;

		// Require a title
		$this->assertNotEmpty($this->title, 'COM_AKEEBASUBS_LEVEL_ERR_TITLE');

		// Make sure the title is unique
		$existingItems = $this->getClone()->setIgnoreRequest(true)->savestate(false)
		                      ->title([
								  'method' => 'exact',
								  'value' => $this->title
							  ])
		                      ->get(true);

		if ($existingItems->count())
		{
			$count = 0;
			$k     = $this->getKeyName();

			foreach ($existingItems as $item)
			{
				if ($item->$k != $this->$k)
				{
					$count ++;
				}
			}

			if ($count != 0)
			{
				$this->title .= ' ' . JFactory::getDate()->format(\JText::_('DATE_FORMAT_LC4'));
			}

			//$this->assert($count == 0, 'COM_AKEEBASUBS_LEVEL_ERR_TITLEUNIQUE');
		}

		// Create a new or sanitise an existing slug
		if (empty($this->slug))
		{
			// Auto-fetch a slug
			$this->slug = \JApplicationHelper::stringURLSafe($this->title);
		}
		else
		{
			// Make sure nobody adds crap characters to the slug
			$this->slug = \JApplicationHelper::stringURLSafe($this->slug);
		}

		// Look for a similar slug
		$existingItems = $this->getClone()->setIgnoreRequest(true)->savestate(false)
								->slug([
									'method' => 'exact',
									'value' => $this->slug
								])
								->get(true);

		if ($existingItems->count())
		{
			$count = 0;
			$k     = $this->getKeyName();

			foreach ($existingItems as $item)
			{
				if ($item->$k != $this->$k)
				{
					$count ++;
				}
			}

			if ($count != 0)
			{
				$this->slug .= ' ' . JFactory::getDate()->toUnix();
			}

			//$this->assert($count == 0, 'COM_AKEEBASUBS_LEVEL_ERR_SLUGUNIQUE');
		}

		// Do we have an image?
		$this->assertNotEmpty($this->image, 'COM_AKEEBASUBS_LEVEL_ERR_IMAGE');

		// Check the fixed expiration date and make sure it's in the future
		$nullDate = JFactory::getDbo()->getNullDate();

		if (!empty($this->fixed_date) && $this->fixed_date != $nullDate)
		{
			$jNow   = JFactory::getDate();
			$jFixed = JFactory::getDate($this->fixed_date);

			if ($jNow->toUnix() > $jFixed->toUnix())
			{
				$this->fixed_date = $nullDate;
			}
		}

		// Is the duration less than a day and this is not a forever or a fixed date subscription?
		if ($this->forever)
		{
			$this->duration = 0;
		}
		elseif (!empty($this->fixed_date) && !($this->fixed_date == $nullDate))
		{
			// We only want the duration to be a positive number or zero
			if ($this->duration < 0)
			{
				$this->duration = 0;
			}
		}
		elseif ($this->duration < 1)
		{
			$this->assert($this->duration >= 1, 'COM_AKEEBASUBS_LEVEL_ERR_LENGTH');
		}
	}

	/**
	 * Converts the loaded comma-separated list of payment plugins into an array
	 *
	 * @param   string  $value  The comma-separated list
	 *
	 * @return  array  The exploded array
	 */
	protected function getPaymentPluginsAttribute($value)
	{
		return $this->getAttributeForImplodedArray($value);
	}

	/**
	 * Converts the array of payment plugins into a comma separated list
	 *
	 * @param   array  $value  The array of values
	 *
	 * @return  string  The imploded comma-separated list
	 */
	protected function setPaymentPluginsAttribute($value)
	{
		return $this->setAttributeForImplodedArray($value);
	}

	/**
	 * Converts the loaded JSON string of params into an array
	 *
	 * @param   string  $value  The JSON string
	 *
	 * @return  array  The data array
	 */
	protected function getParamsAttribute($value)
	{
		return $this->getAttributeForJson($value);
	}

	/**
	 * Converts the array of parameters into a JSON string
	 *
	 * @param   array  $value  The array of values
	 *
	 * @return  string  The JSON string
	 */
	protected function setParamsAttribute($value)
	{
		return $this->setAttributeForJson($value);
	}

	/**
	 * Make sure I will only delete records which are not already used by existing subscriptions
	 *
	 * @param  int  $oid  The id (primary key) of the record I am about to delete
	 */
	protected function onBeforeDelete(&$oid)
	{
		$joins = array(
			array(
				'label'     => 'subscriptions',            // Used to construct the error text
				'name'      => '#__akeebasubs_subscriptions', // Foreign table
				'idfield'   => 'akeebasubs_level_id',    // Field name on this table
				'joinfield' => 'akeebasubs_level_id',    // Foreign table field
				'idalias'   => 'subscription_id',        // Used in the query
			)
		);

		$this->canDelete($oid, $joins);
	}

	/**
	 * Load all the levels inside an associative array, where the index is the
	 * title in upper case
	 *
	 * @return array|bool   array('DUMMY TITLE' => <subscription row>)
	 */
	public function createTitleLookup()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)->select('*')->from('#__akeebasubs_levels');
		$rows  = $db->setQuery($query)->loadObjectList('title');

		return array_change_key_case($rows, CASE_UPPER);
	}

	/**
	 * Load all the levels inside an associative array, where the index is the
	 * id (primary key)
	 *
	 * @return array|bool   array(123 => <subscription row>)
	 */
	public function createIdLookup()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)->select('*')->from('#__akeebasubs_levels');
		$rows  = $db->setQuery($query)->loadObjectList('akeebasubs_level_id');

		return $rows;
	}
}