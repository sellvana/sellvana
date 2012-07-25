<?php

class BLayout_Test extends PHPUnit_Framework_TestCase
{
    public function SetUp()
    {
        BLayout::i()->unsetInstance();
    }

    public function tearDown()
    {
        BLayout::i()->unsetInstance();
    }

    public function testViewRootDirSetGet()
    {
        BLayout::i()->viewRootDir('/tmp');

        $this->assertEquals('/tmp', BLayout::i()->viewRootDir());
    }
}
