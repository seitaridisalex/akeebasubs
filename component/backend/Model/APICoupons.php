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
use JText;

/**
 * Model class for coupon generation API. This is used to remotely and automatically create coupon codes based on
 * predefined criteria and allowances provided to partners of your site.
 *
 * @property int     $akeebasubs_apicoupon_id
 * @property string  $title
 * @property string  $key
 * @property string  $password
 * @property int     $creation_limit
 * @property array   $subscriptions
 * @property int     $subscription_limit
 * @property string  $type
 * @property float   $value
 * @property int     $value_limit
 *
 * @method  $this  akeebasubs_apicoupon_id() akeebasubs_apicoupon_id(int $v)
 * @method  $this  title() title(string $v)
 * @method  $this  key() key(string $v)
 * @method  $this  password() password(string $v)
 * @method  $this  creation_limit() creation_limit(int $v)
 * @method  $this  subscriptions() subscriptions(array $v)
 * @method  $this  subscription_limit() subscription_limit(int $v)
 * @method  $this  type() type(string $v)
 * @method  $this  value() value(float $v)
 * @method  $this  value_limit() value_limit(int $v)
 */
class APICoupons extends DataModel
{
	use Mixin\Assertions, Mixin\ImplodedArrays, Mixin\ImplodedLevels;

