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
use JDate;
use JLoader;
use JText;

/**
 * Model for discount Coupons
 *
 * Fields:
 *
 * @property  int     $akeebasubs_coupon_id
 * @property  int     $akeebasubs_apicoupon_id
 * @property  string  $title
 * @property  string  $coupon
 * @property  string  $publish_up
 * @property  string  $publish_down
 * @property  int[]   $subscriptions
 * @property  int     $user
 * @property  string  $email
 * @property  array   $params
 * @property  int     $hitslimit
 * @property  int     $userhits
 * @property  int[]   $usergroups
 * @property  string  $type
 * @property  float   $value
 * @property  int     $enabled
 * @property  int     $ordering
 * @property  string  $created_on
 * @property  int     $created_by
 * @property  string  $modified_on
 * @property  int     $modified_by
 * @property  string  $locked_on
 * @property  int     $locked_by
 * @property  int     $hits
 *
 * Filters:
 *
 * @method  $this  search()                   search(string $search)
 * @method  $this  akeebasubs_coupon_id()     akeebasubs_coupon_id(int $v)
 * @method  $this  akeebasubs_apicoupon_id()  akeebasubs_apicoupon_id(int $v)
 * @method  $this  title()                    title(string $v)
 * @method  $this  coupon()                   coupon(string $v)
 * @method  $this  publish_up()               publish_up(string $v)
 * @method  $this  publish_down()             publish_down(string $v)
 * @method  $this  subscriptions()            subscriptions(int $v)
 * @method  $this  user()                     user(int $v)
 * @method  $this  email()                    email(string $v)
 * @method  $this  hitslimit()                hitslimit(int $v)
 * @method  $this  userhits()                 userhits(int $v)
 * @method  $this  usergroups()               usergroups(int $v)
 * @method  $this  type()                     type(string $v)
 * @method  $this  value()                    value(float $v)
 * @method  $this  enabled()                  enabled(int $v)
 * @method  $this  ordering()                 ordering(int $v)
 * @method  $this  created_on()               created_on(string $v)
 * @method  $this  created_by()               created_by(int $v)
 * @method  $this  modified_on()              modified_on(string $v)
 * @method  $this  modified_by()              modified_by(int $v)
 * @method  $this  locked_on()                locked_on(string $v)
 * @method  $this  locked_by()                locked_by(int $v)
 * @method  $this  hits()                     hits(int $v)
 * @method  $this  skipOnProcessList()        skipOnProcessList(bool $v)
 *
 * Relations:
 *
 * @property-read  APICoupons  $apiCoupon
 * @property-read  Users       $forUser
 */
class Coupons extends DataModel
{
	use Mixin\JsonData, Mixin\Assertions, Mixin\DateManipulation, Mixin\ImplodedArrays, Mixin\ImplodedLevels;

	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		// Always load the Filters behaviour
		$this->addBehaviour('Filters');

