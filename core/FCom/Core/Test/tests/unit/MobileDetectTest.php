<?php

class MobileDetectTest extends \Codeception\TestCase\Test
{
    /**
     * @var \FCom\Core\UnitTester
     */
    protected $tester;

    /**
     * @var FCom_Core_Vendor_MobileDetect
     */
    protected $mDetect;

    protected function _before()
    {
        $this->mDetect = new FCom_Core_Vendor_MobileDetect;
    }

    public function testBasicMethods()
    {
        $this->assertNotEmpty($this->mDetect->getScriptVersion());
        $this->mDetect->setHttpHeaders([
            'SERVER_SOFTWARE' => 'Apache/2.2.15 (Linux) Whatever/4.0 PHP/5.2.13',
            'REQUEST_METHOD' => 'POST',
            'HTTP_HOST' => 'home.ghita.org',
            'HTTP_X_REAL_IP' => '1.2.3.4',
            'HTTP_X_FORWARDED_FOR' => '1.2.3.5',
            'HTTP_CONNECTION' => 'close',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 6_0_1 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A523 Safari/8536.25',
            'HTTP_ACCEPT' => 'text/vnd.wap.wml, application/json, text/javascript, */*; q=0.01',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_REFERER' => 'http://mobiledetect.net',
            'HTTP_PRAGMA' => 'no-cache',
            'HTTP_CACHE_CONTROL' => 'no-cache',
            'REMOTE_ADDR' => '11.22.33.44',
            'REQUEST_TIME' => '11-3-2016 07:57'
        ]);

        //12 because only 12 start with HTTP_
        $this->assertCount(12, $this->mDetect->getHttpHeaders());
        $this->tester->assertTrue($this->mDetect->checkHttpHeadersForMobile());
        $defaultUserAgent = $this->mDetect->getHttpHeader('HTTP_USER_AGENT');
        $this->tester->assertSame($defaultUserAgent, $this->mDetect->getHttpHeader('HTTP_USER_AGENT'), "Http header {$defaultUserAgent} is not set correctly");
        $this->tester->assertSame($defaultUserAgent, $this->mDetect->getHttpHeader('USER_AGENT'), "Http header {$defaultUserAgent} is not set correctly");
        $this->assertTrue( $this->mDetect->isMobile() );
        $this->assertFalse( $this->mDetect->isTablet() );
    }

    public function testSetHttpHeaders()
    {
        $header1 = array('HTTP_TEST_HEADER' => 'I love SELLVANA so much');
        $md = new FCom_Core_Vendor_MobileDetect($header1);
        $this->tester->assertSame($md->getHttpHeader('TEST_HEADER'), 'I love SELLVANA so much', 'Http header is not correct.');
        $header2 = array('HTTP_TEST_HEADER_2' => 'SELLVANA is the best ecommerge.');
        $md->setHttpHeaders($header2);
        $this->assertArrayHasKey('HTTP_TEST_HEADER_2', $md->getHttpHeaders(), 'Http header is not correct.');
    }

    public function testSetUserAgent()
    {
        $md = new FCom_Core_Vendor_MobileDetect(array());
        $md->setUserAgent('hello Sellvana');
        $this->tester->assertSame('hello Sellvana', $md->getUserAgent());
    }

    public function UAProvider()
    {
        return [
            [['HTTP_USER_AGENT' => 'test'], 'Test'],
            [['HTTP_USER_AGENT' => 'iphone', 'HTTP_X_OPERAMINI_PHONE_UA' => 'some other stuff'], 'iphone some other stuff'],
            [['HTTP_X_DEVICE_USER_AGENT' => 'hello Sellvana'], 'hello Sellvana'],
            [[], null]
        ];
    }

    /**
     * @dataProvider UAProvider
     */
    public function testGetUserAgent($headers, $expectedUserAgent)
    {
        $md = new FCom_Core_Vendor_MobileDetect($headers);
        $md->setUserAgent();
        $this->tester->assertSame($expectedUserAgent, $md->getUserAgent());
    }

