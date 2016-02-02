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
        $locale = $this->BLocale;
        foreach ((array)$messages as $m) {
            if (!empty($m['msgs'])) {
                foreach ($m['msgs'] as &$msg) {
                    $msg = (string)$locale->_($msg);
                }
                unset($msg);
            }
            $out[] = [
                'type' => $m['type'],
                'msg' => !empty($m['msg']) ? (string)$locale->_($m['msg']) : null,
                'msgs' => !empty($m['msgs']) ? $m['msgs'] : null,
                'class' => !empty($this->_classes[$m['type']]) ? $this->_classes[$m['type']] : $m['type'],
                'title' => isset($m['title']) ? (string)$m['title'] :
                    (!empty($this->_titles[$m['type']]) ? (string)$locale->_($this->_titles[$m['type']]) : null),
                'icon' => isset($m['icon']) ? $m['icon'] :
                    (!empty($this->_icons[$m['type']]) ? (string)$locale->_($this->_icons[$m['type']]) : $m['type']),
            ];
        }
        return $out;
    }

    /**
     * @return string
     */
    public function twigName()
    {
        $namespace = $this->get('namespace');
        if ($namespace == 'admin') {
            $this->_params['view_name'] = 'admin/messages';
        }

        return parent::twigName();
    }


}
