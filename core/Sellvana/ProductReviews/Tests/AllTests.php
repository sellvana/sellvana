<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_ProductReviews_Tests_AllTests
{

    public function main()
    {
        PHPUnit_TextUI_TestRunner::run($this->suite());
    }

    public function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit ProductReviews');

        $suite->addTestSuite('Sellvana_ProductReviews_Tests_Model_ReviewsTest');


        return $suite;
    }
}
