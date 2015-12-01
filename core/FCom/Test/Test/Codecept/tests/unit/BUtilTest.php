<?php
namespace FCom\Test;


class BUtilTest extends \Codeception\TestCase\Test
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

    public function testToJson()
    {
        $data = ['key' => 'value'];
        $json = \BUtil::i()->toJson($data);
        $this->assertTrue(is_string($json));
    }

    public function testFromJson()
    {
        $json = '{"key":"value"}';
        $data = \BUtil::i()->fromJson($json);
        $this->assertTrue(is_array($data));
        $this->assertTrue(isset($data['key']));
    }

    public function testToJavascript()
    {
        $data = ['key' => 'value'];
        $json = \BUtil::i()->toJavaScript($data);
        $this->assertTrue(is_string($json));
    }

    public function testObjectToArray()
    {
        $obj = new \stdClass();
        $obj->key = 'value';
        $array = \BUtil::i()->objectToArray($obj);
        $this->assertTrue(is_array($array));
        $this->assertTrue(isset($array['key']));
    }

    public function testArrayToObject()
    {
        $array = ['key' => 'value'];
        $obj = \BUtil::i()->arrayToObject($array);

        $this->assertTrue(is_object($obj));
        $this->assertTrue(isset($obj->key));
        $this->assertEquals('value', $obj->key);
    }

    public function testSprintfn()
    {
        $format = 'Say %hi$s %bye$s!';
        $args = ['hi' => 'Hi', 'bye' => 'Goodbye'];
        $string = \BUtil::i()->sprintfn($format, $args);
        $this->assertEquals('Say Hi Goodbye!', $string);
    }

    public function testInjectVars()
    {
        $str = 'One :two :three';
        $args = ['two' => 2, 'three' => 3];
        $string = \BUtil::i()->injectVars($str, $args);
        $this->assertEquals('One 2 3', $string);
    }

    public function testArrayCompare()
    {
        $a1 = [1, 2, [3, 4, 5]];
        $a2 = [1, 2, [3, 4, 5, 6]];
        $res = \BUtil::i()->arrayCompare($a2, $a1);
        // 0 - number of parameter with difference
        // 2 - first dimenstion of array
        // 3 - second dimenstion of array
        $expected = ['0' => ['2' => ['3' => 6]]];
        $this->assertEquals($expected, $res);

        $a1 = [1, 2, [3, 4, 5]];
        $a2 = [1, 2, [3, 4, 5, 6]];
        $res = \BUtil::i()->arrayCompare($a1, $a2);
        //order of parameters was changed, so we expected '1' as array key
        $expected = ['1' => ['2' => ['3' => 6]]];
        $this->assertEquals($expected, $res);
    }

    public function testArrayMerge()
    {
        $a1 = [1, 2, [3, 4, 5]];
        $a2 = [1, 2, [3, 4, 5, 6]];
        $res = \BUtil::i()->arrayMerge($a1, $a2);
        $expected = [1, 2, [3, 4, 5], [3, 4, 5, 6]];
        $this->assertEquals($expected, $res);

        $a1 = [1, 2, [3, 4, 5], 6];
        $a2 = [1, 2, [3, 4, 5, 6], 7];
        $res = \BUtil::i()->arrayMerge($a1, $a2);
        $expected = [1, 2, [3, 4, 5], 6, [3, 4, 5, 6], 7];
        $this->assertEquals($expected, $res);
    }

    public function testRandomStrng()
    {
        $str = \BUtil::i()->randomString();
        $this->assertTrue(is_string($str));

        $str = \BUtil::i()->randomString(4, 'a');
        $this->assertEquals('aaaa', $str);
    }

    public function testRandomPattern()
    {
        $pattern = "{U10}-{L5}-{D2}";
        $res = \BUtil::i()->randomPattern($pattern);
        list($upper, $lower, $digits) = explode("-", $res);
        $this->assertTrue(strtoupper($upper) == $upper);
        $this->assertTrue(strtolower($lower) == $lower);
        $this->assertTrue(is_numeric($digits));
    }

    public function testUnparseUrl()
    {
        $urlInfo = [
            'scheme' => 'http',
            'user' => 'utest',
            'pass' => 'ptest',
            'host' => 'google.com',
            'port' => 80,
            'path' => '/i/test/',
            'query' => 'a=b&c=d',
            'fragment' => 'start'
        ];
        $url = \BUtil::i()->unparseUrl($urlInfo);
        $this->assertEquals('http://utest:ptest@google.com:80/i/test/?a=b&c=d#start', $url);
    }

    public function testSetUrlQuery()
    {
        $url = "http://google.com?a=b&c=d";
        $urlNew = \BUtil::i()->setUrlQuery($url, ['f' => 'e']);
        $this->assertEquals($url . '&f=e', $urlNew);

        $urlNew = \BUtil::i()->setUrlQuery($url, ['c' => 'd2']);
        $this->assertEquals("http://google.com?a=b&c=d2", $urlNew);
    }

    public function testPreviewText()
    {
        $text = 'abc abc abc abc abc';
        $textPreview = \BUtil::i()->previewText($text, 10);
        $this->assertEquals("abc abc ", $textPreview);

        $textPreview = \BUtil::i()->previewText($text, 13);
        $this->assertEquals("abc abc abc ", $textPreview);
    }
}