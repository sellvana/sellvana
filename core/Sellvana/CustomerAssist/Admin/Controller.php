<?php

/**
 * Class Sellvana_CustomerAssist_Admin_Controller
 */
class Sellvana_CustomerAssist_Admin_Controller extends FCom_Admin_Controller_Abstract
{
    public function action_help_me()
    {
        $sessionId = $this->BRequest->get('session_id');
        $this->layout('/customer_assist/help_me');
    }
}