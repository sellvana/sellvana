<?php
namespace core\FCom\Test;


class BClassDecoratorTest extends \Codeception\TestCase\Test
{
    /**
     * @var \core\FCom\Test\UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    public function testConstructor()
    {
        $a = new \BClassDecorator(["core\FCom\Test\BClassDecorator_Test_A"]);

        $this->assertTrue(is_object($a));
        $this->assertInstanceOf("BClassDecorator", $a);
    }

    public function testFunctionCall()
    {
        $a = new \BClassDecorator(["core\FCom\Test\BClassDecorator_Test_A"]);
        $this->assertEquals("A", $a->me());
    }

    public function testFunctionCallStatic()
    {
        $a = new \BClassDecorator(["core\FCom\Test\BClassDecorator_Test_A"]);
        $this->assertEquals("A", $a->meStatic());
    }

    public function testPropertySetUnset()
    {
        $a = new \BClassDecorator(["core\FCom\Test\BClassDecorator_Test_A"]);
        $a->foo = 123;
        $b = $a->getDecoratedComponent();

        $this->assertTrue(isset($b->foo));
        $this->assertEquals("123", $b->foo);

        unset($a->foo);
        $this->assertFalse(isset($b->foo));
    }
}

class BClassDecorator_Test_A
{
    public function me()
    {
        return 'A';
    }

    public function meStatic()
    {
        return 'A';
    }
}