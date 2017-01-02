<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Site\Model\Subscribe\Validation;

use Akeeba\Subscriptions\Tests\Stubs\ValidatorTestCase;

/**
 * Test the PersonalInformation validator
 *
 * @covers Akeeba\Subscriptions\Site\Model\Subscribe\Validation\PersonalInformation
 */
class PersonalInformationTest extends ValidatorTestCase
{
	public static function setUpBeforeClass()
	{
		// Set the validator type
		self::$validatorType = 'PersonalInformation';

		// Create the base objects
		parent::setUpBeforeClass();

		// Only enable the USA, Greece and Spain states
		$db = \JFactory::getDbo();
		$query = $db->getQuery(true)
		            ->update($db->qn('#__akeebasubs_states'))
		            ->set($db->qn('enabled') . ' = ' . $db->q(0))
		            ->where($db->qn('country') . ' != ' . $db->q('US'));
		$db->setQuery($query)->execute();
		$query = $db->getQuery(true)
		            ->update($db->qn('#__akeebasubs_states'))
		            ->set($db->qn('enabled') . ' = ' . $db->q(1))
		            ->where($db->qn('country') . ' = ' . $db->q('US'));
		$db->setQuery($query)->execute();
		$query = $db->getQuery(true)
		            ->update($db->qn('#__akeebasubs_states'))
		            ->set($db->qn('enabled') . ' = ' . $db->q(1))
		            ->where($db->qn('country') . ' = ' . $db->q('GR'));
		$db->setQuery($query)->execute();
		$query = $db->getQuery(true)
		            ->update($db->qn('#__akeebasubs_states'))
		            ->set($db->qn('enabled') . ' = ' . $db->q(1))
		            ->where($db->qn('country') . ' = ' . $db->q('ES'));
		$db->setQuery($query)->execute();

		\Akeeba\Subscriptions\Admin\Helper\akeebasubsHelperSelect_init();

		// Fake the EU VAT checks
		$reflector     = new \ReflectionClass('Akeeba\Subscriptions\Admin\Helper\EUVATInfo');
		$propReflector = $reflector->getProperty('cache');
		$propReflector->setAccessible(true);
		$propReflector->setValue([
			'vat' => [
				'EL123456789' => false,
				'EL070298898' => true,
				'EL666666666' => false,
				'CY123456789' => false,
				'CY999999999' => true,
			]
		]);
	}

