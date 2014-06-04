<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Core_View_Messages extends FCom_Core_View_Abstract
{
    protected $_classes = [
        'error' => 'danger',
    ];

    protected $_titles = [
        'success' => 'Success',
        'warning' => 'Warning',
        'error' => 'Error',
    ];

    protected $_icons = [
        'success' => 'ok',
        'warning' => 'exclamation',
        'error' => 'remove',
    ];

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
                'msg' => !empty($m['msg']) ? $m['msg'] : null,
                'msgs' => !empty($m['msgs']) ? $m['msgs'] : null,
                'class' => !empty($this->_classes[$m['type']]) ? $this->_classes[$m['type']] : $m['type'],
                'title' => isset($m['title']) ? $m['title'] :
                    (!empty($this->_titles[$m['type']]) ? $this->BLocale->_($this->_titles[$m['type']]) : null),
                'icon' => isset($m['icon']) ? $m['icon'] :
                    (!empty($this->_icons[$m['type']]) ? $this->BLocale->_($this->_icons[$m['type']]) : $m['type']),
            ];
        }
        return $out;
    }
}
