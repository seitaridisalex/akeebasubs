<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Model;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Helper\Email;
use Akeeba\Subscriptions\Admin\Helper\EUVATInfo;
use Akeeba\Subscriptions\Admin\Helper\Format;
use Akeeba\Subscriptions\Admin\Helper\Message;
use FOF30\Container\Container;
use FOF30\Model\DataModel;

/**
 * Model for the invoices issued
 *
 * @property  int		$akeebasubs_subscription_id
 * @property  string	$extension
 * @property  int		$akeebasubs_invoicetemplate_id
 * @property  int		$invoice_no
 * @property  string	$display_number
 * @property  string	$invoice_date
 * @property  string	$html
 * @property  string	$atxt
 * @property  string	$btxt
 * @property  string	$filename
 * @property  string	$sent_on
 * @property  int     	$enabled      		Publish status of this record
 * @property  int     	$created_by   		ID of the user who created this record
 * @property  string  	$created_on   		Date/time stamp of record creation
 * @property  int     	$modified_by  		ID of the user who modified this record
 * @property  string  	$modified_on  		Date/time stamp of record modification
 * @property  int     	$locked_by    		ID of the user who locked this record
 * @property  string  	$locked_on    		Date/time stamp of record locking
 *
 * Filters:
 *
 * @method  $this  akeebasubs_subscription_id()     akeebasubs_subscription_id(int $v)
 * @method  $this  extension()                      extension(string $v)
 * @method  $this  akeebasubs_invoicetemplate_id()  akeebasubs_invoicetemplate_id(int $v)
 * @method  $this  invoice_no()                     invoice_no(int $v)
 * @method  $this  display_number()                 display_number(string $v)
 * @method  $this  invoice_date()                   invoice_date(string $v)
 * @method  $this  html()                           html(string $v)
 * @method  $this  atxt()                           atxt(string $v)
 * @method  $this  btxt()                           btxt(string $v)
 * @method  $this  filename()                       filename(string $v)
 * @method  $this  sent_on()                        sent_on(string $v)
 * @method  $this  enabled()                        enabled(bool $v)
 * @method  $this  created_on()                     created_on(string $v)
 * @method  $this  created_by()                     created_by(int $v)
 * @method  $this  modified_on()                    modified_on(string $v)
 * @method  $this  modified_by()                    modified_by(int $v)
 * @method  $this  locked_on()                      locked_on(string $v)
 * @method  $this  locked_by()                      locked_by(int $v)
 * @method  $this  subids()							subids(array $v)
 *
 * @property-read  Subscriptions  		$subscription	The subscription of this invoice
 * @property-read  InvoiceTemplates		$template		The template for this invoice
 * @property-read  CreditNotes		    $creditNote		The credit note issued against this invoice
 */
class Invoices extends DataModel
{
	use Mixin\Assertions;

	/**
	 * Public constructor. We override it to set up behaviours and relations
	 *
	 * @param   Container  $container
	 * @param   array      $config
	 */
	public function __construct(Container $container, array $config = array())
	{
		// We have a non-standard PK field
		$config['idFieldName'] = 'akeebasubs_subscription_id';

		parent::__construct($container, $config);

		// Add the Filters behaviour
		$this->addBehaviour('Filters');
		// Some filters we will have to handle pragmatically so we need to exclude them from the behaviour
		$this->blacklistFilters([
			'akeebasubs_subscription_id',
			'invoice_date',
			'sent_date',
		]);

		// Set up relations
		$this->hasOne('subscription', 'Subscriptions', 'akeebasubs_subscription_id', 'akeebasubs_subscription_id');
		$this->hasOne('template', 'InvoiceTemplates', 'akeebasubs_invoicetemplate_id', 'akeebasubs_invoicetemplate_id');
		$this->hasOne('creditNote', 'CreditNotes', 'akeebasubs_subscription_id', 'akeebasubs_invoice_id');

		// Eager load the relations. This allows us to get rid of ugly JOINs.
		$this->with(['subscription']);
	}

