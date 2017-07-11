<?php

/**
 * Class FCom_AdminSPA_AdminSPA
 *
 * @property FCom_AdminSPA_AdminSPA_View_App FCom_AdminSPA_AdminSPA_View_App
 * @property FCom_Admin_Model_User FCom_Admin_Model_User
 * @property FCom_Admin_Model_Personalize FCom_Admin_Model_Personalize
 */
class FCom_AdminSPA_AdminSPA extends BClass
{
    protected $_responseTypes = [
        '_messages' => true,
        '_user' => true,
        '_permissions' => true,
        '_nav' => true,
        '_personalize' => true,
        '_local_notifications' => true,
        '_redirect' => true,
        '_login' => true,
        '_csrf_token' => true,
//        '_ok' => true,
    ];

    protected $_responsesToPush = [];

    public function addResponseType($type, $callback)
    {
        $this->_responseTypes[$type] = $callback;
    }

    public function addResponses($responses = true)
    {
        if (true === $responses) {
            $responses = [];
            foreach ($this->_responseTypes as $type => $callback) {
                $responses[$type] = true;
            }
        } else {
            $responses = (array)$responses;
            foreach ($responses as $i => $u) {
                if (is_int($i) && is_string($u)) {
                    $responses[$u] = true;
                }
            }
        }
        $this->_responsesToPush = $this->BUtil->arrayMerge($this->_responsesToPush, $responses);
        return $this;
    }

    public function mergeResponses(array $result = [])
    {
        foreach ($this->_responsesToPush as $type => $data) {
            if (!empty($this->_responseTypes[$type])) {
                $callback = $this->_responseTypes[$type];
                if (true === $callback) {
                    $callback = [$this, 'responseCallback' . $type];
                }
                $result[$type] = $this->BUtil->call($callback, $data);
            } else {
                $result[$type] = !empty($result[$type]) ? $this->BUtil->arrayMerge($result[$type], $data) : $data;
            }
        }
        return $result;
    }
    
    public function responseCallback_messages($data)
    {
        if (is_array($data)) {
            foreach ($data as $i => $r) {
                if (is_string($r)) {
                    $data[$i] = ['type' => 'info', 'message' => $r];
                }
            }
        }
        return $data;
    }
    
    public function responseCallback_user($data)
    {
        $user = $this->FCom_Admin_Model_User->sessionUser();
        return $user ? $user->as_array() : false;
    }
    
    public function responseCallback_permissions($data)
    {
        return new StdClass;
    }
    
    public function responseCallback_nav($data)
    {
        $this->BLayout->applyLayout('/');
        return $this->FCom_AdminSPA_AdminSPA_View_App->getNavTree();
    }

    public function responseCallback_personalize($data)
    {
        $userId = $this->FCom_Admin_Model_User->sessionUserId();
        $pers = $this->FCom_Admin_Model_Personalize->load($userId, 'user_id');
        $result = $pers && $pers->get('data_json') ? $this->BUtil->fromJson($pers->get('data_json')) : false;
        return $result;
    }

    public function responseCallback_local_notifications($data)
    {
        return $data;
    }

    public function responseCallback_redirect($data)
    {
        return $data;
    }

    public function responseCallback_login($data)
    {
        return $data;
    }

//    public function responseCallback_ok($data)
//    {
//        return $data;
//    }

    public function responseCallback_csrf_token($data)
    {
        return $this->BSession->csrfToken();
    }


