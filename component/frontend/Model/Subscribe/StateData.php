<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Site\Model\Levels;
use FOF30\Container\Container;
use FOF30\Model\Model;

/**
 * A handy class to manage all the data sent to us when submitting the subscription form or when a validation request
 * is made.
 *
 * @package  Akeeba\Subscriptions\Site\Model\Subscribe
 */
class StateData
{
	/** @var   boolean  Is this the first run right after selecting a new subscription level? */
	public $firstrun = false;

	/** @var   string   Subscription level slug */
	public $slug = '';

	/** @var   integer  Subscription level ID */
	public $id = 0;

	/** @var   string   Payment method slug */
	public $paymentmethod = '';

	/** @var   string   Payment processor key */
	public $processorkey = '';

	/** @var   string   Requested username */
	public $username = '';

	/** @var   string   Requested password */
	public $password = '';

	/** @var   string   The repeat of the requested password */
	public $password2 = '';

	/** @var   string   Requested full name of the person */
	public $name = '';

	/** @var   string   Requested email address */
	public $email = '';

	/** @var   string   The repeat of the requested email address */
	public $email2 = '';

	/** @var   string   Requested postal address, first part */
	public $address1 = '';

	/** @var   string   Requested postal address, second part */
	public $address2 = '';

	/** @var   string   Country code (2 letters) */
	public $country = '';

	/** @var   string   State/prefecture/territory code (usually 2 to 10 letters) */
	public $state = '';

	/** @var   string   City */
	public $city = '';

	/** @var   string   ZIP / Postal Code */
	public $zip = '';

	/** @var   integer  Is this a business registration (1) or not (0) */
	public $isbusiness = 0;

	/** @var   string  The business name */
	public $businessname = '';

	/** @var   string  The business activity */
	public $occupation = '';

	/** @var   string  VAT number, without the country prefix */
	public $vatnumber = '';

	/** @var   string  Coupon code */
	public $coupon = '';

	/** @var   array  Per user custom field data */
	public $custom = [];

	/** @var   array  Per subscription custom field data */
	public $subcustom = [];

	/** @var   string  Used in validation requests to define what kind of validation to execute */
	public $opt = '';

	/**
	 * Public constructor. Makes sure the data is loaded on object creation.
	 *
	 * @param   Model $model The parent model calling us, used to fetch the saved state variables
	 */
	public function __construct(Model $model)
	{
		$this->loadData($model);
	}

	/**
	 * Loads the data off the session
	 *
	 * @param   Model $model The parent model calling us, used to fetch the saved state variables
	 *
	 * @return  void
	 */
	public function loadData(Model $model)
	{
		// Is this the first run right after selecting a subscription level?
		$firstRun = $model->getContainer()->platform->getSessionVar('firstrun', true, 'com_akeebasubs');

		if (!$firstRun)
		{
			$model->getContainer()->platform->setSessionVar('firstrun', true, 'com_akeebasubs');
		}

		// Apply the state variables from the model
		$stateVars = array(
			'firstrun'      => $firstRun,
			'slug'          => $model->getState('slug', '', 'string'),
			'id'            => $model->getState('id', 0, 'int'),
			'paymentmethod' => $model->getState('paymentmethod', 'none', 'cmd'),
			'processorkey'  => $model->getState('processorkey', '', 'raw'),
			'username'      => $model->getState('username', '', 'string'),
			'password'      => $model->getState('password', '', 'raw'),
			'password2'     => $model->getState('password2', '', 'raw'),
			'name'          => $model->getState('name', '', 'string'),
			'email'         => $model->getState('email', '', 'string'),
			'email2'        => $model->getState('email2', '', 'string'),
			'address1'      => $model->getState('address1', '', 'string'),
			'address2'      => $model->getState('address2', '', 'string'),
			'country'       => $model->getState('country', '', 'cmd'),
			'state'         => $model->getState('state', '', 'cmd'),
			'city'          => $model->getState('city', '', 'string'),
			'zip'           => $model->getState('zip', '', 'string'),
			'isbusiness'    => $model->getState('isbusiness', '', 'int'),
			'businessname'  => $model->getState('businessname', '', 'string'),
			'occupation'    => $model->getState('occupation', '', 'string'),
			'vatnumber'     => $model->getState('vatnumber', '', 'cmd'),
			'coupon'        => $model->getState('coupon', '', 'string'),
			'custom'        => $model->getState('custom', array(), 'raw'),
			'subcustom'     => $model->getState('subcustom', array(), 'raw'),
			'opt'           => $model->getState('opt', '', 'cmd')
		);

		foreach ($stateVars as $k => $v)
		{
			$this->$k = $v;
		}

		unset ($stateVars);

		// Make sure we have a $custom array
		if (!is_array($this->custom))
		{
			$this->custom = [];
		}

		// Make sure we have a $subcustom array
		if (!is_array($this->subcustom))
		{
			$this->subcustom = [];
		}

		// If there is no level ID but there is a slug, use it
		if (empty($this->id) && !empty($this->slug))
		{
			/** @var Levels $levelsModel */
			$levelsModel = $model->getContainer()->factory->model('Levels')->tmpInstance();
			$item = $levelsModel
				->slug([
					'method' => 'exact',
					'value' => $this->slug
				])->firstOrNew();
			$this->id = $item->akeebasubs_level_id;
		}
	}

	/**
	 * Reset the state attributes to their default values
	 *
	 * @return  void
	 */
	public function reset()
	{
		$properties = get_object_vars($this);

		foreach ($properties as $k => $v)
		{
			$this->$k = '';
		}

		$this->firstrun = false;
		$this->id = 0;
		$this->isbusiness = 0;
		$this->custom = [];
		$this->subcustom = [];
	}
}