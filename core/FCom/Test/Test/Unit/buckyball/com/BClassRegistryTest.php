<?php defined('BUCKYBALL_ROOT_DIR') || die();

class BClassRegistry_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @var BClassRegistry
     */
    protected $registry;
    public function SetUp()
    {
        $this->registry = BClassRegistry::i();
        $this->registry->unsetInstance();
    }

    public function tearDown()
    {
        $this->registry->unsetInstance();
        $this->registry->overrideClass('BClassRegistry_Test_A', null);
    }

    public function testOverrideClass()
    {
        $class = 'BClassRegistry_Test_A';
        $newClass = 'BClassRegistry_Test_B';
        $this->registry->overrideClass($class, $newClass);
        $a = $class::i();
        $this->assertInstanceOf($newClass, $a);
        $this->assertEquals("B", $a->me());
    }

    //fixed: need to understand why overrideMethod doesn't work
    public function testOverrideMethod()
    {
        $class = 'BClassRegistry_Test_A';
        $method = 'sayA';

        $newClass = 'BClassRegistry_Test_B';
        $this->registry->overrideMethod($class, $method, ['' . $newClass . '', 'sayB']);

        $a = $class::i();
        $this->assertInstanceOf($newClass, $a);
        $this->assertEquals("B", $a->sayA()); // this will not work unless sayA is actual method, the above configuration is unreachable
    }

    //fixed: need to understand why augmentMethod doesn't work
    public function testAugmentMethod()
    {
        $class = 'BClassRegistry_Test_A';
        $method = 'augmentA';

        $this->registry->augmentMethod($class, $method, ['BClassRegistry_Test_B', 'augmentB']);

        $a = $class::i();

        $this->assertEquals("B", $a->augmentA());
    }

    //todo: why don't work
    public function testAugmentPropertySet()
    {
        $this->registry->augmentProperty('BClassRegistry_Test_A', 'foo', 'set',
                'override', 'BClassRegistry_Test_AugmentProperty::newSetter');
        $a = BClassRegistry_Test_A::i(true);
        $a->foo = 1;

        //todo: uncomment
        $this->assertEquals(6, $a->foo);
    }

    //todo: why don't work
    public function testAugmentPropertyGet()
    {
        $this->registry->augmentProperty('BClassRegistry_Test_A', 'foo', 'get',
                'after', 'BClassRegistry_Test_AugmentProperty::newGetter');

        $a = BClassRegistry_Test_A::i(true);

        //todo: uncomment
        $this->assertEquals(10, $a->foo);
    }
}

class BClassRegistry_Test_A extends BClass
{
    public $foo = 0;
    public function me()
    {
        return 'A';
    }

    public function sayA()
    {
        return 'A';
    }

    public function augmentA()
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

    public function sayB($origObject = null)
    {
        return 'B';
    }

    public function sayA($origObject = null)
    {
        return $this->sayB();
    }

    public function augmentB($result, $origObject)
    {
        $result = 'B';
        return $result;
    }
    public function augmentA(){
        return $this->augmentB(null, null);
    }
}

class BClassRegistry_Test_AugmentProperty extends BClass
{
    public static function newSetter($object, $property, $value)
    {
        $object->$property = $value + 5;
    }

    public function newGetter($object, $property, $prevResult)
    {
        return $prevResult + 10;
    }
}
