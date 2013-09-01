<?php

class FCom_PushServer_Main extends BCLass
{
    static public function bootstrap()
    {
        static::i()
            ->addService('/^./', 'FCom_PushServer_Service_Default::catchAll')
            ->addService('session', 'FCom_PushServer_Service_Default')
            ->addService('/^session:(.*)$/', 'FCom_PushServer_Service_Default')
        ;
    }

    static public function layoutInit()
    {
        $head = BLayout::i()->view('head');
        if ($head) {
            $head->js_raw('pushserver_init', array('content'=>"
FCom.pushserver_url = '".BApp::src('@FCom_PushServer/index.php')."';
            "));
        }
    }

    protected $_services = array();

    public function addService($channel, $callback)
    {
        $this->_services[] = array(
            'channel' => $channel,
            'is_pattern' => $channel[0] === '/', //TODO: needs anything fancier?
            'callback' => $callback,
        );
        return $this;
    }

    public function dispatch($request)
    {
        if (empty($request['messages'])) {
            return $this;
        }
        $client = FCom_PushServer_Model_Client::i()->sessionClient();
        foreach ($request['messages'] as $message) {
            try {
                foreach ($this->_services as $service) {
                    if ($service['channel'] !== $message['channel']
                        && !($service['is_pattern'] && preg_match($service['channel'], $message['channel']))
                    ) {
                        continue;
                    }
                    if (is_callable($service['callback'])) {
                        call_user_func($service['callback'], $message);
                        continue;
                    }
                    if (!class_exists($service['callback'])) {
                        continue;
                    }
                    $class = $service['callback'];
                    $instance = $class::i();
                    if (!($instance instanceof FCom_PushServer_Service_Abstract)) {
                        //TODO: exception?
                        continue;
                    }

                    $instance->setMessage($message, $client);

                    if (!$instance->onBeforeDispatch()) {
                        continue;
                    }

                    if (!empty($message['signal'])) {
                        $method = 'signal_' . $message['signal'];
                        if (!method_exists($class, $method)) {
                            $method = 'onUnknownSignal';
                        }
                    } else {
                        $method = 'onUnknownSignal';
                    }

                    $instance->$method();

                    $instance->onAfterDispatch();
                }
            } catch (Exception $e) {
                $this->send(array(
                    'ref_seq' => !empty($message['seq']) ? $message['seq'] : null,
                    'ref_signal' => !empty($message['signal']) ? $message['signal'] : null,
                    'signal' => 'error',
                    'description' => $e->getMessage()
                ));
            }
        }

        return $this;
    }
}