	/**
	 * Set the default ordering
	 *
	 * @param   \JDatabaseQuery  $query
	 *
	 * @return  void
	 */
	protected function onBeforeBuildQuery(\JDatabaseQuery &$query)
	{
		// Set the default ordering by ID, descending
		if (is_null($this->getState('filter_order', null, 'cmd')) && is_null($this->getState('filter_order_Dir', null, 'cmd')))
		{
			$this->setState('filter_order', $this->getIdFieldName());
			$this->setState('filter_order_Dir', 'DESC');
		}
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

		$id = $this->getState('akeebasubs_subscription_id', null, 'raw');

		if (is_array($id))
		{
			if (isset($id['method']) && ($id['method'] == 'exact'))
			{
				$id = (int) $id['value'];
			}
			else
			{
				$id = 0;
			}
		}
		else
		{
			$id = (int) $id;
		}

		$subIDs = $this->getState('subids', null, 'array');
		$subIDs = empty($subIDs) ? [] : $subIDs;

		// Search by user
		$user = $this->getState('user', null, 'string');

		if (!empty($user))
		{
			// First get the Joomla! users fulfilling the criteria
			/** @var JoomlaUsers $users */
			$users = $this->container->factory->model('JoomlaUsers')->tmpInstance();
			$userIDs = $users->search($user)->with([])->get(true)->modelKeys();
			$filteredIDs = [-1];

			if (!empty($userIDs))
			{
				// Now get the subscriptions IDs for these users
				/** @var Subscriptions $subs */
				$subs = $this->container->factory->model('Subscriptions')->tmpInstance();
				$subs->setState('user_id', $userIDs);
				$subs->with([]);

				$filteredIDs = $subs->get(true)->modelKeys();
				$filteredIDs = empty($filteredIDs) ? [-1] : $filteredIDs;
			}

			if (!empty($subIDs))
			{
				$subIDs = array_intersect($subIDs, $filteredIDs);
			}
			else
			{
				$subIDs = $filteredIDs;
			}

			unset($subs);
		}

		// Search by business information
		$business = $this->getState('business', null, 'string');

		if (!empty($business))
		{
			$search = '%' . $business . '%';

			/** @var Subscriptions $subs */
			$subs = $this->container->factory->model('Subscriptions')->tmpInstance();
			$subs->whereHas('user', function(\JDatabaseQuery $q) use($search) {
				$q->where(
					'((' . $q->qn('businessname') . ' LIKE ' . $q->q($search) . ') OR (' .
					$q->qn('vatnumber') . ' LIKE ' . $q->q($search) . '))'
				);
			});

			$subs->with([]);
			$filteredIDs = $subs->get(true)->modelKeys();
			$filteredIDs = empty($filteredIDs) ? [-1] : $filteredIDs;

			if (!empty($subIDs))
			{
				$subIDs = array_intersect($subIDs, $filteredIDs);
			}
			else
			{
				$subIDs = $filteredIDs;
			}

			unset($subs);
		}

		// Search by a list of subscription IDs
		if (is_numeric($id) && ($id > 0))
		{
			$query->where(
				$db->qn('akeebasubs_subscription_id') . ' = ' . $db->q((int)$id)
			);
		}
		elseif (!empty($subIDs))
		{
			$subIDs = array_unique($subIDs);
			$ids = array();

			foreach ($subIDs as $id)
			{
				$id = (int)$id;

				if ($id == 0)
				{
					continue;
				}

				$ids[] = $db->q($id);
			}

			if (!empty($ids))
			{
				$query->where(
					$db->qn('akeebasubs_subscription_id') . ' IN (' .
					implode(',', $ids) . ')'
				);
			}
		}

		// Search by invoice number (raw or formatted)
		$invoice_number = $this->getState('invoice_number', null, 'string');

		if ( !empty($invoice_number))
		{
			// Unified invoice / display number search
			$query->where(
				'((' .
				$db->qn('invoice_no') . ' = ' . $db->q((int)$invoice_number)
				. ') OR (' .
				$db->qn('display_number') . ' LIKE ' . $db->q('%' . $invoice_number . '%')
				. '))'
			);
		}

		// Prepare for date filtering
		$dateRegEx = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';

		// Filter by invoice issue date
		$invoice_date = $this->getState('invoice_date', null, 'string');
		$invoice_date_before = $this->getState('invoice_date_before', null, 'string');
		$invoice_date_after = $this->getState('invoice_date_after', null, 'string');

		if ( !empty($invoice_date) && preg_match($dateRegEx, $invoice_date))
		{
			$jFrom = \JFactory::getDate($invoice_date);
			$jFrom->setTime(0, 0, 0);
			$jTo = clone $jFrom;
			$jTo->setTime(23, 59, 59);

			$query->where(
				$db->qn('invoice_date') . ' BETWEEN ' . $db->q($jFrom->toSql()) .
				' AND ' . $db->q($jTo->toSql())
			);
		}
		elseif ( !empty($invoice_date_before) || !empty($invoice_date_after))
		{
			if ( !empty($invoice_date_before) && preg_match($dateRegEx, $invoice_date_before))
			{
				$jDate = \JFactory::getDate($invoice_date_before);
				$query->where($db->qn('invoice_date') . ' <= ' . $db->q($jDate->toSql()));
			}
			if ( !empty($invoice_date_after) && preg_match($dateRegEx, $invoice_date_after))
			{
				$jDate = \JFactory::getDate($invoice_date_after);
				$query->where($db->qn('invoice_date') . ' >= ' . $db->q($jDate->toSql()));
			}
		}

		// Filter by invoice email sent date
		$sent_on = $this->getState('sent_on', null, 'string');
		$sent_on_before = $this->getState('sent_on_before', null, 'string');
		$sent_on_after = $this->getState('sent_on_after', null, 'string');

		if ( !empty($sent_on) && preg_match($dateRegEx, $sent_on))
		{
			$jFrom = \JFactory::getDate($sent_on);
			$jFrom->setTime(0, 0, 0);
			$jTo = clone $jFrom;
			$jTo->setTime(23, 59, 59);

			$query->where(
				$db->qn('sent_on') . ' BETWEEN ' . $db->q($jFrom->toSql()) .
				' AND ' . $db->q($jTo->toSql())
			);
		}
		elseif ( !empty($sent_on_before) || !empty($sent_on_after))
		{
			if ( !empty($sent_on_before) && preg_match($dateRegEx, $sent_on_before))
			{
				$jDate = \JFactory::getDate($sent_on_before);
				$query->where($db->qn('sent_on') . ' <= ' . $db->q($jDate->toSql()));
			}
			if ( !empty($sent_on_after) && preg_match($dateRegEx, $sent_on_after))
			{
				$jDate = \JFactory::getDate($sent_on_after);
				$query->where($db->qn('sent_on') . ' >= ' . $db->q($jDate->toSql()));
			}
		}
	}

