<?php

class BPubSub_Test extends PHPUnit_Framework_TestCase
{
    public function testFire()
    {
        $eventName = 'testEvent';
        BPubSub::i()->event($eventName);
        BPubSub::i()->on($eventName, 'BPubSub_Test_Callback::callback');
        BPubSub::i()->fire($eventName);

        //todo: write good test

        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
}

class BPubSub_Test_Callback
{
    static public function callback()
    {
        return 10;
    }
}