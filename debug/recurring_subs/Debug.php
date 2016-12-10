<?php
/**
 * @package     Akeeba\Subscriptions\Site\Controller
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Akeeba\Subscriptions\Site\Controller;

use Akeeba\Subscriptions\Admin\Controller\Mixin;
use Akeeba\Subscriptions\Site\Model\Invoices;
use Akeeba\Subscriptions\Site\Model\Levels;
use Akeeba\Subscriptions\Site\Model\Subscriptions;
use FOF30\Container\Container;
use FOF30\Controller\Controller;
use FOF30\Download\Download;
use FOF30\Utils\Ip;
use Joomla\Registry\Registry;

/**
 * Check if recurring subscriptions actually work. Uses the PayPal plugin's Debug mode for the all-important callbacks.
 *
 * @package     Akeeba\Subscriptions\Site\Controller
 *
 * @since version
 */
class Debug extends Controller
{
	use Mixin\PredefinedTaskList;

	/**
	 * Overridden. Limit the tasks we're allowed to execute.
	 *
	 * @param   Container $container
	 * @param   array     $config
	 */
	public function __construct(Container $container, array $config = array())
	{
		$config['modelName'] = 'Subscribe';
		$config['csrfProtection'] = 0;

		parent::__construct($container, $config);

		$this->predefinedTaskList = ['debug', 'cleanup'];

		$this->cacheableTasks = [];
	}

	public function debug()
	{
		// Make sure this only runs from localhost
		$this->onlyRunLocally();

		// Make sure the invoicing and PayPal plugins are enabled or throw exception
		$this->checkIfPluginsEnabled();

		// Clean up existing data
		$this->cleanUpExistingSubsAndInvoices();

		// Set PayPal plugin to debug mode and get the merchant ID
		$merchantId = $this->setPaypalToDebugAndGetMerchant();

		// Create new unpaid subscription
		$subId = $this->createNewSubAndGetId();

		// Fire the postback
		$postBackData = [
			"txn_type"       => "web_accept",
			"custom"         => $subId,
			"receiver_email" => $merchantId,
			"receiver_id"    => 99999999,
			"mc_gross"       => 10.00,
			"txn_id"         => "TEST_201612_INITIAL",
			"payment_status" => "Completed",
			"mc_currency"    => "EUR",
			"subscr_id"      => "sub_test_201612",
		];

		$response = $this->firePostBack($postBackData);

		if (($response->code != 200) || ($response->body != 'OK'))
		{
			echo "<h1>Failed initial postback</h1>";
			var_dump($response);

			$this->cleanup();
			return;
		}

		// Check that the subscription is created
		$initialSubscription = $this->checkInitialSubscription($subId);

		// Check that the invoice is created
		$initialInvoice = $this->checkInitialInvoice($subId);

		// TODO Fire the recurring postback
		$postBackData = [
			"txn_type"       => "subscr_payment",
			"custom"         => $subId,
			"receiver_email" => $merchantId,
			"receiver_id"    => 99999999,
			"mc_gross"       => 10.00,
			"txn_id"         => "TEST_201612_RECURRING",
			"payment_status" => "Completed",
			"mc_currency"    => "EUR",
			"subscr_id"      => "sub_test_201612",
		];

		$response = $this->firePostBack($postBackData);

		if (($response->code != 200) || ($response->body != 'OK'))
		{
			echo "<h1>Failed initial postback</h1>";
			var_dump($response);

			echo $response->body;

			$this->cleanup();
			return;
		}

		// TODO Check the existing subscription (must be updated)

		// TODO Check the old invoice (must have different sub id)

		// TODO Check new sub ID (must be the same as the old sub)

		// TODO Check the invoice for the new sub (must have the sub ID of the old sub)

		// Clean up existing data after the conclusion of the test
		$this->cleanUpExistingSubsAndInvoices();

	}

	public function cleanup()
	{
		$this->onlyRunLocally();

		$this->cleanUpExistingSubsAndInvoices();

		$this->unsetPaypalFromDebugMode();
	}

	private function cleanUpExistingSubsAndInvoices()
	{
		$db     = $this->container->db;
		$query  = $db->getQuery(true)
					 ->select($db->qn('akeebasubs_subscription_id'))
					 ->from('#__akeebasubs_subscriptions')
					 ->where($db->qn('processor_key') . ' LIKE ' . $db->q('TEST_201612_%'));
		$subIds = $db->setQuery($query)->loadColumn();
	}

