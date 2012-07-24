<?php

class BClassRegistry_Test extends PHPUnit_Framework_TestCase
{
    public function SetUp()
    {
        BClassRegistry::i()->unsetInstance();
    }

    public function tearDown()
    {
        BClassRegistry::i()->unsetInstance();
    }

    public function testOverrideClass()
    {
        $class = 'BClassRegistry_Test_A';
        $newClass = 'BClassRegistry_Test_B';
        BClassRegistry::i()->overrideClass($class, $newClass);
        $a = $class::i();
        $this->assertEquals("B", $a->me());
    }

    //todo: understand why it doesn't work
    public function testOverrideMethod()
    {
        $class = 'BClassRegistry_Test_A';
        $method = 'sayA';

        BClassRegistry::i()->overrideMethod($class, $method, array('BClassRegistry_Test_B', 'sayB'), true);

        $a = $class::i();

        $this->assertEquals("B", $a->sayA());
    }
}

class BClassRegistry_Test_A extends BClass
{
    public function me()
    {
        return 'A';
    }

    static public function sayA()
    {
        return 'A';
    }
}

class BClassRegistry_Test_B extends BClass
{
    public function me()
    {
        return 'B';
    }

    static public function sayB()
    {
        return 'B';
    }
}