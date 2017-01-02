<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\View\APICoupons;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Site\Model\APICoupons;

class Json extends \FOF30\View\DataView\Json
{
	protected function onBeforeCreate($tpl = null)
	{
		/** @var APICoupons $model */
		$model = $this->getModel();

		// Get the key and password
		$key   = $this->input->getCmd('key', '');
		$pwd   = $this->input->getCmd('pwd', '');
        $notes = $this->input->getBase64('notes', '');

        if($notes)
        {
            $notes = base64_decode($notes);
        }

		// Create the coupon and set the response into $this->item
		$this->item = $model->createCoupon($key, $pwd, $notes);
		$this->alreadyLoaded = true;
		$this->useHypermedia = false;

		// Call the parent's onBeforeRead which handles the output
		parent::onBeforeRead($tpl);
	}

    protected function onBeforeGetlimits($tpl = null)
    {
        /** @var APICoupons $model */
        $model = $this->getModel();

        // Get the key and password
        $key = $this->input->getCmd('key', '');
        $pwd = $this->input->getCmd('pwd', '');

        // Create the coupon and set the response into $this->item
        $this->item = $model->getApiLimits($key, $pwd);
        $this->alreadyLoaded = true;
        $this->useHypermedia = false;

        // Call the parent's onBeforeRead which handles the output
        parent::onBeforeRead($tpl);
    }
}