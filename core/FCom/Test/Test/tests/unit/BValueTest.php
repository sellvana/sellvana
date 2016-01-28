<?php

class BValueTest extends \Codeception\TestCase\Test
{
    /**
     * @var \FCom\Test\UnitTester
     */
    protected $tester;

    /**
     * @var BValue
     */
    protected $object;

    protected function _before()
    {
        $this->object = new BValue('test');
    }

    protected function _after()
    {
    }

    /**
     * @covers BValue::toPlain
     */
    public function testToPlain()
    {
        $this->assertEquals('test', $this->object->toPlain());
    }

    /**
     * @covers BValue::__toString
     */
    public function test__toString()
    {
        $this->assertEquals('test', (string)$this->object);
    }

    /**
     * @covers BValue::toPlain
     */
    public function testToPlainArray()
    {
        $this->object = new BValue([1]);
        $this->assertEquals([1], $this->object->toPlain());
    }

    /**
     * @covers BValue::__toString
     */
    public function test__toStringArray()
    {
        $this->object = new VO(['Array']);
        $this->assertEquals('Array', (string)$this->object);
    }

    /**
     * @covers BValue::toPlain
     */
    public function testToPlainStdObj()
    {
        $this->object = new BValue((object)[1]);
        $this->assertEquals((object)[1], $this->object->toPlain());
    }

    /**
     * @covers BValue::toPlain
     */
    public function testToPlainCustomObj()
    {
        $this->object = new BValue(new VO([1]));
        $this->assertEquals('1', $this->object->toPlain());
    }

    /**
     * @covers BValue::__toString
     */
    public function test__toStringCustomObj()
    {
        $this->object = new BValue(new VO([1]));
        $this->assertEquals('1', (string)$this->object);
    }
}

class VO
{
    protected $_val;

    public function __construct($v)
    {
        $this->_val = $v;
    }

    public function __toString()
    {
        if (is_string($this->_val)) {
            return $this->_val;
        }
        if (is_array($this->_val)) {
            return implode(', ', $this->_val);
        }

        return (string) $this->_val;
    }
}