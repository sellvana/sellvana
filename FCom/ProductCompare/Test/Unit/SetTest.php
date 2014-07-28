<?php
/**
 * Created by pp
 * @project sellvana_core
 */

class FCom_ProductCompare_Model_SetTest extends PHPUnit_Framework_TestCase {

    /**
     * @var FCom_ProductCompare_Model_Set
     */
    protected $set;

    protected $_origReq;

    protected function setUp()
    {
        parent::setUp();
        //$this->_origReq = BRequest::i();
        BClassRegistry::i()->overrideMethod('BRequest', 'cookie', ['BRequestDouble', 'cookie']);
        $this->set = FCom_ProductCompare_Model_Set::i();
    }

    public function testSessionSetForNonRegisteredUserCanBeCreated()
    {
        $sessionSet = $this->set->sessionSet(true);
        $this->assertInstanceOf('FCom_ProductCompare_Model_Set', $sessionSet);
    }

    public function testSessionSetForNonRegisteredUserWillNotBeCreatedWithoutCreateAnonymousSwitch()
    {
        $sessionSet = $this->set->sessionSet();
        $this->assertNull($sessionSet);
    }
}


class BRequestDouble extends BRequest
{
    protected $cookies = []; // cookie jar
    public function cookie($req, $name, $value = null, $lifespan = null, $path = null, $domain = null, $secure = null, $httpOnly = null)
    {

        // override cookie
        if (null === $value) {
            return isset($this->cookies[$name])? $this->cookies[$name]: null;
        }
        if (false === $value) {
            unset($this->cookies[$name]);
            return $this->cookie($name, '-CLEAR-', -100000);
        }

        $this->cookies[$name] = $value;//
        return true;
    }
}
