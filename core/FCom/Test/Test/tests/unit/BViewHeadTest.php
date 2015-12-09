<?php defined('BUCKYBALL_ROOT_DIR') || die();

class BViewHeadTest extends \Codeception\TestCase\Test
{
    /**
     * @var \FCom\Test\UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }
    
    public function testTitleSet()
    {
        //every time new object
        /** @var BViewHead $head */
        $head = BViewHead::i(true);
        $head->setTitle("Test");

        $this->assertEquals('<title>Test</title>', $head->getTitle());
    }

    public function testTitleAdd()
    {
        //every time new object
        /** @var BViewHead $head */
        $head = BViewHead::i(true);
        $head->setTitleSeparator(" - ");
        $head->addTitle("Test");
        $head->addTitle("Test2");

        $this->assertEquals('<title>Test2 - Test</title>', $head->getTitle());
    }

    public function testMetaTagAdd()
    {
        //every time new object
        /** @var BViewHead $head */
        $head = BViewHead::i(true);
        $head->addMeta("keywords", "test test test");

        $this->assertEquals('<meta name="keywords" content="test test test" />', $head->getMeta("keywords"));
    }
}