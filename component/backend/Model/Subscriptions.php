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

/**
 * The model for the subscription records
 *
 * Tio: to get the subscriptions of a particular user use the User model and its "subscriptions" relation
 *
 * Fields:
 *
 * @property  int			$akeebasubs_subscription_id	Primary key
 * @property  int			$user_id					User ID. FK to user relation.
 * @property  int			$akeebasubs_level_id		Subscription level. FK to level relation.
 * @property  string		$publish_up					Valid from date/time
 * @property  string		$publish_down				Valid to date/time
 * @property  string		$notes						Notes, displayed in admin only
 * @property  int			$enabled					Is this subscription active?
 * @property  string		$processor					Payments processor
 * @property  string		$processor_key				Unique key for the payments processor
 * @property  string		$state						Payment state (N new, P pending, C completed, X cancelled)
 * @property  float			$net_amount					Payable amount without tax
 * @property  float			$tax_amount					Tax portion of payable amount
 * @property  float			$gross_amount				Total payable amount
 * @property  float			$recurring_amount			Total payable amount for further recurring subscriptions
 * @property  float			$tax_percent				% of tax (tax_amount / net_amount)
 * @property  string		$created_on					Date/time when this subscription was created
 * @property  array 		$params						Parameters, used by custom fields and plugins
 * @property  string		$ip							IP address of the user who created this subscription
 * @property  string		$ip_country					Country of the user who created this subscription, based on IP geolocation
 * @property  int			$akeebasubs_coupon_id		Coupon code used. FK to coupon relation.
 * @property  int			$akeebasubs_upgrade_id		Upgrade rule used. FK to upgrade relation
 * @property  int			$akeebasubs_affiliate_id	NO LONGER USED IN CORE. Store an affiliate ID (plugin specific)
 * @property  float			$affiliate_comission		NO LONGER USED IN CORE. Store the commission amount of you affiliate (plugin specific)
 * @property  int			$akeebasubs_invoice_id		Invoice issues. FK to invoice relation.
 * @property  float			$prediscount_amount			Total amount, before taxes and before any discount was applied.
 * @property  float			$discount_amount			Discount amount (before taxes), applied over the prediscount_amount
 * @property  int			$contact_flag				Which expiration emails we've sent. 0 none, 1 first, 2 second, 3 after expiration
 * @property  string		$first_contact				Date/time of first expiration notification email sent.
 * @property  string		$second_contact				Date/time of second expiration notification email sent.
 * @property  string		$after_contact				Date/time of post-expiration notification email sent.
 *
 * Filters / state:
 *
 * @method  $this  akeebasubs_subscription_id()  akeebasubs_subscription_id(int $v)
 * @method  $this  akeebasubs_level_id()         akeebasubs_level_id(int $v)
 * @method  $this  notes()                       notes(string $v)
 * @method  $this  processor_key()               processor_key(string $v)
 * @method  $this  state()                       state(string $v)
 * @method  $this  net_amount()                  net_amount(float $v)
 * @method  $this  tax_amount()                  tax_amount(float $v)
 * @method  $this  gross_amount()                gross_amount(float $v)
 * @method  $this  recurring_amount()            recurring_amount(float $v)
 * @method  $this  tax_percent()                 tax_percent(float $v)
 * @method  $this  created_on()                  created_on(string $v)
 * @method  $this  akeebasubs_coupon_id()        akeebasubs_coupon_id(int $v)
 * @method  $this  akeebasubs_upgrade_id()       akeebasubs_upgrade_id(int $v)
 * @method  $this  akeebasubs_affiliate_id()     akeebasubs_affiliate_id(int $v)
 * @method  $this  affiliate_comission()         affiliate_comission(float $v)
 * @method  $this  akeebasubs_invoice_id()       akeebasubs_invoice_id(int $v)
 * @method  $this  prediscount_amount()          prediscount_amount(float $v)
 * @method  $this  discount_amount()             discount_amount(float $v)
 * @method  $this  contact_flag()                contact_flag(bool $v)
 * @method  $this  first_contact()               first_contact(string $v)
 * @method  $this  second_contact()              second_contact(string $v)
 * @method  $this  after_contact()               after_contact(string $v)
 * @method  $this _noemail()                     _noemail(bool $v)          	 Do not send email on save when true (resets after successful save)
 * @method  $this refresh()  					 refresh(int $v)            	 Set to 1 to ignore filters, used for running integrations on all subscriptions
 * @method  $this filter_discountmode() 		 filter_discountmode(string $v)  Discount filter mode (none, coupon, upgrade)
 * @method  $this filter_discountcode() 		 filter_discountcode(string $v)  Discount code search (coupon code/title or upgrade title)
 * @method  $this publish_up() 					 publish_up(string $v)      	 Subscriptions coming up after date
 * @method  $this publish_down() 				 publish_down(string $v)  	  	 Subscriptions coming up before date (if publish_up is set), or subscriptions expiring before date (if publish_up is not set)
 * @method  $this since() 						 since(string $v)              	 Subscriptions created after this date
 * @method  $this until() 						 until(string $v)              	 Subscriptions created before this date
 * @method  $this expires_from() 				 expires_from(string $v)  		 Subscriptions expiring from this date onwards
 * @method  $this expires_to()					 expires_to(string $v)      	 Subscriptions expiring before this date
 * @method  $this nozero() 						 nozero(bool $v)              	 Set to 1 to skip free (net_amount=0) subscriptions
 * @method  $this search() 						 search(string $v)            	 Search by user info (username, name, email, business name or VAT number)
 * @method  $this subid() 						 subid(mixed $ids)             	 Search by subscription ID (int or array of int)
 * @method  $this level() 						 level(mixed $ids)             	 Search by subscription level ID (int or array of int)
 * @method  $this coupon_id() 					 coupon_id(mixed $ids)     		 Search by coupon ID (int or array of int)
 * @method  $this paystate() 					 paystate(mixed $states)    	 Search by payment state (string or array of string)
 * @method  $this processor() 					 processor(string $v)      	 	 Search by payment processor identifier
 * @method  $this ip() 							 ip(string $v)                   Search by IP of user signing up
 * @method  $this ip_country() 					 ip_country(string $v)    		 Search by auto-detected country code based on IP
 * @method  $this paykey() 						 paykey(string $key)          	 Search by payment key
 * @method  $this user_id() 					 user_id(mixed $ids)         	 Search by user ID (int or array of int)
 * @method  $this enabled() 					 enabled(int $enabled)       	 Search by enabled status
 *
 * Relations:
 *
 * @property-read  Users	 	$user		The Akeeba Subscriptions user record for the subscription user
 * @property-read  JoomlaUsers	$juser		The Joomla! user record for the subscription user
 * @property-read  Levels	 	$level		The subscription level. Note: the method is a filter, the property is a relation!
 * @property-read  Coupons		$coupon		The coupon used (if akeebasubs_coupon_id is not empty)
 * @property-read  Upgrades		$upgrade	The upgrade rule used (if akeebasubs_upgrade_id is not empty)
 * @property-read  Invoices		$invoice	The invoice issued (if akeebasubs_invoice_id is not empty)
 */
