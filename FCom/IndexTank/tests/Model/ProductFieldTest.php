<?php
class FCom_IndexTank_Model_Tests_ProductFieldTest extends PHPUnit_Framework_TestCase
{
    public function testListArray()
    {
        $list = FCom_IndexTank_Model_ProductField::i()->get_list();
        $this->assertTrue(is_array($list));
    }
}