<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Test_Core_Codeception extends BClass
{
    /**
     * List of the Test sites
     *
     * @var array
     */
    public $site;

    /**
     * Configuration for Codeception
     *
     * Merges the Codeception.yml and Codeception.php
     *
     * @var boolean
     */
    public $config = false;

    /**
     * Temporary copy of the Codeception.yml setup.
     *
     * If this is set, it means the configuration was loaded
     *
     * @var bool
     */
    private $yaml = false;

    /**
     * Tally of all the tests that have been loaded
     *
     * @var integer
     */
    private $tally = 0;

    /**
     * List of all the tests
     *
     * @var array
     */
    private $tests = [];

    /**
     * Initialization of the Codeception class.
     *
     * @param array $config The codeception.php configuration file.
     */
    public function __construct($config = array(), $site = NULL)
    {
        // Set the basic config, just incase.
        $this->config = $config;

        // If the array wasn't loaded, we can't go any further.
        if (sizeof($config) == 0)
            return;

        // Setup the sites available
        $this->site = $site;

        // If the site class isn't ready, we can't load codeception.
        if (! $site->ready())
            return;

        // If the Configuration was loaded successfully, merge the configs!
        if ($this->yaml = $this->loadConfig($site->getConfigPath(), $site->getConfigFile())) {
            $this->config->add($this->yaml);
            $this->loadTests();
        }
    }

    /**
     * Return if Codeception is ready.
     *
     * @return boolean
     */
    public function ready()
    {
        return $this->yaml !== false;
    }

    /**
     * Load the Codeception YAML configuration.
     *
     * @param  string $path
     * @param  string $file
     * @return array  $config
     */
    public function loadConfig($path, $file)
    {
        $fullPath = $path . $file;

        // If the Codeception YAML can't be found, the application can't go any further.
        if (! file_exists($fullPath))
            return false;

        $config = $this->BYAML->load($fullPath);
        // Update the config to include the full path.
        foreach ($config['paths'] as $key => &$testPath) {
            $testPath = file_exists($path . $testPath) ? realpath($path . $testPath) : $path . $testPath;
        }

        return $config;
    }

    /**
     * Load the Codeception tests from disk.
     */
    public function loadTests()
    {
        if (!$this->config->get('tests')) {
            return;
        }

        foreach ($this->config->get('tests') as $type => $active) {
            
            if (!$active) {
                continue;
            }

            if ($this->config->get('paths/tests')) {
                // If codeception.yml has config `tests`

                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator("{$this->config->get('paths/tests')}/{$type}/",
                        \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );

                // Iterate through all the files, and filter out
                //      any files that are in the ignore list.
                foreach ($files as $file) {

                    if (!in_array($file->getFilename(), $this->config->get('ignore')) && $file->isFile()) {
                        // Declare a new test and add it to the list.
                        $test = $this->BApp->instance('FCom_Test_Core_Test');
                        $test->init($type, $file);
                        $this->addTest($test);
                        unset($test);
                    }

                }
            } else {
                // Load and init all modules tests
                $modules = $this->BModuleRegistry->getAllModules();
                foreach ($modules as $module) {
                    /** @var BModule $module */
                    if (!$module || !$module instanceof BModule) {
                        continue;
                    }
                    $rootDir = $module->root_dir;
                    $testsDir = $rootDir . '/Test/Codecept/tests/' . strtolower($type);
                    if (is_dir($testsDir)) {
                        $files = new \RecursiveIteratorIterator(
                            new \RecursiveDirectoryIterator(realpath($testsDir)),
                            \RecursiveIteratorIterator::LEAVES_ONLY
                        );
                        foreach ($files as $file) {
                            $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                            $isTest = preg_match('/[A-z]+Test/', $file->getFilename());
                            if ($ext == 'php' && $isTest && !in_array($file->getFilename(),
                                    $this->config->get('ignore')) && $file->isFile()
                            ) {
                                // Declare a new test and add it to the list.
                                /** @var FCom_Test_Core_Test $test */
                                $test = new FCom_Test_Core_Test;
                                $test->init($type, $file, $module->name);
                                $this->addTest($test);
                                unset($test);
                            }

                        }
                    }
                }
            }
        }
    }

    /**
     * Add a Test to the list.
     *
     * Push the tally count up as well.
     *
     * @param FCom_Test_Core_Test $test
     */
    public function addTest($test)
    {
        $this->tally++;
        $this->tests[$test->getType()][$test->getHash()] = $test;
    }

    /**
     * Get the complete test list.
     *
     * @param array $test List of loaded Tests.
     * @return array
     */
    public function getTests()
    {
        return $this->tests;
    }

    /**
     * Given a test type & hash, return a single Test.
     *
     * @param  string       $type Test type (Unit, Acceptance, Functional)
     * @param  string       $hash Hash of the test.
     * @return FCom_Test_Core_Test or false.
     */
    public function getTest($type, $hash)
    {
        if (isset($this->tests[$type][$hash]))
            return $this->tests[$type][$hash];

        return false;
    }

    /**
     * Return the count of discovered tests
     *
     * @return integer $this->tally
     */
    public function getTestTally()
    {
        return $this->tally;
    }

    /**
     * Given a test, run the Codeception test.
     *
     * @param  FCom_Test_Core_Test $test Current test to Run.
     * @return FCom_Test_Core_Test $test Updated test with log and result.
     */
    public function run($test)
    {
        // Get the full command path to run the test.
        $command = $this->getCommandPath($test->getType(), $test->getFilename(), $test->getModule());

        // Attempt to set the correct writes to Codeceptions Log path.
        @chmod($this->getLogPath(), 0777);

        // Run the helper function (as it's not specific to Codeception)
        // which returns the result of running the terminal command into an array.
        $output  = $this->BUtil->runCLI($command);

        // Add the log to the test which also checks to see if there was a pass/fail.
        $test->setLog($output);

        return $test;
    }

    /**
     * Get the Codeception log path
     *
     * @return  string
     */
    public function getLogPath()
    {
        return $this->config->get('paths/log');
    }

    /**
     * Full command to run a Codeception test.
     *
     * @param  string $type     Test Type (Acceptance, Functional, Unit)
     * @param  string $filename Name of the Test
     * @return string Full command to execute Codeception with requred parameters.
     */
    public function getCommandPath($type, $filename, $module)
    {
        // Build all the different parameters as part of the console command
        $params = array(
            $this->config->get('executable'),   // Codeception Executable
            "run",                              // Command to Codeception
            "--no-colors",                      // Forcing Codeception to not use colors, if enabled in codeception.yml
            "--config=\"{$this->site->getSitePath($module)}\"", // Full path & file of Codeception
            $type,                              // Test Type (Acceptance, Unit, Functional)
            $filename,                          // Filename of the Codeception test
            "2>&1"
        );

        // Build the command to be run.
        return implode(' ', $params);
    }

    public function getRootCmdPath()
    {
        $params = [
            $this->config->get('executable'),
            'run'
        ];

        // Build the command to be run.
        return implode(' ', $params);
    }

    /**
     * Given a test type & hash, handle the test run response for the AJAX call.
     *
     * @param  string $type Test type (Unit, Acceptance, Functional)
     * @param  string $hash Hash of the test.
     * @return array  Array of flags used in the JSON respone.
     */
    public function response($type, $hash)
    {
        $response = [
            'message' => null,
            'run' => false,
            'passed' => false,
            'state' => 'error',
            'log' => null
        ];

        // If Codeceptions not properly configured, the test won't be found
        // and it won't be run.
        if (!$this->ready()) {
            $response['message'] = 'The Codeception configuration could not be loaded.';
        }
        // If the test can't be found, we can't run the test.
        if (!$test = $this->getTest($type, $hash)) {
            $response['message'] = 'The test could not be found.';
        }

        // If there's no error message set yet, it means we're good to go!
        if (is_null($response['message'])) {

            // Run the test!
            $test = $this->run($test);
            $response['run'] = $test->ran();
            $response['log'] = $test->getLog();
            $response['passed'] = $test->passed();
            $response['state'] = $test->getState();
            $response['title'] = $test->getTitle();
        }

        return $response;
    }

    /**
     * Check that the Codeception executable exists and is runnable.
     *
     * @param  string $file   File name of the Codeception executable.
     * @param  string $config Full path of the config of where the $file was defined.
     * @return array  Array of flags used in the JSON respone.
     */
    public function checkExecutable($file, $config)
    {
        $response = [];
        $response['resource'] = $file;

        // Set this to ensure the developer knows there $file was set.
        $response['config'] = realpath($config);

        if (!file_exists($file)) {
            $response['error'] = 'The Codeception executable could not be found.';
        } elseif (!is_executable($file) && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $response['error'] = 'Codeception isn\'t executable. Have you set executable rights to the following (try chmod o+x).';
        }

        // If there wasn't an error, then it's good!
        $response['ready'] = !isset($response['error']);

        return $response;
    }
}
