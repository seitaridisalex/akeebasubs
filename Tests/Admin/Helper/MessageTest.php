<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Admin\Helper;

use Akeeba\Subscriptions\Admin\Helper\Forex;
use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use FOF30\Container\Container;
use Akeeba\Subscriptions\Admin\Helper\Message;

class MessageTest extends \PHPUnit_Framework_TestCase
{
	/** @var   Container  The container of the component */
	public static $container = null;

	/**
	 * Set up the static objects before the class is created
	 */
	public static function setUpBeforeClass()
	{
		if (is_null(static::$container))
		{
			static::$container = Container::getInstance('com_akeebasubs', [
				'platformClass' => 'Akeeba\\Subscriptions\\Tests\\Stubs\\CustomPlatform'
			]);
		}

		// Reset the component configuration
		static::$container->params->setParams([
			'hidecountries' => '',
			'reqcoupon' => 0,
			'currency' => 'USD',
			'currencysymbol' => '$',
			'invoice_altcurrency' => 'EUR',
		]);
		static::$container->params->save();

		// Prime the component parameters
		static::$container->params->get('currency');

		// Add some subscription data
		/** @var Subscriptions $sub */
		$sub = static::$container->factory->model('Subscriptions');
		$sub->findOrFail(2);
		$sub->save([
			'notes' => 'Notes',
			'tax_amount' => '9.00',
			'gross_amount' => '99.00',
			'tax_percent' => '10',
			'ip' => '8.8.8.8',
			'ip_country' => 'US',
			'prediscount_amount' => '100',
			'discount_amount' => '10',
		]);

		// Force load test ForEx update rates
		$db = self::$container->db;
		$query = $db->getQuery(true)
					->delete('#__akeeba_common')
					->where($db->qn('key') . ' = ' . $db->q('akeebasubs_forex_update_timestamp'));
		$db->setQuery($query)->execute();
		$db->truncateTable('#__akeebasubs_forexrates');

		$reflectionClass = new \ReflectionClass('Akeeba\Subscriptions\Admin\Helper\Forex');

		$refRates = $reflectionClass->getProperty('rates');
		$refRates->setAccessible(true);
		$refRates->setValue([]);

		$refSourceUrl = $reflectionClass->getProperty('rateSourceUrl');
		$refSourceUrl->setAccessible(true);
		$refSourceUrl->setValue('file://' . realpath(__DIR__ . '/eurofxref-daily.xml'));

		Forex::updateRates(false, self::$container);
		Forex::reloadCurrencyData(false, self::$container);
	}

	/**
	 * @dataProvider  getTestMessageCode()
	 *
	 * @param   string  $text
	 * @param   bool    $businessInfoAware
	 * @param   string  $expected
	 */
	public function testMessageCode($text, $businessInfoAware, $expected)
	{
		/** @var Subscriptions $sub */
		$sub = static::$container->factory->model('Subscriptions');
		$sub->findOrFail(2);

		$extras = array(
			'foo'		=> 'bar',
			'baz'		=> 'bat',
			'chicken'	=> 'kot'
		);

		$actual = Message::processSubscriptionTags($text, $sub, $extras, $businessInfoAware);

		$this->assertEquals($expected, $actual, $text . ' yields wrong result');
	}