	/**
	 * Create or update an invoice from a subscription
	 *
	 * @param   Subscriptions  $sub  The subscription record
	 *
	 * @return  bool
	 */
	public function createInvoice(Subscriptions $sub)
	{
		$db = $this->getDbo();

		// Do we already have an invoice record?
		$invoiceRecord = $this->getClone()->reset(true, true);
		$invoiceRecord->find($sub->akeebasubs_subscription_id);

		$existingRecord = $invoiceRecord->akeebasubs_subscription_id == $sub->akeebasubs_subscription_id;

		// Flag to know if the template allows me to create an invoice
		$preventInvoice = false;

		// Get the template
		$templateRow = $this->findTemplate($sub);

		if (is_object($templateRow))
		{
			$template = $templateRow->template;
			$templateId = $templateRow->akeebasubs_invoicetemplate_id;
			$globalFormat = $templateRow->globalformat;
			$globalNumbering = $templateRow->globalnumbering;

			// Do I have a "no invoice" flag?
			$preventInvoice = (bool)$templateRow->noinvoice;
		}
		else
		{
			$template = '';
			$templateId = 0;
			$globalFormat = true;
			$globalNumbering = true;
		}

		// Do I have a "no invoice" flag on template or subscription?
		$sub_params = $sub->params;

		if (is_string($sub->params))
		{
			$sub_params = new \JRegistry($sub->params);
		}
		elseif (!($sub->params instanceof \JRegistry))
		{
			$sub_params = new \JRegistry(json_encode($sub->params));
		}

		if ($preventInvoice || $sub_params->get('noinvoice', false))
		{
			$sub_params->set('noinvoice', true);

			// I have to manually update the db, using the table object will cause an endless loop
			$query = $db->getQuery(true)
				->update('#__akeebasubs_subscriptions')
				->set($db->qn('params') . ' = ' . $db->quote((string)$sub_params))
				->where($db->qn('akeebasubs_subscription_id') . ' = ' . $sub->akeebasubs_subscription_id);

			$db->setQuery($query)->execute();

			return false;
		}

		if ($globalFormat)
		{
			$numberFormat = $this->container->params->get('invoice_number_format', '[N:5]');
		}
		else
		{
			$numberFormat = $templateRow->format;
		}

		if ($globalNumbering)
		{
			$numberOverride = $this->container->params->get('invoice_override', 0);
		}
		else
		{
			$numberOverride = $templateRow->number_reset;
		}

		// Get the configuration variables
		if ( !$existingRecord)
		{
			$jInvoiceDate = \JFactory::getDate();
			$invoiceData  = array(
				'akeebasubs_subscription_id' => $sub->akeebasubs_subscription_id,
				'extension'                  => 'akeebasubs',
				'invoice_date'               => $jInvoiceDate->toSql(),
				'enabled'                    => 1,
				'created_on'                 => $jInvoiceDate->toSql(),
				'created_by'                 => $sub->user_id,
			);

			if ($numberOverride)
			{
				// There's an override set. Use it and reset the override to 0.

				$invoice_no = $numberOverride;
				if ($globalNumbering)
				{
					// Global number override reset
					$this->container->params->set('invoice_override', 0);
					$this->container->params->save();
				}
				else
				{
					// Invoice template number override reset
					/** @var InvoiceTemplates $templateTable */
					$templateTable = $this->container->factory->model('InvoiceTemplates')->tmpInstance();
					$templateTable->find($templateRow->akeebasubs_invoicetemplate_id);
					$templateTable->save(array(
						'number_reset' => 0
					));
				}
			}
			else
			{
				$gnitIDs = [];

				if ($globalNumbering)
				{
					// Find all the invoice template IDs using Global Numbering and filter by them
					$q = $db->getQuery(true)
						->select($db->qn('akeebasubs_invoicetemplate_id'))
						->from($db->qn('#__akeebasubs_invoicetemplates'))
						->where($db->qn('globalnumbering') . ' = ' . $db->q(1));
					$db->setQuery($q);
					$rawIDs  = $db->loadColumn();
					$gnitIDs = array();

					foreach ($rawIDs as $id)
					{
						$gnitIDs[] = $db->q($id);
					}
				}

				// Get the new invoice number by adding one to the previous number
				$query = $db->getQuery(true)
					->select($db->qn('invoice_no'))
					->from($db->qn('#__akeebasubs_invoices'))
					->where($db->qn('extension') . ' = ' . $db->q('akeebasubs'))
					->order($db->qn('created_on') . ' DESC');

				// When not using global numbering search only invoices using this specific invoice template
				if ( !$globalNumbering)
				{
					$query->where($db->qn('akeebasubs_invoicetemplate_id') . ' = ' . $db->q($templateId));
				}
				else
				{
					$query->where($db->qn('akeebasubs_invoicetemplate_id') . ' IN(' . implode(',', $gnitIDs) . ')');
				}

				$db->setQuery($query, 0, 1);
				$invoice_no = (int)$db->loadResult();

				if (empty($invoice_no))
				{
					$invoice_no = 0;
				}

				$invoice_no++;
			}

			// Parse the invoice number
			$formated_invoice_no = $this->formatInvoiceNumber($numberFormat, $invoice_no, $jInvoiceDate->toUnix());

			// Add the invoice number (plain and formatted) to the record
			$invoiceData['invoice_no']     = $invoice_no;
			$invoiceData['display_number'] = $formated_invoice_no;

			// Add the invoice template ID to the record
			$invoiceData['akeebasubs_invoicetemplate_id'] = $templateId;
		}
		else
		{
			// Existing record, make sure it's extension=akeebasubs or quit
			if ($invoiceRecord->extension != 'akeebasubs')
			{
				$this->akeebasubs_invoicetemplate_id = 0;

				return false;
			}

			$invoice_no          = $invoiceRecord->invoice_no;
			$formated_invoice_no = $invoiceRecord->display_number;

			if (empty($formated_invoice_no))
			{
				$formated_invoice_no = $invoice_no;
			}

			$jInvoiceDate = \JFactory::getDate($invoiceRecord->invoice_date);

			$invoiceData = $invoiceRecord->toArray();
		}

		// Get the custom variables
		$vat_notice     = '';
		$cyprus_tag     = 'TRIANGULAR TRANSACTION';
		$cyprus_note    = 'We are obliged by local tax laws to write the words "triangular transaction" on all invoices issued in Euros. This doesn\'t mean anything in particular about your transaction.';

		if (is_object($sub->user))
		{
			$asUser = $sub->user;
		}
		else
		{
			/** @var Users $userModel */
			$userModel = $this->container->factory->model('Users')->tmpInstance();
			$asUser = $userModel->find(['user_id' => $this->container->platform->getUser()->id]);
		}

		$country        = $asUser->country;
		$isbusiness     = $asUser->isbusiness;
		$viesregistered = $asUser->viesregistered;

		$inEU = EUVATInfo::isEUVATCountry($country);

		// If the shopCountry is the same as the user's country we don't need to put the reverse charge info
		$shopCountry = $this->container->params->get('invoice_country');
		$reverse = ($country == $shopCountry) ? false : true;

		if ($inEU && $isbusiness && $viesregistered && $reverse)
		{
			$vat_notice  = $this->container->params->get('invoice_vatnote', 'VAT liability is transferred to the recipient, pursuant EU Directive nr 2006/112/EC and local tax laws implementing this directive.');
			$cyprus_tag  = 'REVERSE CHARGE';
			$cyprus_note = 'We are obliged by local and European tax laws to write the words "reverse charge" on all invoices issued to EU business when no VAT is charged. This is supposed to serve as a reminder that the recipient of the invoice (you) have to be registered to your local VAT office so as to apply to YOUR business\' VAT form the VAT owed by this transaction on the reverse charge basis, as described above. The words "reverse charge" DO NOT indicate a problem with your transaction, a cancellation or a refund.';
		}

		$extras = array(
			'[INV:ID]'                 => $invoice_no,
			'[INV:PLAIN_NUMBER]'       => $invoice_no,
			'[INV:NUMBER]'             => $formated_invoice_no,
			'[INV:INVOICE_DATE]'       => Format::date($jInvoiceDate->toUnix()),
			'[INV:INVOICE_DATE_EU]'    => $jInvoiceDate->format('d/m/Y', true),
			'[INV:INVOICE_DATE_USA]'   => $jInvoiceDate->format('m/d/Y', true),
			'[INV:INVOICE_DATE_JAPAN]' => $jInvoiceDate->format('Y/m/d', true),
			'[VAT_NOTICE]'             => $vat_notice,
			'[CYPRUS_TAG]'             => $cyprus_tag,
			'[CYPRUS_NOTE]'            => $cyprus_note,
		);

		// Render the template into HTML
		$invoiceData['html'] = Message::processSubscriptionTags($template, $sub, $extras, true);

		// Save the record
		$invoiceData['akeebasubs_subscription_id'] = $sub->akeebasubs_subscription_id;
		$invoiceRecord->save($invoiceData);
		$this->reset(true, true);
		$this->find($sub->akeebasubs_subscription_id);

		// Create PDF
		$this->createPDF();

		// Update subscription record with the invoice number without saving the
		// record through the Model, as this triggers the integration plugins,
		// which in turn causes double emails to be sent out.
		$query = $db->getQuery(true)
			->update($db->qn('#__akeebasubs_subscriptions'))
			->set($db->qn('akeebasubs_invoice_id') . ' = ' . $db->q($invoice_no))
			->where($db->qn('akeebasubs_subscription_id') . ' = ' . $db->q($sub->akeebasubs_subscription_id));
		$db->setQuery($query);
		$db->execute();

		$sub->akeebasubs_invoice_id = $invoice_no;

		// If auto-send is enabled, send the invoice by email
		$autoSend = $this->container->params->get('invoice_autosend', 1);

		if ($autoSend)
		{
			$this->emailPDF($sub);
		}

		return true;
	}

