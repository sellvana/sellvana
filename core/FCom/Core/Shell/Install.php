<?php

/**
 * Class FCom_Core_Shell_Config
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 */
class FCom_Core_Shell_Install extends FCom_Shell_Action_Abstract
{

    static protected $_origClass = __CLASS__;

    const OPTION_VERBOSE = 'v';
    const OPTION_QUIET   = 'q';

    const OPTION_FIELD_DB_HOST           = 'db_host';
    const OPTION_FIELD_DB_PORT           = 'db_port';
    const OPTION_FIELD_DB_NAME           = 'db_name';
    const OPTION_FIELD_DB_USER           = 'db_user';
    const OPTION_FIELD_DB_PASSWORD       = 'db_password';
    const OPTION_FIELD_DB_PREFIX         = 'db_prefix';

    const OPTION_FIELD_ADMIN_USERNAME    = 'admin_username';
    const OPTION_FIELD_ADMIN_PASSWORD    = 'admin_password';
    const OPTION_FIELD_ADMIN_EMAIL       = 'admin_email';
    const OPTION_FIELD_ADMIN_FIRSTNAME   = 'admin_firstname';
    const OPTION_FIELD_ADMIN_LASTNAME    = 'admin_lastname';

    const OPTION_FIELD_RUN_MODE_ADMIN    = 'run_mode_admin';
    const OPTION_FIELD_RUN_MODE_FRONTEND = 'run_mode_frontend';
    const OPTION_FIELD_RUN_LEVEL_BUNDLE  = 'run_level_bundle';

    static protected $_actionName = 'install';

    static protected $_availOptions = [
        'v'  => 'verbose',
        'q'  => 'quiet',

        'db_host?',
        'db_port?',
        'db_name?',
        'db_user?',
        'db_password?',
        'db_prefix?',

        'admin_username?',
        'admin_password?',
        'admin_email?',
        'admin_firstname?',
        'admin_lastname?',

        'run_mode_admin?',
        'run_mode_frontend?',
        'run_level_bundle?',
    ];

    /**
     * Required options in quit mode
     *
     * @var array
     */
    protected $_quiet_required = [
        self::OPTION_FIELD_DB_HOST,
        //self::OPTION_FIELD_DB_PORT,
        self::OPTION_FIELD_DB_NAME,
        self::OPTION_FIELD_DB_USER,
        self::OPTION_FIELD_DB_PASSWORD,
        //self::OPTION_FIELD_DB_PREFIX,

        self::OPTION_FIELD_ADMIN_USERNAME,
        self::OPTION_FIELD_ADMIN_PASSWORD,
        self::OPTION_FIELD_ADMIN_EMAIL,
        //self::OPTION_FIELD_ADMIN_FIRSTNAME,
        //self::OPTION_FIELD_ADMIN_LASTNAME,

        //self::OPTION_FIELD_RUN_MODE_ADMIN,
        //self::OPTION_FIELD_RUN_MODE_FRONTEND,
        //self::OPTION_FIELD_RUN_LEVEL_BUNDLE,
    ];

    protected $_default_values = [
        self::OPTION_FIELD_DB_HOST           => '127.0.0.1',
        self::OPTION_FIELD_DB_PORT           => 3306,
        self::OPTION_FIELD_DB_NAME           => 'sellvana',
        self::OPTION_FIELD_DB_USER           => 'root',
        self::OPTION_FIELD_DB_PASSWORD       => '',
        self::OPTION_FIELD_DB_PREFIX         => '',

        self::OPTION_FIELD_ADMIN_USERNAME    => 'admin',
        self::OPTION_FIELD_ADMIN_PASSWORD    => '',
        self::OPTION_FIELD_ADMIN_EMAIL       => '',
        self::OPTION_FIELD_ADMIN_FIRSTNAME   => 'admin',
        self::OPTION_FIELD_ADMIN_LASTNAME    => 'admin',

        self::OPTION_FIELD_RUN_MODE_ADMIN    => 'DEBUG',
        self::OPTION_FIELD_RUN_MODE_FRONTEND => 'DEBUG',
        self::OPTION_FIELD_RUN_LEVEL_BUNDLE  => 'all',
    ];

    protected $_option_map = [
        self::OPTION_FIELD_DB_HOST           => ['db', 'host'],
        self::OPTION_FIELD_DB_PORT           => ['db', 'port'],
        self::OPTION_FIELD_DB_NAME           => ['db', 'dbname'],
        self::OPTION_FIELD_DB_USER           => ['db', 'username'],
        self::OPTION_FIELD_DB_PASSWORD       => ['db', 'password'],
        self::OPTION_FIELD_DB_PREFIX         => ['db', 'table_prefix'],

        self::OPTION_FIELD_ADMIN_USERNAME    => ['admin', 'username'],
        self::OPTION_FIELD_ADMIN_PASSWORD    => ['admin', 'password'],
        self::OPTION_FIELD_ADMIN_EMAIL       => ['admin', 'email'],
        self::OPTION_FIELD_ADMIN_FIRSTNAME   => ['admin', 'firstname'],
        self::OPTION_FIELD_ADMIN_LASTNAME    => ['admin', 'lastname'],

        self::OPTION_FIELD_RUN_MODE_ADMIN    => ['config', 'run_mode_admin'],
        self::OPTION_FIELD_RUN_MODE_FRONTEND => ['config', 'run_mode_frontend'],
        self::OPTION_FIELD_RUN_LEVEL_BUNDLE  => ['config', 'run_levels_bundle'],
    ];

