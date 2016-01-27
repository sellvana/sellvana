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

    const OPTION_FIELD_DB_HOST           = 'db-host';
    const OPTION_FIELD_DB_PORT           = 'db-port';
    const OPTION_FIELD_DB_NAME           = 'db-name';
    const OPTION_FIELD_DB_USER           = 'db-user';
    const OPTION_FIELD_DB_PASSWORD       = 'db-password';
    const OPTION_FIELD_DB_PREFIX         = 'db-prefix';

    const OPTION_FIELD_ADMIN_USERNAME    = 'admin-username';
    const OPTION_FIELD_ADMIN_PASSWORD    = 'admin-password';
    const OPTION_FIELD_ADMIN_EMAIL       = 'admin-email';
    const OPTION_FIELD_ADMIN_FIRSTNAME   = 'admin-firstname';
    const OPTION_FIELD_ADMIN_LASTNAME    = 'admin-lastname';

    const OPTION_FIELD_RUN_MODE_ADMIN    = 'run-mode-admin';
    const OPTION_FIELD_RUN_MODE_FRONTEND = 'run-mode-frontend';
    const OPTION_FIELD_RUN_LEVEL_BUNDLE  = 'run-level-bundle';

    static protected $_actionName = 'install';

    static protected $_availOptions = [
        'v'  => 'verbose',
        'q'  => 'quiet',

        'db-host?',
        'db-port?',
        'db-name?',
        'db-user?',
        'db-password?',
        'db-prefix?',

        'admin-username?',
        'admin-password?',
        'admin-email?',
        'admin-firstname?',
        'admin-lastname?',

        'run-mode-admin?',
        'run-mode-frontend?',
        'run-level-bundle?',
    ];

    /**
     * Required options in quit mode
     *
     * @var array
     */
    protected $_quietRequired = [
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

    protected $_defaultValues = [
        self::OPTION_FIELD_DB_HOST           => null,//'127.0.0.1',
        self::OPTION_FIELD_DB_PORT           => 3306,
        self::OPTION_FIELD_DB_NAME           => null,//'sellvana',
        self::OPTION_FIELD_DB_USER           => null,//'root',
        self::OPTION_FIELD_DB_PASSWORD       => '',
        self::OPTION_FIELD_DB_PREFIX         => '',

        self::OPTION_FIELD_ADMIN_USERNAME    => null,//'admin',
        self::OPTION_FIELD_ADMIN_PASSWORD    => null,//'',
        self::OPTION_FIELD_ADMIN_EMAIL       => null,//'',
        self::OPTION_FIELD_ADMIN_FIRSTNAME   => 'admin',
        self::OPTION_FIELD_ADMIN_LASTNAME    => 'admin',

        self::OPTION_FIELD_RUN_MODE_ADMIN    => 'DEBUG',
        self::OPTION_FIELD_RUN_MODE_FRONTEND => 'DEBUG',
        self::OPTION_FIELD_RUN_LEVEL_BUNDLE  => 'all',
    ];

    protected $_optionMap = [
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
        return 'Run installation wizard';
    }

    /**
     * Full help
     *
     * @return string
     */
    public function getLongHelp()
    {
        return <<<EOT

Run installation wizard.

Syntax: {white*}{$this->getParam(self::PARAM_SELF)} {$this->getActionName()} {green*}[command]{/} {red*}[parameters]{/}

Commands:

    {green*}help{/}     This help

Options:

  Informative output:
    {green*}-v, --verbose{/}     Verbose output of the process
    {green*}-q, --quiet{/}       Disable all output of the process

  Database configuration:
    {green*}    --db-host{/}             Database hostname ({red}required{/})
    {green*}    --db-port{/}             Database hostname port ({purple}default: 3306{/})
    {green*}    --db-name{/}             Database name ({red}required{/})
    {green*}    --db-user{/}             Database username ({red}required{/})
    {green*}    --db-password{/}         Database user password
    {green*}    --db-prefix{/}           Database table prefix

  Admin configuration:
    {green*}    --admin-username{/}      Admin username ({red}required{/})
    {green*}    --admin-password{/}      Admin password ({red}required{/})
    {green*}    --admin-email{/}         Admin email ({red}required{/})
    {green*}    --admin-firstname{/}     Admin firstname ({purple}default: admin{/})
    {green*}    --admin-lastname{/}      Admin lastname {purple}default: admin{/}

  Initial configuration:
    {green*}    --run-mode-admin{/}      Run Mode for Admin ({purple}default: DEBUG{/})
    {green*}    --run-mode-frontend{/}   Run Mode for Frontend ({purple}default: DEBUG{/})
    {green*}    --run-level-bundle{/}    Run Levels Bundle({purple}default: all{/})


EOT;
    }

    /**
     *
     */
    protected function _run()
    {
        $cmd = $this->getParam(self::PARAM_COMMAND);
        if ($cmd && $cmd == 'help') {
            $this->_helpCmd();
            exit;
        }
        $options = $this->_getOptionFields();

        if ($this->getOption(self::OPTION_QUIET)) {
            $this->FCom_Shell_Shell->setOutMode(FCom_Shell_Shell::OUT_MODE_QUIET);
            $error = false;
            foreach ($this->_quietRequired as $item) {
                if (!is_string($this->getOption($item))) {
                    $error[$item] = true;
                }
            }
            if ($error) {
                exit;
            }
        } else {
            $this->println('');
            foreach ($this->_optionMap as $option => $field) {
                if (isset($options[$option])) {
                    continue;
                }
                //TODO: in future maybe will need _askFieldsOptions();
                //switch ($option) {
                //    default:
                        $options[$option] = $this->_askFieldString($option,
                            '{yellow}Please enter a value for the field{/} {green}"--'
                            . $option . '"{/}: ',
                            true);
                //}
            }
        }

        $options = array_merge($this->_defaultValues, $options);
        $validator = $this->BValidate;
        if (!$validator->validateInput($options, $this->_getValidateRules())) {
            foreach ($validator->validateErrors() as $validateError) {
                foreach ((array)$validateError as $error) {
                    $this->println('{red*}ERROR:{/} ' . $error);
                }
            }
            exit;
        } else {
            //logic

            try {
                //Transform options to config data;
                $config = $this->BConfig;
                $configData = [];
                foreach ($this->_optionMap as $field => $configMap) {
                    $configData[$configMap[0]][$configMap[1]] = $options[$field];
                }

                //DB Config;
                $config->add(['db' => $configData['db']], true);
                $this->BDb->connect(null, true);

                $config->writeConfigFiles();

                //Create admin
                $migrate = $this->BMigrate;
                $migrate->migrateModules('FCom_Admin', true);

                $adminUser = $this->FCom_Admin_Model_User;
                $adminUser = $adminUser
                    ->create($configData['admin'])
                    ->set('is_superadmin', 1)
                    ->save();
                $adminUser->login();

                //Prepare another config before run migrate
                $runLevels = [];
                $runConfig = $configData['config'];
                if (!empty($runConfig['run_levels_bundle'])) {
                    switch ($runConfig['run_levels_bundle']) {
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

                $runModeFrontend = !empty($runConfig['run_mode_frontend']) ? $runConfig['run_mode_frontend'] : 'DEBUG';
                $runModeBackend = !empty($runConfig['run_mode_admin']) ? $runConfig['run_mode_admin'] : 'DEBUG';
                $config->add([
                    'install_status' => 'installed',
                    'db' => ['implicit_migration' => 1/*, 'currently_migrating' => 0*/],
                    'module_run_levels' => ['FCom_Core' => $runLevels],
                    'mode_by_ip' => [
                        'FCom_Frontend' => $runModeFrontend,
                        'FCom_Admin' => $runModeBackend,
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

                $config->writeConfigFiles();

                //Start migrations
                //$this->println(PHP_EOL . '{purple*}Installation in progress.{/}');

                //$migrate->migrateModules(false);

                //$this->out($this->FCom_Shell_Shell->cursor(FCom_Shell_Shell::CURSOR_CMD_UP, 1));
                $this->println(PHP_EOL . '{green*}Installation configuration finished.{/}');
                $this->println(
                    '{green*}Please run {/}{red*}`' . $this->getParam(self::PARAM_SELF)
                    . ' migrate`{/}{green*} to complete installation.{/}'
                    . PHP_EOL
                );

                $this->BEvents->fire(static::$_origClass . '::install:after', ['data' => $configData]);
            } catch (Exception $e) {
                $this->BDebug->logException($e);
                $this->println('{red*}FATAL ERROR:{/} ' . $e->getMessage());
                exit;
            }
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
                if (is_array($item)) {

                } else {
                    $rules[] = [$field, $item];
                }
            }
        }
        return $rules;
    }

    /**
     * @return array
     */
    protected function _getOptionFields()
    {
        $optionFields = [];
        foreach ($this->_optionMap as $key => $item) {
            if (is_string($this->getOption($key))) {
                $optionFields[$key] = $this->getOption($key);
            }
        }
        return $optionFields;
    }

    /**
     * Shell GUI of asking value
     *
     * @param string $field
     * @param string $question
     * @param bool $colorized
     * @return string
     */
    protected function _askFieldString($field, $question, $colorized = false)
    {
        $answer = '';
        $offset = 0;
        $tryCount = 3;
        $iteration = 0;
        while (true) {
            $shell = $this->FCom_Shell_Shell;

            $defaultValue = null;
            $defaultValueStr = '';
            if (isset($this->_defaultValues[$field]) && null !== $this->_defaultValues[$field]) {
                $defaultValue = $this->_defaultValues[$field];
                $defaultValueStr = '[default: "' . $defaultValue . '"] ';
            }
            if (!$colorized) {
                $question = '{yellow}' . $question . '{/}';
            }
            $this->out($question . $defaultValueStr . str_pad('', $offset));
            if ($offset) {
                $this->out($shell->cursor(FCom_Shell_Shell::CURSOR_CMD_BACK, $offset));
            }
            $answer = $shell->stdin();
            $offset = strlen($answer);

            if (empty($answer) && null !== $defaultValue) {
                $answer = $defaultValue;
            } elseif (empty($answer)) {
                $answer = null;
            }

            $error = false;
            $errorOffset = 0;
            $rules = $this->_getValidateRules($field);
            if(!empty($rules)) {
                $validator = $this->BValidate;
                $validationData = [$field => $answer];
                if ($validator->validateInput($validationData, $rules)) {
                    break;
                } else {
                    $error = true;
                    foreach ($validator->validateErrors() as $validateError) {
                        foreach ((array)$validateError as $error) {
                            $errorOffset++;
                            $this->println('{red*}ERROR:{/} ' . $error);
                        }
                    }
                }
            }

            if (($iteration == $tryCount || null !== $answer) && !$error) {
                break;
            }

            $this->out($shell->cursor(FCom_Shell_Shell::CURSOR_CMD_UP, 1 + $errorOffset));
            $iteration++;
        }
        return $answer;
    }
}