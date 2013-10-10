<?php

class FCom_Core_View_Messages extends BView
{
    protected $_classes = array(
        'error' => 'danger',
    );

    protected $_titles = array(
        'success' => 'Success',
        'warning' => 'Warning',
        'error' => 'Error',
    );

    protected $_icons = array(
        'success' => 'ok',
        'warning' => 'exclamation',
        'error' => 'remove',
    );

    public function getAlerts()
    {
        $namespace = $this->get('namespace');
        $messages = $this->get('messages');
        if (!$messages && $namespace) {
            $messages = BSession::i()->messages($namespace);
        }
        $out = array();
        foreach ((array)$messages as $m) {
            $out[] = array(
                'type' => $m['type'],
                'msg' => $m['msg'],
                'class' => !empty($this->_classes[$m['type']]) ? $this->_classes[$m['type']] : $m['type'],
                'title' => !empty($m['title']) ? $m['title'] :
                    (!empty($this->_titles[$m['type']]) ? BLocale::_($this->_titles[$m['type']]) : null),
                'icon' => !empty($m['icon']) ? $m['icon'] :
                    (!empty($this->_icons[$m['type']]) ? BLocale::_($this->_icons[$m['type']]) : $m['type']),
            );
        }
        return $out;
    }
}
