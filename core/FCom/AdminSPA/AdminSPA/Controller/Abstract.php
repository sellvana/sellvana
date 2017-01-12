<?php

/**
 * Class FCom_AdminSPA_AdminSPA_Controller_Abstract
 *
 * @property FCom_AdminSPA_AdminSPA FCom_AdminSPA_AdminSPA
 */
abstract class FCom_AdminSPA_AdminSPA_Controller_Abstract extends FCom_Admin_Controller_Abstract
{
    public function authenticate($args = [])
    {
        $result = parent::authenticate();
        if (!$result) {
//            $this->BResponse->header([
//                "{$this->BRequest->serverProtocol()} 401 Not authorized",
//                "Status: 401 Not authorized",
//            ]);
            $this->addMessage('Session expired, authorization required', 'error');
            $this->addResponses(['_login' => true]);
            $this->respond();
            return false;
        }
        return $result;
    }

    public function onBeforeDispatch()
    {
        if ($this->BRequest->csrf()) {
            $this->addMessage('Session token expired, please try again', 'warning');
            $this->addResponses(['_csrf_token' => true]);
            $this->respond();
            return false;
            #$this->BResponse->status(403, 'Possible CSRF detected', 'Possible CSRF detected');
        }
        return parent::onBeforeDispatch();
    }

    public function onAfterDispatch()
    {

    }

    public function addResponses($updates)
    {
        $this->FCom_AdminSPA_AdminSPA->addResponses($updates);
        return $this;
    }

    public function addMessage($text, $type = null)
    {
        if ($text instanceof Exception) {
            $text = $text->getMessage();
            if (!$type) {
                $type = 'error';
            }
        } elseif (is_string($text)) {
            if (!$type) {
                $type = 'info';
            }
        } else {
            throw new BException('Invalid message text type');
        }
        $this->addResponses(['_messages' => [
            ['type' => $type, 'text' => $text],
        ]]);
        return $this;
    }

    public function ok()
    {
        $this->addResponses(['ok' => true]);
        return $this;
    }

    public function respond($result = [])
    {
        $result = $this->FCom_AdminSPA_AdminSPA->mergeResponses($result);
        $this->BResponse->json($result);
    }
}