	private function checkIfPluginsEnabled()
	{
		$db    = $this->container->db;
		$query = $db->getQuery(true)
					->select('COUNT(*)')
					->from($db->qn('#__extensions'))
					->where($db->qn('type') . ' = ' . $db->q('plugin'))
					->where($db->qn('element') . ' IN(' . $db->q('paypal') . ', ' . $db->q('invoices') . ')')
					->where($db->qn('enabled') . ' = ' . $db->q('1'));
		$count = $db->setQuery($query)->loadResult();

		if ($count != 2)
		{
			throw new \RuntimeException('The invoices and paypal plugins are not enabled', 500);
		}
	}

	private function onlyRunLocally()
	{
		// Only run debug locally
		$ip = Ip::getIp();

		if (!defined('JDEBUG') || !in_array($ip, ['127.0.0.1', '::1']))
		{
			\JFactory::getApplication()->redirect('index.php');
		}
	}

	/**
	 *
	 * @return mixed
	 *
	 * @since version
	 */
	private function setPaypalToDebugAndGetMerchant()
	{
		$db     = $this->container->db;
		$query  = $db->getQuery(true)
					 ->select($db->qn('params'))
					 ->from($db->qn('#__extensions'))
					 ->where($db->qn('element') . ' = ' . $db->q('paypal'))
					 ->where($db->qn('folder') . ' = ' . $db->q('akpayment'));
		$data   = $db->setQuery($query)->loadResult();
		$params = new Registry($data);
		$params->set('debug', 1);
		$sandbox  = $params->get('sandbox', 0);
		$merchant = $params->get(($sandbox ? 'sandbox_' : '') . 'merchant', '');

		$query = $db->getQuery(true)
					->update($db->qn('#__extensions'))
					->set($db->qn('params') . ' = ' . $db->q($params->toString('JSON')))
					->where($db->qn('element') . ' = ' . $db->q('paypal'))
					->where($db->qn('folder') . ' = ' . $db->q('akpayment'));
		$db->setQuery($query)->execute();

		return $merchant;
	}

	/**
	 *
	 * @since version
	 */
	private function unsetPaypalFromDebugMode()
	{
		$db     = $this->container->db;
		$query  = $db->getQuery(true)
					 ->select($db->qn('params'))
					 ->from($db->qn('#__extensions'))
					 ->where($db->qn('element') . ' = ' . $db->q('paypal'))
					 ->where($db->qn('folder') . ' = ' . $db->q('akpayment'));
		$data   = $db->setQuery($query)->loadResult();
		$params = new Registry($data);
		$params->set('debug', 0);

		$query = $db->getQuery(true)
					->update($db->qn('#__extensions'))
					->set($db->qn('params') . ' = ' . $db->q($params->toString('JSON')))
					->where($db->qn('element') . ' = ' . $db->q('paypal'))
					->where($db->qn('folder') . ' = ' . $db->q('akpayment'));
		$db->setQuery($query)->execute();
	}

	/**
	 *
	 * @return mixed
	 *
	 * @since version
	 */
	private function createOrGetSubscriptionLevelId()
	{
		$db      = $this->container->db;
		$data    = [
			'title'                    => 'DEBUG',
			'slug'                     => 'debug',
			'image'                    => 'images/debug.png',
			'description'              => 'FOR DEBUGGING PURPOSES ONLY',
			'duration'                 => '30',
			'price'                    => 10.00,
			'signupfee'                => 0,
			'ordertext'                => 'Success!',
			'canceltext'               => 'Cancelled :(',
			'cancelurl'                => '',
			'only_once'                => 0,
			'recurring'                => 1,
			'forever'                  => 0,
			'akeebasubs_levelgroup_id' => 0,
			'access'                   => 1,
			'fixed_date'               => $db->getNullDate(),
			'payment_plugins'          => '',
			'renew_url'                => '',
			'content_url'              => '',
			'params'                   => [],
			'enabled'                  => 1,
			'notify1'                  => '7',
			'notify2'                  => '2',
			'notifyafter'              => '30',
		];
		$query   = $db->getQuery(true)
					  ->select($db->qn('akeebasubs_level_id'))
					  ->from($db->qn('#__akeebasubs_levels'))
					  ->where($db->qn('slug') . ' = ' . $db->q($data['slug']));
		$levelId = $db->setQuery($query)->loadResult();

		if (!$levelId)
		{
			/** @var Levels $model */
			$model = $this->container->factory->model('Levels')->tmpInstance();
			$model->bind($data)->save();
			$levelId = $model->getId();
		}

		return $levelId;
	}

