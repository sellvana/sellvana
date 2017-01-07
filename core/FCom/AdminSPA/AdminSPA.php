<?php

/**
 * Class FCom_AdminSPA_AdminSPA
 *
 * @property FCom_Admin_Model_User FCom_Admin_Model_User
 * @property FCom_Admin_Model_Personalize FCom_Admin_Model_Personalize
 */
class FCom_AdminSPA_AdminSPA extends BClass
{
    protected $_responseTypes = [
        '_messages' => true,
        '_user' => true,
        '_permissions' => true,
        //'_nav' => true,
        '_personalize' => true,
        '_local_notifications' => true,
        '_redirect' => true,
        '_login' => true,
        '_csrf_token' => true,
//        '_ok' => true,
    ];

    protected $_responsesToPush = [];

    public function addResponseType($type, $callback)
    {
        $this->_responseTypes[$type] = $callback;
    }

    public function addResponses($responses = true)
    {
        if (true === $responses) {
            $responses = [];
            foreach ($this->_responseTypes as $type => $callback) {
                $responses[$type] = true;
            }
        } else {
            $responses = (array)$responses;
            foreach ($responses as $i => $u) {
                if (is_int($i) && is_string($u)) {
                    $responses[$u] = true;
                }
            }
        }
        $this->_responsesToPush = $this->BUtil->arrayMerge($this->_responsesToPush, $responses);
        return $this;
    }

    public function mergeResponses(array $result = [])
    {
        foreach ($this->_responsesToPush as $type => $data) {
            if (!empty($this->_responseTypes[$type])) {
                $callback = $this->_responseTypes[$type];
                if (true === $callback) {
                    $callback = [$this, 'responseCallback' . $type];
                }
                $result[$type] = $this->BUtil->call($callback, $data);
            } else {
                $result[$type] = !empty($result[$type]) ? $this->BUtil->arrayMerge($result[$type], $data) : $data;
            }
        }
        return $result;
    }
    
    public function responseCallback_messages($data)
    {
        if (is_array($data)) {
            foreach ($data as $i => $r) {
                if (is_string($r)) {
                    $data[$i] = ['type' => 'info', 'message' => $r];
                }
            }
        }
        return $data;
    }
    
    public function responseCallback_user($data)
    {
        $user = $this->FCom_Admin_Model_User->sessionUser();
        $result = $user ? $user->as_array() : false;
        return $result;
    }
    
    public function responseCallback_permissions($data)
    {
        return [];
    }
    
    public function responseCallback_nav($data)
    {
        return [];
    }

    public function responseCallback_personalize($data)
    {
        $userId = $this->FCom_Admin_Model_User->sessionUserId();
        $pers = $this->FCom_Admin_Model_Personalize->load($userId, 'user_id');
        $result = $pers && $pers->get('data_json') ? $this->BUtil->fromJson($pers->get('data_json')) : false;
        return $result;
    }

    public function responseCallback_local_notifications($data)
    {
        return $data;
    }

    public function responseCallback_redirect($data)
    {
        return $data;
    }

    public function responseCallback_login($data)
    {
        return $data;
    }

//    public function responseCallback_ok($data)
//    {
//        return $data;
//    }

    public function responseCallback_csrf_token($data)
    {
        return $this->BSession->csrfToken();
    }

}