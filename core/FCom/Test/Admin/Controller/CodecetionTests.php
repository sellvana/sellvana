<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Test_Admin_Controller_CodecetionTests extends FCom_Admin_Controller_Abstract_GridForm
{
    const TESTS_GRID_ID = 'tests_grid';

    public $codecept;

    protected $config = [
        /*
        |--------------------------------------------------------------------------
        | Codeception Configurations
        |--------------------------------------------------------------------------
        |
        | This is where you add your Codeception configurations.
        */
        'sites' => [
            'Codeception' => FULLERON_ROOT_DIR . '/codeception.yml',
            'Wishlist' => FULLERON_ROOT_DIR . '/core/Sellvana/Wishlist/Test/Codecept/codeception.yml',
            'Test' => FULLERON_ROOT_DIR . '/core/FCom/Test/Test/Codecept/codeception.yml'
        ],

        /*
        |--------------------------------------------------------------------------
        | Codeception Executable
        |--------------------------------------------------------------------------
        |
        */

        'executable' => FULLERON_ROOT_DIR .'/codecept.phar',

        /*
        |--------------------------------------------------------------------------
        | Decide which type of tests get included.
        |--------------------------------------------------------------------------
        */

        'tests' => [
            'acceptance' => false,
            'functional' => false,
            'unit'       => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | When scan for the tests, we need to ignore the following files.
        |--------------------------------------------------------------------------
        */

        'ignore' => [
            'WebGuy.php',
            'TestGuy.php',
            'CodeGuy.php',
            '_bootstrap.php',
            '.DS_Store',
        ],

        /*
        |--------------------------------------------------------------------------
        | Setting the location as the current file helps with offering information
        | about where this configuration file sits on the server.
        |--------------------------------------------------------------------------
        */

        'location'   => __FILE__,
    ];

    public function __construct()
    {
        $this->ensureCodeception($this->getCodecetionExecutable());

        // Register to app
        $site = $this->initSite($this->config['sites']);
        $this->codecept = $this->BApp->instance('FCom_Test_Core_Codeception', false,
            ['config' => $this->getCodeceptionConfig(), 'site' => $site]);

        parent::__construct();
    }

    public function action_index()
    {
        $tests = false;
        if ($this->codecept->ready()) {
            $tests = $this->codecept->getTests();
        }

        $this->layout("/tests/codeception");
        $this->layout()
            ->view("tests/codeception")
            ->set("can_cgi", function_exists("exec"))
            ->set("grid", $this->getTestsConfig($tests));
    }

    /**
     * Check phar is executable
     */
    public function action_executable()
    {
        $response = $this->codecept->checkExecutable(
            $this->config['executable'],
            $this->config['location']
        );

        $r = $this->BResponse;
        $r->json($response);
    }

    /**
     * Attempt to run tests on command line
     *
     */
    public function action_run()
    {
        $rq = $this->BRequest;
        $rp = $this->BResponse;

        $type = strtolower($rq->get('type'));
        $hash = $rq->get('id');

        $response = $this->codecept->response($type, $hash);
        $rs = $this->BUtil->toJson($response);
        echo $rs;
        exit;
    }

    public function getTestsConfig($tests)
    {
        $config = parent::gridConfig();
        $config['id'] = static::TESTS_GRID_ID;
        $config['data_mode'] = 'local';
        $config['caption'] = 'Codeception Tests';

        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'test', 'label' => "Select tests to run"],
            ['name' => 'type', 'label' => 'Engine'],
            ['name' => 'status', 'label' => 'Status']
        ];
        $config['filters'] = [['field' => 'test', 'type' => 'text']];
        $config['callbacks'] = [
            'componentDidMount' => 'codeceptionTestsGridRegister'
        ];
        $config['actions'] = [
            'run-test-cgi' => [
                'caption'  => 'Run Test CGI',
                'type'     => 'button',
                'id'       => 'run-test-cgi',
                'class'    => 'btn-default',
                'callback' => 'runTestCgi'
            ]/*,
            'run-test-web' => [
                'caption'  => 'Run Test Web',
                'type'     => 'button',
                'id'       => 'run-test-web',
                'class'    => 'btn-default',
                'callback' => 'runTestWeb'
            ]*/
        ];
        $gridData = [];
        foreach ($tests as $type => $files) {
            foreach ($files as $file) {
                $obj['id'] = $file->getHash();
                $obj['type'] = ucfirst($file->getType());
                $obj['test'] = $file->getTitle();
                $obj['status'] = 'Ready';
                $gridData[] = $obj;
                unset($class);

            }
        }

        $config['data'] = $gridData;
        return ['config' => $config];
    }

    private function initSite($sites)
    {
        $hashString = $this->BRequest->request('hash');
        $hash = false;
        if (!is_null($hashString) && $hashString !== false) {
            $hash = $hashString;
        } elseif ($this->BSession->get('site_session')) {
            $hash = $this->BSession->get('site_session');
        }

        $site = $this->BApp->instance('FCom_Test_Core_Site', false, ['sites' => $sites]);
        $site->set($hash);
        // Update the users session to use the chosen site
        $this->BSession->set('site_session', $site->getHash());

        return $site;
    }

    /**
     *
     */
    private function getCodeceptionConfig()
    {
        $config = false;
        $testType = $this->BRequest->get('test');

        // If the test query string parameter is set,
        // a test config will be loaded.
        if ($testType !== null) {

            // Sanitize the test type.
            $testType = trim(strtolower($this->BUtil->removeFileExtension($testType)));

            // Filter the test type into the test string.
            $testConfig = sprintf($this->config['test'], $testType);
            // Load the config if it can be found
            if (file_exists($testConfig)) {
                $config = $this->BConfig->addFile($testConfig);
            }
        }

        if ($config === false) {
            $config = $this->BConfig->add($this->config);
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