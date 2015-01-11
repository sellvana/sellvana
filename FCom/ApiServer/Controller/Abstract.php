<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_ApiServer_Controller_Abstract
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 */

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
        $this->BResponse->setContentType('application/json');
    }

    public function ok($msg = null)
    {
        $this->BResponse->set($msg);
        $this->BResponse->status(200);
    }
    public function created($msg = null)
    {
        $this->BResponse->set($msg);
        $this->BResponse->status(201);
    }

    public function notFound($msg = null)
    {
        $this->BResponse->set($msg);
        $this->BResponse->status(404);
    }

    public function badRequest($msg = null)
    {
        $this->BResponse->set($msg);
        $this->BResponse->status(400);
    }

    public function internalError($msg = null)
    {
        $this->BResponse->set($msg);
        $this->BResponse->status(503);
    }

    public function isApiCall()
    {
        return true;
    }

    public function authenticate($args = [])
    {
        $res = $this->FCom_Admin_Model_User->isLoggedIn();
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

        $password = $this->BRequest->server('PHP_AUTH_PW');
        $username = $this->BRequest->server('PHP_AUTH_USER');
        $user = $this->FCom_Admin_Model_User->sessionUser();
        if ($user) {
            return true;
        }
        $user = $this->FCom_Admin_Model_User->authenticateApi($username, $password);
        if ($user) {
            $user->login();
            return true;
        }
        $this->BResponse->status(403, null, $this->BUtil->toJson("Authorization required"));
    }
}
