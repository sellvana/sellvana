<?php

/**
 * Created by pp
 * @project sellvana_core
 */
abstract class FCom_Test_Model_TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * Maximum time in milliseconds a test should run
     * @var int
     */
    protected $_timeOut;

    /**
     * Initial timestamp
     * @var int
     */
    protected $_start;

    /**
     * Set expected time for test to run
     *
     * If time for test run is exceeded, test will fail.
     *
     * @param int $milliSeconds
     * @return int
     * @throws BException
     */
    protected function expectedTimeout($milliSeconds)
    {
        if (!is_integer($milliSeconds)) {
            throw new BException("Invalid argument. Pass integer for milliseconds.");
        }
        $this->_timeOut = $milliSeconds;
        $this->_start = microtime(true);
        return $this->_start;
    }

    protected function assertPostConditions()
    {
        if ($this->_timeOut > 0) {
            $end = microtime(true);
            $diff = ($end - $this->_start) * 1000; // convert float point seconds to milliseconds

            if ($diff > $this->_timeOut) {
                throw new PHPUnit_Framework_AssertionFailedError(sprintf("Expected test run time exceeded. Expected: %d, actual %.03f",
                    $this->_timeOut,
                    $diff));
            }
        }
        parent::assertPostConditions();
    }

    protected function tearDown()
    {
        $this->_timeOut = null;
        return parent::tearDown();
    }

}
