<?php defined('BUCKYBALL_ROOT_DIR') || die();

class BEvents_Test extends PHPUnit_Framework_TestCase
{
    public function testFire()
    {
        $eventName = 'testEvent';
        $this->BEvents->event($eventName);
        $this->BEvents->on($eventName, 'BEvents_Test_Callback::callback');
        $result = $this->BEvents->fire($eventName);

        $this->assertContains(10, $result);
    }
}

class BEvents_Test_Callback
{
    public function callback()
    {
        return 10;
    }
}
