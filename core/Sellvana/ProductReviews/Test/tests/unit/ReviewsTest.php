<?php

/**
 * Class Sellvana_ProductReviews_Tests_Model_ReviewsTest
 *
 * @property Sellvana_ProductReviews_Model_Review $Sellvana_ProductReviews_Model_Review
 */
class ReviewsTest extends \Codeception\TestCase\Test
{
    /**
     * @var \Sellvana\Wishlist\UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->initDataSet();
    }

    protected function _after()
    {
    }

    private function initDataSet()
    {
        $xml = simplexml_load_file(__DIR__ . '/ReviewsTest.xml');
        if ($xml) {
            foreach ($xml->children() as $table => $field) {
                $this->tester->haveInDatabase((string)$table, (array)BUtil::i()->arrayFromXml($field)['@attributes']);
            }
        } else {
            die('__ERROR__');
        }
    }

    public function testAddEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_product_review');
        $mReview = Sellvana_ProductReviews_Model_Review::i(true);

        $data = [
            'title' => 'Review 3',
            'text' => 'review 3',
            'rating' => 4,
            'customer_id' => 1,
            'product_id' => 1
        ];

        $mReview->create($data)->save();

        $this->tester->seeNumRecords(3, 'fcom_product_review');
    }

    public function testHelpfulMark()
    {
        $this->tester->seeNumRecords(2, 'fcom_product_review');

        /** @var Sellvana_ProductReviews_Model_Review $review */
        $review = Sellvana_ProductReviews_Model_Review::i()->load(1);
        $helpfulVoices = $review->helpful_voices;
        $helpful = $review->helpful;
        $this->assertTrue($helpful > 0);
        $this->assertTrue($helpfulVoices > 0);

        $newMark = 5;
        $review->helpful($newMark);

        $review = Sellvana_ProductReviews_Model_Review::i()->load(1);
        $this->assertEquals($newMark + $helpful, $review->helpful, "Update helpful mark failed");
        $this->assertEquals($helpfulVoices + 1, $review->helpful_voices, "Update helpful mark failed");
    }

    public function testRemoveEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_product_review');

        $review = Sellvana_ProductReviews_Model_Review::i()->load(1);
        $review->delete();

        $this->tester->seeNumRecords(1, 'fcom_product_review');
    }
}
