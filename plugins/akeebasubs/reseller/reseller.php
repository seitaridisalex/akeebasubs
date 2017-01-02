<?php
/**
 * @package        akeebasubs
 * @subpackage     plugins.akeebasubs.reseller
 * @copyright      Copyright 2010-2017 Nicholas K. Dionysopoulos
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\Helper\Email;
use Akeeba\Subscriptions\Admin\Model\Subscriptions;

class plgAkeebasubsReseller extends Akeeba\Subscriptions\Admin\PluginAbstracts\AkeebasubsBase
{
    private $company_url;
    private $api_key;
    private $api_pwd;
    private $emails;

    /**
     * Public constructor. Overridden to load the language strings.
     *
     * @param object $subject
     * @param array $config
     */
	public function __construct(& $subject, $config = array())
	{
		if (!is_object($config['params']))
		{
			JLoader::import('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}

        $this->autoloadLanguage = true;

        $config['templatePath'] = dirname(__FILE__);
        $config['name']         = 'reseller';

		parent::__construct($subject, $config);
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
            'title'   => JText::_('PLG_AKEEBASUBS_RESELLER_EMAILSECTION'),
            'keys'    => array(
                'emailerr' => JText::_('PLG_AKEEBASUBS_RESELLER_EMAIL_ERR_TITLE'),
            )
        );
    }

	/**
	 * Called whenever a subscription is modified. Namely, when its enabled status,
	 * payment status or valid from/to dates are changed.
	 *
	 * @param   Subscriptions  $row   The subscriptions row
	 * @param   array          $info  The row modification information
	 *
	 * @return  void
	 */
	public function onAKSubscriptionChange(Subscriptions $row, array $info)
	{
        // If I already have a reseller coupon, there's no need to continue
        $params = $row->params;

        if(isset($params['subcustom']['reseller_coupon']) && $params['subcustom']['reseller_coupon'])
        {
            return;
        }

        // Let's get the params from the current level
        $level = $row->level;
        $level_params = $level->params;

        if(isset($level_params['reseller_company_url']))
        {
            $this->company_url = $level_params['reseller_company_url'];
        }

        if(isset($level_params['reseller_api_key']))
        {
            $this->api_key = $level_params['reseller_api_key'];
        }

        if(isset($level_params['reseller_api_pwd']))
        {
            $this->api_pwd = $level_params['reseller_api_pwd'];
        }

        $emails = isset($level_params['reseller_notify_emails']) ? $level_params['reseller_notify_emails'] : '';
        $emails = explode(',', $emails);
        $emails = array_map('trim', $emails);

        $this->emails = $emails;

        // Sanity checks
        if(!$this->company_url || !$this->api_key || !$this->api_pwd)
        {
            // Required info missing, let's stop here
            return;
        }

        $payState = $row->getFieldValue('state', 'N');

        // No payment has been made yet; do not contact the company site
        if ($payState == 'N')
        {
            return;
        }

        // Did the payment status just change to C or P? It's a new subscription
        if (array_key_exists('state', (array)$info['modified']) && in_array($payState, array('P', 'C')))
        {
            if ($row->enabled)
            {
                if (is_object($info['previous']) && $info['previous']->getFieldValue('state') == 'P')
                {
                    // A pending subscription just got paid
                    $this->requestCode($row);
                }
                else
                {
                    // A new subscription just got paid; send new subscription notification
                    $this->requestCode($row);
                }
            }
            elseif ($payState == 'C')
            {
                if ($row->contact_flag <= 2)
                {
                    // A new subscription which is for a renewal (will be active in a future date)
                    $this->requestCode($row);
                }
            }
        }
        elseif ($info['status'] == 'modified')
        {
            // If the subscription got disabled and contact_flag is 3, do not contact the user.
            // The flag is set to 3 only when a user has already renewed his subscription.
            if (array_key_exists('enabled', (array)$info['modified']) && !$row->enabled && ($row->contact_flag == 3))
            {
                return;
            }
            elseif (array_key_exists('enabled', (array)$info['modified']) && $row->enabled)
            {
                // Subscriptions just enabled, suppose date triggered
                if (($payState == 'C'))
                {
                    $this->requestCode($row);
                }
            }
        }
	}

    public function onSubscriptionFormRenderPerSubFields($cache)
    {
        $fields = array();

        // I don't want to display such field while purchasing a subscription
        if(isset($cache['firstrun']))
        {
            return $fields;
        }

        if(!isset($cache['subscriptionlevel']))
        {
            return $fields;
        }

        $coupon = '';

        if(isset($cache['subcustom']))
        {
            $subparams = $cache['subcustom'];

            if(isset($subparams['subcustom']) && isset($subparams['subcustom']['reseller_coupon']))
            {
                $coupon = $subparams['subcustom']['reseller_coupon'];
            }
        }

        // Let's fetch the params from the subscription level
        /** @var \Akeeba\Subscriptions\Admin\Model\Levels $level */
        $level = $this->container->factory->model('Levels')->tmpInstance();
        $level->find($cache['subscriptionlevel']);
        $params = $level->params;

        // User is displaying his own subscription, readonly field
        if(isset($cache['useredit']) && $cache['useredit'])
        {
            $label = isset($params['reseller_frontend_label'])? $params['reseller_frontend_label'] : '';

            // A single dash means "hide the label"
            if($label == '-')
            {
                $label = '';
            }

            $html  = isset($params['reseller_frontend_format'])? $params['reseller_frontend_format'] : '<span>[COUPONCODE]</span>';
            $html  = str_replace('[COUPONCODE]', $coupon, $html);

            $href = isset($params['reseller_coupon_link'])? $params['reseller_coupon_link'] : '';

            if($href)
            {
                $href  = str_replace('[COUPONCODE]', $coupon, $href);
                $html .= '<a href="'.$href.'" target="_blank" class="btn btn-success">'.JText::_('PLG_AKEEBASUBS_RESELLER_REDEEM').'</a>';
            }
        }
        else
        {
            // Backend layout, we will display the input field with the correct label
            $label = JText::_('PLG_AKEEBASUBS_RESELLER_CODE_LABEL');
            $html  = '<input type="text" name="params[subcustom][reseller_coupon]" value="'.$coupon.'" />';
        }

        // Setup the field
        $field = array(
            'id'              => 'reseller_coupon',
            'label'           => $label,
            'elementHTML'     => $html,
            'invalidLabel'    => '',
            'isValid'         => true,
            'validationClass' => ''
        );

        // Add the field to the return output
        $fields[] = $field;

        return $fields;
    }

	/**
     * Contacts the company site and requests for a coupon code that will be stored inside this subscription
     *
     * @param   Subscriptions   $row
     */
    private function requestCode($row)
    {
        // I have to pass the "dontNotify" flag, otherwise the event will get into an infinite loop
        $new_data = array(
            '_dontNotify' => true
        );

        // Let's pass some notes to the company site about this subscription
        $notes = 'Reseller subscription ID: '.$row->akeebasubs_subscription_id."\n";

        $juser  = $row->juser;
        $notes .= 'User: '.$juser->name."\n";
        $notes .= 'Email: '.$juser->email."\n";

        $notes = base64_encode($notes);

        $url  = trim($this->company_url, '/');
        $url .= '/index.php?option=com_akeebasubs&view=APICoupons&task=create';
        $url .= '&key='.$this->api_key.'&pwd='.$this->api_pwd.'&format=json';
        $url .= '&notes='.$notes;

        $isValid = true;
        $error   = '';

	    $container = \FOF30\Container\Container::getInstance('com_akeebasubs');
        $adapter  = new \FOF30\Download\Download($container);
        $raw_data = $adapter->getFromURL($url);

        // Do I get a connection error?
        if(!$raw_data)
        {
            $isValid = false;
            $error   = JText::_('PLG_AKEEBASUBS_RESELLER_ERR_CONNECTION');
        }

        $data = json_decode($raw_data, true);

        // Did I get an invalid response?
        if($isValid && !$data)
        {
            $isValid = false;
            $error   = JText::_('PLG_AKEEBASUBS_RESELLER_ERR_INVALID_DATA');
        }

        // Did I get an error while creating a coupon?
        if ($isValid && isset($data['error']))
        {
            $isValid = false;
            $error   = $data['error'];
        }

        // Anyway, the coupon code is missing?
        if($isValid && !isset($data['coupon']))
        {
            $isValid = false;
            $error   = JText::_('PLG_AKEEBASUBS_RESELLER_ERR_MISSING_COUPON');
        }

        // Should I notify the user?
        if(!$isValid)
        {
            $this->notifyAdministrator($row, $error);

            $new_data['state']   = 'P';
            $new_data['enabled'] = false;
        }
        else
        {
            // Ah ok, if we're here we can safely continue
            $params = $row->params;
            $params['subcustom']['reseller_coupon'] = $data['coupon'];

            $new_data['params'] = $params;
        }

        try
        {
            $row->save($new_data);
        }
        catch(\Exception $e)
        {
            // Wait something bad happened while saving the subscription?
            $this->notifyAdministrator($row, JText::_('PLG_AKEEBASUBS_RESELLER_ERR_SAVING_SUBSCRIPTION'));
        }
    }

    private function notifyAdministrator($sub, $error)
    {
        if(!$this->emails)
        {
            $this->emails = $this->getSuperAdministrators();
        }

        $extra = array(
            '[RESELLER_ERROR]' => $error
        );

        $mailer = Email::getPreloadedMailer($sub, 'PLG_AKEEBASUBS_RESELLER_EMAIL', $extra);

        if(!$mailer)
        {
            return;
        }

        $mailer->addRecipient($this->emails);
        $mailer->Send();
    }

    private function getSuperAdministrators()
    {
        static $ret = null;

        $db     = JFactory::getDBO();

        // Let's cache the result
        if(!is_null($ret))
        {
            return $ret;
        }

        $ret = array();

        try
        {
            $query = $db->getQuery(true)
                        ->select($db->qn('rules'))
                        ->from($db->qn('#__assets'))
                        ->where($db->qn('parent_id') . ' = ' . $db->q(0));
            $db->setQuery($query, 0, 1);
            $rulesJSON	 = $db->loadResult();
            $rules		 = json_decode($rulesJSON, true);

            $rawGroups = $rules['core.admin'];
            $groups = array();

            if (empty($rawGroups))
            {
                return $ret;
            }

            foreach ($rawGroups as $g => $enabled)
            {
                if ($enabled)
                {
                    $groups[] = $db->q($g);
                }
            }

            if (empty($groups))
            {
                return $ret;
            }
        }
        catch (Exception $exc)
        {
            return $ret;
        }

        // Get the user IDs of users belonging to the SA groups
        try
        {
            $query = $db->getQuery(true)
                        ->select($db->qn('user_id'))
                        ->from($db->qn('#__user_usergroup_map'))
                        ->where($db->qn('group_id') . ' IN(' . implode(',', $groups) . ')' );
            $db->setQuery($query);
            $rawUserIDs = $db->loadColumn(0);

            if (empty($rawUserIDs))
            {
                return $ret;
            }

            $userIDs = array();

            foreach ($rawUserIDs as $id)
            {
                $userIDs[] = $db->q($id);
            }
        }
        catch (Exception $exc)
        {
            return $ret;
        }

        // Get the user information for the Super Administrator users
        try
        {
            $query = $db->getQuery(true)
                ->select(array(
                    $db->qn('email'),
                ))->from($db->qn('#__users'))
                ->where($db->qn('id') . ' IN(' . implode(',', $userIDs) . ')')
                ->where($db->qn('sendEmail') . ' = ' . $db->q('1'));

            $db->setQuery($query);
            $ret = $db->loadColumn();
        }
        catch (Exception $exc)
        {
            return $ret;
        }

        return $ret;
    }
}