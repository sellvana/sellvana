<?php

class BEvents_Test extends PHPUnit_Framework_TestCase
{
    public function testFire()
    {
        $eventName = 'testEvent';
        BEvents::i()->event($eventName);
        BEvents::i()->on($eventName, 'BEvents_Test_Callback::callback');
        $result = BEvents::i()->fire($eventName);

        $this->assertContains(10, $result);
    }
}

class BEvents_Test_Callback
{
    static public function callback()
    {
        return 10;
    }
}
