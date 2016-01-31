<?php

/**
 * Class FCom_Core_View_Messages
 */
class FCom_Core_View_Messages extends FCom_Core_View_Abstract
{
    /**
     * @var array
     */
    protected $_classes = [
        'error' => 'danger',
    ];

    /**
     * @var array
     */
    protected $_titles = [
        'success' => 'Success',
        'warning' => 'Warning',
        'error' => 'Error',
    ];

    /**
     * @var array
     */
    protected $_icons = [
        'success' => 'ok',
        'warning' => 'exclamation',
        'error' => 'remove',
    ];

    /**
     * @return array
     */
    public function getMessages()
    {
        $namespace = $this->get('namespace');
        $messages = $this->get('messages');
        if (!$messages && $namespace) {
            $messages = $this->BSession->messages($namespace);
        }
        $out = [];
        foreach ((array)$messages as $m) {
            $out[] = [
                'type' => $m['type'],
                'msg' => !empty($m['msg']) ? (string)$m['msg'] : null,
                'msgs' => !empty($m['msgs']) ? $m['msgs'] : null,
                'class' => !empty($this->_classes[$m['type']]) ? $this->_classes[$m['type']] : $m['type'],
                'title' => isset($m['title']) ? (string)$m['title'] :
                    (!empty($this->_titles[$m['type']]) ? (string)$this->BLocale->_($this->_titles[$m['type']]) : null),
                'icon' => isset($m['icon']) ? $m['icon'] :
                    (!empty($this->_icons[$m['type']]) ? (string)$this->BLocale->_($this->_icons[$m['type']]) : $m['type']),
            ];
        }
        return $out;
    }
}
