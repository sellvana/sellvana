<?php

class FCom_Test_Admin_Controller_CodeceptionTests extends FCom_Admin_Controller_Abstract_GridForm
{
    const TESTS_GRID_ID = 'tests_grid';

    /**
     * @var FCom_Test_Core_Codeception
     */
    public $codecept;

    protected $config = [];

    public function __construct()
    {

        $codeceptConfigFile = sprintf('%s/codecept.php', $this->BConfig->get('fs/config_dir'));
        if (!file_exists($codeceptConfigFile)) {
            $this->BConfig->writeConfigFiles('codecept');
        }

        $this->config = include $codeceptConfigFile;
        $this->ensureCodeception($this->getCodecetionExecutable());
        // Register to app
        $site = $this->initSite($this->config['codecept_sites']);
        $this->codecept = $this->BApp->instance('FCom_Test_Core_Codeception', false,
            ['config' => $this->config, 'site' => $site]);

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
            $this->config['codecept_executable']
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
            ['name' => 'module', 'label' => 'Module'],
            ['name' => 'status', 'label' => 'Status']
        ];
        $config['filters'] = [['field' => 'test', 'type' => 'text']];
        $config['callbacks'] = [
            'componentDidMount' => 'codeceptionTestsGridRegister'
        ];
        $config['actions'] = [
            'run-test-cgi' => [
                'caption' => 'Run Test CGI',
                'type' => 'button',
                'id' => 'run-test-cgi',
                'class' => 'btn-primary',
                'callback' => 'runTestCgi'
            ],
            /*'run-test-web' => [
                'caption'  => 'Run Test Web',
                'type'     => 'button',
                'id'       => 'run-test-web',
                'class'    => 'btn-default',
                'callback' => 'runTestWeb'
            ]*/

        ];
        $gridData = [];
        if (!empty($tests)) {
            foreach ($tests as $type => $files) {
                foreach ($files as $file) {
                    $obj['id'] = $file->getHash();
                    $obj['type'] = ucfirst($file->getType());
                    $obj['test'] = $file->getTitle();
                    $obj['module'] = $file->getModule();
                    $obj['status'] = '';
                    $gridData[] = $obj;
                    unset($class);

                }
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
        /** @var FCom_Test_Core_Site $site */
        $site->set($hash);
        // Update the users session to use the chosen site
        $this->BSession->set('site_session', $site->getHash());

        return $site;
    }

    /**
     * @param string $codecept desired codecept filename
     */
    protected function ensureCodeception($codecept)
    {
        if (!file_exists($codecept)) {
            if (touch($codecept)) {
                #TODO: Temporary use file_get_contents for getting codeception executable
                $content = file_get_contents($this->config['codecept_executable_url']);
                // $raw = $this->BUtil->remoteHttp('GET', $codeceptUrl);
                file_put_contents($codecept, $content);
                if (function_exists('chmod')) {
                    chmod($codecept, 0755); // make executable
                }
            } else {
                $this->BDebug->warning($this->_("Could not create $codecept file."));
            }
        }

        $this->config['codecept_executable'] = $codecept;
    }

    /**
     * @return string
     */
    protected function getCodecetionExecutable()
    {
        $base = $this->BConfig->get('fs/storage_dir') . '/' . $this->BConfig->get('core/storage_random_dir');
        $codecept = $base . '/codecept.phar';
        return $codecept;
    }
}