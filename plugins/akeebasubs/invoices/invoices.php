<?php
/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2017 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use FOF30\Container\Container;
use Akeeba\Subscriptions\Admin\Model\Invoices;

class plgAkeebasubsInvoices extends JPlugin
{
	/**
	 * Which subscriptions did we generate an invoice for with this plugin during this page load? This helps us prevent
	 * double invoices under some circumstances.
	 *
	 * @var   array
	 */
	protected static $generatedInvoicesFor = array();

	/**
	 * Called whenever a subscription is modified. Namely, when its enabled status, payment status or valid from/to
	 * dates are changed.
	 *
	 * @param   Subscriptions  $row   The subscriptions row
	 * @param   array          $info  The row modification information
	 *
	 * @return  void
	 */
	public function onAKSubscriptionChange(Subscriptions $row, array $info)
	{
		if (is_null($info['modified']) || empty($info['modified']))
		{
			return;
		}

		// Load the plugin's language files
		$lang = JFactory::getLanguage();
		$lang->load('plg_akeebasubs_invoices', JPATH_ADMINISTRATOR, 'en-GB', true);
		$lang->load('plg_akeebasubs_invoices', JPATH_ADMINISTRATOR, null, true);
		// Akeeba Subscriptions language files
		$lang->load('com_akeebasubs', JPATH_SITE, 'en-GB', true);
		$lang->load('com_akeebasubs', JPATH_SITE, $lang->getDefault(), true);
		$lang->load('com_akeebasubs', JPATH_SITE, null, true);
		$lang->load('com_akeebasubs', JPATH_ADMINISTRATOR, 'en-GB', true);
		$lang->load('com_akeebasubs', JPATH_ADMINISTRATOR, $lang->getDefault(), true);
		$lang->load('com_akeebasubs', JPATH_ADMINISTRATOR, null, true);

		// Do not issue invoices for free subscriptions
		if ($row->gross_amount < 0.01)
		{
			return;
		}

		// Should we handle this subscription?
		$generateAnInvoice = ($row->getFieldValue('state') == "C");

		$whenToGenerate = $this->params->get('generatewhen', '0');

		if ($whenToGenerate == 1)
		{
			// Handle new subscription, even if they are not yet enabled
			$state = $row->getFieldValue('state');
			$specialCasePending = in_array($state, array('P', 'C')) && !$row->enabled;
			$generateAnInvoice = $generateAnInvoice || $specialCasePending;
		}

		// If the payment is over a week old do not generate an invoice. This
		// prevents accidentally creating an invoice for past subscriptions not
		// handled by the invoicing system
		JLoader::import('joomla.utilities.date');
		$jCreated = new JDate($row->created_on);
		$jNow = new JDate();
		$dateDiff = $jNow->toUnix() - $jCreated->toUnix();
		if ($dateDiff > 604800)
		{
			return;
		}

		// Only handle not expired subscriptions
		if ($generateAnInvoice && !in_array($row->akeebasubs_subscription_id, self::$generatedInvoicesFor))
		{
			$db = JFactory::getDbo();

			// Check if there is an invoice for this subscription already
			$query = $db->getQuery(true)
				->select('*')
				->from('#__akeebasubs_invoices')
				->where($db->qn('akeebasubs_subscription_id') . ' = ' . $db->q($row->akeebasubs_subscription_id));
			$db->setQuery($query);
			$oldInvoices = $db->loadObjectList('akeebasubs_subscription_id');

			if (count($oldInvoices) > 0)
			{
				return;
			}

			// Create (and, optionally, send) a new invoice
			/** @var Invoices $invoicesModel */
			$invoicesModel = Container::getInstance('com_akeebasubs')->factory->model('Invoices')->tmpInstance();

			$invoicesModel->createInvoice($row);

			self::$generatedInvoicesFor[] = $row->akeebasubs_subscription_id;
		}
	}

	/**
	 * Called whenever the administrator asks to refresh integration status.
	 *
	 * @param $user_id int The Joomla! user ID to refresh information for.
	 */
	public function onAKUserRefresh($user_id)
	{
		// Do nothing
	}

	public function onAKGetInvoicingOptions()
	{
		JLoader::import('joomla.filesystem.file');

		return array(
			'extension'   => 'akeebasubs',
			'title'       => 'Integrated invoicing',
			'enabled'     => 1,
			'backendurl'  => 'index.php?option=com_akeebasubs&view=Invoice&task=read&id=%s',
			'frontendurl' => 'index.php?option=com_akeebasubs&view=Invoice&task=read&id=%s',
		);
	}

	/**
	 * Notifies the component of the supported email keys by this plugin.
	 *
	 * @return  array
	 */
	public function onAKGetEmailKeys()
	{
		$this->loadLanguage();

		return array(
			'section' => $this->_name,
			'title'   => JText::_('PLG_AKEEBASUBS_INVOICES_EMAILSECTION'),
			'keys'    => array(
				'email' => JText::_('PLG_AKEEBASUBS_INVOICES_EMAIL_TITLE'),
				'creditnote' => JText::_('PLG_AKEEBASUBS_INVOICES_CREDITNOTE_TITLE'),
			)
		);
	}
}