	/**
	 * Formats an invoice number
	 *
	 * @param   string  $numberFormat The invoice number format
	 * @param   integer $invoice_no   The plain invoice number
	 * @param   integer $timestamp    Optional timestamp, otherwise uses current timestamp
	 *
	 * @return  string  The formatted invoice number
	 */
	public function formatInvoiceNumber($numberFormat, $invoice_no, $timestamp = null)
	{
		// Tokenise the number format
		$formatstring = $numberFormat;
		$tokens       = array();
		$start        = strpos($formatstring, "[");
		while ($start !== false)
		{
			if ($start != 0)
			{
				$tokens[] = array('s', substr($formatstring, 0, $start));
			}

			$end = strpos($formatstring, ']', $start);

			if ($end == false)
			{
				$tokens[]     = array('s', substr($formatstring, $start));
				$formatstring = '';
				//$start        = false;
			}
			else
			{
				$innerContent = substr($formatstring, $start + 1, $end - $start - 1);
				$formatstring = substr($formatstring, $end + 1);
				$parts        = explode(':', $innerContent, 2);
				$tokens[]     = array(strtolower($parts[0]), $parts[1]);
			}

			$start = strpos($formatstring, "[");
		}

		// Parse the tokens
		if (empty($timestamp))
		{
			$timestamp = time();
		}
		$ret = '';
		foreach ($tokens as $token)
		{
			list($type, $param) = $token;
			switch ($type)
			{
				case 's':
					// String parameter
					$ret .= $param;
					break;
				case 'd':
					// Date parameter
					$ret .= date($param, $timestamp);
					break;
				case 'n':
					// Number format
					$param = (int)$param;
					$ret .= sprintf('%0' . $param . 'u', $invoice_no);
					break;
			}
		}

		return $ret;
	}

