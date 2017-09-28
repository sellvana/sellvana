<?php

use FCom_AdminSPA_AdminSPA_Controller_Abstract as Ctrl;

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

    public function bootstrap()
    {
        $this->FCom_Admin_Model_User;

        if ($this->BConfig->get('modules/FCom_Admin/web/cors_enable')) {
            $this->BResponse->cors();
        }
        if ($this->BConfig->get('modules/FCom_Admin/web/csp_enable')) {
            $this->BResponse->csp();
        }
        if ($this->BRequest->https() && $this->BConfig->get('modules/FCom_Admin/web/hsts_enable')) {
            $this->BResponse->httpSTS();
        }
    }

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
                    $data[$i] = [Ctrl::TYPE => 'info', 'message' => $r];
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
            '/areas' => [Ctrl::LABEL => (('Areas')), 'pos' => 10],
            '/areas/core' => [Ctrl::LABEL => (('Core Settings')), 'pos' => 10],
            '/areas/core/website' => [Ctrl::LABEL => (('Website')), 'pos' => 10],
            '/areas/core/l10n' => [Ctrl::LABEL => (('Localization')), 'pos' => 20],
            '/areas/core/session' => [Ctrl::LABEL => (('Session')), 'pos' => 30],
            '/areas/core/db' => [Ctrl::LABEL => (('DB')), 'pos' => 40, 'hide_for_site' => true],
            '/areas/core/cache' => [Ctrl::LABEL => (('Cache')), 'pos' => 50],
            '/areas/core/dev' => [Ctrl::LABEL => (('Developer')), 'pos' => 60],
            '/areas/core/web' => [Ctrl::LABEL => (('Web Settings')), 'pos' => 70],
            '/areas/core/staging' => [Ctrl::LABEL => (('Staging')), 'pos' => 80],

            '/areas/frontend' => [Ctrl::LABEL => (('Frontend Settings')), 'pos' => 20],
            '/areas/frontend/html' => [Ctrl::LABEL => (('Frontend HTML')), 'pos' => 10],
            '/areas/frontend/session' => [Ctrl::LABEL => (('Frontend Session')), 'pos' => 20],
            '/areas/frontend/web_security' => [Ctrl::LABEL => (('Frontend Web Security')), 'pos' => 30],
            '/areas/frontend/area' => [Ctrl::LABEL => (('Area Settings')), 'pos' => 40],
            '/areas/frontend/custom_tags' => [Ctrl::LABEL => (('Custom Tags')), 'pos' => 50],

            '/areas/admin' => [Ctrl::LABEL => (('Admin Settings')), 'pos' => 30],
            '/areas/admin/html' => [Ctrl::LABEL => (('Admin HTML')), 'pos' => 10],
            '/areas/admin/area' => [Ctrl::LABEL => (('Area Settings')), 'pos' => 20],
            '/areas/admin/user_security' => [Ctrl::LABEL => (('User Security')), 'pos' => 30],
            '/areas/admin/web_security' => [Ctrl::LABEL => (('Frontend Web Security')), 'pos' => 40],
            '/areas/admin/dashboard' => [Ctrl::LABEL => (('Dashboard')), 'pos' => 50],

            '/areas/cron' => [Ctrl::LABEL => (('Cron Settings')), 'pos' => 30],
            '/areas/cron/area' => [Ctrl::LABEL => (('Area Settings')), 'pos' => 10],
            '/areas/cron/dispatch' => [Ctrl::LABEL => (('Cron Dispatch')), 'pos' => 20],

            '/themes' => [Ctrl::LABEL => (('Themes')), 'pos' => 80],

            '/other' => [Ctrl::LABEL => (('Other')), 'pos' => 90],
        ]);

        $args['forms'] = array_merge_recursive($args['forms'], [
            '/areas/core/website' => [
                Ctrl::CONFIG => [
                    Ctrl::FIELDS => [
                        Ctrl::DEFAULT_FIELD => ['root' => 'modules/FCom_Core'],
                        [Ctrl::NAME => 'company_name', Ctrl::LABEL => (('Company Name'))],
                        [Ctrl::NAME => 'site_title', Ctrl::LABEL => (('Site Title'))],
                        [Ctrl::NAME => 'admin_email', Ctrl::LABEL => (('Admin Email')), Ctrl::INPUT_TYPE => 'email'],
                        [Ctrl::NAME => 'sales_name', Ctrl::LABEL => (('Sales Name'))],
                        [Ctrl::NAME => 'sales_email', Ctrl::LABEL => (('Sales Email')), Ctrl::INPUT_TYPE => 'email'],
                        [Ctrl::NAME => 'support_name', Ctrl::LABEL => (('Support Name'))],
                        [Ctrl::NAME => 'support_email', Ctrl::LABEL => (('Support Email')), Ctrl::INPUT_TYPE => 'email'],
                        [Ctrl::NAME => 'copyright_message', Ctrl::LABEL => (('Copyright Message'))],
                    ],
                ],
            ],
            '/areas/core/l10n' => [
                Ctrl::CONFIG => [
                    Ctrl::FIELDS => [
                        Ctrl::DEFAULT_FIELD => ['root' => 'modules/FCom_Core'],
                        [Ctrl::NAME => 'limit_countries', Ctrl::LABEL => (('Limit Countries')), Ctrl::TYPE => 'checkbox'],
                        [Ctrl::NAME => 'allowed_countries', Ctrl::LABEL => (('Allowed Countries')), Ctrl::OPTIONS => $countries,
                            Ctrl::MULTIPLE => true, 'if' => '{limit_countries}'],
                        [Ctrl::NAME => 'default_country', Ctrl::LABEL => (('Default Country')), Ctrl::OPTIONS => $countries],
                        [Ctrl::NAME => 'default_tz', Ctrl::LABEL => (('Default Timezone')), Ctrl::OPTIONS => $tzs],
                        [Ctrl::NAME => 'default_locale', Ctrl::LABEL => (('Default Locale')), Ctrl::OPTIONS => $locales],
                        [Ctrl::NAME => 'base_currency', Ctrl::LABEL => (('Base Currency'))],
                        [Ctrl::NAME => 'default_currency', Ctrl::LABEL => (('Default Currency'))],
                    ],
                ],
            ],
            '/areas/core/session' => [
                Ctrl::CONFIG => [
                    Ctrl::FIELDS => [
                        Ctrl::DEFAULT_FIELD => ['root' => 'cookie'],
                        [Ctrl::NAME => 'session_handler', Ctrl::LABEL => (('Session Handler')), Ctrl::OPTIONS => $sessionHandlers],
                        [Ctrl::NAME => 'session_savepath', Ctrl::LABEL => (('Session Save Path'))],
                        [Ctrl::NAME => 'remember_days', Ctrl::LABEL => (('Remember Me Timeout (days)'))],
                        [Ctrl::NAME => 'domain', Ctrl::LABEL => (('Session Cookie Domain'))],
                        [Ctrl::NAME => 'path', Ctrl::LABEL => (('Session Cookie Path'))],
                        [Ctrl::NAME => 'session_namespace', Ctrl::LABEL => (('Session Cookie Namespace'))],
                        [Ctrl::NAME => 'session_check_ip', Ctrl::LABEL => (('Verify Session IP and User Agent')), Ctrl::TYPE => 'checkbox'],
                        [Ctrl::NAME => 'use_strict_mode', Ctrl::LABEL => (('Use Cookie Strict Mode')), Ctrl::TYPE => 'checkbox',
                            'notes_tpl' => '<a href="https://secure.php.net/manual/en/session.configuration.php#ini.session.use-strict-mode" target="_blank">{{"Details"|_}}</a>'],
                        [Ctrl::NAME => 'delete_old_session', Ctrl::LABEL => (('Delete Old Session on session_regenerate_id()')), Ctrl::TYPE => 'checkbox',
                            'notes_tpl' => '<a href="https://wiki.php.net/rfc/precise_session_management" target="_blank">{{"Details"|_}}</a>'],
                    ],
                ],
            ],
            '/areas/core/db' => [
                Ctrl::CONFIG => [
                    Ctrl::FIELDS => [
                        Ctrl::DEFAULT_FIELD => ['root' => 'db'],
                        [Ctrl::NAME => 'host', Ctrl::LABEL => (('Host'))],
                        [Ctrl::NAME => 'port', Ctrl::LABEL => (('Port'))],
                        [Ctrl::NAME => 'dbname', Ctrl::LABEL => (('Database'))],
                        [Ctrl::NAME => 'username', Ctrl::LABEL => (('Username'))],
                        [Ctrl::NAME => 'password', Ctrl::LABEL => (('Password')), Ctrl::INPUT_TYPE => 'password'],
                        [Ctrl::NAME => 'table_prefix', Ctrl::LABEL => (('Table Prefix'))],
                        [Ctrl::NAME => 'logging', Ctrl::LABEL => (('Logging')), Ctrl::TYPE => 'checkbox'],
                        [Ctrl::NAME => 'implicit_migration', Ctrl::LABEL => (('Implicit Migration')), Ctrl::TYPE => 'checkbox'],
                    ],
                ],
            ],
            '/areas/core/cache' => [
                Ctrl::CONFIG => [
                    Ctrl::FIELDS => [
                        Ctrl::DEFAULT_FIELD => ['root' => 'core/cache'],
                        [Ctrl::NAME => 'manifest_files', Ctrl::LABEL => (('Module Manifest Files Cache')), Ctrl::OPTIONS => $cacheOptions],
                        [Ctrl::NAME => 'layout_files', Ctrl::LABEL => (('Layout Files Cache')), Ctrl::OPTIONS => $cacheOptions],
                        [Ctrl::NAME => 'view_files', Ctrl::LABEL => (('View Template Files Cache')), Ctrl::OPTIONS => $cacheOptions],
                        [Ctrl::NAME => 'twig', Ctrl::LABEL => (('Twig Cache')), Ctrl::OPTIONS => $cacheOptions],
                        [Ctrl::NAME => 'default_backend', Ctrl::LABEL => (('Default Backend')), Ctrl::OPTIONS => $cacheBackends],
                        [Ctrl::NAME => 'host', Ctrl::LABEL => (('Memcached Host')), 'root' => 'core/cache/memcache', 'if' => "{core.cache.default_backend} == 'memcache'"],
                        [Ctrl::NAME => 'port', Ctrl::LABEL => (('Memcached Port')), 'root' => 'core/cache/memcache', 'if' => "{core.cache.default_backend} == 'memcache'"],
                        [Ctrl::NAME => 'prefix', Ctrl::LABEL => (('Memcached Prefix')), 'root' => 'core/cache/memcache', 'if' => "{core.cache.default_backend} == 'memcache'"],
                    ],
                ],
            ],

            '/areas/frontend/html' => [
                Ctrl::CONFIG => [
                    Ctrl::FIELDS => [
                        Ctrl::DEFAULT_FIELD => ['root' => 'modules/FCom_Frontend'],
                        [Ctrl::NAME => 'theme', Ctrl::LABEL => (('Theme')), Ctrl::OPTIONS => $this->BLayout->getThemes('FCom_Frontend', true)],
                        [Ctrl::NAME => 'add_js_files', Ctrl::LABEL => (('Additional JS Files')), Ctrl::TYPE => 'textarea'],
                        [Ctrl::NAME => 'add_css_files', Ctrl::LABEL => (('Additional CSS Files')), Ctrl::TYPE => 'textarea'],
                        [Ctrl::NAME => 'add_js_code', Ctrl::LABEL => (('Additional JS Code')), Ctrl::TYPE => 'textarea'],
                        [Ctrl::NAME => 'add_css_code', Ctrl::LABEL => (('Additional CSS Code')), Ctrl::TYPE => 'textarea'],
                    ],
                ],
            ],
            '/areas/frontend/session' => [
                Ctrl::CONFIG => [
                    Ctrl::FIELDS => [
                        Ctrl::DEFAULT_FIELD => ['root' => 'modules/FCom_Frontend'],
                        [Ctrl::NAME => 'session_handler', Ctrl::LABEL => (('Session Handler')), Ctrl::OPTIONS => $sessionHandlers],
                        [Ctrl::NAME => 'session_savepath', Ctrl::LABEL => (('Session Save Path'))],
                        [Ctrl::NAME => 'remember_days', Ctrl::LABEL => (('Remember Me Timeout (days)'))],
                        [Ctrl::NAME => 'domain', Ctrl::LABEL => (('Session Cookie Domain'))],
                        [Ctrl::NAME => 'path', Ctrl::LABEL => (('Session Cookie Path'))],
                        [Ctrl::NAME => 'session_namespace', Ctrl::LABEL => (('Session Cookie Namespace'))],
                        [Ctrl::NAME => 'session_check_ip', Ctrl::LABEL => (('Verify Session IP and User Agent')), Ctrl::TYPE => 'checkbox'],
                        [Ctrl::NAME => 'use_strict_mode', Ctrl::LABEL => (('Use Cookie Strict Mode')), Ctrl::TYPE => 'checkbox',
                            'notes_tpl' => '<a href="https://secure.php.net/manual/en/session.configuration.php#ini.session.use-strict-mode" target="_blank">{{"Details"|_}}</a>'],
                        [Ctrl::NAME => 'delete_old_session', Ctrl::LABEL => (('Delete Old Session on session_regenerate_id()')), Ctrl::TYPE => 'checkbox',
                            'notes_tpl' => '<a href="https://wiki.php.net/rfc/precise_session_management" target="_blank">{{"Details"|_}}</a>'],
                    ],
                ],
            ],
            '/areas/frontend/web_security' => [
                Ctrl::CONFIG => [
                    Ctrl::FIELDS => [
                        Ctrl::DEFAULT_FIELD => ['root' => 'modules/FCom_Frontend/web'],
                        [Ctrl::NAME => 'hide_script_name', Ctrl::LABEL => (('Hide script file name in URL')), Ctrl::OPTIONS => ['' => '', 0 => (('No')), 1 => (('Automatic')), 2 => (('FORCE'))]],
                        [Ctrl::NAME => 'http_host_whitelist', Ctrl::LABEL => (('HTTP Host Whitelist')), Ctrl::NOTES => (('comma separated'))],
                        [Ctrl::NAME => 'force_domain', Ctrl::LABEL => (('Force Domain Name'))],
                        [Ctrl::NAME => 'force_https', Ctrl::LABEL => (('Force HTTPS')), Ctrl::OPTIONS => $blankBool],
                        [Ctrl::NAME => 'csrf_check_method', Ctrl::LABEL => (('CSRF Check Method')), Ctrl::OPTIONS => $this->BRequest->getAvailableCsrfMethods(true)],
                        [Ctrl::NAME => 'csrf_web_root', Ctrl::LABEL => (('CSRF Referrer Web Root Path (optional)'))],
                        [Ctrl::NAME => 'hsts_enable', Ctrl::LABEL => (('Enable HSTS header')), Ctrl::NOTES => (('HTTP Strict Transport Security')), Ctrl::OPTIONS => $blankBool],
                        [Ctrl::NAME => 'cors_enable', Ctrl::LABEL => (('Enable CORS header')), Ctrl::NOTES => (('Cross-Origin Resource Sharing')), Ctrl::OPTIONS => $blankBool],
                        [Ctrl::NAME => 'csp_enable', Ctrl::LABEL => (('Enable CSP header')), Ctrl::NOTES => (('Content Security Policy')), Ctrl::OPTIONS => $blankBool],
                    ],
                ],
            ],
            '/areas/frontend/area' => [
                Ctrl::CONFIG => [
                    Ctrl::FIELDS => [
                        Ctrl::DEFAULT_FIELD => ['root' => 'modules/FCom_Frontend'],
                        [Ctrl::NAME => 'FCom_Frontend', Ctrl::LABEL => (('IP: Mode')), Ctrl::TYPE => 'component',
                            'component' => 'sv-comp-form-ip-mode', 'root' => 'mode_by_ip'],
                        [Ctrl::NAME => 'modules', Ctrl::LABEL => (('Modules to run in RECOVERY mode')), 'root' => 'recovery/FCom_Admin', Ctrl::TYPE => 'textarea'],
                    ],
                ],
            ],
            '/areas/frontend/custom_tags' => [
                Ctrl::CONFIG => [
                    Ctrl::FIELDS => [
                        Ctrl::DEFAULT_FIELD => ['root' => 'modules/FCom_Frontend'],
                        [Ctrl::NAME => 'custom_tags_homepage', Ctrl::LABEL => 'Home Page', Ctrl::TYPE => 'textarea'],
                        [Ctrl::NAME => 'custom_tags_category', Ctrl::LABEL => 'Category Page', Ctrl::TYPE => 'textarea'],
                        [Ctrl::NAME => 'custom_tags_search', Ctrl::LABEL => 'Search Page', Ctrl::TYPE => 'textarea'],
                        [Ctrl::NAME => 'custom_tags_product', Ctrl::LABEL => 'Product Page', Ctrl::TYPE => 'textarea'],
                        [Ctrl::NAME => 'custom_tags_cart', Ctrl::LABEL => 'Shopping Cart Page', Ctrl::TYPE => 'textarea'],
                        [Ctrl::NAME => 'custom_tags_checkout', Ctrl::LABEL => 'Checkout Page', Ctrl::TYPE => 'textarea'],
                        [Ctrl::NAME => 'custom_tags_success', Ctrl::LABEL => 'Checkout Success Page', Ctrl::TYPE => 'textarea'],
                    ],
                ],
            ],

            '/areas/admin/html' => [
                Ctrl::CONFIG => [
                    Ctrl::FIELDS => [
                        Ctrl::DEFAULT_FIELD => ['root' => 'modules/FCom_Admin'],
                        [Ctrl::NAME => 'theme', Ctrl::LABEL => (('Theme')), Ctrl::OPTIONS => $this->BLayout->getThemes('FCom_Admin', true)],
                        [Ctrl::NAME => 'add_js_files', Ctrl::LABEL => (('Additional JS Files')), Ctrl::TYPE => 'textarea'],
                        [Ctrl::NAME => 'add_css_files', Ctrl::LABEL => (('Additional CSS Files')), Ctrl::TYPE => 'textarea'],
                        [Ctrl::NAME => 'add_js_code', Ctrl::LABEL => (('Additional JS Code')), Ctrl::TYPE => 'textarea'],
                        [Ctrl::NAME => 'add_css_code', Ctrl::LABEL => (('Additional CSS Code')), Ctrl::TYPE => 'textarea'],
                    ],
                ],
            ],
            '/areas/admin/area' => [
                Ctrl::CONFIG => [
                    Ctrl::FIELDS => [
                        Ctrl::DEFAULT_FIELD => ['root' => 'modules/FCom_Admin'],
                        [Ctrl::NAME => 'FCom_Admin', Ctrl::LABEL => (('IP: Mode')), Ctrl::TYPE => 'component',
                            'component' => 'sv-comp-form-ip-mode', 'root' => 'mode_by_ip'],
                        [Ctrl::NAME => 'modules', Ctrl::LABEL => (('Modules to run in RECOVERY mode')), 'root' => 'recovery/FCom_Admin', Ctrl::TYPE => 'textarea'],
                        [Ctrl::NAME => 'enable_locales', Ctrl::LABEL => (('Enable UI Multi Locale')), Ctrl::TYPE => 'checkbox'],
                        [Ctrl::NAME => 'default_locale', Ctrl::LABEL => (('Default Admin UI Locale')), Ctrl::OPTIONS => $locales],
                        [Ctrl::NAME => 'allowed_locales', Ctrl::LABEL => (('Allowed Admin UI Locale')), Ctrl::OPTIONS => $locales, Ctrl::MULTIPLE => true],
                        [Ctrl::NAME => 'enable_debug_in_js', Ctrl::LABEL => (('Enable Debug Moe in JS')), Ctrl::TYPE => 'checkbox'],
                    ],
                ],
            ],
            '/areas/admin/dashboard' => [
                Ctrl::CONFIG => [
                    Ctrl::FIELDS => [
                        Ctrl::DEFAULT_FIELD => ['root' => 'modules/FCom_Admin'],
                        [Ctrl::NAME => 'default_dashboard_widget_limit', Ctrl::LABEL => (('Default Widgets Rows Limit'))],
                    ],
                ],
            ],
            '/areas/admin/web_security' => [
                Ctrl::CONFIG => [
                    Ctrl::FIELDS => [
                        Ctrl::DEFAULT_FIELD => ['root' => 'modules/FCom_Admin/web'],
                        [Ctrl::NAME => 'hide_script_name', Ctrl::LABEL => (('Hide script file name in URL')), Ctrl::OPTIONS => ['' => '', 0 => (('No')), 1 => (('Automatic')), 2 => (('FORCE'))]],
                        [Ctrl::NAME => 'http_host_whitelist', Ctrl::LABEL => (('HTTP Host Whitelist')), Ctrl::NOTES => (('comma separated'))],
                        [Ctrl::NAME => 'force_domain', Ctrl::LABEL => (('Force Domain Name'))],
                        [Ctrl::NAME => 'force_https', Ctrl::LABEL => (('Force HTTPS')), Ctrl::OPTIONS => $blankBool],
                        [Ctrl::NAME => 'csrf_check_method', Ctrl::LABEL => (('CSRF Check Method')), Ctrl::OPTIONS => $this->BRequest->getAvailableCsrfMethods(true)],
                        [Ctrl::NAME => 'csrf_web_root', Ctrl::LABEL => (('CSRF Referrer Web Root Path (optional)'))],
                        [Ctrl::NAME => 'hsts_enable', Ctrl::LABEL => (('Enable HSTS header')), Ctrl::NOTES => (('HTTP Strict Transport Security')), Ctrl::OPTIONS => $blankBool],
                        [Ctrl::NAME => 'cors_enable', Ctrl::LABEL => (('Enable CORS header')), Ctrl::NOTES => (('Cross-Origin Resource Sharing')), Ctrl::OPTIONS => $blankBool],
                        [Ctrl::NAME => 'csp_enable', Ctrl::LABEL => (('Enable CSP header')), Ctrl::NOTES => (('Content Security Policy')), Ctrl::OPTIONS => $blankBool],
                    ],
                ],
            ],
            '/areas/admin/user_security' => [
                Ctrl::CONFIG => [
                    Ctrl::FIELDS => [
                        Ctrl::DEFAULT_FIELD => ['root' => 'modules/FCom_Admin'],
                        [Ctrl::NAME => 'password_strength', Ctrl::LABEL => (('Strong Password Security')), Ctrl::TYPE => 'checkbox'],
                        [Ctrl::NAME => 'password_reset_token_ttl_hr', Ctrl::LABEL => (('Password Reset Token TTL')), Ctrl::NOTES => (('hours, default 24'))],
                        [Ctrl::NAME => 'recaptcha_login', Ctrl::LABEL => (('Recaptcha on Login Form')), Ctrl::TYPE => 'checkbox'],
                        [Ctrl::NAME => 'recaptcha_password_recover', Ctrl::LABEL => (('Recaptcha on Password Recovery Form')), Ctrl::TYPE => 'checkbox'],
                        [Ctrl::NAME => 'recaptcha_g2fa_recover', Ctrl::LABEL => (('Recaptcha on Google 2FA Recovery Form')), Ctrl::TYPE => 'checkbox'],
                    ],
                ],
            ],

            '/areas/cron/area' => [
                Ctrl::CONFIG => [
                    Ctrl::FIELDS => [
                        Ctrl::DEFAULT_FIELD => ['root' => 'modules/FCom_Cron'],
                        [Ctrl::NAME => 'FCom_Cron', Ctrl::LABEL => (('IP: Mode')), Ctrl::TYPE => 'component',
                            'component' => 'sv-comp-form-ip-mode', 'root' => 'mode_by_ip'],
                    ],
                ],
            ],
            '/areas/cron/dispatch' => [
                Ctrl::CONFIG => [
                    Ctrl::FIELDS => [
                        Ctrl::DEFAULT_FIELD => ['root' => 'modules/FCom_Cron'],
                        [Ctrl::NAME => 'leeway_mins', Ctrl::LABEL => (('Leeway Minutes')), Ctrl::INPUT_TYPE => 'number'],
                        [Ctrl::NAME => 'timeout_mins', Ctrl::LABEL => (('Timeout Minutes')), Ctrl::INPUT_TYPE => 'number'],
                        [Ctrl::NAME => 'wait_sec', Ctrl::LABEL => (('Wait Seconds')), Ctrl::INPUT_TYPE => 'number'],
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