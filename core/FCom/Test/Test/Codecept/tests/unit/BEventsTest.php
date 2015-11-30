<?php
namespace FCom\Test;


class BEventsTest extends \Codeception\TestCase\Test
{
    /**
     * @var \FCom\Test\UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    public function testFire()
    {
        $eventName = 'testEvent';
        $events = \BEvents::i();
        $events->event($eventName);
        $events->on($eventName, 'FCom\Test\BEvents_Test_Callback::callback');
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