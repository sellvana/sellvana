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
    protected $timeOut;

    /**
     * Initial timestamp
     * @var int
     */
    protected $start;

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
        $this->timeOut = $milliSeconds;
        $this->start = microtime(true);
        return $this->start;
    }

    protected function assertPostConditions()
    {
        if ($this->timeOut > 0) {
            $end = microtime(true);
            $diff = ($end - $this->start) * 1000; // convert float point seconds to milliseconds

            if ($diff > $this->timeOut) {
                throw new PHPUnit_Framework_AssertionFailedError(sprintf("Expected test run time exceeded. Expected: %d, actual %.03f",
                    $this->timeOut,
                    $diff));
            } else {
                printf("start: %s, end: %s, timeout: %s, diff: %s", $this->start, $end, $this->timeOut, $diff);
            }
        }
        parent::assertPostConditions();
    }

    protected function tearDown()
    {
        $this->timeOut = null;
        return parent::tearDown();
    }

}