    public function testSetDetectionType()
    {
        $md = new FCom_Core_Vendor_MobileDetect(array());
        $md->setDetectionType('bskdfjhs');
        $this->assertAttributeEquals(
            FCom_Core_Vendor_MobileDetect::DETECTION_TYPE_MOBILE,
            'detectionType',
            $md
        );
        $md->setDetectionType();
        $this->assertAttributeEquals(
            FCom_Core_Vendor_MobileDetect::DETECTION_TYPE_MOBILE,
            'detectionType',
            $md
        );
        $md->setDetectionType(FCom_Core_Vendor_MobileDetect::DETECTION_TYPE_MOBILE);
        $this->assertAttributeEquals(
            FCom_Core_Vendor_MobileDetect::DETECTION_TYPE_MOBILE,
            'detectionType',
            $md
        );
        $md->setDetectionType(FCom_Core_Vendor_MobileDetect::DETECTION_TYPE_EXTENDED);
        $this->assertAttributeEquals(
            FCom_Core_Vendor_MobileDetect::DETECTION_TYPE_EXTENDED,
            'detectionType',
            $md
        );
    }

    public function mobileUA()
    {
        return [
            ['Mozilla/5.0 (iPhone; CPU iPhone OS 6_0_1 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A523 Safari/8536.25', 'iPhone', 'IOS'],
            ['Mozilla/5.0 (Linux; Android 4.0.4; Galaxy Nexus Build/IMM76B) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.133 Mobile Safari/535.19', 'Nexus', 'AndroidOS'],
            ['Mozilla/5.0 (BlackBerry; U; BlackBerry 9900; en) AppleWebKit/534.11+ (KHTML, like Gecko) Version/7.1.0.346 Mobile Safari/534.11+', 'BlackBerry', 'BlackBerryOS'],
            ['Mozilla/5.0 (SymbianOS/9.4; Series60/5.0 NokiaC6-00/20.0.042; Profile/MIDP-2.1 Configuration/CLDC-1.1; zh-hk) AppleWebKit/525 (KHTML, like Gecko) BrowserNG/7.2.6.9 3gpp-gba', 'GenericPhone', 'SymbianOS']
        ];
    }

    /**
     * @dataProvider mobileUA
     */
    public function testMobileDevice($UA, $device, $os)
    {
        $this->mDetect->setUserAgent($UA);
        $this->tester->assertNotEmpty($this->mDetect->getUserAgent());
        $this->tester->assertEquals($UA, $this->mDetect->getUserAgent());
        $this->tester->assertTrue($this->mDetect->isMobile(), "Mobile detection is not correct");
        $this->tester->assertFalse($this->mDetect->isTablet(), "Tablet detection is not correct");
        $this->tester->assertTrue($this->mDetect->is($device), "Device {$device} detection is not correct");
        $this->tester->assertTrue($this->mDetect->is($os), "OS {$os} detection is not correct");
    }

    public function tabletUA()
    {
        return [
            ['Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.10', 'iPad', 'IOS'],
            ['Mozilla/5.0 (Linux; U; Android 4.0.4; en-us; A211 Build/IMM76D) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Safari/534.30', 'AcerTablet', 'AndroidOS'],
            ['Mozilla/5.0 (Linux; Android 4.0.3; ASUS Transformer Pad TF700T Build/IML74K) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.166 Safari/535.19', 'AsusTablet', 'AndroidOS'],
            ['Mozilla/5.0 (Linux; U; en-US) AppleWebKit/528.5+ (KHTML, like Gecko, Safari/528.5+) Version/4.0 Kindle/3.0 (screen 600×800; rotate)', 'Kindle', 'FireOS']
        ];
    }

