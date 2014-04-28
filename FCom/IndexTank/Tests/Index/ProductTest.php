<?php

class FCom_IndexTank_Tests_Index_ProductTest extends PHPUnit_Framework_TestCase
{
    private $_model = null;
    public function setUp()
    {
        $this->_model = FCom_IndexTank_Index_Product::i()->model();
    }

    public function testIndex()
    {
        $this->assertTrue( is_object( $this->_model ) );
    }

    public function testIndexStatus()
    {
        $status = FCom_IndexTank_Index_Product::i()->status();
        $this->assertTrue( is_array( $status ) );
    }

}