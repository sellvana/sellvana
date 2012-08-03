<?php

class BLayout_Test extends PHPUnit_Framework_TestCase
{

    public function testViewRootDirSetGet()
    {
        BLayout::i()->viewRootDir('/tmp');

        $this->assertEquals('/tmp', BLayout::i()->viewRootDir());
    }
}
