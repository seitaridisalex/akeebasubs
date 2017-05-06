<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Model;

defined('_JEXEC') or die;

use FOF30\Date\Date;
use FOF30\Model\Model;
use JFactory;
use JLoader;

class Reports extends Model
{
	public function getInvoices()
	{
		// Get the display parameters
		$params = $this->getInvoiceListParameters();

		$db = $this->container->platform->getDbo();

		$query = $db->getQuery(true)
			->select(array(
				'akinv.invoice_date', 'akinv.display_number as number',
				'aksub.net_amount', 'aksub.tax_amount', 'aksub.gross_amount',
				'aksub.tax_percent',
				'akuser.isbusiness', 'akuser.businessname', 'akuser.occupation',
				'akuser.vatnumber',
				'akuser.viesregistered', 'akuser.address1', 'akuser.address2',
				'akuser.city', 'akuser.state', 'akuser.zip', 'akuser.country',
				'aksub.processor', 'aksub.processor_key', 'usr.id', 'usr.name',
				'usr.username', 'usr.email'
			))
			->from($db->qn('#__akeebasubs_invoices') . ' AS ' . $db->qn('akinv'))
			->innerJoin(
				$db->qn('#__akeebasubs_subscriptions') . ' AS ' . $db->qn('aksub') .
				' ON (' . $db->qn('aksub') . '.' . $db->qn('akeebasubs_subscription_id') . ' = ' .
				$db->qn('akinv') . '.' . $db->qn('akeebasubs_subscription_id') . ')'
			)
			->innerJoin(
				$db->qn('#__users') . ' AS ' . $db->qn('usr') .
				' ON (' . $db->qn('usr') . '.' . $db->qn('id') . ' = ' .
				$db->qn('aksub') . '.' . $db->qn('user_id') . ')'
			)
			->join(
				'LEFT OUTER',
				$db->qn('#__akeebasubs_users') . ' AS ' . $db->qn('akuser') .
				' ON (' . $db->qn('akuser') . '.' . $db->qn('user_id') . ' = ' .
				$db->qn('aksub') . '.' . $db->qn('user_id') . ')'
			)
			->where('YEAR(' . $db->qn('akinv') . '.' . $db->qn('invoice_date') . ') =' . $db->q($params['year']))
			->where('MONTH(' . $db->qn('akinv') . '.' . $db->qn('invoice_date') . ') =' . $db->q($params['month']))
			->where($db->qn('akinv') . '.' . $db->qn('extension') . ' = ' . $db->q($params['extension']));

		// Invoices report: sort by invoice date
		if ($params['invoices'])
		{
			$query->order($db->qn('akinv') . '.' . $db->qn('invoice_date'));
		}
		
		// VIES report: when the viesregistered flag is set
		if ($params['vies'])
		{
			$this->layout = 'invoices_vies';
			$query->where($db->qn('akuser') . '.' . $db->qn('viesregistered') . ' >= ' . $db->q(1));
		}

		// VAT MOSS: not VIES registered, non-zero tax, sort by country
		if ($params['vatmoss'])
		{
			$shopCountry = $this->container->params->get('invoice_country');

			$this->layout = 'invoices_vatmoss';
			$query->where($db->qn('akuser') . '.' . $db->qn('viesregistered') . ' = ' . $db->q(0));
			$query->where($db->qn('aksub') . '.' . $db->qn('tax_amount') . ' > ' . $db->q(0.01));
			$query->where($db->qn('akuser') . '.' . $db->qn('country') . ' <> ' . $db->q($shopCountry));
			$query->order($db->qn('akuser') . '.' . $db->qn('country') . ' ASC');
		}

		// Third countries: not VIES registered, zero tax, sort by country
		if ($params['thirdcountry'])
		{
			$this->layout = 'invoices_thirdcountry';
			$query->where($db->qn('akuser') . '.' . $db->qn('viesregistered') . ' = ' . $db->q(0));
			$query->where($db->qn('aksub') . '.' . $db->qn('tax_amount') . ' < ' . $db->q(0.01));
			$query->order($db->qn('akuser') . '.' . $db->qn('country') . ' ASC');
		}

		if ($params['template_id'])
		{
			$template_ids = (array)$params['template_id'];
			$template_ids = array_map(array($db, 'quote'), $template_ids);
			$query->where($db->qn('akeebasubs_invoicetemplate_id').' IN('.implode(',', $template_ids).')');
		}

		$db->setQuery($query);
		$records = $db->loadObjectList();

		return $records;
	}

	public function getInvoiceListParameters()
	{
		JLoader::import('joomla.utilities.date');
		$jNow = new Date();

		$month = $this->input->getInt('month', 0);

		if (($month < 1) || ($month > 12))
		{
			$month = (int)$jNow->format('m');
			$month--;
		}

		$year = $this->input->getInt('year', 0);

		if (($year < 2010) || ($year > 2100))
		{
			$year = (int)$jNow->format('Y');
		}

		if ($month <= 0)
		{
			$month = 12;
			$year--;
		}

		$invoices = false;
		$vies = false;
		$vatmoss = false;
		$thirdcountry = false;

		switch ($this->getState('task', 'invoices'))
		{
			case 'invoices':
				$invoices = true;
				break;
				
			case 'vies':
				$vies = true;
				break;

			case 'vatmoss':
				$vatmoss = true;
				break;

			case 'thirdcountry':
				$thirdcountry = true;
				break;
		}

		$template = $this->getState('template_id', array());

		$invoiceExtension = $this->input->getCmd('extension', 'akeebasubs');

		return array(
			'month'		=> $month,
			'year'		=> $year,
			'vies'		=> $vies,
			'invoices'	=> $invoices,
			'vatmoss'	=> $vatmoss,
			'thirdcountry'	=> $thirdcountry,
			'extension'	=> $invoiceExtension,
			'template_id'	=> $template
		);
	}

}
