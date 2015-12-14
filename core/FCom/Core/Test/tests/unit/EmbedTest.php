<?php defined('BUCKYBALL_ROOT_DIR') || die();

class EmbedTest extends \Codeception\TestCase\Test
{
    /**
     * @var \FCom\Core\UnitTester
     */
    protected $tester;

    protected $embed = null;

    protected $shortLink = 'http://y2u.be/8UVNT4wvIGY';

    protected function _before()
    {
        $this->embed = new FCom_Core_Vendor_Embed;
    }

    protected function _after()
    {
    }

    public function testGetYoutubeUrl()
    {
        $expected = '/^(\<iframe\>)|(youtube)|(\<\/iframe\>)$/';

        $html = $this->embed->parse('https://www.youtube.com/watch?v=hLQl3WQQoQ0');
        $this->assertNotNull($html);
        $this->assertRegExp($expected, $html);

    }

    public function testShortYoutubeUrl()
    {
        $expected = '/^(\<iframe\>)|(youtube)|(\<\/iframe\>)$/';

        $html = $this->embed->parse('http://youtu.be/8UVNT4wvIGY');
        $this->assertNotNull($html);
        $this->assertRegExp($expected, $html);

    }

    public function testShortestYoutubeUrl()
    {
        $expected = "\n<p>http://y2u.be/8UVNT4wvIGY</p>\n";

        $html = $this->embed->parse('http://y2u.be/8UVNT4wvIGY');
        $this->assertNotNull($html);
        $this->assertEquals($expected, $html);
    }

    public function testDataToHtmlFromVimeo()
    {
        $expected = '/^(\<iframe\>)|(vimeo)|(\<\/iframe\>)$/';
        $html = $this->embed->parse('https://vimeo.com/channels/staffpicks/142794636');
        $this->assertNotNull($html);
        $this->assertRegExp($expected, $html);
    }


    public function testDataToJsonFromVimeo()
    {
        $json = $this->embed->linkInfo()
                            ->parse('https://vimeo.com/channels/staffpicks/142794636');
        $this->assertJson($json);
        $decode = BUtil::i()->fromJson($json);

        $expected = ['type', 'title', 'html', 'provider_name', 'thumbnail_url'];
        $keys = array_keys($decode);
        $this->assertEquals($expected, $keys);

        $expected = '/^(\<iframe\>)|(vimeo)|(\<\/iframe\>)$/';
        $this->assertArrayHasKey('html', $decode);
        $this->assertRegExp($expected, $decode['html']);

        $this->assertSame('video', $decode['type']);
        $this->assertSame('Vimeo', $decode['provider_name']);

        $expected = '/[0-9]+_[0-9]{3}x[0-9]{3}?(.jpg|.png|.jpeg)$/';
        $this->assertRegExp($expected, $decode['thumbnail_url']);
    }

    public function testDataToJsonFromYoutube()
    {
        $json = $this->embed->linkInfo()
                            ->parse('https://www.youtube.com/watch?v=hLQl3WQQoQ0');
        $this->assertJson($json);
        $decode = BUtil::i()->fromJson($json);

        $expected = ['type', 'title', 'html', 'provider_name', 'thumbnail_url'];
        $keys = array_keys($decode);
        $this->assertEquals($expected, $keys);

        $expected = '/^(\<iframe\>)|(youtube)|(\<\/iframe\>)$/';
        $this->assertArrayHasKey('html', $decode);
        $this->assertRegExp($expected, $decode['html']);

        $this->assertSame('video', $decode['type']);
        $this->assertSame('YouTube', $decode['provider_name']);

        $expected = '/(.jpg|.png|.jpeg)$/';
        $this->assertRegExp($expected, $decode['thumbnail_url']);
    }
}