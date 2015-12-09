<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Test_Core_Test extends BClass
{
    /**
     * MD5 of the file name
     *
     * @var string
     */
    private $hash;

    /**
     * Filename of the test.
     *
     * @var string
     */
    private $filename;

    /**
     * Readable version of the filename
     *
     * @var string
     */
    private $title;

    /**
     * The file object.
     *
     * @var SplFileInfo
     */
    private $file;

    /**
     * Test Type: Functional, Acceptance or Unit.
     *
     * @var string
     */
    private $type;

    /**
     * Log of the test result
     *
     * @var array  Each array entry is a console log line.
     */
    private $log = [];

    /**
     * Result of running the test.
     *
     * @var bool
     */
    private $passed;

    private $module;

    /**
     * Possible test states
     */
    const STATE_PASSED = 'passed';
    const STATE_FAILED = 'failed';
    const STATE_ERROR  = 'error';
    const STATE_READY  = 'ready';

    /**
     * List of responses that can occur from Codeception.
     *
     * Using these, we scan the result when log is added to the test.
     *
     * @var array
     */
    private $responses = [
        'timeout'   => 'Operation timed out after',
        'writeable' => 'Path for logs is not writable',
        'passed'    => array(
            'PASSED',   // Functional & Acceptance Test
            'OK \('     // Unit Test
        ),
        'failed'    => 'FAILURES',
        'incomplete' => [
            'incomplete', 'skipped', 'risky' // Unit Test
        ]
    ];

    /**
     * On Test __construct, the passed matches are turned into a regex.
     *
     * @var string
     */
    private $passed_regex;

    /**
     * On Test __construct, the incomplete matches are turned into a regex.
     *
     * @var string
     */
    private $incomplete_regex;


    /**
     * On Test __construct, the failure matches are turned into a regex.
     *
     * @var string
     */
    private $failure_regex;

    /**
     * Colour tags from Codeception's coloured output.
     *
     * @var array
     */
    private $colour_codes = [
        "[37;45m",
        "[2K",
        "[1m",
        "[0m",
        "[30;42m",
        "[37;41m",
        "[33m",
        "[36m",
        "[35;1m",
        "-"
    ];

    /**
     * File extensions to remove from the output.
     *
     * @var array
     */
    private $filtered_files = [
        'Cept.php',
        'Cest.php',
        'Test.php',
    ];

    public function __construct()
    {
        // Declare the regex string containing all the responses that
        // can indicate that as a passed test.
        $this->passed_regex = implode('|', $this->responses['passed']);

        $this->incomplete_regex = implode('|', $this->responses['incomplete']);

        // maybe there will be any more failures? Then we are going to need this
        $this->failure_regex = $this->responses['failed'];
    }

    /**
     * Initialization of the Test
     *
     * @param string $type Type of Test
     * @param object $file File for the Test
     */
    public function init($type, $file, $module)
    {
        $filename       = $this->filterFileName($file->getFileName());
        $posTypePath    = strpos($file->getPathname(), $type) + (strlen($type) + 1);

        $this->hash     = $this->makeHash($type . $filename);
        $this->title    = $this->filterTitle($filename);
        $this->filename = substr($file->getPathname(), $posTypePath);
        $this->file     = $file;
        $this->type     = $type;
        $this->state    = self::STATE_READY; // Not used yet.
        $this->module   = $module;
    }

    /**
     * Filter out content from a title any to improve readability of the test name
     *
     * @param  string $filename
     * @return string
     */
    public function filterFileName($filename)
    {
        return str_ireplace($this->filtered_files, '', $filename);
    }

    /**
     * Generate a unique hash for the test.
     *
     * @param  string $string
     * @return string MD5 of $string
     */
    public function makeHash($string)
    {
        return md5($string);
    }

    public function getModule() {
        return $this->module;
    }

    /**
     * Turn a "CamelCasedString" into "Camel Cased String".
     * This is to improve readability of the test list.
     *
     * @param  string $title
     * @return string
     */
    public function filterTitle($title)
    {
        return $this->BUtil->camelToSentance($title);
    }

    /**
     * Get the Test title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get the Test Hash
     *
     * The hash is the Test title that's been md5'd.
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Get the Test type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the file Filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set if the test has been passed.
     *
     * @return boolean
     */
    public function setPassed()
    {
        $this->passed = true;
    }

    /**
     * Set if the test has been passed but incomplete because having skipped test
     */
    public function setIncomplete() {
        $this->passed = 'incomplete';
    }

    /**
     * Sets passed to false if test fails
     */
    public function setFailed()
    {
        $this->passed = false;
    }

    /**
     * Return if the test was run and passed
     *
     * @return boolean
     */
    public function passed()
    {
        return $this->passed;
    }

    /**
     * Return if the test was successfully run.
     *
     * This is deteremined by simply checking the length of the log.
     *
     * @return boolean
     */
    public function ran()
    {
        return sizeof($this->log) > 0;
    }

    /**
     * Get the Test state based on if the test has run or passed.
     *
     * @return boolean
     */
    public function getState()
    {
        return ($this->passed() ? static::STATE_PASSED : ($this->ran() ? static::STATE_FAILED : static::STATE_ERROR));
    }

    /**
     * Add a new line entry to the Test log.
     *
     * Also check the log line may indicate if the Test has passed.
     *
     * @param Array $lines
     */
    public function setLog($lines = [])
    {
        $failed = false;
        foreach ($lines as $line) {

            if ($this->checkLogForTestPass($line) && $failed==false)
                $this->setPassed();

            if ($this->checkLogForTestIncomplete($line) && $failed==false) {
                $this->setIncomplete();
            }

            if ($this->checkLogForTestFailure($line)) {
                $this->setFailed();
                $failed = true;
            }

            // Filter the line of any junk and add to the log.
            $this->log[] = $this->filterLog($line);
        }
    }

    /**
     * Return the log as a HTML string.
     *
     * @param  $format Split the array into HTML with linebreaks or return as-is if false.
     * @return HTML/Array
     */
    public function getLog($format = true)
    {
        return $format ? implode($this->log, PHP_EOL) : $this->log;
    }

    /**
     * Filter out junk content from a log line.
     *
     * @param string $line
     * @return string
     */
    private function filterLog($line)
    {
        return str_replace($this->colour_codes, '', $line);
    }

    /**
     * Check if it contains any text that indiciates that the test has passed.
     *
     * @param  string  $line
     * @return boolean
     */
    public function checkLogForTestPass($line)
    {
        return count(preg_grep("/({$this->passed_regex})/", [$line])) > 0;
    }

    /**
     * Check if it contains any text that indiciates that the test has passed.
     *
     * @param  string  $line
     * @return boolean
     */
    public function checkLogForTestIncomplete($line)
    {
        return count(preg_grep("/({$this->incomplete_regex})/", [$line])) > 0;
    }

    /**
     * Checks if line contains failure regex
     *
     * @param string $line
     * @return bool
     */
    public function checkLogForTestFailure($line)
    {
        return count(preg_grep("/({$this->failure_regex})/", array($line))) > 0;
    }

    /**
     * Reset a Test back to default.
     */
    public function reset()
    {
        $this->log    = [];
        $this->passed = false;
    }
}
