<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Test_Admin_Controller_CodecetionTests extends FCom_Admin_Controller_Abstract_GridForm
{

    protected $site;

    protected $codecept;

    public function __construct()
    {
        $this->ensureCodeception($this->getCodecetionExecutable());
        parent::__construct();
    }

    public function action_index()
    {
        $tests = false;
        $testsCount = 0;

        $config = $this->getCodeceptionConfig();
        $site = $this->initSite($config->get('sites'));
        $codeceptConfig = $this->BConfig->get('modules/Codeception');

        /** @var FCom_Test_Core_Codeception $codeception */
        $codecept = $this->BApp->instance('FCom_Test_Core_Codeception', false, ['config' => $config, 'sites' => $site]);

        $this->layout("/tests/codeception");
        $this->layout()
            ->view("tests/codeception")
            ->set('tests', $tests)
            ->set('testsCount', $testsCount)
            ->set("codecept", $codecept)
            ->set("codeceptConfig", $codeceptConfig)
            ->set("can_cgi", function_exists("exec"));
    }

    /**
     * Attempt to run tests on command line
     *
     * Executes testrun.php, it can also be manually executed
     * If filtered tests are passed, only they will be ran
     */
    public function action_run__POST()
    {

    }

    private function initSite($sites)
    {
        $hashString = $this->BRequest->request('hash');
        $hash = false;
        if (!is_null($hashString) && $hashString !== false) {
            $hash = $hashString;
        } elseif (isset($_SESSION['site_session'])) {
            $hash = $_SESSION['site_session'];
        }
        $site = $this->BApp->instance('FCom_Test_Core_Site', false, ['sites' => $sites]);
        $site->set($hash);

        // Update the users session to use the chosen site
        $_SESSION['site_session'] = $site->getHash();

        return $site;
    }

    /**
     *
     */
    private function getCodeceptionConfig()
    {
        $config = false;
        $testType = $this->BRequest->request('test');
        $codeceptConfig = $this->BConfig->get('modules/Codeception');

        // If the test query string parameter is set,
        // a test config will be loaded.
        if ($testType !== null) {

            // Sanitize the test type.
            $testType = trim(strtolower($this->BUtil->removeFileExtension($testType)));

            // Filter the test type into the test string.
            $testConfig = sprintf($codeceptConfig['test'], $testType);
            // Load the config if it can be found
            if (file_exists($testConfig)) {
                $config = $this->BConfig->addFile($testConfig);
            }
        }

        if ($config === false) {
            $config = $this->BConfig->addFile($codeceptConfig['config']);
        }

        return $config;
    }

    /**
     * @param string $phpunit desired phpunit filename
     */
    protected function ensureCodeception($codecept)
    {
        if (!file_exists($codecept)) {
            if (touch($codecept)) {
                $codeceptUrl = 'http://codeception.com/codecept.phar';
                $raw = $this->BUtil->remoteHttp('GET', $codeceptUrl);
                file_put_contents($codecept, $raw);
                if (function_exists('chmod')) {
                    chmod($codecept, 0755); // make executable
                }
            } else {
                $this->BDebug->warning($this->_("Could not create $codecept file."));
            }
        }

        $this->codeceptPhar = $codecept;
    }

    /**
     * @return string
     */
    protected function getCodecetionExecutable()
    {
        return FULLERON_ROOT_DIR . '/codecept.phar';
    }

}