<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
* Copyright 2014 Boris Gurvich
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
* http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*
* @package BuckyBall
* @link http://github.com/unirgy/buckyball
* @author Boris Gurvich <boris@sellvana.com>
* @copyright (c) 2010-2014 Boris Gurvich
* @license http://www.apache.org/licenses/LICENSE-2.0.html
*/

/**
* Facility to handle request input
*/
class BRequest extends BClass
{
    /**
    * Route parameters
    *
    * Taken from route, ex:
    * Route: /part1/:param1/part2/:param2
    * Request: /part1/test1/param2/test2
    * $_params: array('param1'=>'test1', 'param2'=>'test2')
    *
    * @var array
    */
    protected $_params = [];

    protected $_postTagsWhitelist = [];

    protected static $_language;

    /**
     * Area of the current request
     *
     * @var string
     */
    protected $_area;

    /**
     * Shortcut to help with IDE autocompletion
     *
     * @param bool  $new
     * @param array $args
     * @return BRequest
     */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    /**
    * On first invokation strip magic quotes in case magic_quotes_gpc = on
    *
    * @return BRequest
    */
    public function __construct()
    {
        $this->stripMagicQuotes();

        if (!empty($_SERVER['ORIG_SCRIPT_NAME'])) {
            $_SERVER['ORIG_SCRIPT_NAME'] = str_replace('/index.php/index.php', '/index.php', $_SERVER['ORIG_SCRIPT_NAME']);
        }
        if (!empty($_SERVER['ORIG_SCRIPT_FILENAME'])) {
            $_SERVER['ORIG_SCRIPT_FILENAME'] = str_replace('/index.php/index.php', '/index.php', $_SERVER['ORIG_SCRIPT_FILENAME']);
        }
    }

    /**
     * Returns area of the current request
     *
     * @var string
     */
    public function area()
    {
        return $this->_area;
    }

    /**
     * Set area of the current request
     *
     * @param string $area
     * @return BRequest
     */
    public function setArea($area)
    {
        $this->_area = $area;
        return $this;
    }

    /**
    * Client remote IP
    *
    * @return string
    */
    public function ip()
    {
        return !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    }

    /**
    * Server local IP
    *
    * @return string
    */
    public function serverIp()
    {
        return !empty($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : null;
    }

    /**
    * Server host name
    *
    * @return string
    */
    public function serverName()
    {
        return !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : null;
    }

    /**
    * Host name from request headers
    *
    * @return string
    */
    public function httpHost($includePort = true)
    {
        if (empty($_SERVER['HTTP_HOST'])) {
            return null;
        }
        if ($includePort) {
            return $_SERVER['HTTP_HOST'];
        }
        $a = explode(':', $_SERVER['HTTP_HOST']);
        return $a[0];
    }

    public function validateHttpHost($whitelist = null)
    {
        if (null === $whitelist) {
            $whitelist = $this->BConfig->get('web/http_host_whitelist');
        }
        if (!$whitelist) {
            return true;
        }
        $httpHost = $this->httpHost(false);

        foreach (explode(',', $whitelist) as $allowedHost) {
            if (preg_match('/(^|\.)' . preg_quote(trim($allowedHost, ' .')) .'$/i', $httpHost)) {
                return true;
            }
        }
        return false;
    }

    /**
    * Port from request headers
    *
    * @return string
    */
    public function httpPort()
    {
        return !empty($_SERVER['HTTP_PORT']) ? $_SERVER['HTTP_PORT'] : null;
    }

    /**
    * Origin host name from request headers
    *
    * @return string
    */
    public function httpOrigin()
    {
        return !empty($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : null;
    }

    /**
    * Whether request is SSL
    *
    * @return bool
    */
    public function https()
    {
        return !empty($_SERVER['HTTPS']);
    }

    /**
    * Server protocol (HTTP/1.0 or HTTP/1.1)
    *
    * @return string
    */
    public function serverProtocol()
    {
        $protocol = "HTTP/1.0";
        if (isset($_SERVER['SERVER_PROTOCOL']) && stripos($_SERVER['SERVER_PROTOCOL'], "HTTP") >= 0) {
            $protocol = $_SERVER['SERVER_PROTOCOL'];
        }
        return $protocol;
    }

    public function scheme()
    {
        return $this->https() ? 'https' : 'http';
    }

    /**
     * Retrive language based on HTTP_ACCEPT_LANGUAGE
     * @return string
     */
    public function acceptLanguage()
    {
        $langs = [];

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // break up string into pieces (languages and q factors)
            $langRegex = '/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i';
            preg_match_all($langRegex , $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);

            if (count($lang_parse[1])) {
                // create a list like "en" => 0.8
                $langs = array_combine($lang_parse[1], $lang_parse[4]);

                // set default to 1 for any without q factor
                foreach ($langs as $lang => $val) {
                    if ($val === '') $langs[$lang] = 1;
                }

                // sort list based on value
                arsort($langs, SORT_NUMERIC);
            }
        }

        //if no language detected return false
        if (empty($langs)) {
            return false;
        }

        list($toplang) = each($langs);
        //return en, de, es, it.... first two characters of language code
        return substr($toplang, 0, 2);
    }

    public function language()
    {
        if (null === static::$_language) {
            $this->rawPath();
            if (null === static::$_language) {
                static::$_language = $this->acceptLanguage();
            }
        }
        return static::$_language;
    }

    /**
    * Whether request is AJAX
    *
    * @return bool
    */
    public function xhr()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
    }