class Subscriptions extends DataModel
{
	use Mixin\JsonData, Mixin\Assertions, Mixin\DateManipulation;

	/** @var   self  Caches the row data on load for future reference */
	private $_selfCache = null;

	/**
	 * Public constructor. Adds behaviours and sets up the behaviours and the relations
	 *
	 * @param   Container  $container
	 * @param   array      $config
	 */
	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		// Add the filtering behaviour
		$this->addBehaviour('Filters');
		$this->blacklistFilters([
			'publish_up',
			'publish_down',
		    'created_on',
		]);

		// Set up relations
		$this->hasOne('user', 'Users', 'user_id', 'user_id');
		$this->hasOne('juser', 'JoomlaUsers', 'user_id', 'id');
		$this->hasOne('level', 'Levels', 'akeebasubs_level_id', 'akeebasubs_level_id');
		$this->hasOne('coupon', 'Coupons', 'akeebasubs_coupon_id', 'akeebasubs_coupon_id');
		$this->hasOne('upgrade', 'Upgrades', 'akeebasubs_upgrade_id', 'akeebasubs_upgrade_id');
		$this->hasOne('invoice', 'Invoices', 'akeebasubs_subscription_id', 'akeebasubs_subscription_id');

		// Used for forms
		$this->addKnownField('_noemail', 0, 'int');
	}

	/**
	 * Validates the subscription row
	 */
	public function check()
	{
		$this->assertNotEmpty($this->user_id, 'COM_AKEEBASUBS_SUBSCRIPTION_ERR_USER_ID');
		$this->assertNotEmpty($this->akeebasubs_level_id, 'COM_AKEEBASUBS_SUBSCRIPTION_ERR_LEVEL_ID');
		$this->assertNotEmpty($this->publish_up, 'COM_AKEEBASUBS_SUBSCRIPTION_ERR_PUBLISH_UP');
		$this->assertNotEmpty($this->publish_down, 'COM_AKEEBASUBS_SUBSCRIPTION_ERR_PUBLISH_DOWN');
		$this->assertInArray($this->getFieldValue('state', ''), ['N', 'P', 'C', 'X'], 'COM_AKEEBASUBS_SUBSCRIPTION_ERR_STATE');

		$this->publish_up = $this->normaliseDate($this->publish_up, '2000-01-01');
		$this->publish_down = $this->normaliseDate($this->publish_down, '2038-01-01');
		$this->created_on = $this->normaliseDate($this->created_on, $this->container->platform->getDate()->toSql());
		$this->normaliseEnabled();

		$this->assertNotEmpty($this->processor, 'COM_AKEEBASUBS_SUBSCRIPTION_ERR_PROCESSOR');

		if (!$this->getState('_dontCheckPaymentID', false))
		{
			$this->assertNotEmpty($this->processor, 'COM_AKEEBASUBS_SUBSCRIPTION_ERR_PROCESSOR_KEY');
		}

		// If the _noemail state variable is set we have to modify contact_flag
		$this->applyNoEmailFlag();
	}

	/**
	 * Map state variables from their old names to their new names, for a modicum of backwards compatibility
	 *
	 * @param   \JDatabaseQuery  $query
	 */
	protected function onBeforeBuildQuery(\JDatabaseQuery &$query)
	{
		// DO NOT DELETE -- START
		// Cache the subid and akeebasubs_subscription_id.
		// If we remove that the Invoices view doesn't work correctly. Since the subscription relation is on the
		// akeebasubs_subscription_id column, the Relations manager tries to filter by the
		// akeebasubs_subscription_id state. However, the foreach below overrides the akeebasubs_subscription_id
		// state with the contents of the subid state EVEN THOUGH subid may be null while akeebasubs_subscription_id not
		// null. Therefore we need to cache these two model states, run the foreach and THEN decide if we have to
		// unmask the akeebasubs_subscription_id state. Also, if anyone tells me that commenting code is discouraged
		// I will print this comment on a dildo and stick it up their ass.
		$subid = $this->getState('subid', null);
		$subid2 = $this->getState('akeebasubs_subscription_id', null);
		// DO NOT DELETE -- END

		// Map state variables to what is used by automatic filters
		foreach (
			[
				'subid'     => 'akeebasubs_subscription_id',
				'level'     => 'akeebasubs_level_id',
				'paystate'  => 'state',
				'paykey'    => 'processor_key',
			    'coupon_id' => 'akeebasubs_coupon_id',
			] as $from => $to)
		{
			$this->setState($to, $this->getState($from, null));
		}

		// DO NOT DELETE - START
		// See big comment block above
		if (empty($subid) && !empty($subid2))
		{
			$this->setState('akeebasubs_subscription_id', $subid2);
		}
		// DO NOT DELETE - END

		// Set the default ordering by ID, descending
		if (is_null($this->getState('filter_order', null, 'cmd')) && is_null($this->getState('filter_order_Dir', null, 'cmd')))
		{
			$this->setState('filter_order', $this->getIdFieldName());
			$this->setState('filter_order_Dir', 'DESC');
		}

		// Apply filtering by user. This is a relation filter, it needs to go before the main query builder fires.
		$this->filterByUser($query);
	}

	/**
	 * Apply additional filtering to the select query
	 *
	 * @param   \JDatabaseQuery  $query  The query to modify
	 */
	protected function onAfterBuildQuery(\JDatabaseQuery &$query)
	{
		// If the refresh flag is set in the state we must return all records, without honoring any kind of filter,
		// custom WHERE clause or relation filter.
		$refresh = $this->getState('refresh', null, 'int');

		if ($refresh)
		{
			// Remove already added WHERE clauses
			$query->clear('where');

			// Remove user-defined WHERE clauses
			$this->whereClauses = [];

			// Remove relation filters which would result in WHERE clauses with sub-queries
			$this->relationFilters = [];

			// Do not process anything else, we're done
			return;
		}

		// Filter by discount mode and code (filter_discountmode / filter_discountcode)
		$this->filterByDiscountCode($query);

		// Filter by publish_up / publish_down dates
		$this->filterByDate($query);

		// Filter by created date (since / until)
		$this->filterByCreatedOn($query);

		// Filter by expiration date range (expires_from / expires_to)
		$this->filterByExpirationDate($query);

		// Filter by non-free subscriptions (nozero)
		$this->filterByNonFree($query);

		/**
		 * Filter by subscription ID. See onBeforeBuildQuery. When we have caught the case where subid is not set but
		 * akeebasubs_subscription_id is we keep the latter in the state. However, this happens _after_ the Filters
		 * behaviour has run. Therefore we have to filter again AS LONG AS the result is numeric. It will NOT be numeric
		 * when akeebasubs_subscription_id is set as part of relation, e.g. in the Invoices model when I fetch
		 * $invoice->subscription. In this case the relation manager sets the Subscription model's
		 * akeebasubs_subscription_id state to an array to indicate both the filter value and the filtering mode
		 * (exact). This is something I want to ignore here because it's already caught by the Filters behaviour.
		 */
		$subId = $this->getState('akeebasubs_subscription_id', 0);

		if (!is_array($subId) && ((int) $subId > 0))
		{
			$query->where($query->qn($this->getKeyName()) . ' = ' . $query->q((int) $subId));
		}
	}

	/**
	 * Apply select query filtering by username, email, business name or VAT / tax ID number
	 *
	 * @return  void
	 */
	protected function filterByUser(\JDatabaseQuery &$query)
	{
		// User search feature
		$search = $this->getState('search', null, 'string');

		if ($search)
		{
			// First get the Joomla! users fulfilling the criteria
			/** @var JoomlaUsers $users */
			$users = $this->container->factory->model('JoomlaUsers')->tmpInstance();
			$userIDs = $users->search($search)->with([])->get(true)->modelKeys();

			// If there are user IDs, we need to filter by them and not search for business name or VAT number
			if (!empty($userIDs))
			{
				$query->where($query->qn('user_id') . ' IN (' . implode(',', array_map(array($query, 'q'), $userIDs)) . ')');

				return;
			}

			// Otherwise we have to do a relation filter against the user relation, filtering by business name or VAT number
			$this->whereHas('user', function (\JDatabaseQuery $q) use($search) {
				$q->where(
					'(' .
					'(' . $q->qn('businessname') . ' LIKE ' . $q->q("%$search%") . ')' .
					' OR ' .
					'(' . $q->qn('vatnumber') . ' LIKE ' . $q->q("%$search%") . ')' .
					')'
				);
			});
		}
	}

	/**
	 * Apply select query filtering by discount code
	 *
	 * @param   \JDatabaseQuery  $query  The query to modify
	 *
	 * @return  void
	 */
	protected function filterByDiscountCode(\JDatabaseQuery $query)
	{
		$db = $this->getDbo();

		$tableAlias = $this->getBehaviorParam('tableAlias', null);
		$tableAlias = !empty($tableAlias) ? ($db->qn($tableAlias) . '.') : '';

		$filter_discountmode = $this->getState('filter_discountmode', null, 'cmd');
		$filter_discountcode = $this->getState('filter_discountcode', null, 'string');

		$coupon_ids  = array();
		$upgrade_ids = array();

		switch ($filter_discountmode)
		{
			case 'none':
				$query->where(
					'(' .
					'(' . $tableAlias . $db->qn('akeebasubs_coupon_id') . ' = ' . $db->q(0) . ')'
					. ' AND ' .
					'(' . $tableAlias . $db->qn('akeebasubs_upgrade_id') . ' = ' . $db->q(0) . ')'
					. ')'
				);
				break;

			case 'coupon':
				$query->where(
					'(' .
					'(' . $tableAlias . $db->qn('akeebasubs_coupon_id') . ' > ' . $db->q(0) . ')'
					. ' AND ' .
					'(' . $tableAlias . $db->qn('akeebasubs_upgrade_id') . ' = ' . $db->q(0) . ')'
					. ')'
				);

				if ($filter_discountcode)
				{
					/** @var Coupons $couponsModel */
					$couponsModel = $this->container->factory->model('Coupons')->tmpInstance();

					$coupons = $couponsModel
						->search($filter_discountcode)
						->get(true);

					if ($coupons->count())
					{
						foreach ($coupons as $coupon)
						{
							$coupon_ids[] = $coupon->akeebasubs_coupon_id;
						}
					}
					unset($coupons);
				}
				break;

			case 'upgrade':
				$query->where(
					'(' .
					'(' . $tableAlias . $db->qn('akeebasubs_coupon_id') . ' = ' . $db->q(0) . ')'
					. ' AND ' .
					'(' . $tableAlias . $db->qn('akeebasubs_upgrade_id') . ' > ' . $db->q(0) . ')'
					. ')'
				);
				if ($filter_discountcode)
				{
					/** @var Upgrades $upgradesModel */
					$upgradesModel = $this->container->factory->model('Upgrades')->tmpInstance();

					$upgrades = $upgradesModel
						->search($filter_discountcode)
						->get(true);

					if ($upgrades->count())
					{
						foreach ($upgrades as $upgrade)
						{
							$upgrade_ids[] = $upgrade->akeebasubs_upgrade_id;
						}
					}
					unset($upgrades);
				}
				break;

			default:
				if ($filter_discountcode)
				{
					/** @var Coupons $couponsModel */
					$couponsModel = $this->container->factory->model('Coupons')->tmpInstance();

					$coupons = $couponsModel
						->search($filter_discountcode)
						->get(true);

					if ($coupons->count())
					{
						foreach ($coupons as $coupon)
						{
							$coupon_ids[] = $coupon->akeebasubs_coupon_id;
						}
					}
					unset($coupons);
				}

				if ($filter_discountcode)
				{
					/** @var Upgrades $upgradesModel */
					$upgradesModel = $this->container->factory->model('Upgrades')->tmpInstance();

					$upgrades = $upgradesModel
						->search($filter_discountcode)
						->get(true);

					if ($upgrades->count())
					{
						foreach ($upgrades as $upgrade)
						{
							$upgrade_ids[] = $upgrade->akeebasubs_upgrade_id;
						}
					}

					unset($upgrades);
				}
				break;
		}

		if (!empty($coupon_ids) && !empty($upgrade_ids))
		{
			$query->where(
				'(' .
				'(' . $tableAlias . $db->qn('akeebasubs_coupon_id') . ' IN (' . $db->q(implode(',', $coupon_ids)) . '))'
				. ' OR ' .
				'(' . $tableAlias . $db->qn('akeebasubs_upgrade_id') . ' IN (' . $db->q(implode(',', $upgrade_ids)) . '))'
				. ')'
			);
		}
		elseif (!empty($coupon_ids))
		{
			$query->where($tableAlias . $db->qn('akeebasubs_coupon_id') . ' IN (' . $db->q(implode(',', $coupon_ids)) . ')');
		}
		elseif (!empty($upgrade_ids))
		{
			$query->where($tableAlias . $db->qn('akeebasubs_upgrade_id') . ' IN (' . $db->q(implode(',', $upgrade_ids)) . ')');
		}
	}

	/**
	 * Filter the select query by publish_up / publish_down date
	 *
	 * @param   \JDatabaseQuery  $query  The query to modify
	 *
	 * @return  void
	 */
	protected function filterByDate(\JDatabaseQuery $query)
	{
		$db = $this->getDbo();

		$tableAlias = $this->getBehaviorParam('tableAlias', null);
		$tableAlias = !empty($tableAlias) ? ($db->qn($tableAlias) . '.') : '';

		\JLoader::import('joomla.utilities.date');
		$publish_up = $this->getState('publish_up', null, 'string');
		$publish_down = $this->getState('publish_down', null, 'string');

		$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';

		// Filter the dates
		$from = trim($publish_up);

		if (empty($from))
		{
			$from = '';
		}
		else
		{
			if (!preg_match($regex, $from))
			{
				$from = '2001-01-01';
			}

			$jFrom = new JDate($from);
			$from  = $jFrom->toUnix();

			if ($from == 0)
			{
				$from = '';
			}
			else
			{
				$from = $jFrom->toSql();
			}
		}

		$to = trim($publish_down);

		if (empty($to) || ($to == '0000-00-00') || ($to == '0000-00-00 00:00:00'))
		{
			$to = '';
		}
		else
		{
			if (!preg_match($regex, $to))
			{
				$to = '2037-01-01';
			}

			$jTo = new JDate($to);
			$to  = $jTo->toUnix();

			if ($to == 0)
			{
				$to = '';
			}
			else
			{
				$to = $jTo->toSql();
			}
		}

		if (!empty($from) && !empty($to))
		{
			// Filter from-to dates
			$query->where(
				$tableAlias . $db->qn('publish_up') . ' >= ' .  $db->q($from)
			);
			$query->where(
				$tableAlias . $db->qn('publish_up') . ' <= ' . $db->q($to)
			);
		}
		elseif (!empty($from) && empty($to))
		{
			// Filter after date
			$query->where(
				$tableAlias . $db->qn('publish_up') . ' >= ' . $db->q($from)
			);
		}
		elseif (empty($from) && !empty($to))
		{
			// Filter up to a date
			$query->where(
				$tableAlias . $db->qn('publish_down') . ' <= ' . $db->q($to)
			);
		}
	}

	/**
	 * Filter the select query by created date (since / until)
	 *
	 * @param   \JDatabaseQuery  $query  The query to modify
	 *
	 * @return  void
	 */
	protected function filterByCreatedOn(\JDatabaseQuery $query)
	{
		$db = $this->getDbo();

		$tableAlias = $this->getBehaviorParam('tableAlias', null);
		$tableAlias = !empty($tableAlias) ? ($db->qn($tableAlias) . '.') : '';

		\JLoader::import('joomla.utilities.date');
		$since = $this->getState('since', null, 'string');
		$until = $this->getState('until', null, 'string');

		$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{1,2}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';

		// "Since" queries
		$since = trim($since);

		if (empty($since) || ($since == '0000-00-00') || ($since == '0000-00-00 00:00:00') || ($since == $db->getNullDate()))
		{
			$since = '';
		}
		else
		{
			if (!preg_match($regex, $since))
			{
				$since = '2001-01-01';
			}

			$jFrom = new JDate($since);
			$since = $jFrom->toUnix();

			if ($since == 0)
			{
				$since = '';
			}
			else
			{
				$since = $jFrom->toSql();
			}

			// Filter from-to dates
			$query->where(
				$tableAlias . $db->qn('created_on') . ' >= ' . $db->q($since)
			);
		}

		// "Until" queries
		$until = trim($until);

		if (empty($until) || ($until == '0000-00-00') || ($until == '0000-00-00 00:00:00') || ($until == $db->getNullDate()))
		{
			$until = '';
		}
		else
		{
			if (!preg_match($regex, $until))
			{
				$until = '2037-01-01';
			}

			$jFrom = new JDate($until);
			$until = $jFrom->toUnix();

			if ($until == 0)
			{
				$until = '';
			}
			else
			{
				$until = $jFrom->toSql();
			}

			$query->where(
				$tableAlias . $db->qn('created_on') . ' <= ' . $db->q($until)
			);
		}
	}

	/**
	 * Filter the select query by expiration date range (expires_from / expires_to)
	 *
	 * @param   \JDatabaseQuery  $query  The query to modify
	 *
	 * @return  void
	 */
	protected function filterByExpirationDate(\JDatabaseQuery $query)
	{
		$db = $this->getDbo();

		$tableAlias = $this->getBehaviorParam('tableAlias', null);
		$tableAlias = !empty($tableAlias) ? ($db->qn($tableAlias) . '.') : '';

		\JLoader::import('joomla.utilities.date');
		$expires_from = $this->getState('expires_from', null, 'string');
		$expires_to = $this->getState('expires_to', null, 'string');

		$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{1,2}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';

		$from = trim($expires_from);

		if (empty($from))
		{
			$from = '';
		}
		else
		{
			if (!preg_match($regex, $from))
			{
				$from = '2001-01-01';
			}

			$jFrom = new JDate($from);
			$from  = $jFrom->toUnix();

			if ($from == 0)
			{
				$from = '';
			}
			else
			{
				$from = $jFrom->toSql();
			}
		}

		$to = trim($expires_to);

		if (empty($to) || ($to == '0000-00-00') || ($to == '0000-00-00 00:00:00'))
		{
			$to = '';
		}
		else
		{
			if (!preg_match($regex, $to))
			{
				$to = '2037-01-01';
			}

			$jTo = new JDate($to);
			$to  = $jTo->toUnix();

			if ($to == 0)
			{
				$to = '';
			}
			else
			{
				$to = $jTo->toSql();
			}
		}

		if (!empty($from) && !empty($to))
		{
			// Filter from-to dates
			$query->where(
				$tableAlias . $db->qn('publish_down') . ' >= ' . $db->q($from)
			);
			$query->where(
				$tableAlias . $db->qn('publish_down') . ' <= ' . $db->q($to)
			);
		}
		elseif (!empty($from) && empty($to))
		{
			// Filter after date
			$query->where(
				$tableAlias . $db->qn('publish_down') . ' >= ' . $db->q($from)
			);
		}
		elseif (empty($from) && !empty($to))
		{
			// Filter up to a date
			$query->where(
				$tableAlias . $db->qn('publish_down') . ' <= ' . $db->q($to)
			);
		}
	}

	/**
	 * Filter the select query by non-free subscriptions (nozero)
	 *
	 * @param   \JDatabaseQuery  $query  The query to modify
	 *
	 * @return  void
	 */
	protected function filterByNonFree(\JDatabaseQuery $query)
	{
		$db = $this->getDbo();

		$tableAlias = $this->getBehaviorParam('tableAlias', null);
		$tableAlias = !empty($tableAlias) ? ($db->qn($tableAlias) . '.') : '';

		$nozero = $this->getState('nozero', null, 'int');

		if (!empty($nozero))
		{
			$query->where(
				$tableAlias . $db->qn('net_amount') . ' > ' . $db->q('0')
			);
		}
	}

	/**
	 * Automatically process a list of subscriptions loaded off the database
	 *
	 * @param   Subscriptions[]  $resultArray  The list of loaded subscriptions to process
	 */
	protected function onAfterGetItemsArray(array &$resultArray)
	{
		static $alreadyRunning = false;

		// Implement the subscription automatic expiration
		if (empty($resultArray))
		{
			return;
		}

		if ($this->getState('skipOnProcessList', 0))
		{
			return;
		}

		if ($alreadyRunning)
		{
			return;
		}

		JLoader::import('joomla.utilities.date');
		$jNow = new JDate();
		$uNow = $jNow->toUnix();

		$alreadyRunning = true;

		foreach ($resultArray as $index => &$row)
		{
			if (!($row instanceof Subscriptions))
			{
				continue;
			}

			$row->triggerOnAfterLoad();

			if (is_null($row->params) || empty($row->params))
			{
				$row->params = array();
			}

			$triggered = false;

			if (($row->getFieldValue('state', 'N') != 'C') && $row->enabled)
			{
				$row->enabled = false;
				$row->save();

				continue;
			}

			if ($row->publish_down && ($row->publish_down != $this->getDbo()->getNullDate()))
			{
				$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';

				if (!preg_match($regex, $row->publish_down))
				{
					$row->publish_down = '2037-01-01';
				}

				if (!preg_match($regex, $row->publish_up))
				{
					$row->publish_up = '2001-01-01';
				}

				$jDown = new JDate($row->publish_down);
				$jUp   = new JDate($row->publish_up);

				if (($uNow >= $jDown->toUnix()) && $row->enabled)
				{
					$row->enabled = 0;
					$triggered    = true;
				}
				elseif (($uNow >= $jUp->toUnix()) && !$row->enabled && ($uNow < $jDown->toUnix()))
				{
					$row->enabled = 1;
					$triggered    = true;
				}
			}

			if ($triggered)
			{
				$row->save();
			}
		}

		$alreadyRunning = false;
	}

	/**
	 * Handle the flags communicated through the data to be bound / saved. Also handle the subcustom array passed by the
	 * GUI which needs to be saved in the params field.
	 *
	 * @param   array  $data
	 */
	protected function onBeforeBind(&$data)
	{
		// No point doing anything on null data, huh?
		if (!is_array($data))
		{
			return;
		}

		// Handle the flags
		foreach (['_noemail', '_dontNotify', '_dontCheckPaymentID'] as $flag)
		{
			if (isset($data[$flag]))
			{
				$this->setState($flag, $data[$flag]);

				unset($data[$flag]);
			}
		}

		// Handle the fake payment_state field
		if (isset($data['payment_state']))
		{
			//$this->setFieldValue('state', $data['payment_state']);
			$data['state'] = $data['payment_state'];

			unset($data['payment_state']);
		}

		// Handle the subcustom array which really belongs inside the params array
		if (isset($data['params']) && is_array($data['params']))
		{
			if (!isset($data['params']))
			{
				$data['params'] = [];
			}

			if (!isset($data['params']['subcustom']))
			{
				$data['params']['subcustom'] = [];
			}

			if (isset($data['subcustom']))
			{
				$data['params']['subcustom'] = array_merge($data['params']['subcustom'], $data['subcustom']);

				unset($data['subcustom']);
			}
		}
	}

	/**
	 * Runs before saving data. We use it to remove the fake _noemail field.
	 *
	 * @param  $data
	 */
	protected function onBeforeSave(&$data)
	{
		// This is just a fake record field, it must not be present when saving the record
		if (isset($this->recordData['_noemail']))
		{
			unset($this->recordData['_noemail']);
		}
	}

	/**
	 * Reset the flags communicated through the data to be bound / saved and run post-save features (unblock user,
	 * notify plugins about subscription change)
	 *
	 * @return  void
	 */
	protected function onAfterSave()
	{
		$notify = $this->getState('_dontNotify', null) != true;

		// Reset the flags communicated through the data to be bound / saved.
		foreach (['_noemail', '_dontNotify', '_dontCheckPaymentID'] as $flag)
		{
			$this->setState($flag, false);
		}

		// Unblock users when their payment is Complete
		$this->userUnblock();

		// Run the subscription change notification plugins unless we're told otherwise
		if ($notify)
		{
			$this->subNotifiable();
		}
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
	 * If the current date is outside the publish_up / publish_down range then disable the subscription. Otherwise make
	 * sure it's enabled if state = C or disabled in any other case.
	 *
	 * @return  void
	 */
	protected function normaliseEnabled()
	{
		JLoader::import('joomla.utilities.date');
		$jNow  = new JDate();
		$uNow  = $jNow->toUnix();
		$jDown = new JDate($this->publish_down);
		$jUp   = new JDate($this->publish_up);

		if (($uNow >= $jDown->toUnix()))
		{
			$this->enabled = 0;
		}
		elseif (($uNow >= $jUp->toUnix()) && ($uNow < $jDown->toUnix()))
		{
			$this->enabled = ($this->getFieldValue('state', null) == 'C') ? 1 : 0;
		}
		else
		{
			$this->enabled = 0;
		}
	}

	/**
	 * If the _noemail state variable is set we have to modify the contact_flag. This is used by the backend GUI to let
	 * the managers set subscriptions to not send notification emails, e.g. when renewed manually.
	 *
	 * @return  void
	 */
	protected function applyNoEmailFlag()
	{
		$noEmailFlag = $this->getState('_noemail', null);

		if (!is_null($noEmailFlag) && is_numeric($noEmailFlag))
		{
			if ($noEmailFlag == 1)
			{
				$this->contact_flag = 3;
			}
			elseif ($this->contact_flag == 3)
			{
				$nullDate = $this->getDbo()->getNullDate();

				if (!empty($this->after_contact) && ($this->after_contact != $nullDate))
				{
					$this->contact_flag = 3;
				}
				elseif (!empty($this->second_contact) && ($this->second_contact != $nullDate))
				{
					$this->contact_flag = 2;
				}
				elseif (!empty($this->first_contact) && ($this->first_contact != $nullDate))
				{
					$this->contact_flag = 1;
				}
				else
				{
					$this->contact_flag = 0;
				}
			}
		}
	}

	/**
	 * Trigger onAfterLoad. This is called by onAfterGetItemsArray to allow plugins to work when save() is triggered.
	 */
	public function triggerOnAfterLoad()
	{
		$tmpID = $this->getId();
		$this->onAfterLoad(true, $tmpID);
	}

	/**
	 * Runs after loading a new record. Caches the record to allow us to detect subscription changes which must cause
	 * the subscription notification plugins to run.
	 *
	 * @param   bool   $result  Did the load succeed?
	 * @param   mixed  $keys    The PK we were asked to load or an associative array of (filter) keys
	 *
	 * @return  void
	 */
	protected function onAfterLoad($result, &$keys)
	{
		// Set up the cache
		$this->_selfCache = $result ? clone $this : null;

		$this->setFieldValue('_noemail', 0);

		if ($result && ($this->contact_flag == 3))
		{
			$this->setFieldValue('_noemail', 1);
		}
	}

	/**
	 * Runs after the model resets itself. We clear the record cache.
	 *
	 * @param   bool  $useDefaults     Did we use the field default values when resetting?
	 * @param   bool  $resetRelations  Did we also reset the relations?
	 *
	 * @return  void
	 */
	protected function onAfterReset($useDefaults, $resetRelations)
	{
		$this->_selfCache = null;
	}

	/**
	 * Automatically unblock a user whose subscription is paid (status = C) and
	 * enabled, if he's not already enabled.
	 *
	 * @return  void
	 */
	private function userUnblock()
	{
		// Make sure the payment is complete
		if ($this->getFieldValue('state', null) != 'C')
		{
			return;
		}

		// Make sure the subscription is enabled
		if (!$this->enabled)
		{
			return;
		}

		// Paid and enabled subscription; enable the user if he's not already enabled
		$user = $this->container->platform->getUser($this->user_id);

		if ($user->block)
		{
			$confirmfree = $this->container->params->get('confirmfree', 0);

			if ($confirmfree && ($this->level->price < 0.01))
			{
				// Do not activate free subscription
				return;
			}

			$user->block = 0;
			$user->activation = '';
			$user->save(true);
		}
	}

	/**
	 * Notifies the plugins if a subscription has changed. We use _selfCache to determine which fields have changed.
	 * Some fields ($ignoredFields) are considered indifferent for notification plugins. If only any number of these
	 * were modified the plugins will NOT be triggered.
	 *
	 * @return  void
	 */
	private function subNotifiable()
	{
		// Load the "akeebasubs" plugins
		$this->container->platform->importPlugin('akeebasubs');

		// We don't care to trigger plugins when certain fields change
		$ignoredFields = array(
			'notes',
			'processor',
			'processor_key',
			'net_amount',
			'tax_amount',
			'gross_amount',
			'recurring_amount',
			'tax_percent',
			'params',
			'akeebasubs_coupon_id',
			'akeebasubs_upgrade_id',
			'akeebasubs_affiliate_id',
			'affiliate_comission',
			'akeebasubs_invoice_id',
			'prediscount_amount',
			'discount_amount',
			'contact_flag',
			'first_contact',
			'second_contact',
			'after_contact',
		);

		$info = array(
			'status'   => 'unmodified',
			'previous' => empty($this->_selfCache) ? null : $this->_selfCache,
			'current'  => clone $this,
			'modified' => null
		);

		// New record
		if (is_null($this->_selfCache) || !is_object($this->_selfCache))
		{
			$info['status'] = 'new';

			$data     = $this->getData();
			$modified = array();

			foreach ($data as $key => $value)
			{
				// Skip ignored fields
				if (in_array($key, $ignoredFields))
				{
					continue;
				}

				$modified[ $key ] = $value;
			}

			$info['modified'] = empty($modified) ? null : (object) $modified;
		}
		// Possibly modified record. Let's find out!
		else
		{
			$data     = $this->_selfCache->toArray();
			$currentData = $this->toArray();

			$modified = array();

			foreach ($data as $key => $value)
			{
				// Skip ignored fields
				if (in_array($key, $ignoredFields))
				{
					continue;
				}

				// Check if the value has changed
				if (isset($currentData[$key]) && ($currentData[$key] != $value))
				{
					$info['status']   = 'modified';
					$modified[ $key ] = $value;
				}
			}

			$info['modified'] = empty($modified) ? null : (object) $modified;
		}

		if ($info['status'] != 'unmodified')
		{
			// Fire plugins (onAKSubscriptionChange) passing ourselves as a parameter
			$this->container->platform->runPlugins('onAKSubscriptionChange', array($this, $info));
		}

		$this->_selfCache = clone $this;
	}
}