<?php

/**
 * Class FCom_PushServer_Shell_Debug
 *
 * @property FCom_PushServer_Model_Client $FCom_PushServer_Model_Client
 */
class FCom_PushServer_Shell_Debug extends FCom_Core_Shell_Abstract
{
    static protected $_origClass = __CLASS__;

    static protected $_actionName = 'pushserver:debug';

    static protected $_availOptions = [
        'l!' => 'listen',
    ];

    protected function _run()
    {
        if ($this->getOption('l')) {
            $this->println('Starting debug listener...');
            $this->BResponse->nocache()->startLongResponse(false);

            /** @var FCom_PushServer_Model_Client $client */
            $client = $this->FCom_PushServer_Model_Client->load('DEBUG', 'session_id');
            if (!$client) {
                $client = $this->FCom_PushServer_Model_Client->create(['session_id' => 'DEBUG'])
                    #->setData('windows', [$sessionId => ['connections' => [0 => 0]]])
                    ->save();
            }
            $client->setClientData('DEBUG', '0')->checkIn();
            foreach ((array)$this->getOption('l') as $channel) {
                $client->subscribe($channel);
            }
            while (true) {
                $client->waitForMessages();
                $this->println(print_r($client->getMessages(), 1));
            }
        } else {
            $this->println('No action specified, nothing done.');
        }
    }

    public function getShortHelp()
    {
        return 'PushServer Debugger';
    }

    public function getLongHelp()
    {
        return <<<EOT

PushServer Debugger

Options:
    {white*}-l {green*}[channel]{white*}
    --listen={green*}[channel] {white*}...{/}     Listen to a channel

EOT;
    }
}