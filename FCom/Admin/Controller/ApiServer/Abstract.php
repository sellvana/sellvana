<?php

class FCom_Admin_Controller_ApiServer_Abstract extends FCom_Admin_Controller_Abstract
{
    protected static $_origClass;
    protected $_permission;
    protected $_authorizeActions = array('put', 'post', 'delete');
    protected $_authorizeActionsWhitelist = array();

    public function __construct() {
        parent::__construct();
        foreach ($this->_authorizeActionsWhitelist as &$action) {
            $action = strtolower($action);
        }
        BResponse::i()->contentType('application/json');
    }

    public function afterDispatch() {
        BResponse::i()->status(200);
    }


    public function action_get()
    {
    }
    public function action_post()
    {
    }
    public function action_put()
    {
    }
    public function action_delete()
    {
    }

    public function created($msg = null)
    {
        if (!is_string($msg)) {
            $msg = BUtil::toJson($msg);
        }
        BResponse::i()->status(201, null, $msg);
    }

    public function notFound($msg = null)
    {
        BResponse::i()->status(404, null, $msg);
    }

    public function badRequest($msg = null)
    {
        BResponse::i()->status(400, null, $msg);
    }

    public function isApiCall()
    {
        return true;
    }

    public function authenticate($args=array())
    {
        $res = FCom_Admin_Model_User::i()->isLoggedIn();
        if (!$res) {
            return $this->authorize($args);
        }
        return $res;
    }


    public function authorize($args=array())
    {
        $authorizeActions = $this->_authorizeActions;
        if (false == $authorizeActions) {
            return true;
        }

        if (!is_array($authorizeActions)) {
            $authorizeActions = array($authorizeActions);
        }

        if (!empty($this->_authorizeActionsWhitelist)) {
            $authorizeActions = array_diff($authorizeActions, $this->_authorizeActionsWhitelist);
        }

        if (false == in_array($this->getAction(), $authorizeActions)) {
            return true;
        }

        $password = BRequest::i()->headers('PHP_AUTH_PW');
        $username = BRequest::i()->headers('PHP_AUTH_USER');
        $user = FCom_Admin_Model_User::i()->sessionUser();
        if ($user) {
            return true;
        }
        $user = FCom_Admin_Model_User::i()->authenticateApi($username, $password);
        if ($user) {
            $user->login();
            return true;
        }
        BResponse::i()->status(403, null, BUtil::toJson("Authorization required"));
    }
}