    public function userAgent($pattern = null)
    {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return null;
        }
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        if (null === $pattern) {
            return $userAgent;
        }
        preg_match($pattern, $userAgent, $match);
        return $match;
    }

    /**
    * Request method:
    *
    * @return string GET|POST|HEAD|PUT|DELETE
    */
    public function method()
    {
        return !empty($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    }

    /**
    * Web server document root dir
    *
    * @return string
    */
    public function docRoot()
    {
        return !empty($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']) : null;
    }

    /**
    * Entry point script web path
    *
    * @return string
    */
    public function scriptName()
    {
        return !empty($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) :
            (!empty($_SERVER['ORIG_SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['ORIG_SCRIPT_NAME']) : null);
    }

    /**
    * Entry point script file name
    *
    * @return string
    */
    public function scriptFilename()
    {
        return !empty($_SERVER['SCRIPT_FILENAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']) :
            (!empty($_SERVER['ORIG_SCRIPT_FILENAME']) ? str_replace('\\', '/', $_SERVER['ORIG_SCRIPT_FILENAME']) : null);
    }

    /**
    * Entry point directory name
    *
    * @return string
    */
    public function scriptDir()
    {
        return ($script = $this->scriptFilename()) ? dirname($script) : null;
    }

    protected static $_webRootCache = [];

    /**
    * Web root path for current application
    *
    * If request is /folder1/folder2/index.php, return /folder1/folder2/
    *
    * @param $parent if required a parent of current web root, specify depth
    * @return string
    */
    public function webRoot($parentDepth = 0)
    {
        if (isset(static::$_webRootCache[$parentDepth])) {
            return static::$_webRootCache[$parentDepth];
        }
        $scriptName = $this->scriptName();
        if (empty($scriptName)) {
            return null;
        }
        if (substr($scriptName, -1) !== '/') {
            $scriptName = dirname($scriptName);
        }
        $root = rtrim(str_replace(['//', '\\'], ['/', '/'], $scriptName), '/');

        if ($parentDepth) {
            $arr = explode('/', rtrim($root, '/'));
            $len = sizeof($arr) - $parentDepth;
            $root = $len > 1 ? join('/', array_slice($arr, 0, $len)) : '/';
        }
        if (!$root) {
            $root = '/';
        }
        static::$_webRootCache[$parentDepth] = $root;

        return $root;
    }

    /**
    * Full base URL, including scheme and domain name
    *
    * @todo optional omit http(s):
    * @param null|boolean $forceSecure - if not null, force scheme
    * @param boolean $includeQuery - add origin query string
    * @return string
    */
    public function baseUrl($forceSecure = null, $includeQuery = false)
    {
        if (null === $forceSecure) {
            $scheme = $this->https() ? 'https:' : '';
        } else {
            $scheme = $forceSecure ? 'https:' : '';
        }
        $url = $scheme . '//' . $this->serverName() . $this->webRoot();
        if ($includeQuery && ($query = $this->rawGet())) {
            $url .= '?' . $query;
        }
        return $url;
    }

    /**
    * Full request path, one part or slice of path
    *
    * @param int $offset
    * @param int $length
    * @return string
    */
    public function path($offset, $length = null)
    {
        $pathInfo = $this->rawPath();
        if (empty($pathInfo)) {
            return null;
        }

        $path = explode('/', ltrim($pathInfo, '/'));
        if (null === $length) {
            return isset($path[$offset]) ? $path[$offset] : null;
        }
        return join('/', array_slice($path, $offset, true === $length ? null : $length));
    }

    /**
    * Raw path string
    *
    * @return string
    */
    public function rawPath()
    {
        static $path;

        if (null === $path) {
    #echo "<pre>"; print_r($_SERVER); exit;
            $path = !empty($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] :
                (!empty($_SERVER['ORIG_PATH_INFO']) ? $_SERVER['ORIG_PATH_INFO'] : '/');
                /*
                    (!empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] :
                        (!empty($_SERVER['SERVER_URL']) ? $_SERVER['SERVER_URL'] : '/')
                    )
                );*/

            // nginx rewrite fix
            $basename = basename($this->scriptName());
            $path = preg_replace('#^/.*?' . preg_quote($basename, '#') . '#', '', $path);

            if ($this->BConfig->get('web/language_in_url') && preg_match('#^/([a-z]{2})(/.*|$)#', $path, $match)) {
                static::$_language = $match[1];
                $path = $match[2];
            }

            if (!$path) {
                $path = '/';
            }
        }

        return $path;
    }

    /**
     * PATH_TRANSLATED
     *
     */
    public function pathTranslated()
    {
        return !empty($_SERVER['PATH_TRANSLATED']) ? $_SERVER['PATH_TRANSLATED'] :
            (!empty($_SERVER['ORIG_PATH_TRANSLATED']) ? $_SERVER['ORIG_PATH_TRANSLATED'] : '/');
    }

    /**
    * Request query variables
    *
    * @param string $key
    * @return array|string|null
    */
    public function get($key = null)
    {
        // Encountered this in some nginx + apache environments
        if (empty($_GET) && !empty($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $_GET);
        }
        return null === $key ? $_GET : (isset($_GET[$key]) ? $_GET[$key] : null);
    }

    public function server($key = null)
    {
        $key = strtoupper($key);
        return null === $key ? $_SERVER : (isset($_SERVER[$key]) ? $_SERVER[$key] : null);
    }

    /**
    * Request query as string
    *
    * @return string
    */
    public function rawGet()
    {
        return !empty($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    }

    /**
    * Request POST variables
    *
    * @param string|null $key
    * @return array|string|null
    */
    public function post($key = null)
    {
        return null === $key ? $_POST : (isset($_POST[$key]) ? $_POST[$key] : null);
    }

    /**
    * Request raw POST text
    *
    * @param bool $json Receive request as JSON
    * @param bool $asObject Return as object vs array
    * @return object|array|string
    */
    public function rawPost()
    {
        $post = file_get_contents('php://input');
        return $post;
    }

    /**
    * Request array/object from JSON API call
    *
    * @param boolean $asObject
    * @return mixed
    */
    public function json($asObject = false)
    {
        return $this->BUtil->fromJson(static::rawPost(), $asObject);
    }

    /**
    * Request variable (GET|POST|COOKIE)
    *
    * @param string|null $key
    * @return array|string|null
    */
    public function request($key = null)
    {
        return null === $key ? $_REQUEST : (isset($_REQUEST[$key]) ? $_REQUEST[$key] : null);
    }

    /**
     * Set or retrieve cookie value
     *
     * @param string $name Cookie name
     * @param string $value Cookie value to be set
     * @param int $lifespan Optional lifespan, default from config
     * @param string $path Optional cookie path, default from config
     * @param string $domain Optional cookie domain, default from config
     * @return bool
     */
    public function cookie($name, $value = null, $lifespan = null, $path = null, $domain = null, $secure = null, $httpOnly = null)
    {
        if (null === $value) {
            return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
        }
        if (false === $value) {
            unset($_COOKIE[$name]);
            return $this->cookie($name, '-CLEAR-', -100000);
        }

        $config = $this->BConfig->get('cookie');
        $lifespan = null !== $lifespan ? $lifespan : (!empty($config['timeout']) ? $config['timeout'] : null);
        $path = null !== $path ? $path : (!empty($config['path']) ? $config['path'] : $this->webRoot());
        $domain = null !== $domain ? $domain : (!empty($config['domain']) ? $config['domain'] : $this->httpHost(false));
        $secure = null !== $secure ? $secure : $this->https();
        $httpOnly = null !== $httpOnly ? $httpOnly : true;
        return setcookie($name, $value, time() + $lifespan, $path, $domain, $secure, $httpOnly);
    }

    /**
    * Get request referrer
    *
    * @see http://en.wikipedia.org/wiki/HTTP_referrer#Origin_of_the_term_referer
    * @param string $default default value to use in case there is no referrer available
    * @return string|null
    */
    public function referrer($default = null)
    {
        return !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $default;
    }

    public function receiveFiles($source, $targetDir, $typesRegex = null)
    {
        if (is_string($source)) {
            if (!empty($_FILES[$source])) {
                $source = $_FILES[$source];
            } else {
                //TODO: missing enctype="multipart/form-data" ?
                throw new BException('Missing enctype="multipart/form-data"?');
            }
        }
        if (empty($source)) {
            return;
        }
        $result = [];

        $uploadErrors = [
            UPLOAD_ERR_OK         => "No errors.",
            UPLOAD_ERR_INI_SIZE   => "Larger than upload_max_filesize.",
            UPLOAD_ERR_FORM_SIZE  => "Larger than form MAX_FILE_SIZE.",
            UPLOAD_ERR_PARTIAL    => "Partial upload.",
            UPLOAD_ERR_NO_FILE    => "No file.",
            UPLOAD_ERR_NO_TMP_DIR => "No temporary directory.",
            UPLOAD_ERR_CANT_WRITE => "Can't write to disk.",
            UPLOAD_ERR_EXTENSION  => "File upload stopped by extension."
        ];
        if (is_array($source['error'])) {
            foreach ($source['error'] as $key => $error) {
                if ($error == UPLOAD_ERR_OK) {
                    $tmpName = $source['tmp_name'][$key];
                    $name = $source['name'][$key];
                    $type = $source['type'][$key];
                    if (null !== $typesRegex && !preg_match('#' . $typesRegex . '#i', $type)) {
                        $result[$key] = ['error' => 'invalid_type', 'tp' => 1, 'type' => $type, 'name' => $name];
                        continue;
                    }
                    $this->BUtil->ensureDir($targetDir);
                    move_uploaded_file($tmpName, $targetDir . '/' . $name);
                    $result[$key] = ['name' => $name, 'tp' => 2, 'type' => $type, 'target' => $targetDir . '/' . $name];
                } else {
                    $message = !empty($uploadErrors[$error]) ? $uploadErrors[$error] : null;
                    $result[$key] = ['error' => $error, 'message' => $message, 'tp' => 3];
                }
            }
        } else {
            $error = $source['error'];
            if ($error == UPLOAD_ERR_OK) {
                $tmpName = $source['tmp_name'];
                $name = $source['name'];
                $type = $source['type'];
                if (null !== $typesRegex && !preg_match('#' . $typesRegex . '#i', $type)) {
                    $result[] = ['error' => 'invalid_type', 'tp' => 4, 'type' => $type, 'pattern' => $typesRegex,
                        'source' => $source, 'name' => $name];
                } else {
                    $this->BUtil->ensureDir($targetDir);
                    move_uploaded_file($tmpName, $targetDir . '/' . $name);
                    $result[] = ['name' => $name, 'type' => $type, 'target' => $targetDir . '/' . $name];
                }
            } else {
                $message = !empty($uploadErrors[$error]) ? $uploadErrors[$error] : null;
                $result[] = ['error' => $error, 'message' => $message, 'tp' => 5];
            }
        }
        return $result;
    }

    /**
     * Check whether the request can be CSRF attack
     *
     * Uses HTTP_REFERER header to compare with current host and path.
     * By default only POST, DELETE, PUT requests are protected
     * Only these methods should be used for data manipulation.
     *
     * The following specific cases will return csrf true:
     * - posting from different host or web root path
     * - posting from https to http
     *
     * @see http://en.wikipedia.org/wiki/Cross-site_request_forgery
     *
     * @param string $checkMethod
     * @param mixed $httpMethods
     * @throws BException
     * @return boolean
     */
    public function csrf($checkMethod = null, $httpMethods = null)
    {
        $c = $this->BConfig;
        if (null === $httpMethods) {
            $m = $c->get('web/csrf_http_methods');
        }
        if (!$httpMethods) {
            $httpMethods = ['POST', 'PUT', 'DELETE'];
        } elseif (is_string($httpMethods)) {
            $httpMethods = array_map('trim', explode(',', $httpMethods));
        } elseif (!is_array($httpMethods)) {
            throw new BException('Invalid HTTP Methods argument');
        }
        if (!in_array($this->method(), $httpMethods)) {
            return false; // not one of checked methods, pass
        }

        $whitelist = $c->get('web/csrf_path_whitelist');
        if ($whitelist) {
            $path = $this->rawPath();
            foreach ((array)$whitelist as $pattern) {
                if (preg_match($pattern, $path)) {
                    return false;
                }
            }
        }

        if (null === $checkMethod) {
            $m = $c->get('web/csrf_check_method');
            $checkMethod = $m ? $m : 'token';
        }

        switch ($checkMethod) {
            case 'referrer':
                $ref = $this->referrer();
                if (!$ref) {
                    return true; // no referrer sent, high prob. csrf
                }
                $p = parse_url($ref);
                $p['path'] = preg_replace('#/+#', '/', $p['path']); // ignore duplicate slashes
                $webRoot = $c->get('web/csrf_web_root');
                if (!$webRoot) {
                    $webRoot = $c->get('web/base_src');
                }
                if (!$webRoot) {
                    $webRoot = $this->webRoot();
                }
                if ($p['host'] !== $this->httpHost(false) || $webRoot && strpos($p['path'], $webRoot) !== 0) {
                    return true; // referrer host or doc root path do not match, high prob. csrf
                }
                return false; // not csrf

            case 'origin':
                $origin = $this->httpOrigin();
                if (!$origin) {
                    return true;
                }
                $p = parse_url($origin);
                if ($p['host'] !== $this->httpHost(false)) {
                    return true;
                }
                return false;
                break;

            case 'token':
            case 'token+referrer':
                if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
                    $receivedToken = $_SERVER['HTTP_X_CSRF_TOKEN'];
                } elseif (!empty($_POST['X-CSRF-TOKEN'])) {
                    $receivedToken = $_POST['X-CSRF-TOKEN'];
                }
                return empty($receivedToken) || !$this->BSession->validateCsrfToken($receivedToken);


            default:
                throw new BException('Invalid CSRF check method: ' . $checkMethod);
        }
    }

    /**
     * Verify that HTTP_HOST or HTTP_ORIGIN
     *
     * @param string $method (HOST|ORIGIN|OR|AND)
     * @param string $host
     * @return boolean
     */
    public function verifyOriginHostIp($method = 'OR', $host = null)
    {
        $ip = $this->ip();
        if (!$host) {
            $host = $this->httpHost(false);
        }
        $origin = $this->httpOrigin();
        $hostIPs = gethostbynamel($host);
        $hostMatches = $host && $method != 'ORIGIN' ? in_array($ip, (array)$hostIPs) : false;
        $originIPs = gethostbynamel($origin);
        $originMatches = $origin && $method != 'HOST' ? in_array($ip, (array)$originIPs) : false;
        switch ($method) {
            case 'HOST': return $hostMatches;
            case 'ORIGIN': return $originMatches;
            case 'AND': return $hostMatches && $originMatches;
            case 'OR': return $hostMatches || $originMatches;
        }
        return false;
    }

    /**
    * Get current request URL
    *
    * @return string
    */
    public function currentUrl()
    {
        $host = $this->scheme() . '://' . $this->httpHost(true);
        if ($this->BUrl->hideScriptName() && $this->BRequest->area() !== 'FCom_Admin') {
            $root = $this->webRoot();
        } else {
            $root = $this->scriptName();
        }
        $root = trim($root, '/');
        $path = ltrim($this->rawPath(), '/');
        $get = $this->rawGet();
        $url = $host . '/' . ($root ? $root . '/' : '') . $path . ($get ? '?' . $get : '');
        return $url;
    }

    /**
     * Validate that URL is within boundaries of domain and webroot
     */
    public function isUrlLocal($url, $checkPath = false)
    {
        if (!$url) {
            return null;
        }
        $parsed = parse_url($url);
        if (empty($parsed['host'])) {
            return true;
        }
        if ($parsed['host'] !== $this->httpHost(false)) {
            return false;
        }
        if ($checkPath) {
            $webRoot = $this->BConfig->get('web/root_dir');
            if (!preg_match('#^' . preg_quote($webRoot, '#') . '#', $parsed['path'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Initialize route parameters
     *
     * @param array $params
     * @return $this
     */
    public function initParams(array $params)
    {
        $this->_params = $params;
        return $this;
    }

    /**
    * Return route parameter by name or all parameters as array
    *
    * @param string $key
    * @param boolean $fallbackToGet
    * @return array|string|null
    */
    public function param($key = null, $fallbackToGet = false)
    {
        if (null === $key) {
            return $this->_params;
        } elseif (isset($this->_params[$key]) && '' !== $this->_params[$key]) {
            return $this->_params[$key];
        } elseif ($fallbackToGet && !empty($_GET[$key])) {
            return $_GET[$key];
        } else {
            return null;
        }
    }

    /**
     * Alias for legacy code
     *
     * @deprecated
     * @param mixed $key
     * @param mixed $fallbackToGet
     * @return array|null|string
     */
    public function params($key = null, $fallbackToGet = false)
    {
        return $this->param($key, $fallbackToGet);
    }

    /**
    * Sanitize input and assign default values
    *
    * Syntax: $this->BRequest->sanitize($post, array(
    *   'var1' => 'alnum', // return only alphanumeric components, default null
    *   'var2' => array('trim|ucwords', 'default'), // trim and capitalize, default 'default'
    *   'var3' => array('regex:/[^0-9.]/', '0'), // remove anything not number or .
    * ));
    *
    * @todo replace with filter_var_array
    *
    * @param array|object $data Array to be sanitized
    * @param array $config Configuration for sanitizing
    * @param bool $trim Whether to return only variables specified in config
    * @return array Sanitized result
    */
    public function sanitize($data, $config, $trim = true)
    {
        $data = (array)$data;
        if ($trim) {
            $data = array_intersect_key($data, $config);
        }
        foreach ($data as $k => &$v) {
            $filter = is_array($config[$k]) ? $config[$k][0] : $config[$k];
            $v = $this->sanitizeOne($v, $filter);
        }
        unset($v);
        foreach ($config as $k => $c) {
            if (!isset($data[$k])) {
                $data[$k] = is_array($c) && isset($c[1]) ? $c[1] : null;
            }
        }
        return $data;
    }

    /**
    * Sanitize one variable based on specified filter(s)
    *
    * Filters:
    * - int
    * - positive
    * - float
    * - trim
    * - nohtml
    * - plain
    * - upper
    * - lower
    * - ucwords
    * - ucfirst
    * - urle
    * - urld
    * - alnum
    * - regex
    * - date
    * - datetime
    * - gmdate
    * - gmdatetime
    *
    * @param string $v Value to be sanitized
    * @param array|string $filter Filters as array or string separated by |
    * @return string Sanitized value
    */
    public function sanitizeOne($v, $filter)
    {
        if (is_array($v)) {
            foreach ($v as $k => &$v1) {
                $v1 = $this->sanitizeOne($v1, $filter);
            }
            unset($v1);
            return $v;
        }
        if (!is_array($filter)) {
            $filter = explode('|', $filter);
        }
        foreach ($filter as $f) {
            if (strpos($f, ':')) {
                list($f, $p) = explode(':', $f, 2);
            } else {
                $p = null;
            }
            switch ($f) {
                case 'int': $v = (int)$v; break;
                case 'positive': $v = $v > 0 ? $v : null; break;
                case 'float': $v = (float)$v; break;
                case 'trim': $v = trim($v); break;
                case 'nohtml': $v = htmlentities($v, ENT_QUOTES); break;
                case 'plain': $v = htmlentities($v, ENT_NOQUOTES); break;
                case 'upper': $v = strtoupper($v); break;
                case 'lower': $v = strtolower($v); break;
                case 'ucwords': $v = ucwords($v); break;
                case 'ucfirst': $v = ucfirst($v); break;
                case 'urle': $v = urlencode($v); break;
                case 'urld': $v = urldecode($v); break;
                case 'alnum': $p = !empty($p) ? $p : '_'; $v = preg_replace('#[^a-z0-9' . $p . ']#i', '', $v); break;
                case 'regex': case 'regexp': $v = preg_replace($p, '', $v); break;
                case 'date': $v = date('Y-m-d', strtotime($v)); break;
                case 'datetime': $v = date('Y-m-d H:i:s', strtotime($v)); break;
                case 'gmdate': $v = gmdate('Y-m-d', strtotime($v)); break;
                case 'gmdatetime': $v = gmdate('Y-m-d H:i:s', strtotime($v)); break;
            }
        }
        return $v;
    }

    /**
    * String magic quotes in case magic_quotes_gpc = on
    *
    * @return BRequest
    */
    public function stripMagicQuotes()
    {
        static $alreadyRan = false;
        if (get_magic_quotes_gpc() && !$alreadyRan) {
            $process = [&$_GET, &$_POST, &$_COOKIE, &$_REQUEST];
            while (list($key, $val) = each($process)) {
                foreach ($val as $k => $v) {
                    unset($process[$key][$k]);
                    if (is_array($v)) {
                        $process[$key][stripslashes($k)] = $v;
                        $process[] = &$process[$key][stripslashes($k)];
                    } else {
                        $process[$key][stripslashes($k)] = stripslashes($v);
                    }
                }
            }
            unset($process);
            $alreadyRan = true;
        }
    }

    public function modRewriteEnabled()
    {
        if (function_exists('apache_get_modules')) {
            $modules = apache_get_modules();
            $modRewrite = in_array('mod_rewrite', $modules);
        } else {
            $modRewrite =  strtolower(getenv('HTTP_MOD_REWRITE')) == 'on' ? true : false;
        }
        return $modRewrite;
    }

    public function addRequestFieldsWhitelist($whitelist)
    {
        foreach ((array)$whitelist as $urlPath => $fieldPaths) {
            foreach ($fieldPaths as $fieldPath => $allowTags) {
                if (is_numeric($fieldPath)) {
                    $fieldPath = $allowTags;
                    $allowTags = '*';
                }
                $this->_postTagsWhitelist[$urlPath][$fieldPath] = $allowTags;
            }
        }
        return $this;
    }

    public function stripRequestFieldsTags()
    {
        static $alreadyStripped;
        if ($alreadyStripped) {
            return null;
        }

        mb_internal_encoding('UTF-8');
        if (version_compare(PHP_VERSION, '5.6.0', '<')) {
            // bellow emits deprecated errors on php 5.6
            iconv_set_encoding('input_encoding', 'UTF-8');
            iconv_set_encoding('internal_encoding', 'UTF-8');
            iconv_set_encoding('output_encoding', 'UTF-8');
        }

        $data = ['GET' => & $_GET, 'POST' => & $_POST, 'REQUEST' => & $_REQUEST, 'COOKIE' => & $_COOKIE];
        $urlPath = rtrim($this->rawPath(), '/');
        $this->stripTagsRecursive($data, $urlPath);
        $alreadyStripped = true;
        return $this;
    }

    public function stripTagsRecursive(&$data, $forUrlPath, $curPath = null)
    {
        foreach ($data as $k => &$v) {
            $childPath = null === $curPath ? $k : ($curPath . '/' . $k);
            if (is_array($v)) {
                $this->stripTagsRecursive($v,  $forUrlPath, $childPath);
            } elseif (!empty($v) && !is_numeric($v)) {
                if (!mb_check_encoding($v)) {
                    $v = null;
                } elseif (empty($this->_postTagsWhitelist[$forUrlPath][$childPath])) {
                    $v = strip_tags($v);
                } else {
                    $tags = $this->_postTagsWhitelist[$forUrlPath][$childPath];
                    if ('+' === $tags) {
                        $tags = $this->getAllowedTags();
                    }
                    if ('*' !== $tags) {
                        $v = strip_tags($v, $tags);
                    }
                }
            }
        }
        unset($v);
    }

    public function getAllowedTags()
    {
        $tags = "<a><b><blockquote><code><del><dd><dl><dt><em><h1><i><img><kbd><li><ol><p><pre><s><sup>'
            . '<sub><strong><strike><ul><br><hr>";
        return $tags;
    }
}

/**
* Facility to handle response to client
*/
class BResponse extends BClass
{
    protected static $_httpStatuses = [
        // INFORMATIONAL CODES
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        // SUCCESS CODES
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        // REDIRECTION CODES
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy', // Deprecated
        307 => 'Temporary Redirect',
        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        // SERVER ERROR
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];
    /**
    * Response content MIME type
    *
    * @var string
    */
    protected $_contentType = 'text/html';

    protected $_charset = 'UTF-8';

    protected $_contentPrefix;

    protected $_contentSuffix;

    /**
    * Content to be returned to client
    *
    * @var mixed
    */
    protected $_content;

    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return BResponse
    */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    /**
    * Escape HTML
    *
    * @param string $str
    * @return string
    */
    public function q($str)
    {
        if (null === $str) {
            return '';
        }
        if (!is_scalar($str)) {
            var_dump($str);
            return ' ** ERROR ** ';
        }
        return htmlspecialchars($str);
    }

    /**
    * Alias for $this->BRequest->cookie()
    *
    * @param string $name
    * @param string $value
    * @param int $lifespan
    * @param string $path
    * @param string $domain
    * @return BResponse
    */
    public function cookie($name, $value = null, $lifespan = null, $path = null, $domain = null)
    {
        $this->BRequest->cookie($name, $value, $lifespan, $path, $domain);
        return $this;
    }

    /**
     * Set response content
     *
     * @param mixed $content
     * @return BResponse
     */
    public function set($content)
    {
        $this->_content = $content;
        return $this;
    }

    /**
     * Add content to response
     *
     * @param mixed $content
     * @return BResponse
     */
    public function add($content)
    {
        $this->_content = (array)$this->_content + (array)$content;
        return $this;
    }

    public function header($header, $replace = true)
    {
        if (headers_sent($file, $line)) {
            BDebug::notice("Can't send header: '" . print_r($header, 1) . "', output started in {$file}:{$line}");
            return $this;
        }
        if (is_string($header)) {
            header($header, $replace);
        } elseif (is_array($header)) {
            foreach ($header as $h) {
                header($h, $replace);
            }
        }
        return $this;
    }

    public function setContentType($type)
    {
        $this->_contentType = $type;
        return $this;
    }

    public function getContentType()
    {
        return $this->_contentType;
    }

    public function setContentPrefix($string)
    {
        $this->_contentPrefix = $string;
        return $this;
    }

    public function getContentPrefix()
    {
        return $this->_contentPrefix;
    }

    public function setContentSuffix($string)
    {
        $this->_contentSuffix = $string;
        return $this;
    }

    public function getContentSuffix()
    {
        return $this->_contentSuffix;
    }

    /**
    * Send json data as a response (for json API implementation)
    *
    * Supports JSON-P
    *
    * @param mixed $data
    */
    public function json($data)
    {
        $response = $this->BUtil->toJson($data);
        $callback = $this->BRequest->get('callback');
        if ($callback) {
            $response = $callback . '(' . $response . ')';
        }
        $this->setContentType('application/json')->set($response)->render();
    }

    public function fileContentType($fileName)
    {
        $type = 'application/octet-stream';
        switch (strtolower(pathinfo($fileName, PATHINFO_EXTENSION))) {
            case 'jpeg': case 'jpg': $type = 'image/jpg'; break;
            case 'png': $type = 'image/png'; break;
            case 'gif': $type = 'image/gif'; break;
        }
        return $type;
    }

    /**
     * Send file download to client
     *
     * @param        $source
     * @param null   $fileName
     * @param string $disposition
     * @internal param string $filename
     * @return exit
     */
    public function sendFile($source, $fileName = null, $disposition = 'attachment')
    {
        $this->BSession->close();

        if (!file_exists($source)) {
            $this->status(404, 'File not found', 'File not found');
        }

        if (!$fileName) {
            $fileName = basename($source);
        }

        $this->header([
            'Pragma: public',
            'Cache-Control: must-revalidate, post-check=0, pre-check=0',
            'Content-Length: ' . filesize($source),
            'Last-Modified: ' . date('r'),
            'Content-Type: ' . $this->fileContentType($fileName),
            'Content-Disposition: ' . $disposition . '; filename=' . $fileName,
        ]);

        //echo file_get_contents($source);
        $fs = fopen($source, 'rb');
        $fd = fopen('php://output', 'wb');
        while (!feof($fs)) fwrite($fd, fread($fs, 8192));
        fclose($fs);
        fclose($fd);

        $this->shutdown(__METHOD__);
    }

    /**
     * Send text content as a file download to client
     *
     * @param string $content
     * @param string $fileName
     * @param string $disposition
     * @return exit
     */
    public function sendContent($content, $fileName = 'download.txt', $disposition = 'attachment')
    {
        $this->BSession->close();

        $this->header([
            'Pragma: public',
            'Cache-Control: must-revalidate, post-check=0, pre-check=0',
            'Content-Type: ' . $this->fileContentType($fileName),
            'Content-Length: ' . strlen($content),
            'Last-Modified: ' . date('r'),
            'Content-Disposition: ' . $disposition . '; filename=' . $fileName,
        ]);
        echo $content;
        $this->shutdown(__METHOD__);
    }

    /**
    * Send status response to client
    *
    * @param int $status Status code number
    * @param string $message Message to be sent to client
    * @param bool|string $output Proceed to output content and exit
    * @return BResponse|exit
    */
    public function status($status, $message = null, $output = true)
    {
        if (null === $message) {
            if (!empty(static::$_httpStatuses[$status])) {
                $message = static::$_httpStatuses[$status];
            } else {
                $message = 'Unknown';
            }
        }
        $protocol = $this->BRequest->serverProtocol();

        $this->header([
            "{$protocol} {$status} {$message}",
            "Status: {$status} {$message}",
        ]);

        if (is_string($output)) {
            echo $output;
            exit;
        } elseif ($output) {
            $this->output();
        }
        return $this;
    }

    /**
    * Output the response to client
    *
    * @param string $type Optional content type
    * @return exit
    */
    public function output($type = null)
    {
        if (null !== $type) {
            $this->setContentType($type);
        }
        //$this->BSession->close();
        $headers = ['Content-Type: ' . $this->_contentType . '; charset=' . $this->_charset];

        foreach ((array)$this->BConfig->get('web/headers') as $header => $content) {
            $headers[] = $header . ': ' . $content;
            //header('X-Frame-Options: SAMEORIGIN');
            //header('X-UA-Compatible: IE=edge');
        }
        $this->header($headers);

        if ($this->_contentType == 'application/json') {
            if (!empty($this->_content)) {
                $this->_content = is_string($this->_content) ? $this->_content : $this->BUtil->toJson($this->_content);
            }
        } elseif (null === $this->_content) {
            $this->_content = $this->BLayout->render();
        }
        $this->BEvents->fire(__METHOD__ . ':before', ['content' => &$this->_content]);

        if ($this->_contentPrefix) {
            echo $this->_contentPrefix;
        }
        if ($this->_content) {
            echo $this->_content;
        }
        if ($this->_contentSuffix) {
            echo $this->_contentSuffix;
        }

        $this->BEvents->fire(__METHOD__ . ':after', ['content' => $this->_content]);

        $this->shutdown(__METHOD__);
    }

    /**
    * Alias for output
    *
    */
    public function render()
    {
        $this->output();
    }

    /**
    * Redirect browser to another URL
    *
    * @param string $url URL to redirect
    * @param int $status Default 302 (temporary), another possible value 301 (permanent)
    */
    public function redirect($url, $status = 302)
    {
        $this->BSession->close();
        $this->status($status, null, false);
        if (true === $url) {
            $referrer = $this->BRequest->referrer();
            $url = $referrer ? $referrer : $this->BRequest->currentUrl();
        } elseif (!$this->BUtil->isUrlFull($url)) {
            $url = $this->BApp->href($url);
        }
        header("Location: {$url}", null, $status);
        $this->shutdown(__METHOD__);
    }

    public function httpsRedirect()
    {
        $this->redirect(str_replace('http://', 'https://', $this->BRequest->currentUrl()));
    }

    /**
    * Send HTTP STS header
    *
    * @see http://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security
    */
    public function httpSTS()
    {
        $this->header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        return $this;
    }

    /**
    * Enable CORS (Cross-Origin Resource Sharing)
    *
    * @param array $options
    * @return BResponse
    */
    public function cors($options = [])
    {
        if (empty($options['origin'])) {
            $options['origin'] = $this->BRequest->httpOrigin();
        }
        $headers = ['Access-Control-Allow-Origin: ' . $options['origin']];
        if (!empty($options['methods'])) {
            $headers[] = 'Access-Control-Allow-Methods: ' . $options['methods'];
        }
        if (!empty($options['credentials'])) {
            $headers[] = 'Access-Control-Allow-Credentials: true';
        }
        if (!empty($options['headers'])) {
            $headers[] = 'Access-Control-Allow-Headers: ' . $options['headers'];
        }
        if (!empty($options['expose-headers'])) {
            $headers[] = 'Access-Control-Expose-Headers: ' . $options['expose-headers'];
        }
        if (!empty($options['age'])) {
            $headers[] = 'Access-Control-Max-Age: ' . $options['age'];
        }
        $this->header($headers);
        return $this;
    }

    public function nocache()
    {
        $this->header([
            "Expires: Sat, 26 Jul 1997 05:00:00 GMT", // Date in the past
            "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT", // Current time
            "Cache-Control: no-cache, must-revalidate", // HTTP/1.1
            "Pragma: no-cache",
        ]);
        return $this;
    }

    public function startLongResponse($bypassBuffering = true)
    {
        // improve performance by not processing debug log
        if ($this->BDebug->is('DEBUG')) {
            BDebug::mode('DEVELOPMENT');
        }
        // redundancy: avoid memory leakage from debug log
        BDebug::level(BDebug::MEMORY, false);
        // turn off in-memory SQL log
        $this->BConfig->set('db/logging', 0);
        // remove process timeout limitation
        set_time_limit(0);
        // output in real time
        @ob_end_flush();
        ob_implicit_flush();
        // enable garbage collection
        gc_enable();
        // remove session lock
        session_write_close();
        // bypass initial webservice buffering
        if ($bypassBuffering) {
            echo str_pad('', 2000, ' ');
        }
        // continue in background if the browser request was interrupted
        //ignore_user_abort(true);
        return $this;
    }

    public function shutdown($lastMethod = null)
    {
        $this->BEvents->fire(__METHOD__, ['last_method' => $lastMethod]);
        $this->BSession->close();
        $this->BRouting->stop();
        //exit;
    }
}

/**
* Front controller class to register and dispatch routes
*/
class BRouting extends BClass
{
    /**
    * Array of routes
    *
    * @var array
    */
    protected $_routes = [];

    protected $_routesRegex = [];

    /**
    * Partial route changes
    *
    * @var array
    */
    protected static $_routeChanges = [];

    /**
    * Current route node, empty if front controller dispatch wasn't run yet
    *
    * @var mixed
    */
    protected $_currentRoute;

    /**
    * Templates to generate URLs based on routes
    *
    * @var array
    */
    protected $_urlTemplates = [];

    /**
    * Current controller name
    *
    * @var string
    */
    protected $_controllerName;

    /**
     * Exit dispatch loop
     *
     * @var boolean
     */
    protected $_stop = false;

    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return BRouting
    */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    public function __construct()
    {
        $this->route('_ /noroute', 'BActionController.noroute', [], null, false);
    }

    /**
    * Change route part (usually 1st)
    *
    * @param string $partValue
    * @param mixed $options
    */
    public function changeRoute($from, $opt)
    {
        if (!is_array($opt)) {
            $opt = ['to' => $opt];
        }
        $type = !empty($opt['type']) ? $opt['type'] : 'first';
        unset($opt['type']);
        $this->_routeChanges[$type][$from] = $opt;
        return $this;
    }

    public function processHref($href)
    {
        $href = ltrim($href, '/');
        if (!empty(static::$_routeChanges['first'])) {
            $rules = static::$_routeChanges['first'];
            $parts = explode('/', $href, 2);
            if (!empty($rules[$parts[0]])) {
                $href = ($part0 = $rules[$parts[0]]['to'])
                    . ($part0 && isset($parts[1]) ? '/' : '')
                    . (isset($parts[1]) ? $parts[1] : '');
            }
        }
        return $href;
    }

    public function processRoutePath($route, $args = [])
    {
        if (!empty($args['module_name'])) {
            $module = $this->BModuleRegistry->module($args['module_name']);
            if ($module && ($prefix = $module->url_prefix)) {
                $route = $prefix . $route;
            }
        }
        #$route = $this->processHref($route); // slower than alternative (replacing keys on saveRoute)
        return $route;
    }

    /**
     * Declare route
     *
     * @param string $route
     *   - "{GET|POST|DELETE|PUT|HEAD} /part1/part2/:param1"
     *   - "/prefix/*anything"
     *   - "/prefix/.action" : $args=array('_methods'=>array('create'=>'POST', ...))
     * @param mixed  $callback PHP callback
     * @param array  $args Route arguments
     * @param string $name optional name for the route for URL templating
     * @param bool   $multiple
     * @return BFrontController for chain linking
     */
    public function route($route, $callback = null, $args = null, $name = null, $multiple = true)
    {
        if (is_array($route)) {
            foreach ($route as $a) {
                if (null === $callback) {
                    $this->route($a[0], $a[1], isset($a[2]) ? $a[2] : null, isset($a[3]) ? $a[3] : null);
                } else {
                    $this->route($a, $callback, $args, $name, $multiple);
                }
            }
            return $this;
        }
        if (empty($args['module_name'])) {
            $args['module_name'] = $this->BModuleRegistry->currentModuleName();
        }
        BDebug::debug('ROUTE ' . $route);
        if (empty($this->_routes[$route])) {
            $this->_routes[$route] = new BRouteNode(['route_name' => $route]);
        }

        $this->_routes[$route]->observe($callback, $args, $multiple);

        if (null !== $name) {
            $this->_urlTemplates[$name] = $route;
        }
        return $this;
    }

    public function removeRoute($route, $callback = null)
    {
        if (null === $callback) {
            unset($this->_routes[$route]);
            BDebug::debug('REMOVE ROUTE ' . $route);
        } else {
            if (!empty($this->_routes[$route])) {
                $this->_routes[$route]->removeObserver($callback);
                BDebug::debug('REMOVE ROUTE CALLBACK ' . $route . ' : ' . print_r($callback, 1));
            }
        }
        return $this;
    }

    /**
     * Shortcut to $this->route() for GET http verb
     *
     * @deprecated
     * @param mixed  $route
     * @param mixed  $callback
     * @param array  $args
     * @param string $name
     * @param bool   $multiple
     * @return BFrontController
     */
    public function get($route, $callback = null, $args = null, $name = null, $multiple = true)
    {
        return $this->_route($route, 'get', $callback, $args, $name, $multiple);
    }

    /**
     * Shortcut to $this->route() for POST http verb
     *
     * @deprecated
     * @param mixed  $route
     * @param mixed  $callback
     * @param array  $args
     * @param string $name
     * @param bool   $multiple
     * @return BFrontController
     */
    public function post($route, $callback = null, $args = null, $name = null, $multiple = true)
    {
        return $this->_route($route, 'post', $callback, $args, $name, $multiple);
    }

    /**
     * Shortcut to $this->route() for PUT http verb
     *
     * @deprecated
     * @param mixed $route
     * @param null  $callback
     * @param null  $args
     * @param null  $name
     * @param bool  $multiple
     * @return $this|BFrontController
     */
    public function put($route, $callback = null, $args = null, $name = null, $multiple = true)
    {
        return $this->_route($route, 'put', $callback, $args, $name, $multiple);
    }

    /**
     * Shortcut to $this->route() for GET|POST|DELETE|PUT|HEAD http verbs
     *
     * @deprecated
     * @param mixed $route
     * @param null  $callback
     * @param null  $args
     * @param null  $name
     * @param bool  $multiple
     * @return $this|BFrontController
     */
    public function any($route, $callback = null, $args = null, $name = null, $multiple = true)
    {
        return $this->_route($route, 'any', $callback, $args, $name, $multiple);
    }

    /**
     * Process shortcut methods
     *
     * @deprecated
     * @param mixed  $route
     * @param string $verb
     * @param null   $callback
     * @param null   $args
     * @param null   $name
     * @param bool   $multiple
     * @return $this|BFrontController
     */
    protected function _route($route, $verb, $callback = null, $args = null, $name = null, $multiple = true)
    {
        if (is_array($route)) {
            foreach ($route as $a) {
                if (null === $callback) {
                    $this->_route($a[0], $verb, $a[1], isset($a[2]) ? $a[2] : null, isset($a[3]) ? $a[3] : null);
                } else {
                    $this->any($a, $verb, $callback, $args);
                }
            }
            return $this;
        }
        $verb = strtoupper($verb);
        $isRegex = false;
        if ($route[0] === '^') {
            $isRegex = true;
            $route = substr($route, 1);
        }
        if ($verb === 'GET' || $verb === 'POST' || $verb === 'PUT') {
            $route = $verb . ' ' . $route;
        } else {
            if ($isRegex) {
                $route = '(GET|POST|DELETE|PUT|HEAD) ' . $route;
            } else {
                $route = 'GET|POST|DELETE|PUT|HEAD ' . $route;
            }
        }
        if ($isRegex) {
            $route = '^' . $route;
        }

        return $this->route($route, $callback, $args, $name, $multiple);
    }

    public function findRoute($requestRoute = null)
    {
        if (null === $requestRoute) {
            $requestRoute = $this->BRequest->rawPath();
        }

        // try first new route syntax, without method included
        if (!empty($this->_routes[$requestRoute]) && $this->_routes[$requestRoute]->validObserver()) {
            BDebug::debug('DIRECT ROUTE: ' . $requestRoute);
            return $this->_routes[$requestRoute];
        }

        if (strpos($requestRoute, ' ') === false) {
            $requestRoute = $this->BRequest->method() . ' ' . $requestRoute;
        }

        if (!empty($this->_routes[$requestRoute]) && $this->_routes[$requestRoute]->validObserver()) {
            BDebug::debug('DIRECT ROUTE: ' . $requestRoute);
            return $this->_routes[$requestRoute];
        }

        BDebug::debug('FIND ROUTE: ' . $requestRoute);
        foreach ($this->_routes as $routeName => $route) {
            if ($route->match($requestRoute)) {
                return $route;
            }
        }
        return null;
    }

    /**
    * Sort collected routes by specificity
    *
    * @return BFrontController
    */
    public function processRoutes()
    {
        uasort($this->_routes, function($a, $b) {
            $a1 = $a->num_parts;
            $b1 = $b->num_parts;
            $res = $a1 < $b1 ? 1 : ($a1 > $b1 ? -1 : 0);
            if ($res != 0) {
#echo ' ** ('.$a->route_name.'):('.$b->route_name.'): '.$res.' ** <br>';
                return $res;
            }
            $ap = (strpos($a->route_name, '/*') !== false ? 10 : 0)
                + (strpos($a->route_name, '/.') !== false ? 5 : 0)
                + (strpos($a->route_name, '/:') !== false ? 1 : 0);
            $bp = (strpos($b->route_name, '/*') !== false ? 10 : 0)
                + (strpos($b->route_name, '/.') !== false ? 5 : 0)
                + (strpos($b->route_name, '/:') !== false ? 1 : 0);
#echo $a->route_name.' ('.$ap.'), '.$b->route_name.'('.$bp.')<br>';
            return $ap === $bp ? 0 : ($ap < $bp ? -1 : 1);
        });
#echo "<pre>"; print_r($this->_routes); echo "</pre>";
        return $this;
    }

    public function forward($from, $to, $args = [])
    {
        $args['target'] = $to;
        $this->route($from, [$this, '_forwardCallback'], $args);
        /*
        $this->route($from, function($args) {
            return array('forward'=>$this->processRoutePath($args['target'], $args));
        }, $args);
        */
        return $this;
    }

    protected function _forwardCallback($args)
    {
        return $this->processRoutePath($args['target'], $args);
    }

    public function redirect($from, $to, $args = [])
    {
        $args['target'] = $to;
        $this->route($from, [$this, 'redirectCallback'], $args);
        return $this;
    }

    public function redirectCallback($args)
    {
        $this->BResponse->redirect($args['target']);
    }

    /**
    * Retrieve current route node
    *
    */
    public function currentRoute()
    {
        return $this->_currentRoute;
    }

    /**
    * Dispatch current route
    *
    * @param string $requestRoute optional route for explicit route dispatch
    * @return BFrontController
    */
    public function dispatch($requestRoute = null)
    {
        $this->BEvents->fire(__METHOD__ . ':before');

        $this->processRoutes();

        $attempts = 0;
        $forward = false; // null: no forward, false: try next route, true: exit loop, array: forward without new route
#echo "<pre>"; print_r($this->_routes); exit;
        while (($attempts++ < 100) && (false === $forward || is_array($forward))) {
            $route = $this->findRoute($requestRoute);
#echo "<pre>"; print_r($route); echo "</pre>";
            if (!$route) {
                $route = $this->findRoute('_ /noroute');
            }
            $this->_currentRoute = $route;

            $forward = $route->dispatch();
            if ($this->_stop) {
                return $this;
            }
#var_dump($forward); exit;
            if (is_array($forward)) {
                list($actionName, $forwardCtrlName, $params) = $forward;
                $controllerName = $forwardCtrlName ? $forwardCtrlName : $route->controller_name;
                $requestRoute = '_ /forward';
                $this->route($requestRoute, $controllerName . '.' . $actionName, ['params' => $params], null, false);
            }
        }

        if ($attempts >= 100) {
            echo "<pre>"; print_r($route); echo "</pre>";
            BDebug::error($this->BLocale->_('BFrontController: Reached 100 route iterations: %s', print_r($route, 1)));
        }
    }

    public function stop($flag = true)
    {
        $this->_stop = $flag;
        return $this;
    }

    public function isStopped()
    {
        return $this->_stop;
    }

    public function debug()
    {
        echo "<pre>"; print_r($this->_routes); echo "</pre>";
    }
}

/**
* Controller Route Node
*/
class BRouteNode extends BClass
{
    /**
    * Route flags
    *
    * @var array
    */
    protected $_flags = [];

    /**
    * Route Observers
    *
    * @var array(BRouteObserver)
    */
    protected $_observers = [];

    public $controller_name;
    public $action_idx;
    public $action_name;
    public $route_name;
    public $regex;
    public $num_parts; // specificity for sorting
    public $params;
    public $params_values;
    public $multi_method;

    public function __construct($args = [])
    {
        foreach ($args as $k => $v) {
            $this->{$k} = $v;
        }

        // convert route name into regex and save param references
        if ($this->route_name[0] === '^') {
            $this->regex = '#' . $this->route_name . '#';
            return;
        }
        $a = explode(' ', $this->route_name);
        if (sizeof($a) < 2) {
            $a = [
                'GET|POST|DELETE|PUT|HEAD',
                $a[0],
            ];
            $this->multi_method = true;
        } else {
            $this->multi_method = strpos($a[0], '|') !== false;
        }
        if ($a[1] === '/') {
            $this->regex = '#^(' . $a[0] . ') (/)$#';
        } else {
            $a1 = explode('/', trim($a[1], '/'));
            $this->num_parts = sizeof($a1);
            $paramId = 2;
            foreach ($a1 as $i => $k) {
                $k0 = $k[0];
                $part = '';
                if ($k0 === '?') {
                    $k = substr($k, 1);
                    $k0 = $k[0];
                    $part = '?';
                }
                if ($k0 === ':') { // optional param
                    $this->params[++$paramId] = substr($k, 1);
                    $part .= '([^/]*)';
                } elseif ($k0 === '!') { // required param
                    $this->params[++$paramId] = substr($k, 1);
                    $part .= '([^/]+)';
                } elseif ($k0 === '*') { // param until end of url
                    $this->params[++$paramId] = substr($k, 1);
                    $part .= '(.*)';
                } elseif ($k0 === '.') { // dynamic action
                    $this->params[++$paramId] = substr($k, 1);
                    $this->action_idx = $paramId;
                    $part .= '([a-zA-Z0-9_]*)';
                } else {
                    //$part .= preg_quote($a1[$i]);
                }
                if ('' !== $part) {
                    $a1[$i] = $part;
                }
            }
            $this->regex = '#^(' . $a[0] . ') (/' . join('/', $a1) . '/?)$#'; // #...#i option?
#echo $this->regex.'<hr>';
        }
    }

    public function match($route)
    {
        if (!preg_match($this->regex, $route, $match)) {
            return false;
        }
        if (!$this->validObserver()) {
            return false;
        }
        if ($this->action_idx) {
            $this->action_name = !empty($match[$this->action_idx]) ? $match[$this->action_idx] : 'index';
        }
        if ($this->route_name[0] === '^') {
            $this->params_values = $match;
        } elseif ($this->params) {
            $this->params_values = [];
            foreach ($this->params as $i => $p) {
                $this->params_values[$p] = $match[$i];
            }
        }
        return true;
    }

    /**
    * Set route flag
    *
    * - ? - allow trailing slash
    *
    * @todo make use of it
    *
    * @param string $flag
    * @param mixed $value
    * @return BRouteNode
    */
    public function flag($flag, $value = true)
    {
        $this->_flags[$flag] = $value;
        return $this;
    }

    /**
    * Add an observer to the route node
    *
    * @param mixed $callback
    * @param array $args
    * @param boolean $multiple whether to allow multiple observers for the route
    */
    public function observe($callback, $args = null, $multiple = true)
    {
        $observer = new BRouteObserver([
            'callback' => $callback,
            'args' => $args,
            'route_node' => $this,
        ]);
        if ($multiple) {
            $this->_observers[] = $observer;
        } else {
            //$this->_observers = $this->BUtil->arrayMerge($this->_observers[0], $observer);
            $this->_observers = [$observer];
        }
        return $this;
    }

    /**
    * Retrieve next valid (not skipped) observer
    *
    * @return BRouteObserver
    */
    public function validObserver()
    {
        foreach ($this->_observers as $o) {
            if (!$o->skip) return $o;
        }
        return null;
    }

    public function removeObserver($callback)
    {
        foreach ($this->_observers as $i => $o) {
            if ($o->callback == $callback) {
                unset($this->_observers[$i]);
            }
        }
        return $this;
    }

    /**
    * Try to dispatch valid observers
    *
    * Will try to call observers in this node in order of save
    *
    * @return array|boolean forward info
    */
    public function dispatch()
    {
        $attempts = 0;
        $observer = $this->validObserver();
        while ((++$attempts < 100) && $observer) {

            $forward = $observer->dispatch();
            if (is_array($forward)) {
                return $forward;
            } elseif ($forward === false) {
                $observer->skip = true;
                $observer = $this->validObserver();
            } else {
                return null;
            }
        }
        if ($attempts >= 100) {
            BDebug::error($this->BLocale->_('BRouteNode: Reached 100 route iterations: %s', print_r($observer, 1)));
        }
        return false;
    }

    public function __destruct()
    {
        unset($this->_observers, $this->_children, $this->_match);
    }
}

/**
* Controller route observer
*/
class BRouteObserver extends BClass
{
    /**
    * Observer callback
    *
    * @var mixed
    */
    public $callback;

    /**
    * Callback arguments
    *
    * @var array
    */
    public $args;

    /**
    * Whether to skip the route when trying another
    *
    * @var boolean
    */
    public $skip;

    /**
    * Parent route node
    *
    * @var BRouteNode
    */
    public $route_node;

    public function __construct($args)
    {
        foreach ($args as $k => $v) {
            $this->{$k} = $v;
        }
    }

    /**
    * Dispatch route node callback
    *
    * @return forward info
    */
    public function dispatch()
    {
        $this->BModuleRegistry->currentModule(!empty($this->args['module_name']) ? $this->args['module_name'] : null);

        $node = $this->route_node;
        $params = (array)$node->params_values;
        if (!empty($this->args['params'])) {
            $params = array_merge_recursive($params, $this->args['params']);
        }
        $this->BRequest->initParams($params);
        if (is_string($this->callback) && $node->action_name) {
            // prevent envoking action_index__POST methods directly
            $actionNameArr = explode('__', $node->action_name, 2);
            $this->callback .= '.' . $actionNameArr[0];
        }
        if (is_callable($this->callback)) {
            return call_user_func($this->callback, $this->args);
        }
        if (is_string($this->callback)) {
            foreach (['.', '->'] as $sep) {
                $r = explode($sep, $this->callback);
                if (sizeof($r) == 2) {
                    $this->callback = $r;
                    break;
                }
            }
        }

        $actionName = '';
        $controllerName = '';
        if (is_array($this->callback)) {
            $controllerName = $this->callback[0];
            $node->controller_name = $controllerName;
            $actionName = $this->callback[1];
        }
#var_dump($controllerName, $actionName);
        /** @var BActionController */
        $controller = BClassRegistry::instance($controllerName, [], true);

        return $controller->dispatch($actionName, $this->args);
    }

    public function __destruct()
    {
        unset($this->route_node, $this->callback, $this->args, $this->params);
    }
}

/**
* Action controller class for route action declarations
*/
class BActionController extends BClass
{
    /**
    * Current action name
    *
    * @var string
    */
    protected $_action;

    /**
    * Forward location. If set the dispatch will loop and forward to next action
    *
    * @var string|null
    */
    protected $_forward;

    /**
    * Prefix for action methods
    *
    * @var string
    */
    protected $_actionMethodPrefix = 'action_';

    public function __construct()
    {

    }

    /**
    * Shortcut for fetching layout views
    *
    * @param string $viewname
    * @return BView
    */
    public function view($viewname)
    {
        return $this->BLayout->view($viewname);
    }

    /**
    * Dispatch action within the action controller class
    *
    * @param string $actionName
    * @param array $args Action arguments
    * @return mixed forward information
    */
    public function dispatch($actionName, $args = [])
    {
        $this->_action = $actionName;
        $this->_forward = null;

        if (!$this->beforeDispatch($args)) {
            return false;
        }
        if (null !== $this->_forward) {
            return $this->_forward;
        }
        if ($actionName !== 'unauthenticated') {
            $authenticated = $this->authenticate($args);
            if (!$authenticated) {
                $this->forward('unauthenticated');
                return $this->_forward;
            }

            if ($actionName !== 'unauthorized' && !$this->authorize($args)) {
                $this->forward('unauthorized');
                return $this->_forward;
            }
        } else {
            $authenticated = true;
        }

        $this->tryDispatch($actionName, $args);

        if (null === $this->_forward && !$this->BRouting->isStopped()) {
            $this->afterDispatch($args);
        }
        return $this->_forward;
    }

    /**
     * Try to dispatch action and catch exception if any
     *
     * @param string $actionName
     * @param array $args
     * @return $this
     */
    public function tryDispatch($actionName, $args)
    {
        if (!is_string($actionName) && is_callable($actionName)) {
            try {
                call_user_func($actionName);
            } catch (Exception $e) {
                BDebug::exceptionHandler($e);
                $this->sendError($e->getMessage());
            }
            return $this;
        }
        $actionMethod = $this->_actionMethodPrefix . $actionName;
        $reqMethod = $this->BRequest->method();
        if ($reqMethod !== 'GET') {
            $tmpMethod = $actionMethod . '__' . $reqMethod;
            if (method_exists($this, $tmpMethod)) {
                $actionMethod = $tmpMethod;
            } elseif ($this->BRouting->currentRoute()->multi_method) {
                $this->forward(false); // If route has multiple methods, require method suffix
                return $this;
            }
        }
        //echo $actionMethod;exit;
        if (!method_exists($this, $actionMethod)) {
            $this->forward(false);
            return $this;
        }

        $this->BRequest->stripRequestFieldsTags();

        // try {
            $this->{$actionMethod}($args);
        // } catch (Exception $e) {
            //BDebug::exceptionHandler($e);
            // $this->sendError($e->getMessage());
        // }
        return $this;
    }

    /**
    * Forward to another action or retrieve current forward
    *
    * @param string $actionName
    * @param string $controllerName
    * @param array $params
    * @return string|null|BActionController
    */
    public function forward($actionName = null, $controllerName = null, array $params = [])
    {
        if (false === $actionName) {
            $this->_forward = false;
        } else {
            $this->_forward = [$actionName, $controllerName, $params];
        }
        return $this;
    }

    public function getForward()
    {
        return $this->_forward;
    }

    /**
    * Authenticate logic for current action controller, based on arguments
    *
    * Use $this->_action to fetch current action
    *
    * @param array $args
    */
    public function authenticate($args = [])
    {
        return true;
    }

    /**
    * Authorize logic for current action controller, based on arguments
    *
    * Use $this->_action to fetch current action
    *
    * @param array $args
    */
    public function authorize($args = [])
    {
        return true;
    }

    /**
    * Execute before dispatch and return resutl
    * If false, do not dispatch action, and either forward or default
    *
    * @return bool
    */
    public function beforeDispatch()
    {
        $this->BEvents->fire(__METHOD__); // general beforeDispatch event for all controller
        $className = static::$_origClass ? static::$_origClass : get_class($this);
        $args = ['action' => $this->_action, 'controller' => $this];
        $this->BEvents->fire($className . '::beforeDispatch', $args); // specific controller instance
        return true;
    }

    /**
    * Execute after dispatch
    *
    */
    public function afterDispatch()
    {
        $this->BEvents->fire(__METHOD__); // general afterDispatch event for all controller
        $className = static::$_origClass ? static::$_origClass : get_class($this);
        $args = ['action' => $this->_action, 'controller' => $this];
        $this->BEvents->fire($className . '::afterDispatch', $args); // specific controller instance
    }

    /**
    * Send error to the browser
    *
    * @param string $message to be in response
    * @return exit
    */
    public function sendError($message)
    {
        $this->BResponse->set($message)->status(503);
    }

    /**
    * Default unauthorized action
    *
    */
    public function action_unauthenticated()
    {
        $this->BResponse->set("Unauthenticated")->status(401);
    }

    /**
    * Default unauthorized action
    *
    */
    public function action_unauthorized()
    {
        $this->BResponse->set("Unauthorized")->status(403);
    }

    /**
    * Default not found action
    *
    */
    public function action_noroute()
    {
        $this->BResponse->set("Route not found")->status(404);
    }

    /**
    * Render output
    *
    * Final method to be called in standard action method
    */
    public function renderOutput()
    {
        $this->BResponse->output();
    }

    public function getAction()
    {
        return $this->_action;
    }

    public function getController()
    {
        return self::origClass();
    }

    public function viewProxy($viewPrefix, $defaultView = 'index', $hookName = 'main', $baseLayout = null)
    {
        $layout = $this->BLayout;
        $viewPrefix = trim($viewPrefix, '/') . '/';
        $page = $this->BRequest->param('view');
        if (!$page) {
            $page = $defaultView;
        }

        $theme = $this->BConfig->get('modules/' . $this->BRequest->area() . '/theme');
        if (!$theme) {
            $theme = $this->BLayout->getDefaultTheme();
        }
        if ($theme) {
            $layout->loadThemeViews($theme);
        }
        $view = $this->view($viewPrefix . $page);
        if ($view instanceof BViewEmpty) {
            $this->forward(false);
            return false;
        }

        if ($theme) {
            $layout->applyTheme($theme);
        }
        if ($baseLayout) {
            $layout->applyLayout($baseLayout);
        }

        $this->BLayout->applyLayout('view-proxy')->applyLayout($viewPrefix . $page);
        $view->useMetaData();

        if (($root = $this->BLayout->view('root'))) {
            $root->addBodyClass('page-' . $page);
        }

        $this->BLayout->hookView($hookName, $viewPrefix . $page);

        if (!empty($metaData['http_status'])) {
            $this->BResponse->status($metaData['http_status']);
        }

        return $page;
    }

    /**
    * Translate string within controller action
    *
    * @param string $string
    * @param array $params
    * @param string $module if null, try to get current controller module
    */
    public function _($string, $params = [], $module = null)
    {
        if (empty($module)) {
            $module = $this->BModuleRegistry->currentModuleName();
        }
        return $this->BLocale->_($string, $params, $module);
    }
}