	/**
	 *
	 * @return int
	 *
	 * @since version
	 */
	private function getSuperUserId()
	{
		$db = $this->container->db;

		// Get all admin users (by definition the have sendEmail set to 1)
		$query = $db->getQuery(true)
					->select($db->qn(array('name', 'email', 'sendEmail', 'id')))
					->from($db->qn('#__users'))
					->where($db->qn('sendEmail') . ' = ' . 1);

		$rows = $db->setQuery($query)->loadObjectList();

		// Send mail to all users with users creating permissions and receiving system emails
		foreach ($rows as $row)
		{
			$user = \JFactory::getUser($row->id);

			if ($user->authorise('core.manage'))
			{
				return $user->id;
			}
		}

		return 42;
	}

	/**
	 *
	 * @return mixed
	 *
	 * @since version
	 */
	private function createNewSubAndGetId()
	{
		$publishUp   = time() - 2548800;
		$publishDown = time() + 43200;

		$data = [
			'user_id'                 => $this->getSuperUserId(),
			'akeebasubs_level_id'     => $this->createOrGetSubscriptionLevelId(),
			'publish_up'              => (new \JDate($publishUp))->toSql(),
			'publish_down'            => (new \JDate($publishDown))->toSql(),
			'notes'                   => 'Auto-generated debug subscription',
			'enabled'                 => 0,
			'processor'               => 'paypal',
			'processor_key'           => '',
			'state'                   => 'N',
			'net_amount'              => 10,
			'tax_amount'              => 0,
			'gross_amount'            => 10,
			'recurring_amount'        => 10,
			'tax_percent'             => 0,
			'created_on'              => (new \JDate())->toSql(),
			'params'                  => [],
			'ip'                      => Ip::getIp(),
			'ip_country'              => 'LOL!',
			'akeebasubs_coupon_id'    => 0,
			'akeebasubs_upgrade_id'   => '',
			'akeebasubs_affiliate_id' => 0,
			'affiliate_comission'     => 0,
			'akeebasubs_invoice_id'   => 0,
			'prediscount_amount'      => 10,
			'discount_amount'         => 0,
			'contact_flag'            => 3
		];

		/** @var Subscriptions $sub */
		$sub = $this->container->factory->model('Subscriptions')->tmpInstance();
		$sub->bind($data);
		$sub->save();

		return $sub->akeebasubs_subscription_id;
	}

	/**
	 * @param $postBackData
	 *
	 * @return \JHttpResponse
	 *
	 * @since version
	 */
	private function firePostBack($postBackData)
	{
		$http = \JHttpFactory::getHttp();
		$url  = \JUri::getInstance();
		$url->setVar('option', 'com_akeebasubs');
		$url->setVar('view', 'Callback');
		$url->setVar('paymentmethod', 'paypal');
		$response = $http->post($url->toString(), $postBackData);

		return $response;
	}

	/**
	 * @param $subId
	 *
	 * @return Subscriptions
	 *
	 * @since version
	 */
	private function checkInitialSubscription($subId)
	{
		/** @var Subscriptions $initialSub */
		$initialSub = $this->container->factory->model('Subscriptions')->tmpInstance();
		$initialSub->load($subId);

		if (!$initialSub->enabled)
		{
			throw new \RuntimeException("Subscription $subId is not enabled after postback");
		}

		if ($initialSub->processor_key != 'TEST_201612_INITIAL')
		{
			throw new \RuntimeException("Subscription $subId does not have the correct processor key after initial postback");
		}

		return $initialSub;
	}

	/**
	 * @param $subId
	 *
	 * @return Invoices
	 *
	 * @since version
	 */
	private function checkInitialInvoice($subId)
	{
		/** @var Invoices $initialInvoice */
		$initialInvoice = $this->container->factory->model('Invoices')->tmpInstance();
		$initialInvoice->findOrFail($subId);

		return $initialInvoice;
	}
}