    protected $_validateRules = [
        self::OPTION_FIELD_DB_HOST           => ['@required', '/^[A-Za-z0-9.\[\]:-]+$/'],
        self::OPTION_FIELD_DB_PORT           => ['@required', '@numeric'],
        self::OPTION_FIELD_DB_NAME           => ['@required', '/^[A-Za-z0-9_]+$/'],
        self::OPTION_FIELD_DB_USER           => ['@required', '/^[A-Za-z0-9_]+$/'],
        self::OPTION_FIELD_DB_PREFIX         => '/^[A-Za-z0-9_]+$/',

        self::OPTION_FIELD_ADMIN_USERNAME    => ['@required', '/^[A-Za-z0-9_.@-]+$/'],
        self::OPTION_FIELD_ADMIN_PASSWORD    => '@required',
        self::OPTION_FIELD_ADMIN_EMAIL       => ['@required', '@email'],
        self::OPTION_FIELD_ADMIN_FIRSTNAME   => '@required',
        self::OPTION_FIELD_ADMIN_LASTNAME    => '@required',
    ];

    /**
     * Short help.
     *
     * @return string
     */
    public function getShortHelp()
    {
        return '';
    }

    /**
     * Full help
     *
     * @return string
     */
    public function getLongHelp()
    {
        return <<<EOT

EOT;
    }

    /**
     *
     */
    protected function _run()
    {
        $options = $this->getOptionFields();

        if ($this->getOption(self::OPTION_QUIET)) {
            $this->FCom_Shell_Shell->setOutMode(FCom_Shell_Shell::OUT_MODE_QUIET);
            $error = false;
            foreach ($this->_quiet_required as $item) {
                if (!is_string($this->getOption($item))) {
                    $error[$item] = true;
                }
            }
            if ($error) {
                exit();
            }
        }

        $options = array_merge($this->_default_values, $options);

        $validator = $this->BValidate;
        if (!$validator->validateInput($options, $this->_getValidateRules())) {
            var_dump($validator->validateErrors());
            exit();
        } else {
            //logic

            //Transform options to config data;
            $configData = [];
            foreach ($this->_option_map as $field => $configMap) {
                $configData[$configMap[0]][$configMap[1]] = $options[$field];
            }

            //DB Config;
            $this->BConfig->add(['db' => $configData['db']], true);
            try {
                $this->BDb->connect(null, true);
            } catch (PDOException $e) {
                var_dump($e->getMessage());
                exit();
            }

            //Create admin
            $this->BMigrate->migrateModules('FCom_Admin', true);
            exit();
            try {
                $this->FCom_Admin_Model_User
                    ->create($configData['admin'])
                    ->set('is_superadmin', 1)
                    ->save()
                    ->login();
            } catch (Exception $e) {
                var_dump($e->getMessage());
                exit();
            }

            //Prepare another config before run migrate
            $runLevels = [];
            if (!empty($configData['config']['run_levels_bundle'])) {
                switch ($configData['config']['run_levels_bundle']) {
                    case 'min':
                        $runLevels = [
                            'Sellvana_MarketClient' => 'REQUESTED',
                            'Sellvana_FrontendThemeBootSimple' => 'REQUESTED',
                        ];
                        break;

                    case 'all':
                        $runLevels = [
                            'Sellvana_VirtPackCoreEcom' => 'REQUESTED',
                        ];
                        break;
                }
            }
            $this->BConfig->add([
                'install_status' => 'installed',
                'db' => ['implicit_migration' => 1/*, 'currently_migrating' => 0*/],
                'module_run_levels' => ['FCom_Core' => $runLevels],
                'mode_by_ip' => [
                    'FCom_Frontend' => !empty($configData['config']['run_mode_frontend']) ? $configData['config']['run_mode_frontend'] : 'DEBUG',
                    'FCom_Admin' => !empty($configData['config']['run_mode_admin']) ? $configData['config']['run_mode_admin'] : 'DEBUG',
                ],
                'modules' => [
                    'FCom_Frontend' => [
                        'theme' => 'Sellvana_FrontendThemeBootSimple',
                    ],
                ],
                'cache' => [
                    'default_backend' => $this->BCache->getFastestAvailableBackend(),
                ],
            ], true);


            $this->BEvents->fire(static::$_origClass . '::install:after', ['data' => $configData]);
        }
    }

    /**
     * @param null $fieldName
     * @return array
     */
    protected function _getValidateRules($fieldName = null)
    {
        if (null === $fieldName) {
            $validateRules = $this->_validateRules;
        } elseif (isset($this->_validateRules[$fieldName])) {
            $validateRules = [$fieldName => $this->_validateRules[$fieldName]];
        } else {
            return [];
        }

        $rules = [];
        foreach ($validateRules as $field => $rule) {
            foreach ((array)$rule as $item) {
                $rules[] = [$field, $item];
            }
        }
        return $rules;
    }

    /**
     * @return array
     */
    public function getOptionFields(){
        $optionFields = [];
        foreach ($this->_option_map as $key => $item) {
            if (is_string($this->getOption($key))) {
                $optionFields[$key] = $this->getOption($key);
            }
        }
        return $optionFields;
    }
}