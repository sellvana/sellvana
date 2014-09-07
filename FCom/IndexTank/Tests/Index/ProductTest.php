<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_IndexTank_Tests_Index_ProductTest extends PHPUnit_Framework_TestCase
{
    private $_model = null;
    public function setUp()
    {
        $this->_model = $this->FCom_IndexTank_Index_Product->model();
    }

    public function testIndex()
    {
        $this->assertTrue(is_object($this->_model));
    }

    public function testIndexStatus()
    {
        $status = $this->FCom_IndexTank_Index_Product->status();
        $this->assertTrue(is_array($status));
    }

}