    public function onSettingsConfig($args)
    {
        $countries = $this->BLocale->getAvailableCountries();
        $tzs = $this->BLocale->tzOptions();
        $locales = $this->BLocale->getAvailableLocaleCodes();
        $sessionHandlers = $this->BSession->getHandlers();
        $cacheOptions = [
            '' => 'Enable in staging or production modes',
            'enable' => 'Enable always',
            'disable' => 'Disable always',
        ];
        $cacheBackends = $this->BCache->getAllBackendsAsOptions();

        $args['forms'] = array_merge_recursive($args['forms'], [
            '/areas/core/website' => [
                'config' => [
                    'default_field' => [
                        'root' => 'modules/FCom_Core',
                    ],
                    'fields' => [
                        ['name' => 'company_name', 'label' => 'Company Name'],
                        ['name' => 'site_title', 'label' => 'Site Title'],
                        ['name' => 'admin_email', 'label' => 'Admin Email', 'input_type' => 'email'],
                        ['name' => 'sales_name', 'label' => 'Sales Name'],
                        ['name' => 'sales_email', 'label' => 'Sales Email', 'input_type' => 'email'],
                        ['name' => 'support_name', 'label' => 'Support Name'],
                        ['name' => 'support_email', 'label' => 'Support Email', 'input_type' => 'email'],
                        ['name' => 'copyright_message', 'label' => 'Copyright Message'],
                    ],
                ],
            ],
            '/areas/core/l10n' => [
                'config' => [
                    'default_field' => [
                        'root' => 'modules/FCom_Core',
                    ],
                    'fields' => [
                        ['name' => 'limit_countries', 'label' => 'Limit Countries', 'type' => 'checkbox'],
                        ['name' => 'allowed_countries', 'label' => 'Allowed Countries', 'options' => $countries,
                            'multiple' => true, 'if' => '{limit_countries}'],
                        ['name' => 'default_country', 'label' => 'Default Country', 'options' => $countries],
                        ['name' => 'default_tz', 'label' => 'Default Timezone', 'options' => $tzs],
                        ['name' => 'default_locale', 'label' => 'Default Locale', 'options' => $locales],
                        ['name' => 'base_currency', 'label' => 'Base Currency'],
                        ['name' => 'default_currency', 'label' => 'Default Currency'],
                    ],
                ],
            ],
            '/areas/core/session' => [
                'config' => [
                    'default_field' => [
                        'root' => 'cookie',
                    ],
                    'fields' => [
                        ['name' => 'session_handler', 'label' => 'Session Handler', 'options' => $sessionHandlers],
                        ['name' => 'session_savepath', 'label' => 'Session Save Path'],
                        ['name' => 'remember_days', 'label' => 'Remember Me Timeout (days)'],
                        ['name' => 'domain', 'label' => 'Session Cookie Domain'],
                        ['name' => 'path', 'label' => 'Session Cookie Path'],
                        ['name' => 'session_namespace', 'label' => 'Session Cookie Namespace'],
                        ['name' => 'session_check_ip', 'label' => 'Verify Session IP and User Agent', 'type' => 'checkbox'],
                        ['name' => 'use_strict_mode', 'label' => 'Use Cookie Strict Mode', 'type' => 'checkbox',
                            'notes' => '<a href="https://secure.php.net/manual/en/session.configuration.php#ini.session.use-strict-mode" target="_blank">{{\'Details\'|_}}</a>'],
                        ['name' => 'delete_old_session', 'label' => 'Delete Old Session on session_regenerate_id()', 'type' => 'checkbox',
                            'notes' => '<a href="https://wiki.php.net/rfc/precise_session_management" target="_blank">{{\'Details\'|_}}</a>'],
                    ],
                ],
            ],
            '/areas/core/db' => [
                'config' => [
                    'default_field' => [
                        'root' => 'db',
                    ],
                    'fields' => [
                        ['name' => 'host', 'label' => 'Host'],
                        ['name' => 'port', 'label' => 'Port'],
                        ['name' => 'dbname', 'label' => 'Database'],
                        ['name' => 'username', 'label' => 'Username'],
                        ['name' => 'password', 'label' => 'Password', 'input_type' => 'password'],
                        ['name' => 'table_prefix', 'label' => 'Table Prefix'],
                        ['name' => 'logging', 'label' => 'Logging', 'type' => 'checkbox'],
                        ['name' => 'implicit_migration', 'label' => 'Implicit Migration', 'type' => 'checkbox'],
                    ],
                ],
            ],
            '/areas/core/cache' => [
                'config' => [
                    'default_field' => [
                        'root' => 'core/cache',
                    ],
                    'fields' => [
                        ['name' => 'manifest_files', 'label' => 'Module Manifest Files Cache', 'options' => $cacheOptions],
                        ['name' => 'layout_files', 'label' => 'Layout Files Cache', 'options' => $cacheOptions],
                        ['name' => 'view_files', 'label' => 'View Template Files Cache', 'options' => $cacheOptions],
                        ['name' => 'twig', 'label' => 'Twig Cache', 'options' => $cacheOptions],
                        ['name' => 'default_backend', 'label' => 'Default Backend', 'options' => $cacheBackends],
                        ['name' => 'host', 'label' => 'Memcached Host', 'root' => 'core/cache/memcache', 'if' => "{core/cache/default_backend} == 'memcache'"],
                        ['name' => 'port', 'label' => 'Memcached Port', 'root' => 'core/cache/memcache', 'if' => "{core/cache/default_backend} == 'memcache'"],
                        ['name' => 'prefix', 'label' => 'Memcached Prefix', 'root' => 'core/cache/memcache', 'if' => "{core/cache/default_backend} == 'memcache'"],
                    ],
                ],
            ],
            '/areas/frontend/area' => [
                'config' => [
                    'default_field' => [
                        'root' => 'modules/FCom_Frontend',
                    ],
                    'fields' => [
                        ['name' => 'FCom_Frontend', 'label' => 'IP: Mode', 'type' => 'component',
                            'component' => 'sv-comp-form-ip-mode', 'root' => 'mode_by_ip'],
                    ],
                ],
            ],
            '/areas/admin/area' => [
                'config' => [
                    'default_field' => [
                        'root' => 'modules/FCom_Admin',
                    ],
                    'fields' => [
                        ['name' => 'FCom_Admin', 'label' => 'IP: Mode', 'type' => 'component',
                            'component' => 'sv-comp-form-ip-mode', 'root' => 'mode_by_ip'],
                    ],
                ],
            ],
            '/areas/cron/area' => [
                'config' => [
                    'default_field' => [
                        'root' => 'modules/FCom_Cron',
                    ],
                    'fields' => [
                        ['name' => 'FCom_Cron', 'label' => 'IP: Mode', 'type' => 'component',
                            'component' => 'sv-comp-form-ip-mode', 'root' => 'mode_by_ip'],
                    ],
                ],
            ],
        ]);
    }

    public function onSettingsDataAfter($args)
    {
        if (!empty($args['db']['password'])) {
            $args['db']['password'] = '*****';
        }
    }
}