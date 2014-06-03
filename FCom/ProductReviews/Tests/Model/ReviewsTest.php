<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_ProductReviews_Tests_Model_ReviewsTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__ . '/ReviewsTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_product_review'), "Pre-Condition");

        $customerId = 1;
        $productId = 1;
        $data = ['title' => 'Review 3', 'text' => 'review 3', 'rating' => 4];
        $this->FCom_ProductReviews_Model_Review->addNew($customerId, $productId, $data);

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_product_review'), "Inserting failed");
    }

    public function testHelpfulMark()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_product_review'), "Pre-Condition");

        $review = $this->FCom_ProductReviews_Model_Review->load(1);
        $helpfulVoices = $review->helpful_voices;
        $helpful = $review->helpful;
        $this->assertTrue($helpful > 0);
        $this->assertTrue($helpfulVoices > 0);

        $newMark = 5;
        $review->helpful($newMark);

        $review = $this->FCom_ProductReviews_Model_Review->load(1);
        $this->assertEquals($newMark + $helpful, $review->helpful, "Update helpful mark failed");
        $this->assertEquals($helpfulVoices + 1, $review->helpful_voices, "Update helpful mark failed");
    }

    public function testRemoveEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_product_review'), "Pre-Condition");

        $review = $this->FCom_ProductReviews_Model_Review->load(1);
        $review->delete();

        $this->assertEquals(1, $this->getConnection()->getRowCount('fcom_product_review'), "Delete failed");
    }
}
