<?php
/**
 * Created by pp
 * @project sellvana_core
 */

class Sellvana_ProductCompare_Model_SetTest extends PHPUnit_Framework_TestCase {

    /**
     * @var Sellvana_ProductCompare_Model_Set
     */
    protected $set;

    protected $_origReq;

    protected function setUp()
    {
        parent::setUp();
        $this->set = Sellvana_ProductCompare_Model_Set::i(true);

    }

    /**
     * Order of these tests is important, we need to test negative before positive scenario
     * because once session set is created, we don't have a way to reset it.
     */
    public function testSessionSetForNonRegisteredUserWillNotBeCreatedWithoutCreateAnonymousSwitch()
    {
        $cookieName = 'compare';
        $cookieValue = $this->set->BRequest->cookie($cookieName);
        $this->set->BRequest->cookie($cookieName, false); // reset cookie$sessionSet = $this->set->sessionSet();
        $sessionSet = $this->set->sessionSet(); // no $createAnonymousIfNeeded = true
        $this->assertFalse($sessionSet);
        $this->set->BRequest->cookie($cookieName, $cookieValue); // set cookie back
    }
    public function testSessionSetForNonRegisteredUserCanBeCreated()
    {
        $cookieName = 'compare';
        $cookieValue = $this->set->BRequest->cookie($cookieName);
        $this->set->BRequest->cookie($cookieName, false); // reset cookie
        $sessionSet = $this->set->sessionSet(true);
        $this->assertInstanceOf('Sellvana_ProductCompare_Model_Set', $sessionSet);
        $this->set->BRequest->cookie($cookieName, $cookieValue); // set cookie back
    }
}