    /**
     * @dataProvider tabletUA
     */
    public function testTabletDevices($UA, $device, $os)
    {
        $this->mDetect->setUserAgent($UA);
        $this->tester->assertNotEmpty($this->mDetect->getUserAgent());
        $this->tester->assertEquals($UA, $this->mDetect->getUserAgent());
        $this->tester->assertTrue($this->mDetect->isTablet(), "Tablet detection is not correct");
        $this->tester->assertTrue($this->mDetect->is($device), "Device {$device} detection is not correct");

        switch ($device) {
            case 'Kindle':
                $this->tester->assertFalse($this->mDetect->is($os), "OS {$os} detection is not correct");
                break;
            default:
                $this->tester->assertTrue($this->mDetect->is($os), "OS {$os} detection is not correct");
                break;
        }
    }

    public function headers()
    {
        return [
            [
                [
                    'SERVER_SOFTWARE' => 'Apache/2.2.15 (Linux) Whatever/4.0 PHP/5.2.13',
                    'REQUEST_METHOD' => 'POST',
                    'HTTP_HOST' => 'home.ghita.org',
                    'HTTP_X_REAL_IP' => '1.2.3.4',
                    'HTTP_X_FORWARDED_FOR' => '1.2.3.5',
                    'HTTP_CONNECTION' => 'close',
                    'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 6_0_1 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A523 Safari/8536.25',
                    'HTTP_ACCEPT' => 'text/vnd.wap.wml, application/json, text/javascript, */*; q=0.01',
                    'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
                    'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
                    'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
                    'HTTP_REFERER' => 'http://mobiledetect.net',
                    'HTTP_PRAGMA' => 'no-cache',
                    'HTTP_CACHE_CONTROL' => 'no-cache',
                    'REMOTE_ADDR' => '11.22.33.44',
                    'REQUEST_TIME' => '11-03-2016 07:57'
                ]
            ],
            [
                [
                    'SERVER_SOFTWARE' => 'Fulleron Inc.',
                    'REQUEST_METHOD' => 'GET',
                    'REMOTE_ADDR' => '8.8.8.8',
                    'REQUEST_TIME' => '11-03-2016 07:57',
                    'HTTP_USER_AGENT' => "garbage/1.0"
                ]
            ],
            [
                [
                    'SERVER_SOFTWARE' => 'Apache/1.3.17 (Linux) PHP/5.5.2',
                    'REQUEST_METHOD' => 'HEAD',
                    'HTTP_USER_AGENT' => 'Mozilla/5.0 (Linux; U; Android 1.5; en-us; ADR6200 Build/CUPCAKE) AppleWebKit/528.5+ (KHTML, like Gecko) Version/3.1.2 Mobile Safari/525.20.1',
                    'REMOTE_ADDR' => '1.250.250.0',
                    'REQUEST_TIME' => '11-03-2016 07:57'
                ]
            ],
        ];
    }

    /**
     * @dataProvider headers
     */
    public function testConstructor(array $headers)
    {
        $md = new FCom_Core_Vendor_MobileDetect($headers);
        foreach ($headers as $header => $value) {
            if (substr($header, 0, 5) !== 'HTTP_') {
                //make sure it wasn't set
                $this->tester->assertNull($md->getHttpHeader($value));
            } else {
                //make sure it's equal
                $this->tester->assertEquals($value, $md->getHttpHeader($header));
            }
        }

        //verify some of the headers work with the translated getter
        $this->tester->assertNull($md->getHttpHeader('Remote-Addr'));
        $this->tester->assertNull($md->getHttpHeader('Server-Software'));
        $this->tester->assertEquals($headers['HTTP_USER_AGENT'], $md->getHttpHeader('User-Agent'));
    }

    /**
     * @dataProvider headers
     */
    public function testInvalidHeader($headers)
    {
        $md = new FCom_Core_Vendor_MobileDetect($headers);
        $this->tester->assertNull($md->getHttpHeader('garbage_is_Garbage'));
    }
}