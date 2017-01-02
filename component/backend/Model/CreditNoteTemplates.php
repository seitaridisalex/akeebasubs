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
 * Model for handling credit note templates
 *
 * Fields:
 *
 * @property  int     $akeebasubs_invoicetemplate_id
 * @property  string  $template
 * @property  bool    $globalformat
 * @property  bool    $globalnumbering
 * @property  int     $number_reset
 * @property  string  $format
 *
 * Filters:
 *
 * @method  $this  akeebasubs_invoicetemplate_id()  akeebasubs_invoicetemplate_id(int $v)
 * @method  $this  template()                       template(string $v)
 * @method  $this  globalformat()                   globalformat(bool $v)
 * @method  $this  globalnumbering()                globalnumbering(bool $v)
 * @method  $this  number_reset()                   number_reset(int $v)
 * @method  $this  format()                         format(string $v)
 * @method  $this  ordering()                       ordering(int $v)
 * @method  $this  created_on()                     created_on(string $v)
 * @method  $this  created_by()                     created_by(int $v)
 * @method  $this  modified_on()                    modified_on(string $v)
 * @method  $this  modified_by()                    modified_by(int $v)
 * @method  $this  locked_on()                      locked_on(string $v)
 * @method  $this  locked_by()                      locked_by(int $v)
 *
 * @property-read  InvoiceTemplates   $invoiceTemplate  The invoice template this credit note template is linked to
 */
class CreditNoteTemplates extends DataModel
{
	public function __construct(Container $container, array $config = array())
	{
		// We have a non-standard PK field
		$config['idFieldName'] = 'akeebasubs_invoicetemplate_id';

		parent::__construct($container, $config);

		// Always load the Filters behaviour
		$this->addBehaviour('Filters');

		$this->hasOne('invoiceTemplate', 'InvoiceTemplates', 'akeebasubs_invoicetemplate_id', 'akeebasubs_invoicetemplate_id');
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

}