	public function getTestData()
	{
		return [
			[
				'componentParams' => [
					'reqcoupon'    => 0,
				],
				'loggedIn'        => 'guest',
				'state'           => [
					'name'         => '',
					'email'        => '',
					'email2'       => '',
					'address1'     => '',
					'country'      => '',
					'state'        => '',
					'city'         => '',
					'zip'          => '',
					'isbusiness'   => 1,
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
					'coupon'       => ''
				],
				'expected'        => [
					'name'          => false,
					'email'         => false,
					'email2'        => false,
					'address1'      => false,
					'country'       => false,
					'state'         => false,
					'city'          => false,
					'zip'           => false,
					'businessname'  => false,
					'occupation'    => false,
					'vatnumber'     => false,
					'novatrequired' => true,
					'coupon'        => true,
				],
				'message'         => 'Collect personal information; all empty'
			],

			[
				'componentParams' => [
					'reqcoupon'    => 0,
				],
				'loggedIn'        => 'guest',
				'state'           => [
					'name'         => 'Foobar',
					'email'        => '',
					'email2'       => '',
					'address1'     => '',
					'country'      => '',
					'state'        => '',
					'city'         => '',
					'zip'          => '',
					'isbusiness'   => 1,
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
					'coupon'       => ''
				],
				'expected'        => [
					'name'          => false,
					'email'         => false,
					'email2'        => false,
					'address1'      => false,
					'country'       => false,
					'state'         => false,
					'city'          => false,
					'zip'           => false,
					'businessname'  => false,
					'occupation'    => false,
					'vatnumber'     => false,
					'novatrequired' => true,
					'coupon'        => true,
				],
				'message'         => 'Collect personal information; one word name (invalid)'
			],

			[
				'componentParams' => [
					'reqcoupon'    => 0,
				],
				'loggedIn'        => 'guest',
				'state'           => [
					'name'         => 'Foo Bar',
					'email'        => '',
					'email2'       => '',
					'address1'     => '',
					'country'      => '',
					'state'        => '',
					'city'         => '',
					'zip'          => '',
					'isbusiness'   => 1,
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
					'coupon'       => ''
				],
				'expected'        => [
					'name'          => true,
					'email'         => false,
					'email2'        => false,
					'address1'      => false,
					'country'       => false,
					'state'         => false,
					'city'          => false,
					'zip'           => false,
					'businessname'  => false,
					'occupation'    => false,
					'vatnumber'     => false,
					'novatrequired' => true,
					'coupon'        => true,
				],
				'message'         => 'Collect personal information; two word name (valid)'
			],

			[
				'componentParams' => [
					'reqcoupon'    => 0,
				],
				'loggedIn'        => 'guest',
				'state'           => [
					'name'         => 'Foo Bar',
					'email'        => 'newuser@test.web',
					'email2'       => '',
					'address1'     => '',
					'country'      => '',
					'state'        => '',
					'city'         => '',
					'zip'          => '',
					'isbusiness'   => 1,
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
					'coupon'       => ''
				],
				'expected'        => [
					'name'          => true,
					'email'         => true,
					'email2'        => false,
					'address1'      => false,
					'country'       => false,
					'state'         => false,
					'city'          => false,
					'zip'           => false,
					'businessname'  => false,
					'occupation'    => false,
					'vatnumber'     => false,
					'novatrequired' => true,
					'coupon'        => true,
				],
				'message'         => 'Collect personal information; email1 but not email2'
			],

			[
				'componentParams' => [
					'reqcoupon'    => 0,
				],
				'loggedIn'        => 'guest',
				'state'           => [
					'name'         => 'Foo Bar',
					'email'        => '',
					'email2'       => 'newuser@test.web',
					'address1'     => '',
					'country'      => '',
					'state'        => '',
					'city'         => '',
					'zip'          => '',
					'isbusiness'   => 1,
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
					'coupon'       => ''
				],
				'expected'        => [
					'name'          => true,
					'email'         => false,
					'email2'        => false,
					'address1'      => false,
					'country'       => false,
					'state'         => false,
					'city'          => false,
					'zip'           => false,
					'businessname'  => false,
					'occupation'    => false,
					'vatnumber'     => false,
					'novatrequired' => true,
					'coupon'        => true,
				],
				'message'         => 'Collect personal information; email2 but not email1'
			],

			[
				'componentParams' => [
					'reqcoupon'    => 0,
				],
				'loggedIn'        => 'guest',
				'state'           => [
					'name'         => 'Foo Bar',
					'email'        => 'newuser@test.web',
					'email2'       => 'newuser_NOTREALLY@test.web',
					'address1'     => '',
					'country'      => '',
					'state'        => '',
					'city'         => '',
					'zip'          => '',
					'isbusiness'   => 1,
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
					'coupon'       => ''
				],
				'expected'        => [
					'name'          => true,
					'email'         => true,
					'email2'        => false,
					'address1'      => false,
					'country'       => false,
					'state'         => false,
					'city'          => false,
					'zip'           => false,
					'businessname'  => false,
					'occupation'    => false,
					'vatnumber'     => false,
					'novatrequired' => true,
					'coupon'        => true,
				],
				'message'         => 'Collect personal information; email and mismatching email2'
			],

			[
				'componentParams' => [
					'reqcoupon'    => 0,
				],
				'loggedIn'        => 'guest',
				'state'           => [
					'name'         => 'Foo Bar',
					'email'        => 'newuser@test.web',
					'email2'       => 'newuser@test.web',
					'address1'     => '',
					'country'      => '',
					'state'        => '',
					'city'         => '',
					'zip'          => '',
					'isbusiness'   => 1,
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
					'coupon'       => ''
				],
				'expected'        => [
					'name'          => true,
					'email'         => true,
					'email2'        => true,
					'address1'      => false,
					'country'       => false,
					'state'         => false,
					'city'          => false,
					'zip'           => false,
					'businessname'  => false,
					'occupation'    => false,
					'vatnumber'     => false,
					'novatrequired' => true,
					'coupon'        => true,
				],
				'message'         => 'Collect personal information; email and matching email2'
			],

			[
				'componentParams' => [
					'reqcoupon'    => 0,
				],
				'loggedIn'        => 'guest',
				'state'           => [
					'name'         => 'Foo Bar',
					'email'        => 'newuser@test.web',
					'email2'       => 'newuser@test.web',
					'address1'     => '123 Someplace Drive',
					'country'      => '',
					'state'        => '',
					'city'         => '',
					'zip'          => '',
					'isbusiness'   => 1,
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
					'coupon'       => ''
				],
				'expected'        => [
					'name'          => true,
					'email'         => true,
					'email2'        => true,
					'address1'      => true,
					'country'       => false,
					'state'         => false,
					'city'          => false,
					'zip'           => false,
					'businessname'  => false,
					'occupation'    => false,
					'vatnumber'     => false,
					'novatrequired' => true,
					'coupon'        => true,
				],
				'message'         => 'Collect personal information; address1'
			],

			[
				'componentParams' => [
					'reqcoupon'    => 0,
				],
				'loggedIn'        => 'guest',
				'state'           => [
					'name'         => 'Foo Bar',
					'email'        => 'newuser@test.web',
					'email2'       => 'newuser@test.web',
					'address1'     => '123 Someplace Drive',
					'country'      => 'US',
					'state'        => '',
					'city'         => '',
					'zip'          => '',
					'isbusiness'   => 1,
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
					'coupon'       => ''
				],
				'expected'        => [
					'name'          => true,
					'email'         => true,
					'email2'        => true,
					'address1'      => true,
					'country'       => true,
					'state'         => false,
					'city'          => false,
					'zip'           => false,
					'businessname'  => false,
					'occupation'    => false,
					'vatnumber'     => false,
					'novatrequired' => true,
					'coupon'        => true,
				],
				'message'         => 'Collect personal information; country'
			],

			[
				'componentParams' => [
					'reqcoupon'    => 0,
				],
				'loggedIn'        => 'guest',
				'state'           => [
					'name'         => 'Foo Bar',
					'email'        => 'newuser@test.web',
					'email2'       => 'newuser@test.web',
					'address1'     => '123 Someplace Drive',
					'country'      => 'US',
					'state'        => 'AL',
					'city'         => '',
					'zip'          => '',
					'isbusiness'   => 1,
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
					'coupon'       => ''
				],
				'expected'        => [
					'name'          => true,
					'email'         => true,
					'email2'        => true,
					'address1'      => true,
					'country'       => true,
					'state'         => true,
					'city'          => false,
					'zip'           => false,
					'businessname'  => false,
					'occupation'    => false,
					'vatnumber'     => false,
					'novatrequired' => true,
					'coupon'        => true,
				],
				'message'         => 'Collect personal information; state'
			],

			[
				'componentParams' => [
					'reqcoupon'    => 0,
				],
				'loggedIn'        => 'guest',
				'state'           => [
					'name'         => 'Foo Bar',
					'email'        => 'newuser@test.web',
					'email2'       => 'newuser@test.web',
					'address1'     => '123 Someplace Drive',
					'country'      => 'GR',
					'state'        => 'GR-ATT',
					'city'         => 'Αθήνα',
					'zip'          => '',
					'isbusiness'   => 1,
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
					'coupon'       => ''
				],
				'expected'        => [
					'name'          => true,
					'email'         => true,
					'email2'        => true,
					'address1'      => true,
					'country'       => true,
					'state'         => true,
					'city'          => true,
					'zip'           => false,
					'businessname'  => false,
					'occupation'    => false,
					'vatnumber'     => false,
					'novatrequired' => false,
					'coupon'        => true,
				],
				'message'         => 'Collect personal information; city'
			],

			[
				'componentParams' => [
					'reqcoupon'    => 0,
				],
				'loggedIn'        => 'guest',
				'state'           => [
					'name'         => 'Foo Bar',
					'email'        => 'newuser@test.web',
					'email2'       => 'newuser@test.web',
					'address1'     => '123 Someplace Drive',
					'country'      => 'GR',
					'state'        => 'GR-ATT',
					'city'         => 'Αθήνα',
					'zip'          => '123 45',
					'isbusiness'   => 1,
					'businessname' => '',
					'occupation'   => '',
					'vatnumber'    => '',
					'coupon'       => ''
				],
				'expected'        => [
					'name'          => true,
					'email'         => true,
					'email2'        => true,
					'address1'      => true,
					'country'       => true,
					'state'         => true,
					'city'          => true,
					'zip'           => true,
					'businessname'  => false,
					'occupation'    => false,
					'vatnumber'     => false,
					'novatrequired' => false,
					'coupon'        => true,
				],
				'message'         => 'Collect personal information; zip'
			],

			[
				'componentParams' => [
					'reqcoupon'    => 0,
				],
				'loggedIn'        => 'guest',
				'state'           => [
					'name'         => 'Foo Bar',
					'email'        => 'newuser@test.web',
					'email2'       => 'newuser@test.web',
					'address1'     => '123 Someplace Drive',
					'country'      => 'GR',
					'state'        => 'GR-ATT',
					'city'         => 'Αθήνα',
					'zip'          => '123 45',
					'isbusiness'   => 1,
					'businessname' => 'Τρία Κιλά Κώδικα ΑΕ',
					'occupation'   => 'Εμπορία λογισμικού',
					'vatnumber'    => '070298898',
					'coupon'       => ''
				],
				'expected'        => [
					'name'          => true,
					'email'         => true,
					'email2'        => true,
					'address1'      => true,
					'country'       => true,
					'state'         => true,
					'city'          => true,
					'zip'           => true,
					'businessname'  => true,
					'occupation'    => true,
					'vatnumber'     => true,
					'novatrequired' => false,
					'coupon'        => true,
				],
				'message'         => 'Collect personal information; business info'
			],

			[
				'componentParams' => [
					'reqcoupon'    => 1,
				],
				'loggedIn'        => 'guest',
				'state'           => [
					'name'         => 'Foo Bar',
					'email'        => 'newuser@test.web',
					'email2'       => 'newuser@test.web',
					'address1'     => '123 Someplace Drive',
					'country'      => 'GR',
					'state'        => 'GR-ATT',
					'city'         => 'Αθήνα',
					'zip'          => '123 45',
					'isbusiness'   => 1,
					'businessname' => 'Τρία Κιλά Κώδικα ΑΕ',
					'occupation'   => 'Εμπορία λογισμικού',
					'vatnumber'    => '070298898',
					'coupon'       => ''
				],
				'expected'        => [
					'name'          => true,
					'email'         => true,
					'email2'        => true,
					'address1'      => true,
					'country'       => true,
					'state'         => true,
					'city'          => true,
					'zip'           => true,
					'businessname'  => true,
					'occupation'    => true,
					'vatnumber'     => true,
					'novatrequired' => false,
					'coupon'        => false,
				],
				'message'         => 'Collect personal information; no coupon but coupon required'
			],

			[
				'componentParams' => [
					'reqcoupon'    => 1,
				],
				'loggedIn'        => 'guest',
				'state'           => [
					'name'         => 'Foo Bar',
					'email'        => 'newuser@test.web',
					'email2'       => 'newuser@test.web',
					'address1'     => '123 Someplace Drive',
					'country'      => 'GR',
					'state'        => 'GR-ATT',
					'city'         => 'Αθήνα',
					'zip'          => '123 45',
					'isbusiness'   => 1,
					'businessname' => 'Τρία Κιλά Κώδικα ΑΕ',
					'occupation'   => 'Εμπορία λογισμικού',
					'vatnumber'    => '070298898',
					'coupon'       => 'IAMNOTTHERE'
				],
				'expected'        => [
					'name'          => true,
					'email'         => true,
					'email2'        => true,
					'address1'      => true,
					'country'       => true,
					'state'         => true,
					'city'          => true,
					'zip'           => true,
					'businessname'  => true,
					'occupation'    => true,
					'vatnumber'     => true,
					'novatrequired' => false,
					'coupon'        => false,
				],
				'message'         => 'Collect personal information; invalid coupon; coupon required'
			],

			[
				'componentParams' => [
					'reqcoupon'    => 0,
				],
				'loggedIn'        => 'guest',
				'state'           => [
					'name'         => 'Foo Bar',
					'email'        => 'newuser@test.web',
					'email2'       => 'newuser@test.web',
					'address1'     => '123 Someplace Drive',
					'country'      => 'GR',
					'state'        => 'GR-ATT',
					'city'         => 'Αθήνα',
					'zip'          => '123 45',
					'isbusiness'   => 1,
					'businessname' => 'Τρία Κιλά Κώδικα ΑΕ',
					'occupation'   => 'Εμπορία λογισμικού',
					'vatnumber'    => '070298898',
					'coupon'       => 'IAMNOTTHERE'
				],
				'expected'        => [
					'name'          => true,
					'email'         => true,
					'email2'        => true,
					'address1'      => true,
					'country'       => true,
					'state'         => true,
					'city'          => true,
					'zip'           => true,
					'businessname'  => true,
					'occupation'    => true,
					'vatnumber'     => true,
					'novatrequired' => false,
					'coupon'        => true,
				],
				'message'         => 'Collect personal information; invalid coupon; coupon NOT required'
			],

			[
				'componentParams' => [
					'reqcoupon'    => 1,
				],
				'loggedIn'        => 'guest',
				'state'           => [
					'name'         => 'Foo Bar',
					'email'        => 'newuser@test.web',
					'email2'       => 'newuser@test.web',
					'address1'     => '123 Someplace Drive',
					'country'      => 'GR',
					'state'        => 'GR-ATT',
					'city'         => 'Αθήνα',
					'zip'          => '123 45',
					'isbusiness'   => 1,
					'businessname' => 'Τρία Κιλά Κώδικα ΑΕ',
					'occupation'   => 'Εμπορία λογισμικού',
					'vatnumber'    => '070298898',
					'coupon'       => 'ALREADYEXPIRED'
				],
				'expected'        => [
					'name'          => true,
					'email'         => true,
					'email2'        => true,
					'address1'      => true,
					'country'       => true,
					'state'         => true,
					'city'          => true,
					'zip'           => true,
					'businessname'  => true,
					'occupation'    => true,
					'vatnumber'     => true,
					'novatrequired' => false,
					'coupon'        => false,
				],
				'message'         => 'Collect personal information; expired coupon; coupon required'
			],

			[
				'componentParams' => [
					'reqcoupon'    => 0,
				],
				'loggedIn'        => 'guest',
				'state'           => [
					'name'         => 'Foo Bar',
					'email'        => 'newuser@test.web',
					'email2'       => 'newuser@test.web',
					'address1'     => '123 Someplace Drive',
					'country'      => 'GR',
					'state'        => 'GR-ATT',
					'city'         => 'Αθήνα',
					'zip'          => '123 45',
					'isbusiness'   => 1,
					'businessname' => 'Τρία Κιλά Κώδικα ΑΕ',
					'occupation'   => 'Εμπορία λογισμικού',
					'vatnumber'    => '070298898',
					'coupon'       => 'ALREADYEXPIRED'
				],
				'expected'        => [
					'name'          => true,
					'email'         => true,
					'email2'        => true,
					'address1'      => true,
					'country'       => true,
					'state'         => true,
					'city'          => true,
					'zip'           => true,
					'businessname'  => true,
					'occupation'    => true,
					'vatnumber'     => true,
					'novatrequired' => false,
					'coupon'        => false,
				],
				'message'         => 'Collect personal information; expired coupon; coupon NOT required'
			],

			[
				'componentParams' => [
					'reqcoupon'    => 1,
				],
				'loggedIn'        => 'guest',
				'state'           => [
					'name'         => 'Foo Bar',
					'email'        => 'newuser@test.web',
					'email2'       => 'newuser@test.web',
					'address1'     => '123 Someplace Drive',
					'country'      => 'GR',
					'state'        => 'GR-ATT',
					'city'         => 'Αθήνα',
					'zip'          => '123 45',
					'isbusiness'   => 1,
					'businessname' => 'Τρία Κιλά Κώδικα ΑΕ',
					'occupation'   => 'Εμπορία λογισμικού',
					'vatnumber'    => '070298898',
					'coupon'       => 'VALIDALL'
				],
				'expected'        => [
					'name'          => true,
					'email'         => true,
					'email2'        => true,
					'address1'      => true,
					'country'       => true,
					'state'         => true,
					'city'          => true,
					'zip'           => true,
					'businessname'  => true,
					'occupation'    => true,
					'vatnumber'     => true,
					'novatrequired' => false,
					'coupon'        => true,
				],
				'message'         => 'Collect personal information; valid coupon; coupon required'
			]
		];
	}

	/**
	 * Test the validator
	 *
	 * @dataProvider getTestData
	 */
	public function testGetValidationResult($componentParams, $loggedIn, $state, $expected, $message)
	{
		foreach ($componentParams as $k => $v)
		{
			if (static::$container->params->get($k) != $v)
			{
				static::$container->params->set($k, $v);
				static::$container->params->save();
			}
		}

		self::$jUser = self::$users[ $loggedIn ];
		static::$factory->reset();

		parent::testGetValidationResult($state, $expected, $message);
	}

	public function performAssertion($expected, $actual, $message)
	{
		$expected = array_merge($expected, [
			'rawDataForDebug' => (array)self::$state
		]);

		parent::performAssertion($expected, $actual, $message); // TODO: Change the autogenerated stub
	}


}