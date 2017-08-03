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
            '' => (('Enable in staging or production modes')),
            'enable' => (('Enable always')),
            'disable' => (('Disable always')),
        ];
        $blankBool = ['' => '', 0 => (('no')), 1 => (('YES'))];
        $cacheBackends = $this->BCache->getAllBackendsAsOptions();

        $args['navs'] = array_merge_recursive($args['navs'], [
            '/areas' => ['label' => (('Areas')), 'pos' => 10],
            '/areas/core' => ['label' => (('Core Settings')), 'pos' => 10],
            '/areas/core/website' => ['label' => (('Website')), 'pos' => 10],
            '/areas/core/l10n' => ['label' => (('Localization')), 'pos' => 20],
            '/areas/core/session' => ['label' => (('Session')), 'pos' => 30],
            '/areas/core/db' => ['label' => (('DB')), 'pos' => 40, 'hide_for_site' => true],
            '/areas/core/cache' => ['label' => (('Cache')), 'pos' => 50],
            '/areas/core/dev' => ['label' => (('Developer')), 'pos' => 60],
            '/areas/core/web' => ['label' => (('Web Settings')), 'pos' => 70],
            '/areas/core/staging' => ['label' => (('Staging')), 'pos' => 80],

            '/areas/frontend' => ['label' => (('Frontend Settings')), 'pos' => 20],
            '/areas/frontend/html' => ['label' => (('Frontend HTML')), 'pos' => 10],
            '/areas/frontend/session' => ['label' => (('Frontend Session')), 'pos' => 20],
            '/areas/frontend/web_security' => ['label' => (('Frontend Web Security')), 'pos' => 30],
            '/areas/frontend/area' => ['label' => (('Area Settings')), 'pos' => 40],
            '/areas/frontend/custom_tags' => ['label' => (('Custom Tags')), 'pos' => 50],

            '/areas/admin' => ['label' => (('Admin Settings')), 'pos' => 30],
            '/areas/admin/html' => ['label' => (('Admin HTML')), 'pos' => 10],
            '/areas/admin/area' => ['label' => (('Area Settings')), 'pos' => 20],
            '/areas/admin/user_security' => ['label' => (('User Security')), 'pos' => 30],
            '/areas/admin/dashboard' => ['label' => (('Dashboard')), 'pos' => 50],

            '/areas/cron' => ['label' => (('Cron Settings')), 'pos' => 30],
            '/areas/cron/area' => ['label' => (('Area Settings')), 'pos' => 10],
            '/areas/cron/dispatch' => ['label' => (('Cron Dispatch')), 'pos' => 20],

            '/themes' => ['label' => (('Themes')), 'pos' => 80],

            '/other' => ['label' => (('Other')), 'pos' => 90],
        ]);

        $args['forms'] = array_merge_recursive($args['forms'], [
            '/areas/core/website' => [
                'config' => [
                    'fields' => [
                        'default' => ['root' => 'modules/FCom_Core'],
                        ['name' => 'company_name', 'label' => (('Company Name'))],
                        ['name' => 'site_title', 'label' => (('Site Title'))],
                        ['name' => 'admin_email', 'label' => (('Admin Email')), 'input_type' => 'email'],
                        ['name' => 'sales_name', 'label' => (('Sales Name'))],
                        ['name' => 'sales_email', 'label' => (('Sales Email')), 'input_type' => 'email'],
                        ['name' => 'support_name', 'label' => (('Support Name'))],
                        ['name' => 'support_email', 'label' => (('Support Email')), 'input_type' => 'email'],
                        ['name' => 'copyright_message', 'label' => (('Copyright Message'))],
                    ],
                ],
            ],
            '/areas/core/l10n' => [
                'config' => [
                    'fields' => [
                        'default' => ['root' => 'modules/FCom_Core'],
                        ['name' => 'limit_countries', 'label' => (('Limit Countries')), 'type' => 'checkbox'],
                        ['name' => 'allowed_countries', 'label' => (('Allowed Countries')), 'options' => $countries,
                            'multiple' => true, 'if' => '{limit_countries}'],
                        ['name' => 'default_country', 'label' => (('Default Country')), 'options' => $countries],
                        ['name' => 'default_tz', 'label' => (('Default Timezone')), 'options' => $tzs],
                        ['name' => 'default_locale', 'label' => (('Default Locale')), 'options' => $locales],
                        ['name' => 'base_currency', 'label' => (('Base Currency'))],
                        ['name' => 'default_currency', 'label' => (('Default Currency'))],
                    ],
                ],
            ],
            '/areas/core/session' => [
                'config' => [
                    'fields' => [
                        'default' => ['root' => 'cookie'],
                        ['name' => 'session_handler', 'label' => (('Session Handler')), 'options' => $sessionHandlers],
                        ['name' => 'session_savepath', 'label' => (('Session Save Path'))],
                        ['name' => 'remember_days', 'label' => (('Remember Me Timeout (days)'))],
                        ['name' => 'domain', 'label' => (('Session Cookie Domain'))],
                        ['name' => 'path', 'label' => (('Session Cookie Path'))],
                        ['name' => 'session_namespace', 'label' => (('Session Cookie Namespace'))],
                        ['name' => 'session_check_ip', 'label' => (('Verify Session IP and User Agent')), 'type' => 'checkbox'],
                        ['name' => 'use_strict_mode', 'label' => (('Use Cookie Strict Mode')), 'type' => 'checkbox',
                            'notes_tpl' => '<a href="https://secure.php.net/manual/en/session.configuration.php#ini.session.use-strict-mode" target="_blank">{{"Details"|_}}</a>'],
                        ['name' => 'delete_old_session', 'label' => (('Delete Old Session on session_regenerate_id()')), 'type' => 'checkbox',
                            'notes_tpl' => '<a href="https://wiki.php.net/rfc/precise_session_management" target="_blank">{{"Details"|_}}</a>'],
                    ],
                ],
            ],
            '/areas/core/db' => [
                'config' => [
                    'fields' => [
                        'default' => ['root' => 'db'],
                        ['name' => 'host', 'label' => (('Host'))],
                        ['name' => 'port', 'label' => (('Port'))],
                        ['name' => 'dbname', 'label' => (('Database'))],
                        ['name' => 'username', 'label' => (('Username'))],
                        ['name' => 'password', 'label' => (('Password')), 'input_type' => 'password'],
                        ['name' => 'table_prefix', 'label' => (('Table Prefix'))],
                        ['name' => 'logging', 'label' => (('Logging')), 'type' => 'checkbox'],
                        ['name' => 'implicit_migration', 'label' => (('Implicit Migration')), 'type' => 'checkbox'],
                    ],
                ],
            ],
            '/areas/core/cache' => [
                'config' => [
                    'fields' => [
                        'default' => ['root' => 'core/cache'],
                        ['name' => 'manifest_files', 'label' => (('Module Manifest Files Cache')), 'options' => $cacheOptions],
                        ['name' => 'layout_files', 'label' => (('Layout Files Cache')), 'options' => $cacheOptions],
                        ['name' => 'view_files', 'label' => (('View Template Files Cache')), 'options' => $cacheOptions],
                        ['name' => 'twig', 'label' => (('Twig Cache')), 'options' => $cacheOptions],
                        ['name' => 'default_backend', 'label' => (('Default Backend')), 'options' => $cacheBackends],
                        ['name' => 'host', 'label' => (('Memcached Host')), 'root' => 'core/cache/memcache', 'if' => "{core/cache/default_backend} == 'memcache'"],
                        ['name' => 'port', 'label' => (('Memcached Port')), 'root' => 'core/cache/memcache', 'if' => "{core/cache/default_backend} == 'memcache'"],
                        ['name' => 'prefix', 'label' => (('Memcached Prefix')), 'root' => 'core/cache/memcache', 'if' => "{core/cache/default_backend} == 'memcache'"],
                    ],
                ],
            ],

            '/areas/frontend/html' => [
                'config' => [
                    'fields' => [
                        'default' => ['root' => 'modules/FCom_Frontend'],
                        ['name' => 'theme', 'label' => (('Theme')), 'options' => $this->BLayout->getThemes('FCom_Frontend', true)],
                        ['name' => 'add_js_files', 'label' => (('Additional JS Files')), 'type' => 'textarea'],
                        ['name' => 'add_css_files', 'label' => (('Additional CSS Files')), 'type' => 'textarea'],
                        ['name' => 'add_js_code', 'label' => (('Additional JS Code')), 'type' => 'textarea'],
                        ['name' => 'add_css_code', 'label' => (('Additional CSS Code')), 'type' => 'textarea'],
                    ],
                ],
            ],
            '/areas/frontend/session' => [
                'config' => [
                    'fields' => [
                        'default' => ['root' => 'modules/FCom_Frontend'],
                        ['name' => 'session_handler', 'label' => (('Session Handler')), 'options' => $sessionHandlers],
                        ['name' => 'session_savepath', 'label' => (('Session Save Path'))],
                        ['name' => 'remember_days', 'label' => (('Remember Me Timeout (days)'))],
                        ['name' => 'domain', 'label' => (('Session Cookie Domain'))],
                        ['name' => 'path', 'label' => (('Session Cookie Path'))],
                        ['name' => 'session_namespace', 'label' => (('Session Cookie Namespace'))],
                        ['name' => 'session_check_ip', 'label' => (('Verify Session IP and User Agent')), 'type' => 'checkbox'],
                        ['name' => 'use_strict_mode', 'label' => (('Use Cookie Strict Mode')), 'type' => 'checkbox',
                            'notes_tpl' => '<a href="https://secure.php.net/manual/en/session.configuration.php#ini.session.use-strict-mode" target="_blank">{{"Details"|_}}</a>'],
                        ['name' => 'delete_old_session', 'label' => (('Delete Old Session on session_regenerate_id()')), 'type' => 'checkbox',
                            'notes_tpl' => '<a href="https://wiki.php.net/rfc/precise_session_management" target="_blank">{{"Details"|_}}</a>'],
                    ],
                ],
            ],
            '/areas/frontend/web_security' => [
                'config' => [
                    'fields' => [
                        'default' => ['root' => 'modules/FCom_Frontend/web'],
                        ['name' => 'hide_script_name', 'label' => (('Hide script file name in URL')), 'options' => ['' => '', 0 => (('No')), 1 => (('Automatic')), 2 => (('FORCE'))]],
                        ['name' => 'http_host_whitelist', 'label' => (('HTTP Host Whitelist')), 'notes' => (('comma separated'))],
                        ['name' => 'force_domain', 'label' => (('Force Domain Name'))],
                        ['name' => 'force_https', 'label' => (('Force HTTPS')), 'options' => $blankBool],
                        ['name' => 'csrf_check_method', 'label' => (('CSRF Check Method')), 'options' => $this->BRequest->getAvailableCsrfMethods(true)],
                        ['name' => 'csrf_web_root', 'label' => (('CSRF Referrer Web Root Path (optional)'))],
                        ['name' => 'hsts_enable', 'label' => (('Enable HSTS header')), 'notes' => (('HTTP Strict Transport Security')), 'options' => $blankBool],
                    ],
                ],
            ],
            '/areas/frontend/area' => [
                'config' => [
                    'fields' => [
                        'default' => ['root' => 'modules/FCom_Frontend'],
                        ['name' => 'FCom_Frontend', 'label' => (('IP: Mode')), 'type' => 'component',
                            'component' => 'sv-comp-form-ip-mode', 'root' => 'mode_by_ip'],
                        ['name' => 'modules', 'label' => (('Modules to run in RECOVERY mode')), 'root' => 'recovery/FCom_Admin', 'type' => 'textarea'],
                    ],
                ],
            ],
            '/areas/frontend/custom_tags' => [
                'config' => [
                    'fields' => [
                        'default' => ['root' => 'modules/FCom_Frontend'],
                        ['name' => 'custom_tags_homepage', 'label' => 'Home Page', 'type' => 'textarea'],
                        ['name' => 'custom_tags_category', 'label' => 'Category Page', 'type' => 'textarea'],
                        ['name' => 'custom_tags_search', 'label' => 'Search Page', 'type' => 'textarea'],
                        ['name' => 'custom_tags_product', 'label' => 'Product Page', 'type' => 'textarea'],
                        ['name' => 'custom_tags_cart', 'label' => 'Shopping Cart Page', 'type' => 'textarea'],
                        ['name' => 'custom_tags_checkout', 'label' => 'Checkout Page', 'type' => 'textarea'],
                        ['name' => 'custom_tags_success', 'label' => 'Checkout Success Page', 'type' => 'textarea'],
                    ],
                ],
            ],

            '/areas/admin/html' => [
                'config' => [
                    'fields' => [
                        'default' => ['root' => 'modules/FCom_Admin'],
                        ['name' => 'theme', 'label' => (('Theme')), 'options' => $this->BLayout->getThemes('FCom_Admin', true)],
                        ['name' => 'add_js_files', 'label' => (('Additional JS Files')), 'type' => 'textarea'],
                        ['name' => 'add_css_files', 'label' => (('Additional CSS Files')), 'type' => 'textarea'],
                        ['name' => 'add_js_code', 'label' => (('Additional JS Code')), 'type' => 'textarea'],
                        ['name' => 'add_css_code', 'label' => (('Additional CSS Code')), 'type' => 'textarea'],
                    ],
                ],
            ],
            '/areas/admin/area' => [
                'config' => [
                    'fields' => [
                        'default' => ['root' => 'modules/FCom_Admin'],
                        ['name' => 'FCom_Admin', 'label' => (('IP: Mode')), 'type' => 'component',
                            'component' => 'sv-comp-form-ip-mode', 'root' => 'mode_by_ip'],
                        ['name' => 'modules', 'label' => (('Modules to run in RECOVERY mode')), 'root' => 'recovery/FCom_Admin', 'type' => 'textarea'],
                        ['name' => 'enable_locales', 'label' => (('Enable UI Multi Locale')), 'type' => 'checkbox'],
                        ['name' => 'default_locale', 'label' => (('Default Admin UI Locale')), 'options' => $locales],
                        ['name' => 'allowed_locales', 'label' => (('Allowed Admin UI Locale')), 'options' => $locales, 'multiple' => true],
                        ['name' => 'enable_debug_in_js', 'label' => (('Enable Debug Moe in JS')), 'type' => 'checkbox'],
                    ],
                ],
            ],
            '/areas/admin/dashboard' => [
                'config' => [
                    'fields' => [
                        'default' => ['root' => 'modules/FCom_Admin'],
                        ['name' => 'default_dashboard_widget_limit', 'label' => (('Default Widgets Rows Limit'))],
                    ],
                ],
            ],
            '/areas/admin/user_security' => [
                'config' => [
                    'fields' => [
                        'default' => ['root' => 'modules/FCom_Admin'],
                        ['name' => 'password_strength', 'label' => (('Strong Password Security')), 'type' => 'checkbox'],
                        ['name' => 'password_reset_token_ttl_hr', 'label' => (('Password Reset Token TTL')), 'notes' => (('hours, default 24'))],
                        ['name' => 'recaptcha_login', 'label' => (('Recaptcha on Login Form')), 'type' => 'checkbox'],
                        ['name' => 'recaptcha_password_recover', 'label' => (('Recaptcha on Password Recovery Form')), 'type' => 'checkbox'],
                        ['name' => 'recaptcha_g2fa_recover', 'label' => (('Recaptcha on Google 2FA Recovery Form')), 'type' => 'checkbox'],
                    ],
                ],
            ],

            '/areas/cron/area' => [
                'config' => [
                    'fields' => [
                        'default' => ['root' => 'modules/FCom_Cron'],
                        ['name' => 'FCom_Cron', 'label' => (('IP: Mode')), 'type' => 'component',
                            'component' => 'sv-comp-form-ip-mode', 'root' => 'mode_by_ip'],
                    ],
                ],
            ],
            '/areas/cron/dispatch' => [
                'config' => [
                    'fields' => [
                        'default' => ['root' => 'modules/FCom_Cron'],
                        ['name' => 'leeway_mins', 'label' => (('Leeway Minutes')), 'input_type' => 'number'],
                        ['name' => 'timeout_mins', 'label' => (('Timeout Minutes')), 'input_type' => 'number'],
                        ['name' => 'wait_sec', 'label' => (('Wait Seconds')), 'input_type' => 'number'],
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