<?php

class FCom_ApiServer_Controller_Abstract extends FCom_Admin_Controller_Abstract
{
    protected static $_origClass;
    protected $_permission;
    protected $_authorizeActions = ['get', 'put', 'post', 'delete'];
    protected $_authorizeActionsWhitelist = [];

    public function __construct()
    {
        parent::__construct();
        foreach ($this->_authorizeActionsWhitelist as &$action) {
            $action = strtolower($action);
        }
        BResponse::i()->setContentType('application/json');
    }

    public function ok($msg = null)
    {
        BResponse::i()->set($msg);
        BResponse::i()->status(200);
    }
    public function created($msg = null)
    {
        BResponse::i()->set($msg);
        BResponse::i()->status(201);
    }

    public function notFound($msg = null)
    {
        BResponse::i()->set($msg);
        BResponse::i()->status(404);
    }

    public function badRequest($msg = null)
    {
        BResponse::i()->set($msg);
        BResponse::i()->status(400);
    }

    public function internalError($msg = null)
    {
        BResponse::i()->set($msg);
        BResponse::i()->status(503);
    }

    public function isApiCall()
    {
        return true;
    }

    public function authenticate($args = [])
    {
        $res = FCom_Admin_Model_User::i()->isLoggedIn();
        if (!$res) {
            return $this->authorize($args);
        }
        return $res;
    }


    public function authorize($args = [])
    {
        $authorizeActions = $this->_authorizeActions;
        if (false == $authorizeActions) {
            return true;
        }

        if (!is_array($authorizeActions)) {
            $authorizeActions = [$authorizeActions];
        }

        if (!empty($this->_authorizeActionsWhitelist)) {
            $authorizeActions = array_diff($authorizeActions, $this->_authorizeActionsWhitelist);
        }

        if (false == in_array($this->getAction(), $authorizeActions)) {
            return true;
        }

        $password = BRequest::i()->server('PHP_AUTH_PW');
        $username = BRequest::i()->server('PHP_AUTH_USER');
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