	/**
	 * Public constructor. Overrides the parent constructor.
	 *
	 * @see DataModel::__construct()
	 *
	 * @param   Container $container The configuration variables to this model
	 * @param   array     $config    Configuration values for this model
	 *
	 * @throws \FOF30\Model\DataModel\Exception\NoTableColumns
	 */
	public function __construct(Container $container, array $config = array())
	{
		$config['tableName'] = '#__akeebasubs_apicoupons';

		parent::__construct($container, $config);

        $this->autoChecks = false;
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
		$this->assertNotEmpty($this->title, 'COM_AKEEBASUBS_APICOUPONS_ERR_TITLE');

		if (!$this->key)
		{
			$this->key = md5(microtime());
		}

		if (!$this->password)
		{
			$this->password = md5(microtime());
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

		parent::check();

		return $this;
	}

	/**
	 * Generate a coupon via an API method
	 *
	 * @param   string  $APIKey      API key for the partner
	 * @param   string  $APIPassword API password for the partner
     * @param   string  $notes       Additional notes for this coupon
	 *
	 * @return  array  Only contains a single key, "coupon" with the generated coupon code or "error" with the error
	 *                 message if the operation failed.
	 */
	public function createCoupon($APIKey, $APIPassword, $notes = '')
	{
		// Do I have a key/pwd pair?
		if (!$APIKey || !$APIPassword)
		{
			return array('error' => JText::_('COM_AKEEBASUBS_APICOUPONS_INVALID_CREDENTIALS'));
		}

		$this->addBehaviour('Filters');
		$this->setState('key', $APIKey);
		$this->setState('password', $APIPassword);

		try
		{
			$item = $this->firstOrFail();
		}
		catch (\RuntimeException $e)
		{
			return array('error' => JText::_('COM_AKEEBASUBS_APICOUPONS_INVALID_CREDENTIALS'));
		}

		// Are they valid?
		if (!$item->akeebasubs_apicoupon_id || !$item->enabled)
		{
			return array('error' => JText::_('COM_AKEEBASUBS_APICOUPONS_INVALID_CREDENTIALS'));
		}

		// Do I hit a limit?
		if (!$this->performApiChecks($item))
		{
			return array('error' => JText::_('COM_AKEEBASUBS_APICOUPONS_LIMIT_EXCEEDED'));
		}

		// If I'm here, I'm clear to go
		\JLoader::import('joomla.user.helper');
		/** @var Coupons $coupon */
		$coupon = $this->container->factory->model('Coupons')->tmpInstance();
		$coupon->clearState()->reset(true, true);

		$data['akeebasubs_apicoupon_id'] = $item->akeebasubs_apicoupon_id;
		$data['title']                   = 'API coupon for: ' . $item->title;
		$data['coupon']                  = strtoupper(\JUserHelper::genRandomPassword(10));
		$data['subscriptions']           = $item->subscriptions;
        $data['params']['notes']         = $notes;

		// By default I want the coupon to be single-use
		$data['hitslimit'] = 1;
		$data['userhits']  = 1;

		$data['type']  = $item->type;
		$data['value'] = $item->value;

		if (!$coupon->save($data))
		{
			return array('error' => JText::_('COM_AKEEBASUBS_APICOUPONS_COUPON_ERROR'));
		}

		return array('coupon' => $coupon->coupon);
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
	 * Make sure the $apiCouponRecord parameters allow us to create one more new coupon code
	 *
	 * @param   APICoupons $apiCouponRecord The API Coupon record to check
	 *
	 * @return  bool  True if you can create one more new coupon code
	 */
	protected function performApiChecks(APICoupons $apiCouponRecord)
	{
		$db = JFactory::getDbo();

		if ($apiCouponRecord->creation_limit)
		{
			$query = $db->getQuery(true)
			            ->select('COUNT(*)')
			            ->from('#__akeebasubs_coupons')
			            ->where('akeebasubs_apicoupon_id = ' . $apiCouponRecord->akeebasubs_apicoupon_id);

			if ($db->setQuery($query)->loadResult() >= $apiCouponRecord->creation_limit)
			{
				return false;
			}
		}

		if ($apiCouponRecord->subscription_limit)
		{
			$query   = $db->getQuery(true)
			              ->select('akeebasubs_coupon_id')
			              ->from('#__akeebasubs_coupons')
			              ->where('akeebasubs_apicoupon_id = ' . $apiCouponRecord->akeebasubs_apicoupon_id);
			$coupons = $db->setQuery($query)->loadColumn();

			if ($coupons)
			{
				$query = $db->getQuery(true)
				            ->select('COUNT(*)')
				            ->from('#__akeebasubs_subscriptions')
				            ->where('akeebasubs_coupon_id IN(' . implode(',', $coupons) . ')');
				if ($db->setQuery($query)->loadResult() >= $apiCouponRecord->subscription_limit)
				{
					return false;
				}
			}
		}

		if ($apiCouponRecord->value_limit)
		{
			$query   = $db->getQuery(true)
			              ->select('akeebasubs_coupon_id')
			              ->from('#__akeebasubs_coupons')
			              ->where('akeebasubs_apicoupon_id = ' . $apiCouponRecord->akeebasubs_apicoupon_id);
			$coupons = $db->setQuery($query)->loadColumn();

			if ($coupons)
			{
				$query = $db->getQuery(true)
				            ->select('SUM(net_amount) as total_amount, COUNT(*) as total')
				            ->from('#__akeebasubs_subscriptions')
				            ->where('akeebasubs_coupon_id IN(' . implode(',', $coupons) . ')');
				$sub   = $db->setQuery($query)->loadObject();

				if ($apiCouponRecord->type == 'value')
				{
					// Did I hit the limit using "fixed" discount value (ie 5$ off)?
					if (($sub->total * $apiCouponRecord->value) >= $apiCouponRecord->value_limit)
					{
						return false;
					}
				}
				else
				{
					// Did I hit the limit using % discount value (ie 15% off)?
					if (($sub->total_amount * $apiCouponRecord->value / 100) >= $apiCouponRecord->value_limit)
					{
						return false;
					}
				}
			}
		}

		return true;
	}

    /**
     * Given an APICoupon record, it returns the limits and the current usage
     *
     * @param   string $APIKey      API key for the partner
     * @param   string $APIPassword API password for the partner
     *
     * @return array
     */
    public function getApiLimits($APIKey, $APIPassword)
    {
        // Do I have a key/pwd pair?
        if (!$APIKey || !$APIPassword)
        {
            return array('error' => JText::_('COM_AKEEBASUBS_APICOUPONS_INVALID_CREDENTIALS'));
        }

        $this->addBehaviour('Filters');
        $this->setState('key', $APIKey);
        $this->setState('password', $APIPassword);

        try
        {
            $item = $this->firstOrFail();
        }
        catch (\RuntimeException $e)
        {
            return array('error' => JText::_('COM_AKEEBASUBS_APICOUPONS_INVALID_CREDENTIALS'));
        }

        // Are they valid?
        if (!$item->akeebasubs_apicoupon_id || !$item->enabled)
        {
            return array('error' => JText::_('COM_AKEEBASUBS_APICOUPONS_INVALID_CREDENTIALS'));
        }

        $db     = JFactory::getDbo();
        $result = array(
            'type'         => 'unlimited',   // Type of the limit
            'subscription' => '',   // If the limit is binded to a specific subscription
            'current'      => 0,    // Current usage
            'limit'        => 0     // Usage limit
        );

        // Let's get the title of all the subscriptions
        if($item->subscriptions)
        {
            /** @var Levels $level */
            $level  = $this->container->factory->model('Levels')->tmpInstance();
            $levels = $level->id($item->subscriptions)->get();

            $result['subscription'] = $levels->implode('title', ', ');
        }

        if ($item->creation_limit)
        {
            $query = $db->getQuery(true)
                        ->select('COUNT(*)')
                        ->from('#__akeebasubs_coupons')
                        ->where('akeebasubs_apicoupon_id = ' . $item->akeebasubs_apicoupon_id);

            $result['type']         = 'creation_limit';
            $result['current']      = $db->setQuery($query)->loadResult();
            $result['limit']        = $item->creation_limit;

            return $result;
        }

        if ($item->subscription_limit)
        {
            $query = $db->getQuery(true)
                        ->select('akeebasubs_coupon_id')
                        ->from('#__akeebasubs_coupons')
                        ->where('akeebasubs_apicoupon_id = ' . $item->akeebasubs_apicoupon_id);

            $coupons = $db->setQuery($query)->loadColumn();

            if ($coupons)
            {
                $query = $db->getQuery(true)
                            ->select('COUNT(*)')
                            ->from('#__akeebasubs_subscriptions')
                            ->where('akeebasubs_coupon_id IN(' . implode(',', $coupons) . ')');

                $result['type']         = 'subscription_limit';
                $result['current']      = $db->setQuery($query)->loadResult();
                $result['limit']        = $item->subscription_limit;

                return $result;
            }
        }

        if ($item->value_limit)
        {
            $query = $db->getQuery(true)
                        ->select('akeebasubs_coupon_id')
                        ->from('#__akeebasubs_coupons')
                        ->where('akeebasubs_apicoupon_id = ' . $item->akeebasubs_apicoupon_id);

            $coupons = $db->setQuery($query)->loadColumn();

            if ($coupons)
            {
                $query = $db->getQuery(true)
                            ->select('SUM(net_amount) as total_amount, COUNT(*) as total')
                            ->from('#__akeebasubs_subscriptions')
                            ->where('akeebasubs_coupon_id IN(' . implode(',', $coupons) . ')');
                $sub   = $db->setQuery($query)->loadObject();

                if ($item->type == 'value')
                {
                    $result['type']         = 'value_limit_value';
                    $result['current']      = $sub->total * $item->value;
                    $result['limit']        = $item->value_limit;

                    return $result;
                }
                else
                {
                    $result['type']         = 'value_limit_perc';
                    $result['current']      = ($sub->total_amount * $item->value / 100);
                    $result['limit']        = $item->value_limit;

                    return $result;
                }
            }
        }

        return $result;
    }
}