	/**
	 * Find and return an invoice template based on the subscription
	 *
	 * @param   Subscriptions $sub The susbcription record
	 *
	 * @return  object  The invoice template record
	 */
	private function findTemplate(Subscriptions $sub)
	{
		$level_id = $sub->akeebasubs_level_id;

		if (is_object($sub->user))
		{
			$mergedData = $sub->user->getMergedData($sub->user_id);
		}
		else
		{
			/** @var Users $userModel */
			$userModel = $this->container->factory->model('Users')->tmpInstance();
			$mergedData = $userModel->getMergedData();
		}


		$ret = null;

		// Load all enabled templates and check if they fit
		$templatesModel = $this->container->factory->model('InvoiceTemplates')->tmpInstance();
		$templates =
			$templatesModel
			->enabled(1)
			->filter_order('enabled')
			->filter_order_Dir('ASC')
			->get(true);

		$lastscore = 0;

		if ( !empty($templates))
		{
			foreach ($templates as $template)
			{
				$levels = [0];

				if (!empty($levels) && is_string($levels))
				{
					$levels = explode(',', $template->levels);
				}

				if (in_array(-1, $levels))
				{
					// "No template" is selected
					continue;
				}

				// Assume all "All levels" is selected...
				$found = true;

				// ...unless it's really not.
				if (!in_array(0, $levels))
				{
					// Check if our level is included
					$found = in_array($level_id, $levels);
				}

				if ( !$found)
				{
					continue;
				}

				$score = 0;

				// Calculate a "fitness" score based on:
				// a. country
				if (empty($template->country))
				{
					$score++;
				}
				elseif ($mergedData->country != $template->country)
				{
					continue;
				}
				else
				{
					$score += 3;
				}

				// b. isbusiness
				if ($template->isbusiness < 0)
				{
					$score++;
				}
				elseif ($mergedData->isbusiness != $template->isbusiness)
				{
					continue;
				}
				else
				{
					$score += 3;
				}

				if (($score > 0) && ($score > $lastscore))
				{
					$ret       = $template;
					$lastscore = $score;
				}
			}
		}

		return $ret;
	}

