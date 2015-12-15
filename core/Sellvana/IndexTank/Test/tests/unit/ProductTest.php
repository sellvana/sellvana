<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_IndexTank_Tests_Index_ProductTest
 *
 * @property Sellvana_IndexTank_Index_Product $Sellvana_IndexTank_Index_Product
 */

class ProductTest extends \Codeception\TestCase\Test
{
    /**
     * @var \Sellvana\Wishlist\UnitTester
     */
    protected $tester;

    private $_model = null;

    public function _before()
    {
        $this->_model = Sellvana_IndexTank_Index_Product::i()->model();
    }

    public function testIndex()
    {
        $this->assertTrue(is_object($this->_model));
    }

    public function testIndexStatus()
    {
        $status = Sellvana_IndexTank_Index_Product::i()->status();
        $this->assertTrue(is_array($status));
    }

}