		$this->hasOne('apiCoupon', 'APICoupons', 'akeebasubs_apicoupon_id', 'akeebasubs_apicoupon_id');
		$this->hasOne('forUser', 'Users', 'user', 'user_id');
	}

	/**
	 * Implement custom filters
	 *
	 * @param   \JDatabaseQuery  $q               The query to modify
	 * @param   bool             $overrideLimits  Should I override limits?
	 */
	public function onAfterBuildQuery(\JDatabaseQuery $q, $overrideLimits = false)
	{
		$search = $this->getState('search', null, 'string');

		if (!empty($search))
		{
			$q->where(
				"(" . $q->qn('title') . ' LIKE ' . $q->q("%$search%") . ") OR (" .
				$q->qn('coupon') . ' LIKE ' . $q->q("%$search%") . ")"
			);
		}
	}

    public function onBeforeSave(&$data)
    {
        $params = $this->params;

        if(isset($data['notes']))
        {
            $params['notes'] = $data['notes'];
            unset($data['notes']);
        }

        $this->params = $params;
    }

	/**
	 * Check the data for validity.
	 *
	 * @return  static  Self, for chaining
	 *
	 * @throws \RuntimeException  When the data bound to this record is invalid
	 */
	public function check()
	{
		// Check for title
		$this->assertNotEmpty($this->title, 'COM_AKEEBASUBS_COUPON_ERR_TITLE');

		// Check for coupon code
		$this->assertNotEmpty($this->coupon, 'COM_AKEEBASUBS_COUPON_ERR_COUPON');

		// Normalize coupon code to uppercase
		$this->coupon = strtoupper($this->coupon);

		// Assign sensible publish_up and publish_down settings
		JLoader::import('joomla.utilities.date');

		// Normalise the publish up / down dates
		$this->publish_up = $this->normaliseDate($this->publish_up, '2001-01-01 00:00:00');
		$this->publish_down = $this->normaliseDate($this->publish_down, '2038-01-18 00:00:00');
		list($this->publish_up, $this->publish_down) = $this->sortPublishDates($this->publish_up, $this->publish_down);

		// Make sure the specified user (if any) exists
		if (!empty($this->user))
		{
			$userObject = \JFactory::getUser($this->user);
			$this->user = null;

			if (is_object($userObject))
			{
				if ($userObject->id > 0)
				{
					$this->user = $userObject->id;
				}
			}
		}

		// Check the hits limit
		if ($this->hitslimit <= 0)
		{
			$this->hitslimit = 0;
		}

		// Check the type
		if (!in_array($this->type, array('value', 'percent')))
		{
			$this->type = 'value';
		}

		// Check value
		if ($this->value < 0)
		{
			throw new \RuntimeException(JText::_('COM_AKEEBASUBS_COUPON_ERR_VALUE'));
		}

		if (($this->value > 100) && ($this->type == 'percent'))
		{
			$this->value = 100;
		}

		return $this;
	}

	/**
	 * Converts the loaded comma-separated list of user groups into an array
	 *
	 * @param   string  $value  The comma-separated list
	 *
	 * @return  array  The exploded array
	 */
	protected function getUsergroupsAttribute($value)
	{
		return $this->getAttributeForImplodedArray($value);
	}

	/**
	 * Converts the array of user groups into a comma separated list
	 *
	 * @param   array  $value  The array of values
	 *
	 * @return  string  The imploded comma-separated list
	 */
	protected function setUsergroupsAttribute($value)
	{
		return $this->setAttributeForImplodedArray($value);
	}

	/**
	 * Converts the loaded comma-separated list of subscription levels into an array
	 *
	 * @param   string  $value  The comma-separated list
	 *
	 * @return  array  The exploded array
	 */
	protected function getSubscriptionsAttribute($value)
	{
		return $this->getAttributeForImplodedArray($value);
	}

	/**
	 * Converts the array of subscription levels into a comma separated list
	 *
	 * @param   array  $value  The array of values
	 *
	 * @return  string  The imploded comma-separated list
	 */
	protected function setSubscriptionsAttribute($value)
	{
		return $this->setAttributeForImplodedLevels($value);
	}

    /**
     * Decode the JSON-encoded params field into an associative array when loading the record
     *
     * @param   string  $value  JSON data
     *
     * @return  array  The decoded array
     */
    protected function getParamsAttribute($value)
    {
        return $this->getAttributeForJson($value);
    }

    /**
     * Encode the params array field into a JSON-encoded string when saving the record
     *
     * @param   array  $value  The array
     *
     * @return  string  The JSON-encoded data
     */
    protected function setParamsAttribute($value)
    {
        return $this->setAttributeForJson($value);
    }

	/**
	 * Post-process the loaded items list. Used to implement automatic expiration of coupons.
	 *
	 * @param   Coupons[]  $resultArray
	 */
	protected function onAfterGetItemsArray(&$resultArray)
	{
		// Implement the coupon automatic expiration
		if (empty($resultArray))
		{
			return;
		}

		if ($this->getState('skipOnProcessList', 0))
		{
			return;
		}

		foreach ($resultArray as $index => &$row)
		{
			$this->publishByDate($row);
		}
	}
}