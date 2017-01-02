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

/**
 * Model for handling invoice templates
 *
 * Fields:
 *
 * @property  int     $akeebasubs_invoicetemplate_id
 * @property  string  $title
 * @property  string  $template
 * @property  array   $levels
 * @property  bool    $globalformat
 * @property  bool    $globalnumbering
 * @property  int     $number_reset
 * @property  string  $country
 * @property  string  $format
 * @property  bool    $isbusiness
 * @property  bool    $noinvoice
 *
 * Filters:
 *
 * @method  $this  akeebasubs_invoicetemplate_id()  akeebasubs_invoicetemplate_id(int $v)
 * @method  $this  title()                          title(string $v)
 * @method  $this  template()                       template(string $v)
 * @method  $this  levels()                         levels(int $v)
 * @method  $this  globalformat()                   globalformat(bool $v)
 * @method  $this  globalnumbering()                globalnumbering(bool $v)
 * @method  $this  number_reset()                   number_reset(int $v)
 * @method  $this  country()                        country(string $v)
 * @method  $this  format()                         format(string $v)
 * @method  $this  isbusiness()                     isbusiness(bool $v)
 * @method  $this  enabled()                        enabled(bool $v)
 * @method  $this  ordering()                       ordering(int $v)
 * @method  $this  noinvoice()                      noinvoice(bool $v)
 * @method  $this  created_on()                     created_on(string $v)
 * @method  $this  created_by()                     created_by(int $v)
 * @method  $this  modified_on()                    modified_on(string $v)
 * @method  $this  modified_by()                    modified_by(int $v)
 * @method  $this  locked_on()                      locked_on(string $v)
 * @method  $this  locked_by()                      locked_by(int $v)
 *
 * @property-read  CreditNoteTemplates   $creditNoteTemplate  The credit note template this invoice template is linked to
 */
class InvoiceTemplates extends DataModel
{
	use Mixin\Assertions, Mixin\DateManipulation, Mixin\ImplodedArrays, Mixin\ImplodedLevels;

	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		// Always load the Filters behaviour
		$this->addBehaviour('Filters');

		$this->hasOne('creditNoteTemplate', 'CreditNoteTemplates', 'akeebasubs_invoicetemplate_id', 'akeebasubs_invoicetemplate_id');

		$this->with(['creditNoteTemplate']);
	}

	/**
	 * If you pass a format request parameter to Joomla! it screws up the page load. So we have to use the request
	 * parameter localformat and map it back to format here.
	 *
	 * @param   mixed  $data
	 */
	protected function onBeforeBind(&$data)
	{
		if (empty($data))
		{
			return;
		}

		if (!is_array($data))
		{
			$data = (array)$data;
		}

		if (isset($data['localformat']))
		{
			$data['format'] = $data['localformat'];
		}
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
		$this->assertNotEmpty($this->title, 'COM_AKEEBASUBS_INVOICETEMPLATE_ERR_TITLE');

		return $this;
	}

	/**
	 * Converts the loaded comma-separated list of subscription levels into an array
	 *
	 * @param   string  $value  The comma-separated list
	 *
	 * @return  array  The exploded array
	 */
	protected function getLevelsAttribute($value)
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
	protected function setLevelsAttribute($value)
	{
		return $this->setAttributeForImplodedLevels($value);
	}

}