	public function getTestMessageCode()
	{
		global $akeebasubsTestConfig;

		return [
			// text, businessInfoAware, expected
			// Akeeba Subs merge codes
			['[SITENAME]', true, $akeebasubsTestConfig['site_name']],
			['[SITEURL]', true, $akeebasubsTestConfig['site_url']],
			['[FULLNAME]', true, 'User One'],
			['[FIRSTNAME]', true, 'User'],
			['[LASTNAME]', true, 'One'],
			['[USERNAME]', true, 'user1'],
			['[USEREMAIL]', true, 'user1@test.web'],
			['[LEVEL]', true, 'LEVEL2'],
			['[SLUG]', true, 'level2'],
			['[RENEWALURL]', true, $akeebasubsTestConfig['site_url'] . 'index.php?option=com_akeebasubs&view=Level&slug=level2&layout=default'],
			['[RENEWALURL:]', true, $akeebasubsTestConfig['site_url'] . 'index.php?option=com_akeebasubs&view=Level&slug=level2&layout=default'],
			['[ENABLED]', true, 'COM_AKEEBASUBS_SUBSCRIPTION_COMMON_DISABLED'],
			['[PAYSTATE]', true, 'COM_AKEEBASUBS_SUBSCRIPTION_STATE_C'],
			['[PUBLISH_UP]', true, 'Wednesday, 30 April 2014 00:00'],
			['[PUBLISH_UP_EU]', true, '30/04/2014 00:00:00'],
			['[PUBLISH_UP_USA]', true, '04/30/2014 12:00:00 am'],
			['[PUBLISH_UP_JAPAN]', true, '2014/04/30 00:00:00'],
			['[PUBLISH_DOWN]', true, 'Wednesday, 29 April 2015 00:00'],
			['[PUBLISH_DOWN_EU]', true, '29/04/2015 00:00:00'],
			['[PUBLISH_DOWN_USA]', true, '04/29/2015 12:00:00 am'],
			['[PUBLISH_DOWN_JAPAN]', true, '2015/04/29 00:00:00'],
			['[MYSUBSURL]', true, $akeebasubsTestConfig['site_url'] . 'index.php?option=com_akeebasubs&view=Subscriptions'],
			['[URL]', true, $akeebasubsTestConfig['site_url'] . 'index.php?option=com_akeebasubs&view=Subscriptions'],
			['[CURRENCY]', true, 'USD'],
			['[CURRENCY_ALT]', true, 'EUR'],
			['[$]', true, '$'],
			['[$_ALT]', true, '€'],
			['[EXCHANGE_RATE]', true, 0.901225666907],
			['[DLID]', true, ''],
			['[COUPONCODE]', true, 'ONEUSERHIT'],
			['[USER:STATE_FORMATTED]', true, 'California'],
			['[USER:COUNTRY_FORMATTED]', true, 'United States'],
			// Legacy keys
			['[NAME]', true, 'User'],
			['[STATE]', true, 'COM_AKEEBASUBS_SUBSCRIPTION_STATE_C'],
			['[FROM]', true, 'Wednesday, 30 April 2014 00:00'],
			['[TO]', true, 'Wednesday, 29 April 2015 00:00'],
			// Subscription merge codes (automatic)
			['[SUB:ID]', true, 2],
			['[SUB:USER_ID]', true, 1000],
			['[SUB:AKEEBASUBS_LEVEL_ID]', true, 2],
			['[SUB:PUBLISH_UP]', true, '2014-04-30 00:00:00'],
			['[SUB:PUBLISH_DOWN]', true, '2015-04-29 00:00:00'],
			['[SUB:NOTES]', true, 'Notes'],
			['[SUB:ENABLED]', true, 0],
			['[SUB:PROCESSOR]', true, 'none'],
			['[SUB:PROCESSOR_KEY]', true, '20140430000000'],
			['[SUB:STATE]', true, 'C'],
			['[SUB:NET_AMOUNT]', true, '90.00'],
			['[SUB:TAX_AMOUNT]', true, '9.00'],
			['[SUB:GROSS_AMOUNT]', true, '99.00'],
			['[SUB:RECURRING_AMOUNT]', true, '0.00'],
			['[SUB:TAX_PERCENT]', true, '10'],
			['[SUB:CREATED_ON]', true, '2014-04-30 00:00:00'],
			['[SUB:IP]', true, '8.8.8.8'],
			['[SUB:IP_COUNTRY]', true, 'US'],
			['[SUB:AKEEBASUBS_COUPON_ID]', true, '15'],
			['[SUB:AKEEBASUBS_UPGRADE_ID]', true, ''],
			['[SUB:AKEEBASUBS_INVOICE_ID]', true, ''],
			['[SUB:PREDISCOUNT_AMOUNT]', true, '100'],
			['[SUB:DISCOUNT_AMOUNT]', true, '10'],
			['[SUB:CONTACT_FLAG]', true, '0'],
			['[SUB:FIRST_CONTACT]', true, '0000-00-00 00:00:00'],
			['[SUB:SECOND_CONTACT]', true, '0000-00-00 00:00:00'],
			['[SUB:AFTER_CONTACT]', true, '0000-00-00 00:00:00'],
			// SUBCUSTOM:
			['[SUBCUSTOM:LOL]', true, 'wut'],
			['[SUBCUSTOM:FOO]', true, '123'],
			// LEVEL:
			['[LEVEL:ID]', true, '2'],
			['[LEVEL:TITLE]', true, 'LEVEL2'],
			['[LEVEL:SLUG]', true, 'level2'],
			['[LEVEL:IMAGE]', true, 'images/joomla_black.gif'],
			['[LEVEL:DESCRIPTION]', true, '<p>Sample subscription level with a non-zero price and no sign-up fee</p>'],
			['[LEVEL:DURATION]', true, '365'],
			['[LEVEL:PRICE]', true, '100.00'],
			['[LEVEL:SIGNUPFEE]', true, '0.00'],
			['[LEVEL:ORDERTEXT]', true, '<p>Successful sign-up</p>'],
			['[LEVEL:ORDERURL]', true, ''],
			['[LEVEL:CANCELTEXT]', true, '<p>Canceled sign-up</p>'],
			['[LEVEL:CANCELURL]', true, ''],
			['[LEVEL:ONLY_ONCE]', true, '0'],
			['[LEVEL:RECURRING]', true, '0'],
			['[LEVEL:FOREVER]', true, '0'],
			['[LEVEL:AKEEBASUBS_LEVELGROUP_ID]', true, '0'],
			['[LEVEL:ACCESS]', true, '1'],
			['[LEVEL:FIXED_DATE]', true, '0000-00-00 00:00:00'],
			['[LEVEL:RENEW_URL]', true, ''],
			['[LEVEL:CONTENT_URL]', true, ''],
			['[LEVEL:ENABLED]', true, '1'],
			['[LEVEL:ORDERING]', true, '0'],
			['[LEVEL:CREATED_ON]', true, '2015-04-30 10:49:56'],
			['[LEVEL:CREATED_BY]', true, '100'],
			['[LEVEL:NOTIFY1]', true, '30'],
			['[LEVEL:NOTIFY2]', true, '15'],
			['[LEVEL:NOTIFYAFTER]', true, '0'],
			// USER:
			['[USER:ID]', true, '1000'],
			['[USER:NAME]', true, 'User One'],
			['[USER:USERNAME]', true, 'user1'],
			['[USER:EMAIL]', true, 'user1@test.web'],
			['[USER:BLOCK]', true, '0'],
			['[USER:SENDEMAIL]', true, '0'],
			['[USER:REGISTERDATE]', true, '2015-04-29 18:13:57'],
			['[USER:LASTVISITDATE]', true, '0000-00-00 00:00:00'],
			['[USER:ACTIVATION]', true, ''],
			['[USER:LASTRESETTIME]', true, '0000-00-00 00:00:00'],
			['[USER:USER_ID]', true, '1000'],
			['[USER:ISBUSINESS]', true, '1'],
			['[USER:BUSINESSNAME]', true, 'Unit Test Ltd'],
			['[USER:OCCUPATION]', true, 'Software TEsting'],
			['[USER:VATNUMBER]', true, '123456789'],
			['[USER:VIESREGISTERED]', true, '1'],
			['[USER:TAXAUTHORITY]', true, ''],
			['[USER:ADDRESS1]', true, '123 Someplace Drive'],
			['[USER:ADDRESS2]', true, 'Suite 404'],
			['[USER:CITY]', true, 'Beverly Hills'],
			['[USER:STATE]', true, 'CA'],
			['[USER:ZIP]', true, '90210'],
			['[USER:COUNTRY]', true, 'US'],
			['[USER:NOTES]', true, 'This is a user note'],
			// CUSTOM:
			['[CUSTOM:BAZ]', true, 'bat'],
			['[CUSTOM:SOMETHING]', true, '12.34'],
		];
	}
}