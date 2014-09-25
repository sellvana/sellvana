<?php defined('BUCKYBALL_ROOT_DIR') || die();

class BEvents_Test extends PHPUnit_Framework_TestCase
{
    public function testFire()
    {
        $eventName = 'testEvent';
        $events = BEvents::i();
        $events->event($eventName);
        $events->on($eventName, 'BEvents_Test_Callback::callback');
        $result = $events->fire($eventName);

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