	/**
	 * Create a PDF representation of an invoice.
	 *
	 * @return  string  The (mangled) filename of the PDF file
	 */
	public function createPDF()
	{
		// Repair the input HTML
		if (function_exists('tidy_repair_string'))
		{
			$tidyConfig = array(
				'bare'                        => 'yes',
				'clean'                       => 'yes',
				'drop-proprietary-attributes' => 'yes',
				'output-html'                 => 'yes',
				'show-warnings'               => 'no',
				'ascii-chars'                 => 'no',
				'char-encoding'               => 'utf8',
				'input-encoding'              => 'utf8',
				'output-bom'                  => 'no',
				'output-encoding'             => 'utf8',
				'force-output'                => 'yes',
				'tidy-mark'                   => 'no',
				'wrap'                        => 0,
			);
			$repaired   = tidy_repair_string($this->html, $tidyConfig, 'utf8');

			if ($repaired !== false)
			{
				$this->html = $repaired;
			}
		}

		// Fix any relative URLs in the HTML
		$this->html = $this->fixURLs($this->html);

		//echo "<pre>" . htmlentities($invoiceRecord->html) . "</pre>"; die();

		// Create the PDF
		$pdf = $this->getTCPDF();
		$pdf->AddPage();
		$pdf->writeHTML($this->html, true, false, true, false, '');
		$pdf->lastPage();
		$pdfData = $pdf->Output('', 'S');

		unset($pdf);

		// Write the PDF data to disk using JFile::write();
		\JLoader::import('joomla.filesystem.file');

		if (function_exists('openssl_random_pseudo_bytes'))
		{
			$rand = openssl_random_pseudo_bytes(16);

			if ($rand === false)
			{
				// Broken or old system
				$rand = mt_rand();
			}
		}
		else
		{
			$rand = mt_rand();
		}

		$hashThis = json_encode($this->toArray()) . microtime() . $rand;

		if (function_exists('hash'))
		{
			$hash = hash('sha256', $hashThis);
		}
		else if (function_exists('sha1'))
		{
			$hash = sha1($hashThis);
		}
		else
		{
			$hash = md5($hashThis);
		}

		$name = $hash . '_' . $this->invoice_no . '.pdf';

		$path = $this->getInvoicePath();

		$ret = \JFile::write($path . $name, $pdfData);

		if ($ret)
		{
			// Delete the old invoice file
			$oldName = $this->filename;
			if (\JFile::exists($path . $oldName))
			{
				\JFile::delete($path . $oldName);
			}

			// Update the invoice record
			$this->filename = $name;
			$this->save();

			// return the name of the file
			return $name;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Send an invoice by email. If the invoice's PDF doesn't exist it will
	 * attempt to create it. If the extension != akeebasubs it will return
	 * false.
	 *
	 * @return  string  The filename of the PDF or false if the creation failed.
	 */
	public function emailPDF($sub)
	{
		\JLoader::import('joomla.filesystem.file');
		$path = $this->getInvoicePath();

		if (empty($this->filename) || !\JFile::exists($path . $this->filename))
		{
			$this->filename = $this->createPDF();
		}

		if (empty($this->filename) || !\JFile::exists($path . $this->filename))
		{
			return false;
		}

		// Get the subscription record
		if (empty($sub))
		{
			$sub = $this->subscription;
		}

		// Get the mailer
		$mailer = Email::getPreloadedMailer($sub, 'PLG_AKEEBASUBS_INVOICES_EMAIL');

		if ($mailer === false)
		{
			return false;
		}

		// Attach the PDF invoice
		$mailer->AddAttachment($path . $this->filename, 'invoice.pdf', 'base64', 'application/pdf');

		// Set the recipient
		$mailer->addRecipient(\JFactory::getUser($sub->user_id)->email);

		// Send it
		$result = $mailer->Send();
		$mailer = null;

		if ($result == true)
		{
			$this->sent_on = \JFactory::getDate()->toSql();
			$this->save();
		}

		return $result;
	}

	/**
	 * @return \TCPDF
	 */
	public function &getTCPDF()
	{
		$certificateFile = $this->container->params->get('invoice_certificatefile', 'certificate.cer');
		$secretKeyFile   = $this->container->params->get('invoice_secretkeyfile', 'secret.cer');
		$secretKeyPass   = $this->container->params->get('invoice_secretkeypass', '');
		$extraCertFile   = $this->container->params->get('invoice_extracert', 'extra.cer');

		$certificate = '';
		$secretkey   = '';
		$extracerts  = '';

		$path = JPATH_ADMINISTRATOR . '/components/com_akeebasubs/assets/tcpdf/certificates/';

		if (\JFile::exists($path . $certificateFile))
		{
			$certificate = @file_get_contents($path . $certificateFile);
		}
		if ( !empty($certificate))
		{
			if (\JFile::exists($path . $secretKeyFile))
			{
				$secretkey = @file_get_contents($path . $secretKeyFile);
			}
			if (empty($secretkey))
			{
				$secretkey = $certificate;
			}

			if (\JFile::exists($path . $extraCertFile))
			{
				$extracerts = @file_get_contents($path . $extraCertFile);
			}
			if (empty($extracerts))
			{
				$extracerts = '';
			}
		}

		// Set up TCPDF
		$jreg     = \JFactory::getConfig();
		$tmpdir   = $jreg->get('tmp_path');
		$tmpdir   = rtrim($tmpdir, '/' . DIRECTORY_SEPARATOR) . '/';
		$siteName = $jreg->get('sitename');

		$baseurl = \JUri::base();
		$baseurl = rtrim($baseurl, '/');

		define('K_TCPDF_EXTERNAL_CONFIG', 1);

		define ('K_PATH_MAIN', JPATH_BASE . '/');
		define ('K_PATH_URL', $baseurl);
		define ('K_PATH_FONTS', JPATH_ROOT . '/media/com_akeebasubs/tcpdf/fonts/');
		define ('K_PATH_CACHE', $tmpdir);
		define ('K_PATH_URL_CACHE', $tmpdir);
		define ('K_PATH_IMAGES', JPATH_ROOT . '/media/com_akeebasubs/tcpdf/images/');
		define ('K_BLANK_IMAGE', K_PATH_IMAGES . '_blank.png');
		define ('PDF_PAGE_FORMAT', 'A4');
		define ('PDF_PAGE_ORIENTATION', 'P');
		define ('PDF_CREATOR', 'Akeeba Subscriptions');
		define ('PDF_AUTHOR', $siteName);
		define ('PDF_UNIT', 'mm');
		define ('PDF_MARGIN_HEADER', 5);
		define ('PDF_MARGIN_FOOTER', 10);
		define ('PDF_MARGIN_TOP', 27);
		define ('PDF_MARGIN_BOTTOM', 25);
		define ('PDF_MARGIN_LEFT', 15);
		define ('PDF_MARGIN_RIGHT', 15);
		define ('PDF_FONT_NAME_MAIN', 'dejavusans');
		define ('PDF_FONT_SIZE_MAIN', 8);
		define ('PDF_FONT_NAME_DATA', 'dejavusans');
		define ('PDF_FONT_SIZE_DATA', 8);
		define ('PDF_FONT_MONOSPACED', 'dejavusansmono');
		define ('PDF_IMAGE_SCALE_RATIO', 1.25);
		define('HEAD_MAGNIFICATION', 1.1);
		define('K_CELL_HEIGHT_RATIO', 1.25);
		define('K_TITLE_MAGNIFICATION', 1.3);
		define('K_SMALL_RATIO', 2 / 3);
		define('K_THAI_TOPCHARS', true);
		define('K_TCPDF_CALLS_IN_HTML', false);

		require_once JPATH_ADMINISTRATOR . '/components/com_akeebasubs/assets/tcpdf/tcpdf.php';

		$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor(PDF_AUTHOR);
		$pdf->SetTitle('Invoice');
		$pdf->SetSubject('Invoice');

		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		$pdf->setHeaderFont(array('dejavusans', '', 8, '', false));
		$pdf->setFooterFont(array('dejavusans', '', 8, '', false));
		$pdf->SetFont('dejavusans', '', 8, '', false);

		if ( !empty($certificate))
		{
			$pdf->setSignature($certificate, $secretkey, $secretKeyPass, $extracerts);
		}

		return $pdf;
	}

	/**
	 * Returns a list of known invoicing extensions
	 *
	 * @param   integer $style 0 = raw sections list, 1 = list options, 2 = key/description array
	 *
	 * @return  string[]
	 */
	public function getExtensions($style = 0)
	{
		static $rawOptions = null;
		static $htmlOptions = null;
		static $shortlist = null;

		if (is_null($rawOptions))
		{
			$rawOptions = array();

			\JLoader::import('joomla.plugin.helper');
			\JPluginHelper::importPlugin('akeebasubs');
			\JPluginHelper::importPlugin('system');
			$app       = \JFactory::getApplication();
			$jResponse = $app->triggerEvent('onAKGetInvoicingOptions', array());

			if (is_array($jResponse) && !empty($jResponse))
			{
				foreach ($jResponse as $pResponse)
				{
					if ( !is_array($pResponse))
					{
						continue;
					}
					if (empty($pResponse))
					{
						continue;
					}

					$rawOptions[$pResponse['extension']] = $pResponse;
				}
			}
		}

		if ($style == 0)
		{
			return $rawOptions;
		}

		if (is_null($htmlOptions))
		{
			$htmlOptions = array();

			foreach ($rawOptions as $def)
			{
				$htmlOptions[]                = \JHtml::_('select.option', $def['extension'], $def['title']);
				$shortlist[$def['extension']] = $def['title'];
			}
		}

		if ($style == 1)
		{
			return $htmlOptions;
		}
		else
		{
			return $shortlist;
		}
	}

	private function fixURLs($buffer)
	{
		$pattern           = '/(href|src)=\"([^"]*)\"/i';
		$number_of_matches = preg_match_all($pattern, $buffer, $matches, PREG_OFFSET_CAPTURE);

		if ($number_of_matches > 0)
		{
			$substitutions = $matches[2];
			$last_position = 0;
			$temp          = '';

			// Loop all URLs
			foreach ($substitutions as &$entry)
			{
				// Copy unchanged part, if it exists
				if ($entry[1] > 0)
				{
					$temp .= substr($buffer, $last_position, $entry[1] - $last_position);
				}
				// Add the new URL
				$temp .= $this->replaceDomain($entry[0]);
				// Calculate next starting offset
				$last_position = $entry[1] + strlen($entry[0]);
			}
			// Do we have any remaining part of the string we have to copy?
			if ($last_position < strlen($buffer))
			{
				$temp .= substr($buffer, $last_position);
			}

			return $temp;
		}

		return $buffer;
	}

	private function replaceDomain($url)
	{
		static $mydomain = null;

		if (empty($mydomain))
		{
			$mydomain = \JUri::base(false);
			if (substr($mydomain, -1) == '/')
			{
				$mydomain = substr($mydomain, 0, -1);
			}
			if (substr($mydomain, -13) == 'administrator')
			{
				$mydomain = substr($mydomain, 0, -13);
			}
		}

		// Do we have a domain name?
		if (substr($url, 0, 7) == 'http://')
		{
			return $url;
		}
		if (substr($url, 0, 8) == 'https://')
		{
			return $url;
		}

		return $mydomain . '/' . ltrim($url, '/');
	}

	public function getInvoiceTemplateNames()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select(array(
				$db->qn('akeebasubs_invoicetemplate_id'),
				$db->qn('title'),
			))
			->from($db->qn('#__akeebasubs_invoicetemplates'));
		$db->setQuery($query);
		$res = $db->loadObjectList('akeebasubs_invoicetemplate_id');

		return $res;
	}

	public function getInvoicePath()
	{
		$date = new \JDate($this->invoice_date);
		$timezone = \JFactory::getConfig()->get('offset', null);

		if ($timezone && $timezone != 'UTC')
		{
			$date->setTimezone(new \DateTimeZone($timezone));
		}

		return JPATH_ADMINISTRATOR . '/components/com_akeebasubs/invoices/'. $date->format('Y-m', true, false) . '/';
    }
}
