<?php

class FCom_PushServer_Main extends BCLass
{
    protected $_services = array();

    static public function bootstrap()
    {
        static::i()->addService('default', 'FCom_PushServer_Service_Default');
    }

    public function addService($name, $config = array())
    {
        if (!empty($this->_services[$name])) {
            throw new BException('PushServer service is already declared: '.$name);
        }
        if (is_string($config)) {
            $config = array('class' => $config);
        } elseif (empty($config['class'])) {
            $config['class'] = $name;
        }
        if (empty($config['class']) || !class_exists($config['class'])) {
            throw new BException('Missing or invalid service class: ' . $name);
        }
        $this->_services[$name] = $config;
        $class = $config['class'];
        $instance = $class::i();
        $this->_services[$name]['instance'] = $instance;
        $instance->init();
        return $this;
    }

    static public function layoutInit()
    {
        $head = BLayout::i()->view('head');
        if ($head) {
            $head->js_raw('pushserver_init', array('content'=>"
FCom.pushserver_url = '".BApp::src('FCom_PushServer', 'index.php')."';
            "));
        }
    }
}
