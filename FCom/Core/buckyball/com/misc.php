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
* Utility class to parse and construct strings and data structures
*/
class BUtil extends BClass
{
    /**
    * IV for mcrypt operations
    *
    * @var string
    */
    protected static $_mcryptIV;

    /**
    * Encryption key from configuration (encrypt/key)
    *
    * @var string
    */
    protected static $_mcryptKey;

    /**
    * Default hash algorithm
    *
    * @var string default sha512 for strength and slowness
    */
    protected static $_hashAlgo = 'bcrypt';

    /**
    * Default number of hash iterations
    *
    * @var int
    */
    protected static $_hashIter = 3;

    /**
    * Default full hash string separator
    *
    * @var string
    */
    protected static $_hashSep = '$';

    /**
    * Default character pool for random and sequence strings
    *
    * Chars "c", "C" are ommited to avoid accidental obscene language
    * Chars "0", "1", "I" are removed to avoid leading 0 and ambiguity in print
    *
    * @var string
    */
    protected static $_defaultCharPool = '23456789abdefghijklmnopqrstuvwxyzABDEFGHJKLMNOPQRSTUVWXYZ';

    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return BUtil
    */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    /**
    * Convert any data to JSON string
    *
    * $data can be BData instance, or array of BModel objects, will be automatically converted to array
    *
    * @param mixed $data
    * @return string
    */
    public function toJson($data)
    {
        if (is_array($data) && is_object(current($data)) && current($data) instanceof BModel) {
            $data = $this->BDb->many_as_array($data);
        } elseif (is_object($data) && $data instanceof BData) {
            $data = $data->as_array(true);
        }
        return json_encode($data);
    }

    /**
    * Parse JSON into PHP data
    *
    * @param string $json
    * @param bool $asObject if false will attempt to convert to array,
    *                       otherwise standard combination of objects and arrays
    */
    public function fromJson($json, $asObject = false)
    {
        $obj = json_decode($json);
        return $asObject ? $obj : static::objectToArray($obj);
    }

    /**
    * Indents a flat JSON string to make it more human-readable.
    *
    * @param string $json The original JSON string to process.
    *
    * @return string Indented version of the original JSON string.
    */
    public function jsonIndent($json)
    {

        $result      = '';
        $pos         = 0;
        $strLen      = strlen($json);
        $indentStr   = '  ';
        $newLine     = "\n";
        $prevChar    = '';
        $outOfQuotes = true;

        for ($i = 0; $i <= $strLen; $i++) {

            // Grab the next character in the string.
            $char = substr($json, $i, 1);

            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;

            // If this character is the end of an element,
            // output a new line and indent the next line.
            } else if (($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos --;
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            // Add the character to the result string.
            $result .= $char;

            // If the last character was the beginning of an element,
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos ++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            $prevChar = $char;
        }

        return $result;
    }

    /**
    * Convert data to JavaScript string
    *
    * Notable difference from toJson: allows raw function callbacks
    *
    * @param mixed $val
    * @return string
    */
    public function toJavaScript($val)
    {
        if (null === $val) {
            return 'null';
        } elseif (is_bool($val)) {
            return $val ? 'true' : 'false';
        } elseif (is_string($val)) {
            if (preg_match('#^\s*function\s*\(#', $val)) {
                return $val;
            } else {
                return "'" . addslashes($val) . "'";
            }
        } elseif (is_int($val) || is_float($val)) {
            return $val;
        } elseif ($val instanceof BValue) {
            return $val->toPlain();
        } elseif (($isObj = is_object($val)) || is_array($val)) {
            $out = [];
            if (!empty($val) && ($isObj || array_keys($val) !== range(0, count($val)-1))) { // assoc?
                foreach ($val as $k => $v) {
                    $out[] = "'" . addslashes($k) . "':" . static::toJavaScript($v);
                }
                return '{' . join(',', $out) . '}';
            } else {
                foreach ($val as $k => $v) {
                    $out[] = static::toJavaScript($v);
                }
                return '[' . join(',', $out) . ']';
            }
        }
        return '"UNSUPPORTED TYPE"';
    }

    public function toRss($data)
    {
        $lang = !empty($data['language']) ? $data['language'] : 'en-us';
        $ttl = !empty($data['ttl']) ? (int)$data['ttl'] : 40;
        $descr = !empty($data['description']) ? $data['description'] : $data['title'];
        $xml = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel>'
. '<title><![CDATA[' . $data['title'] . ']]></title><link><![CDATA[' . $data['link'] . ']]></link>'
. '<description><![CDATA[' . $descr . ']]></description><language><![CDATA[' . $lang . ']]></language><ttl>' . $ttl . '</ttl>';
        foreach ($data['items'] as $item) {
            if (!is_numeric($item['pubDate'])) {
                $item['pubDate'] =  strtotime($item['pubDate']);
            }
            if (empty($item['guid'])) {
                $item['guid'] = $item['link'];
            }
            $xml .= '<item><title><![CDATA[' . $item['title'] . ']]></title>'
. '<description><![CDATA[' . $item['description'] . ']]></description>'
. '<pubDate>' . date('r', $item['pubDate']) . '</pubDate>'
. '<guid><![CDATA[' . $item['guid'] . ']]></guid><link><![CDATA[' . $item['link'] . ']]></link></item>';
        }
        $xml .= '</channel></rss>';
        return $xml;
    }

    /**
    * Convert object to array recursively
    *
    * @param object $d
    * @return array
    */
    public function objectToArray($d)
    {
        if (is_object($d)) {
            $d = get_object_vars($d);
        }
        if (is_array($d)) {
            return array_map([$this->BUtil, 'objectToArray'], $d);
        }
        return $d;
    }

    /**
    * Convert array to object
    *
    * @param mixed $d
    * @return object
    */
    public function arrayToObject($d)
    {
        if (is_array($d)) {
            return (object) array_map([$this->BUtil, 'objectToArray'], $d);
        }
        return $d;
    }

    /**
     * Convert sequential array of rows to associated array by one of the fields
     *
     * @param array $array
     * @param string $idField
     * @param array|string $mapFields
     * @return array
     */
    public function arraySeqToMap($array, $idField, $mapFields = null)
    {
        $map = [];
        foreach ($array as $k => $row) {
            if (is_array($mapFields)) {
                $outRow = $this->BUtil->arrayMask($row, $mapFields);
            } elseif (is_string($mapFields)) {
                $outRow = !empty($row[$mapFields]) ? $row[$mapFields] : null;
            }
            if (!is_numeric($k)) {
                $map[$k] = $outRow;
            } elseif (isset($row[$idField])) {
                $map[$row[$idField]] = $outRow;
            }
        }
        return $map;
    }

    /**
     * version of sprintf for cases where named arguments are desired (php syntax)
     *
     * with sprintf: sprintf('second: %2$s ; first: %1$s', '1st', '2nd');
     *
     * with sprintfn: sprintfn('second: %second$s ; first: %first$s', array(
     *  'first' => '1st',
     *  'second'=> '2nd'
     * ));
     *
     * @see http://www.php.net/manual/en/function.sprintf.php#94608
     * @param string $format sprintf format string, with any number of named arguments
     * @param array $args array of [ 'arg_name' => 'arg value', ... ] replacements to be made
     * @return string|false result of sprintf call, or bool false on error
     */
    public function sprintfn($format, $args = [])
    {
        $args = (array)$args;

        // map of argument names to their corresponding sprintf numeric argument value
        $arg_nums = array_slice(array_flip(array_keys([0 => 0] + $args)), 1);

        // find the next named argument. each search starts at the end of the previous replacement.
        for ($pos = 0; preg_match('/(?<=%)([a-zA-Z_]\w*)(?=\$)/', $format, $match, PREG_OFFSET_CAPTURE, $pos);) {
            $arg_pos = $match[0][1];
            $arg_len = strlen($match[0][0]);
            $arg_key = $match[1][0];

            // programmer did not supply a value for the named argument found in the format string
            if (! array_key_exists($arg_key, $arg_nums)) {
                user_error("sprintfn(): Missing argument '${arg_key}'", E_USER_WARNING);
                return false;
            }

            // replace the named argument with the corresponding numeric one
            $format = substr_replace($format, $replace = $arg_nums[$arg_key], $arg_pos, $arg_len);
            $pos = $arg_pos + strlen($replace); // skip to end of replacement for next iteration
        }

        if (!$args) {
            $args = [''];
        }
        return vsprintf($format, array_values($args));
    }

    /**
    * Inject vars into string template
    *
    * Ex: echo $this->BUtil->injectVars('One :two :three', array('two'=>2, 'three'=>3))
    * Result: "One 2 3"
    *
    * @param string $str
    * @param array $vars
    * @return string
    */
    public function injectVars($str, $vars)
    {
        $from = []; $to = [];
        foreach ($vars as $k => $v) {
            $from[] = ':' . $k;
            $to[] = $v;
        }
        return str_replace($from, $to, $str);
    }

    /**
     * Merges any number of arrays / parameters recursively, replacing
     * entries with string keys with values from latter arrays.
     * If the entry or the next value to be assigned is an array, then it
     * automagically treats both arguments as an array.
     * Numeric entries are appended, not replaced, but only if they are
     * unique
     *
     * calling: result = $this->BUtil->arrayMerge(a1, a2, ... aN)
     *
     * @param array $array1
     * @param array $array2...
     * @return array
     **/
     public function arrayMerge() {
         $arrays = func_get_args();
         $base = array_shift($arrays);
         if (!is_array($base))  {
             $base = empty($base) ? [] : [$base];
         }
         foreach ($arrays as $append) {
             if (!is_array($append)) {
                 $append = [$append];
             }
             foreach ($append as $key => $value) {
                 if (is_numeric($key)) {
                     if (!in_array($value, $base)) {
                        $base[] = $value;
                     }
                 } elseif (!array_key_exists($key, $base)) {
                     $base[$key] = $value;
                 } elseif (is_array($value) && is_array($base[$key])) {
                     $base[$key] = static::arrayMerge($base[$key], $append[$key]);
                 } else {
                     $base[$key] = $value;
                 }
             }
         }
         return $base;
     }

    /**
    * Compare 2 arrays recursively
    *
    * @param array $array1
    * @param array $array2
    */
    public function arrayCompare(array $array1, array $array2)
    {
        $diff = false;
        // Left-to-right
        foreach ($array1 as $key => $value) {
            if (!array_key_exists($key, $array2)) {
                $diff[0][$key] = $value;
            } elseif (is_array($value)) {
                if (!is_array($array2[$key])) {
                    $diff[0][$key] = $value;
                    $diff[1][$key] = $array2[$key];
                } else {
                    $new = static::arrayCompare($value, $array2[$key]);
                    if ($new !== false) {
                        if (isset($new[0])) $diff[0][$key] = $new[0];
                        if (isset($new[1])) $diff[1][$key] = $new[1];
                    }
                }
            } elseif ($array2[$key] !== $value) {
                 $diff[0][$key] = $value;
                 $diff[1][$key] = $array2[$key];
            }
        }
        // Right-to-left
        foreach ($array2 as $key => $value) {
            if (!array_key_exists($key, $array1)) {
                $diff[1][$key] = $value;
            }
            // No direct comparsion because matching keys were compared in the
            // left-to-right loop earlier, recursively.
        }
        return $diff;
    }

    /**
    * Walk over array of objects and perform method or callback on each row
    *
    * @param array $arr
    * @param callback $cb
    * @param array $args
    * @param boolean $ignoreExceptions
    * @return array
    */
    public function arrayWalk($arr, $cb, $args = [], $ignoreExceptions = false)
    {
        $result = [];
        foreach ($arr as $i => $r) {
            $callback = is_string($cb) && $cb[0] === '.' ? [$r, substr($cb, 1)] : $cb;
            if ($ignoreExceptions) {
                try {
                    $result[] = call_user_func_array($callback, $args);
                } catch (Exception $e) {
                    BDebug::warning('EXCEPTION class(' . get_class($r) . ') arrayWalk(' . $i . '): ' . $e->getMessage());
                }
            } else {
                $result[] = call_user_func_array($callback, $args);
            }
        }
        return $result;
    }

    /**
     * Find index of array item that matches filter values
     *
     * @param array $array
     * @param array $filter
     * @return boolean|int
     */
    public function arrayFind(array $array, array $filter)
    {
        foreach ($array as $i => $item) {


            $found = true;
            foreach ($filter as $k => $v) {
                if (!(isset($item[$k]) && $item[$k] === $v)) {
                    $found = false;
                    break;
                }
            }
            if (!$found) {
                continue;
            }
            return $i;
        }
        return false;
    }

    /**
    * Clean array of ints from empty and non-numeric values
    *
    * If parameter is a string, splits by comma
    *
    * @param array|string $arr
    * @return array
    */
    public function arrayCleanInt($arr)
    {
        $res = [];
        if (is_string($arr)) {
            $arr = explode(',', $arr);
        }
        if (is_array($arr)) {
            foreach ($arr as $k => $v) {
                if (is_numeric($v)) {
                    $res[$k] = intval($v);
                }
            }
        }
        return $res;
    }

    /**
    * Insert 1 or more items into array at specific position
    *
    * Note: code repetition is for better iteration performance
    *
    * @todo normalize where syntax
    * @param array $array The original container array
    * @param array $items Items to be inserted
    * @param string $where
    *   - start
    *   - end
    *   - offset==$key
    *   - key.(before|after)==$key
    *   - key.(before|after)~=$key
    *   - obj.(before|after).$object_property==$key
    *   - arr.(before|after).$item_array_key==$key
    * @return array resulting array
    */
    public function arrayInsert($array, $items, $where)
    {
        $result = [];
        preg_match('#^(.*?)\s*(~=|==)\s*(.*)$#', $where, $w1);
        $w2 = explode('.', $w1[1], 3);

        switch ($w2[0]) {
        case 'start':
            $result = array_merge($items, $array);
            break;

        case 'end':
            $result = array_merge($array, $items);
            break;

        case 'offset': // for associative only
            $key = $w1[3];
            $i = 0;
            foreach ($array as $k => $v) {
                if ($key === $i++) {
                    foreach ($items as $k1 => $v1) {
                        $result[$k1] = $v1;
                    }
                }
                $result[$k] = $v;
            }
            break;

        case 'key': // for associative only
            $rel = $w2[1];
            $key = $w1[3];
            $op = $w1[2];
            foreach ($array as $k => $v) {
                if ($op === '==' && $key === $k || $op === '~=' && preg_match('#' . preg_quote($key, '#') . '#', $k)) {
                    if ($rel === 'after') {
                        $result[$k] = $v;
                    }
                    foreach ($items as $k1 => $v1) {
                        $result[$k1] = $v1;
                    }
                    if ($rel === 'before') {
                        $result[$k] = $v;
                    }
                } else {
                    $result[$k] = $v;
                }
            }
            break;

        case 'obj':
            $rel = $w2[1];
            $f = $w2[2];
            $key = $w1[3];
            foreach ($array as $k => $v) {
                if ($key === $v->$f) {
                    if ($rel === 'after') {
                        $result[$k] = $v;
                    }
                    foreach ($items as $k1 => $v1) {
                        $result[$k1] = $v1;
                    }
                    if ($rel === 'before') {
                        $result[$k] = $v;
                    }
                } else {
                    $result[$k] = $v;
                }
            }
            break;

        case 'arr':
            $rel = $w2[1];
            $f = $w2[2];
            $key = $w1[3];
            $isAssoc = empty($array[0]);
            foreach ($array as $k => $v) {
                if (!isset($v[$f])) {
                    if ($isAssoc) $result[$k] = $v; else $result[] = $v;
                    continue;
                }
                if ($key === $v[$f]) {
                    if ($rel === 'after') {
                        if ($isAssoc) $result[$k] = $v; else $result[] = $v;
                    }
                    foreach ($items as $k1 => $v1) {
                        if ($isAssoc) $result[$k1] = $v1; else $result[] = $v1;
                    }
                    if ($rel === 'before') {
                        if ($isAssoc) $result[$k] = $v; else $result[] = $v;
                    }
                } else {
                    if ($isAssoc) $result[$k] = $v; else $result[] = $v;
                }
            }
            break;

        default: BDebug::error('Invalid where condition: ' . $where);
        }

        return $result;
    }

    /**
     * Return only specific fields from source array
     *
     * @param array        $source
     * @param array|string $fields
     * @param boolean      $inverse if true, will return anything NOT in $fields
     * @param boolean      $setNulls fill missing fields with nulls
     * @return array
     * @result array
     */
    public function arrayMask(array $source, $fields, $inverse = false, $setNulls = true)
    {
        if (is_string($fields)) {
            $fields = explode(',', $fields);
            @array_walk($fields, 'trim'); // LLVM BUG
        }
        $result = [];
        if (!$inverse) {
            foreach ($fields as $k) {
                if (isset($source[$k])) {
                    $result[$k] = $source[$k];
                } elseif ($setNulls) {
                    $result[$k] = null;
                }
            }
        } else {
            foreach ($source as $k => $v) {
                if (!in_array($k, $fields)) $result[$k] = $v;
            }
        }
        return $result;
    }

    public function arrayToOptions($source, $labelField, $keyField = null, $emptyLabel = null)
    {
        $options = [];
        if (null !== $emptyLabel) {
            $options = ["" => $emptyLabel];
        }
        if (empty($source)) {
            return [];
        }
        $isObject = is_object(current($source));
        foreach ($source as $k => $item) {
            if ($isObject) {
                $key = null === $keyField ? $k : $item->$keyField;
                $label = $labelField[0] === '.' ? $item-> {substr($labelField, 1)}() : $item->labelField;
                $options[$key] = $label;
            } else {
                $key = null === $keyField ? $k : $item[$keyField];
                $options[$key] = $item[$labelField];
            }
        }
        return $options;
    }

    /**
     * @todo consolidate with arraySeqToMap()
     */
    public function arrayMakeAssoc($source, $keyField)
    {
        $isObject = is_object(current($source));
        $assocArray = [];
        foreach ($source as $k => $item) {
            if ($isObject) {
                $assocArray[$item->$keyField] = $item;
            } else {
                $assocArray[$item[$keyField]] = $item;
            }
        }
        return $assocArray;
    }

    /**
    * Create IV for mcrypt operations
    *
    * @return string
    */
    public function mcryptIV()
    {
        if (!static::$_mcryptIV) {
            static::$_mcryptIV = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_DEV_URANDOM);
        }
        return static::$_mcryptIV;
    }

    /**
    * Fetch default encryption key from config
    *
    * @return string
    */
    public function mcryptKey($key = null, $configPath = null)
    {
        if (null !== $key) {
            static::$_mcryptKey = $key;
        } elseif (null === static::$_mcryptKey && $configPath) {
            static::$_mcryptKey = $this->BConfig->get($configPath);
        }
        return static::$_mcryptKey;

    }

    /**
    * Encrypt using AES256
    *
    * Requires PHP extension mcrypt
    *
    * @param string $value
    * @param string $key
    * @param boolean $base64
    * @return string
    */
    public function encrypt($value, $key = null, $base64 = true)
    {
        if (null === $key) $key = static::mcryptKey();
        $enc = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $value, MCRYPT_MODE_ECB, static::mcryptIV());
        return $base64 ? trim(base64_encode($enc)) : $enc;
    }

    /**
    * Decrypt using AES256
    *
    * Requires PHP extension mcrypt
    *
    * @param string $value
    * @param string $key
    * @param boolean $base64
    * @return string
    */
    public function decrypt($value, $key = null, $base64 = true)
    {
        if (null === $key) $key = static::mcryptKey();
        $enc = $base64 ? base64_decode($value) : $value;
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $enc, MCRYPT_MODE_ECB, static::mcryptIV()));
    }

    /**
    * Generate random string
    *
    * @param int $strLen length of resulting string
    * @param string $chars allowed characters to be used
    */
    public function randomString($strLen = 8, $chars = null)
    {
        if (null === $chars) {
            $chars = static::$_defaultCharPool;
        }
        $charsLen = strlen($chars)-1;
        $str = '';
        mt_srand();
        for ($i = 0; $i < $strLen; $i++) {
            $str .= $chars[mt_rand(0, $charsLen)];
        }
        return $str;
    }

    /**
    * Generate random string based on pattern
    *
    * Syntax: {ULD10}-{U5}
    * - U: upper case letters
    * - L: lower case letters
    * - D: digits
    *
    * @param string $pattern
    * @return string
    */
    public function randomPattern($pattern)
    {
        static $chars = ['L' => 'bcdfghjkmnpqrstvwxyz', 'U' => 'BCDFGHJKLMNPQRSTVWXYZ', 'D' => '123456789'];

        while (preg_match('#\{([ULD]+)([0-9]+)\}#i', $pattern, $m)) {
            for ($i = 0, $c = ''; $i < strlen($m[1]); $i++) $c .= $chars[$m[1][$i]];
            $pattern = preg_replace('#' . preg_quote($m[0], '#') . '#', $this->BUtil->randomString($m[2], $c), $pattern, 1);
        }
        return $pattern;
    }

    public function nextStringValue($string = '', $chars = null)
    {
        if (null === $chars) {
            $chars = static::$_defaultCharPool; // avoid leading 0
        }
        $pos = strlen($string);
        $lastChar = substr($chars, -1);
        while (--$pos >= -1) {
            if ($pos == -1) {
                $string = $chars[0] . $string;
                return $string;
            } elseif ($string[$pos] === $lastChar) {
                $string[$pos] = $chars[0];
                continue;
            } else {
                $string[$pos] = $chars[strpos($chars, $string[$pos]) + 1];
                return $string;
            }
        }
        // should never get here
        return $string;
    }

    /**
    * Set or retrieve current hash algorithm
    *
    * @param string $algo
    */
    public function hashAlgo($algo = null)
    {
        if (null === $algo) {
            return static::$_hashAlgo;
        }
        static::$_hashAlgo = $algo;
    }

    public function hashIter($iter = null)
    {
        if (null === $iter) {
            return static::$_hashIter;
        }
        static::$iter = $iter;
    }

    /**
    * Generate salted hash
    *
    * @deprecated by Bcrypt
    * @param string $string original text
    * @param mixed $salt
    * @param mixed $algo
    * @return string
    */
    public function saltedHash($string, $salt, $algo = null)
    {
        $algo = null !== $algo ? $algo : static::$_hashAlgo;
        return hash($algo, $salt . $string);
    }

    /**
    * Generate fully composed salted hash
    *
    * Ex: $sha512$2$<salt1>$<salt2>$<double-hashed-string-here>
    *
    * @deprecated by Bcrypt
    * @param string $string
    * @param string $salt
    * @param string $algo
    * @param integer $iter
    */
    public function fullSaltedHash($string, $salt = null, $algo = null, $iter = null)
    {
        $algo = null !== $algo ? $algo : static::$_hashAlgo;
        if ('bcrypt' === $algo) {
            return $this->Bcrypt->hash($string);
        }
        $iter = null !== $iter ? $iter : static::$_hashIter;
        $s = static::$_hashSep;
        $hash = $s . $algo . $s . $iter;
        for ($i = 0; $i < $iter; $i++) {
            $salt1 = null !== $salt ? $salt : static::randomString();
            $hash .= $s . $salt1;
            $string = static::saltedHash($string, $salt1, $algo);
        }
        return $hash . $s . $string;
    }

    /**
    * Validate salted hash against original text
    *
    * @deprecated by $this->BUtil->bcrypt()
    * @param string $string original text
    * @param string $storedHash fully composed salted hash
    * @return bool
    */
    public function validateSaltedHash($string, $storedHash)
    {
        if (strpos($storedHash, '$2a$') === 0 || strpos($storedHash, '$2y$') === 0) {
            return $this->Bcrypt->verify($string, $storedHash);
        }
        if (!$storedHash) {
            return false;
        }
        $sep = $storedHash[0];
        $arr = explode($sep, $storedHash);
        array_shift($arr);
        $algo = array_shift($arr);
        $iter = array_shift($arr);
        $verifyHash = $string;
        for ($i = 0; $i < $iter; $i++) {
            $salt = array_shift($arr);
            $verifyHash = static::saltedHash($verifyHash, $salt, $algo);
        }
        $knownHash = array_shift($arr);
        return $verifyHash === $knownHash;
    }

    /**
     * Used for hash regeneration of old password hashes
     *
     * @return string
     */
    public function isPreferredPasswordHash($password)
    {
        return strpos($password, '$2y$12$') === 0;
    }

    public function sha512base64($str)
    {
        return base64_encode(pack('H*', hash('sha512', $str)));
    }

    static protected $_lastRemoteHttpInfo;
    /**
    * Send simple POST request to external server and retrieve response
    *
    * @param string $method
    * @param string $url
    * @param array $data
    * @return string
    */
    public function remoteHttp($method, $url, $data = [], $headers = [], $options = [])
    {
        $debugProfile = BDebug::debug(chunk_split('REMOTE HTTP: ' . $method . ' ' . $url));
        $timeout = 5;
        $userAgent = !empty($options['useragent']) ? $options['useragent'] : 'Mozilla/5.0';
        if (preg_match('#^//#', $url)) {
            $url = 'http:' . $url;
        }
        if ($method === 'GET' && $data) {
            if (is_array($data)) {
                $request = http_build_query($data, '', '&');
            } else {
                $request = $data;
            }

            $url .= (strpos($url, '?') === false ? '?' : '&') . $request;
        }

        // curl disabled by default because file upload doesn't work for some reason. TODO: figure out why
        if (!empty($options['curl']) && function_exists('curl_init')
            || ini_get('safe_mode') || !ini_get('allow_url_fopen')
        ) {
            $curlOpt = [
                CURLOPT_USERAGENT => $userAgent,
                CURLOPT_URL => $url,
                CURLOPT_ENCODING => '',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_AUTOREFERER => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => dirname(__DIR__) . '/ssl/ca-bundle.crt',
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_HTTPHEADER, ['Expect:'], //Fixes the HTTP/1.1 417 Expectation Failed
                CURLOPT_HEADER => true,
            ];
            if (!ini_get('safe_mode') && !ini_get('open_basedir')) {
                $curlOpt += [
                    CURLOPT_FOLLOWLOCATION => true,
                ];
            }
            if (false) { // TODO: figure out cookies handling
                $cookieDir = $this->BApp->storageRandomDir() . '/cache';
                $this->BUtil->ensureDir($cookieDir);
                $cookie = tempnam($cookieDir, 'CURLCOOKIE');
                $curlOpt += [
                    CURLOPT_COOKIEJAR => $cookie,
                ];
            }

            if ($method === 'POST') {
                $curlOpt += [
                    CURLOPT_POSTFIELDS => $data,
                    CURLOPT_POST => 1,
                ];
            } elseif ($method === 'PUT') {
                $curlOpt += [
                    CURLOPT_POSTFIELDS => $data,
                    CURLOPT_PUT => 1,
                ];
            }

            if (!empty($headers)) {
                $curlOpt += [
                    CURLOPT_HTTPHEADER => array_values($headers),
                ];
            }
            if (!empty($options['proxy'])) {
                $curlOpt += [
                    CURLOPT_PROXY => $options['proxy'],
                    CURLOPT_PROXYTYPE => !empty($options['proxytype']) ? $options['proxytype'] : CURLPROXY_HTTP,
                ];
            }
            $ch = curl_init();
            curl_setopt_array($ch, $curlOpt);
            $rawResponse = curl_exec($ch);
            list($headers, $response) = explode("\r\n\r\n", $rawResponse, 2);
            static::$_lastRemoteHttpInfo = curl_getinfo($ch);
#echo "<xmp>"; var_dump($rawResponse, static::$_lastRemoteHttpInfo); echo "</xmp>";
            $respHeaders = explode("\r\n", $headers);
            if (curl_errno($ch) != 0) {
                static::$_lastRemoteHttpInfo['errno'] = curl_errno($ch);
                static::$_lastRemoteHttpInfo['error'] = curl_error($ch);
            }
            curl_close($ch);
        } else {
            $streamOptions = ['http' => [
                'method' => $method,
                'timeout' => $timeout,
                'header' => "User-Agent: {$userAgent}\r\n",
            ]];
            if ($headers) {
                $streamOptions['http']['header'] .= join("\r\n", array_values($headers)) . "\r\n";
            }
            if (!empty($options['proxy'])) {
                $streamOptions['http']['proxy'] = $options['proxy'];
            }
            if ($method === 'POST' || $method === 'PUT') {
                $multipart = false;
                if (is_array($data)) {
                    foreach ($data as $k => $v) {
                        if (is_string($v) && $v[0] === '@') {
                            $multipart = true;
                            break;
                        }
                    }
                }
                if (!$multipart) {
                    $contentType = 'application/x-www-form-urlencoded';
                    $streamOptions['http']['content'] = is_array($data) ? http_build_query($data) : $data;
                } else {
                    $boundary = '--------------------------' . microtime(true);
                    $contentType = 'multipart/form-data; boundary=' . $boundary;
                    $streamOptions['http']['content'] = '';
                    //TODO: implement recursive forms
                    foreach ($data as $k => $v) {
                        if (is_string($v) && $v[0] === '@') {
                            $filename = substr($v, 1);
                            $fileContents = file_get_contents($filename);
                            $streamOptions['http']['content'] .= "--{$boundary}\r\n" .
                                "Content-Disposition: form-data; name=\"{$k}\"; filename=\"" . basename($filename) . "\"\r\n" .
                                "Content-Type: application/zip\r\n" .
                                "\r\n" .
                                "{$fileContents}\r\n";
                        } else {
                            $streamOptions['http']['content'] .= "--{$boundary}\r\n" .
                                "Content-Disposition: form-data; name=\"{$k}\"\r\n" .
                                "\r\n" .
                                "{$v}\r\n";
                        }
                    }
                    $streamOptions['http']['content'] .= "--{$boundary}--\r\n";
                }
                $streamOptions['http']['header'] .= "Content-Type: {$contentType}\r\n";
                    //."Content-Length: ".strlen($request)."\r\n";
                if (preg_match('#^(ssl|ftps|https):#', $url)) {
                    $streamOptions['ssl'] = [
                        'verify_peer' => true,
                        'cafile' => dirname(__DIR__) . '/ssl/ca-bundle.crt',
                        'verify_depth' => 5,
                    ];
                }
            }
            if (empty($options['debug'])) {
                $oldErrorReporting = error_reporting(0);
            }
            $response = file_get_contents($url, false, stream_context_create($streamOptions));
#var_dump($response, $url, $streamOptions, $http_response_header); exit(__METHOD__);
            if (empty($options['debug'])) {
                error_reporting($oldErrorReporting);
            }
            static::$_lastRemoteHttpInfo = []; //TODO: emulate curl data?
            $respHeaders = isset($http_response_header) ? $http_response_header : [];
        }
        foreach ($respHeaders as $i => $line) {
            if ($i) {
                $arr = explode(':', $line, 2);
                static::$_lastRemoteHttpInfo['headers'][strtolower($arr[0])] = trim($arr[1]);
            } else {
                preg_match('#^HTTP/([0-9.]+) ([0-9]+) (.*)$#', $line, $m);
                static::$_lastRemoteHttpInfo['headers']['http'] = [
                    'full' => $m[0],
                    'protocol' => $m[1],
                    'code' => $m[2],
                    'status' => $m[3],
                ];
            }
        }
        #BDebug::log(print_r(compact('method', 'url', 'data', 'response'), 1), 'remotehttp.log');

        BDebug::profile($debugProfile);
        return $response;
    }

    public function lastRemoteHttpInfo()
    {
        return static::$_lastRemoteHttpInfo;
    }

    public function normalizePath($path)
    {
        $path = str_replace('\\', '/', $path);
        if (strpos($path, '/..') !== false) {
            $a = explode('/', $path);
            $b = [];
            foreach ($a as $p) {
                if ($p === '..') array_pop($b); else $b[] = $p;
            }
            $path = join('/', $b);
        }
        return $path;
    }

    public function globRecursive($dir, $pattern = null, $flags = 0)
    {
        /**/
        if (null === $pattern) {
            $pattern = '*';
        }
        $files = glob($dir . '/' . $pattern, $flags);
        if (!$files) {
            return [];
        }
        $result = $files;
        foreach ($files as $file) {
            if (is_dir($file)) {
                $subFiles = static::globRecursive($file, $pattern, $flags);
                $result = array_merge($result, $subFiles);
            }
        }
        return $result;
        /*
        // recursive iterator proves slower than glob + is_dir
        $dirIte = new RecursiveDirectoryIterator($dir);
        $flatIte = new RecursiveIteratorIterator($dirIte);
        if (is_null($pattern)) {
            $pattern = '#.*#';
        }
        $files = new RegexIterator($flatIte, $pattern, RegexIterator::GET_MATCH);
        $fileList = array();
        foreach($files as $file) {
            if (substr($file[0], -2) === '..') {
                continue;
            }
            $file = preg_replace(array('#\\\\#', '#/.$#'), array('/', ''), $file[0]);
            $fileList[] = $file;
        }
        return $fileList;
        /**/
    }

    public function isPathAbsolute($path)
    {
        return !empty($path) && ($path[0] === '/' || $path[0] === '\\') // starting with / or \
            || !empty($path[1]) && $path[1] === ':'; // windows drive letter C:
    }

    public function isUrlFull($url)
    {
        return preg_match('#^(https?:)?//#', $url);
    }

    public function ensureDir($dir)
    {
        if (is_file($dir)) {
            BDebug::warning($dir . ' is a file, directory required');
            return;
        }
        if (!is_dir($dir)) {
            @$res = mkdir($dir, 0777, true);
            if (!$res) {
                BDebug::warning("Can't create directory: " . $dir);
            }
        }
    }

    /**
    * Put together URL components generated by parse_url() function
    *
    * @see http://us2.php.net/manual/en/function.parse-url.php#106731
    * @param array $p result of parse_url()
    * @return string
    */
    public function unparseUrl($p)
    {
        $scheme   = isset($p['scheme'])   ? $p['scheme'] . '://' : '';
        $user     = isset($p['user'])     ? $p['user']           : '';
        $pass     = isset($p['pass'])     ? ':' . $p['pass']     : '';
        $pass     = ($user || $pass)      ? $pass . '@'          : '';
        $host     = isset($p['host'])     ? $p['host']           : '';
        $port     = isset($p['port'])     ? ':' . $p['port']     : '';
        $path     = isset($p['path'])     ? $p['path']           : '';
        $query    = isset($p['query'])    ? '?' . $p['query']    : '';
        $fragment = isset($p['fragment']) ? '#' . $p['fragment'] : '';
        return $scheme . $user . $pass . $host . $port . $path . $query . $fragment;
    }

    /**
    * Add or set URL query parameters
    *
    * @param string $url
    * @param array $params
    * @return string
    */
    public function setUrlQuery($url, $params)
    {
        if (true === $url) {
            $url = $this->BRequest->currentUrl();
        }
        $parsed = parse_url($url);
        $query = [];
        if (!empty($parsed['query'])) {
            foreach (explode('&', $parsed['query']) as $q) {
                $a = explode('=', $q);
                if ($a[0] === '') {
                    continue;
                }
                $a[0] = urldecode($a[0]);
                $query[$a[0]] = urldecode($a[1]);
            }
        }
        foreach ($params as $k => $v) {
            if ($v === "") {
                if (isset($query[$k])) {
                    unset($query[$k]);
                }
                unset($params[$k]);
            }
        }
        $query = array_merge($query, $params);
        $parsed['query'] = http_build_query($query);
        return static::unparseUrl($parsed);
    }

    public function paginateSortUrl($url, $state, $field)
    {
        return static::setUrlQuery($url, [
            's' => $field,
            'sd' => $state['s'] != $field || $state['sd'] == 'desc' ? 'asc' : 'desc',
        ]);
    }

    public function paginateSortAttr($url, $state, $field, $class = '')
    {
        return 'href="' . static::paginateSortUrl($url, $state, $field)
            . '" class="' . $class . ' ' . ($state['s'] == $field ? $state['sd'] : '') . '"';
    }

    /**
     * @param string $tag
     * @param array  $attrs
     * @param null   $content
     * @return string
     */
    public function tagHtml($tag, $attrs = [], $content = null)
    {
        $attrsHtmlArr = [];
        foreach ($attrs as $k => $v) {
            if (null === $v || false === $v) {
                continue;
            }
            if (true === $v) {
                $v = $k;
            } elseif (is_array($v)) {
                switch ($k) {
                    case 'class':
                        $v = join(' ', $v);
                        break;

                    case 'style':
                        $attrHtmlArr = [];
                        foreach ($v as $k1 => $v1) {
                            $attrHtmlArr[] = $k1 . ':' . $v1;
                        }
                        $v = join('; ', $attrHtmlArr);
                        break;

                    default:
                        $v = join('', $v);
                }
            }
            $attrsHtmlArr[] = $k . '="' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '"';
        }
        return '<' . $tag . ' ' . join(' ', $attrsHtmlArr) . '>' . $content . '</' . $tag . '>';
    }

    /**
     * @param array $options
     * @param string $default
     * @return string
     */
    public function optionsHtml($options, $default = '')
    {
        if (!is_array($default)) {
            $default = (string)$default;
        }
        $htmlArr = [];
        foreach ($options as $k => $v) {
            $k = (string)$k;
            if (is_array($v) && $k !== '' && $k[0] === '@') { // group
                $label = trim(substr($k, 1));
                $htmlArr[] = $this->BUtil->tagHtml('optgroup', ['label' => $label], static::optionsHtml($v, $default));
                continue;
            }
            if (is_array($v)) {
                $attr = $v;
                $v = !empty($attr['text']) ? $attr['text'] : '';
                unset($attr['text']);
            } else {
                $attr = [];
            }
            $attr['value'] = $k;
            $attr['selected'] = is_array($default) && in_array($k, $default) || $default === $k;
            $htmlArr[] = $this->BUtil->tagHtml('option', $attr, $v);
        }

        return join("\n", $htmlArr);
    }

    /**
     * Strip html tags and shorten to specified length, to the whole word
     *
     * @param string  $text
     * @param integer $limit
     * @return string
     */
    public function previewText($text, $limit)
    {
        $text = strip_tags($text);
        if (strlen($text) < $limit) {
            return $text;
        }
        preg_match('/^(.{1,' . $limit . '})\b/', $text, $matches);
        return $matches[1];
    }

    public function isEmptyDate($date)
    {
        return preg_replace('#[0 :-]#', '', (string)$date) === '';
    }

    /**
     * Get gravatar image src by email
     *
     * @param string $email
     * @param array  $params
     *   - size (default 80)
     *   - rating (G, PG, R, X)
     *   - default
     *   - border
     * @return string
     */
    public function gravatar($email, $params = [])
    {
        if (empty($params['default'])) {
            $params['default'] = 'identicon';
        }
        return $this->BRequest->scheme() . '://www.gravatar.com/avatar/' . md5(strtolower($email))
            . ($params ? '?' . http_build_query($params) : '');
    }

    public function extCallback($callback)
    {
        static $callbackMapCache = [];

        if (is_string($callback)) {
            if (isset($callbackMapCache[$callback])) {
                $callback = $callbackMapCache[$callback];
            } else {
                $origCallback = $callback;
                if (strpos($callback, '::') !== false) {
                    list($class, $method) = explode('::', $callback);
                    $reflMethod = new ReflectionMethod($class, $method);
                    if ($reflMethod->isStatic()) {
                        $class = null; // proceed with usual callback
                    }
                } elseif (strpos($callback, '.') !== false) {
                    list($class, $method) = explode('.', $callback);
                } elseif (strpos($callback, '->')) {
                    list($class, $method) = explode('->', $callback);
                }
                if (!empty($class)) {
                    $instance = BClassRegistry::instance($class, [], true);
                    $callback = [$instance, $method];
                }
                $callbackMapCache[$origCallback] = $callback;
            }
        }
        return $callback;
    }

    public function call($callback, $args = [], $array = false)
    {
        $callback = static::extCallback($callback);
        if ($array) {
            return call_user_func_array($callback, $args);
        } else {
            return call_user_func($callback, $args);
        }
    }

    public function varSize($var)
    {
        /*
        function varCopy($src)
        {
            if (is_string($src)) {
                return str_replace('!@#$%^&*()', ')(*&^%$#@!', $src);
            }

            if (is_numeric($src)) {
                return ($src + 0);
            }

            if (is_bool($src)) {
                return ($src ? TRUE : FALSE);
            }
            if (null === $src) {
                return NULL;
            }

            if (is_object($src)) {
                $new = (object) array();
                foreach ($src as $key => $val) {
                    $new->$key = rec_copy($val);
                }
                return $new;
            }

            if (!is_array($src)) {
                //print_r(gettype($src) . "\n");
                return $src;
            }

            $new = array();
            foreach ($src as $k=>$v) {
                $new[$k] = varCopy($v);
            }
            return $new;
        }
        */
        $old = memory_get_usage();
        $dummy = unserialize(serialize($var));
        return memory_get_usage() - $old;
    }

    public function formatDateRecursive($source, $format = 'm/d/Y')
    {
        foreach ($source as $i => $val) {
            if (is_string($val)) {
                // checking only beginning of string for speed, assuming it is a date
                if (preg_match('#^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]( |$)#', $val)) {
                    $source[$i] = date($format, strtotime($val));
                }
            } elseif (is_array($val)) {
                $source[$i] = static::formatDateRecursive($val, $format);
            }
        }
        return $source;
    }

    public function timeAgo($ptime, $now = null, $long = false)
    {
        if (!is_numeric($ptime)) {
            $ptime = strtotime($ptime);
        }
        if (!$now) {
            $now = strtotime($this->BDb->now());#time();
        } elseif (!is_numeric($now)) {
            $now = strtotime($now);
        }
        $etime = $now - $ptime;
        if ($etime < 1) {
            return $long ? 'less than 1 second' : '0s';
        }
        $a = [
            12 * 30 * 24 * 60 * 60  =>  ['year', 'y'],
            30 * 24 * 60 * 60       =>  ['month', 'mon'],
            24 * 60 * 60            =>  ['day', 'd'],
            60 * 60                 =>  ['hour', 'h'],
            60                      =>  ['minute', 'm'],
            1                       =>  ['second', 's'],
        ];

        foreach ($a as $secs => $sa) {
            $d = $etime / $secs;
            if ($d >= 1) {
                $r = round($d);
                return $r . ($long ? ' ' . $sa[0] . ($r > 1 ? 's' : '') : $sa[1]);
            }
        }
    }

    /**
    * Output locale formatted date/time HTML
    *
    * @param string|int $time
    * @param string $showTime
    * @param mixed $format date() format or
    *                   SHORT:'m/d/Y'|'m/d/Y h:ia',
    *                   MEDIUM:'M jS, Y'|'M jS, Y \at h:ia',
    *                   LONG:'l jS \of F Y'|'l jS \of F Y \at h:i:s A'
    * @param mixed $tz
    */
    public function timeHtml($time, $showTime = false, $format = null, $tz = null)
    {
        if (!is_numeric($time)) {
            $time = strtotime($time);
        }
        $timeStr = date($showTime ? 'Y-m-d H:i:s' : 'Y-m-d', $time);
        if (null === $format) {
            $format = $this->BSession->get($showTime ? '_timeformat' : '_dateformat');
            if (!$format) {
                $format = $showTime ? 'm/d/Y H:i' : 'm/d/Y';
            }
        }
        if (null !== $tz) {
            $oldTz = date_default_timezone_get();
            date_default_timezone_set($tz);
        }
        $formattedTime = date($format, $time);
        if (null !== $tz) {
            date_default_timezone_set($oldTz);
        }
        return '<time datetime="' . $timeStr . '">' . $formattedTime . '</time>';
    }

    /**
     * Simplify string to allowed characters only
     *
     * @param string $str input string
     * @param string $pattern RegEx pattern to specify not allowed characters
     * @param string $filler character to replace not allowed characters with
     * @return string
     */
    public function simplifyString($str, $pattern = '#[^a-z0-9-]+#', $filler = '-')
    {
        if (preg_match('#e[a-zA-Z]*$#', $pattern)) {
            throw new BException('Restricted modifier');
        }
        return trim(preg_replace($pattern, $filler, strtolower($str)), $filler);
    }

    /**
    * Remove directory recursively
    *
    * DANGEROUS
    *
    * @param string $dir
    */
    public function rmdirRecursive_YesIHaveCheckedThreeTimes($dir, $first = true)
    {
        if ($first) {
            $dir = realpath($dir);
        }
        if (!$dir || !file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir) || is_link($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!static::rmdirRecursive_YesIHaveCheckedThreeTimes($dir . "/" . $item, false)) {
                chmod($dir . "/" . $item, 0777);
                if (!static::rmdirRecursive_YesIHaveCheckedThreeTimes($dir . "/" . $item, false)) {
                    return false;
                }
            }
        }
        return rmdir($dir);
    }

    public function topoSort(array $array, array $args = [])
    {
        if (empty($array)) {
            return [];
        }

        // nodes listed in 'after' are parents
        // nodes listed in 'before' are children
        // prepare initial $nodes array
        $beforeVar = !empty($args['before']) ? $args['before'] : 'before';
        $afterVar = !empty($args['before']) ? $args['after'] : 'after';
        $isObject = is_object(current($array));
        $nodes = [];
        foreach ($array as $k => $v) {
            $before = $isObject ? $v->$beforeVar : $v[$beforeVar];
            if (is_string($before)) {
                $before = array_walk(explode(',', $before), 'trim');
            }
            $after = $isObject ? $v->$afterVar : $v[$afterVar];
            if (is_string($after)) {
                $after = array_walk(explode(',', $after), 'trim');
            }
            $nodes[$k] = ['key' => $k, 'item' => $v, 'parents' => (array)$after, 'children' => (array)$before];
        }

        // get nodes without parents
        $rootNodes = [];
        foreach ($nodes as $k => $node) {
            if (empty($node['parents'])) {
                $rootNodes[] = $node;
            }
        }
        // begin algorithm
        $sorted = [];
        while ($nodes) {
            // check for circular reference
            if (!$rootNodes) return false;
            // remove this node from root nodes and add it to the output
            $n = array_pop($rootNodes);
            $sorted[$n['key']] = $n['item'];
            // for each of its children: queue the new node, finally remove the original
            for ($i = count($n['children'])-1; $i >= 0; $i--) {
                // get child node
                $childNode = $nodes[$n['children'][$i]];
                // remove child nodes from parent
                unset($n['children'][$i]);
                // remove parent from child node
                unset($childNode['parents'][array_search($n['name'], $childNode['parents'])]);
                // check if this child has other parents. if not, add it to the root nodes list
                if (!$childNode['parents']) {
                    array_push($rootNodes, $childNode);
                }
            }
            // remove processed node from list
            unset($nodes[$n['key']]);
        }
        return $sorted;
    }

    /**
     * Wrapper for ZipArchive::open+extractTo
     *
     * @param string $filename
     * @param string $targetDir
     * @return boolean Result
     */
    public function zipExtract($filename, $targetDir)
    {
        if (!class_exists('ZipArchive')) {
            throw new BException("Class ZipArchive doesn't exist");
        }
        $zip = new ZipArchive;
        $res = $zip->open($filename);
        if (!$res) {
            throw new BException("Can't open zip archive for reading: " . $filename);
        }
        $this->BUtil->ensureDir($targetDir);
        $res = $zip->extractTo($targetDir);
        $zip->close();
        if (!$res) {
            throw new BException("Can't extract zip archive: " . $filename . " to " . $targetDir);
        }
        return true;
    }

    public function zipCreateFromDir($filename, $sourceDir)
    {
        if (!class_exists('ZipArchive')) {
            throw new BException("Class ZipArchive doesn't exist");
        }
        $files = $this->BUtil->globRecursive($sourceDir);
        if (!$files) {
            throw new BException('Invalid or empty source dir');
        }
        $zip = new ZipArchive;
        $res = $zip->open($filename, ZipArchive::CREATE);
        if (!$res) {
            throw new BException("Can't open zip archive for writing: " . $filename);
        }
        foreach ($files as $file) {
            $packedFile = str_replace($sourceDir . '/', '', $file);
            if (is_dir($file)) {
                $zip->addEmptyDir($packedFile);
            } else {
                $zip->addFile($file, $packedFile);
            }
        }
        $zip->close();
        return true;
    }

    public function getMemoryLimit()
    {
        preg_match('#^([0-9]+)([GMK]?)$#', ini_get('memory_limit'), $val);
        $mult = ['G' => 1073741824, 'M' => 1048576, 'K' => 1024];
        return $val[1] * (!empty($mult[$val[2]]) ? $mult[$val[2]] : 1);
    }

    /**
     * convert image to jpeg, also resize if have width or height destination image
     * @param string  $srcFile
     * @param string  $dstFile
     * @param integer $dw width destination image
     * @param integer $dh height destination image
     * @param string  $fileType
     * @return boolean
     */
    public function convertImage($srcFile, $dstFile, $dw = null, $dh = null, $fileType = 'jpg')
    {
        clearstatcache(true, $srcFile);

        if (!file_exists($srcFile) || !filesize($srcFile)) {
            return false;
        }

        list($sw, $sh, $type) = getimagesize($srcFile);
        if (!$sw) {
            return false;
        }

        if (!$dw) {
            $dw = $sw;
        }
        if (!$dh) {
            $dh = $sh;
        }

        //get image
        switch ($type) {
            case IMAGETYPE_GIF :
                $srcImage = imagecreatefromgif($srcFile);
                break;
            case IMAGETYPE_BMP:
                $srcImage = imagecreatefromwbmp($srcFile);
                break;
            case IMAGETYPE_PNG:
                $srcImage = imagecreatefrompng($srcFile);
                break;
            case IMAGETYPE_JPEG:
            default:
                $srcImage = imagecreatefromjpeg($srcFile);
                break;
        }

        if ($srcImage) {
            $dstImage = imagecreatetruecolor($dw, $dh);
            $color = imagecolorallocate($dstImage,
                base_convert(substr('FFFFFF', 0, 2), 16, 10),
                base_convert(substr('FFFFFF', 2, 2), 16, 10),
                base_convert(substr('FFFFFF', 4, 2), 16, 10)
            );
            imagefill($dstImage, 0, 0, $color);
            $scale = $sw > $sh ? $dw / $sw : $dh / $sh;
            $dfw = $sw * $scale; //diff width
            $dfh = $sh * $scale; //diff height
            if ($sh < $dh) {
                $dfh = $sh;
            }
            if ($sw < $dw) {
                $dfw = $sw;
            }
            imagecopyresampled($dstImage, $srcImage, ($dw - $dfw) / 2, ($dh - $dfh) / 2, 0, 0, $dfw, $dfh, $sw, $sh);

            //write image base on file_type
            switch ($fileType) {
                case 'gif':
                    $success = imagegif($dstImage, $dstFile);
                    break;
                case 'png':
                    $success = imagepng($dstImage, $dstFile, 7);
                    break;
                case 'jpg':
                case 'jpeg':
                default:
                    $success = imagejpeg($dstImage, $dstFile, 90);
                    break;
            }
            imagedestroy($dstImage);
            @chmod($dstFile, 0664);
            return $success;
        }
        return false;
    }

    /**
     * Alias of version_compare for use in Twig templates
     */
    public function versionCompare($v1, $v2, $op = null)
    {
        return version_compare($v1, $v2, $op);
    }
}

class BHtml extends BClass
{

}

class BUrl extends BClass
{
    public function hideScriptName()
    {
        $hideConf = $this->BConfig->get('web/hide_script_name');
        return $hideConf == 2 || $hideConf == 1 && $this->BRequest->modRewriteEnabled();
    }
}

/**
 * @todo Verify license compatibility and integrate with https://github.com/PHPMailer/PHPMailer
 */
class BEmail extends BClass
{
    static protected $_handlers = [];
    static protected $_defaultHandler = 'default';

    public function __construct()
    {
        $this->addHandler('default', [$this, 'defaultHandler']);
    }

    public function addHandler($name, $params)
    {
        if (is_callable($params)) {
            $params = [
                'description' => $name,
                'callback' => $params,
            ];
        }
        static::$_handlers[$name] = $params;
    }

    public function getHandlers()
    {
        return static::$_handlers;
    }

    public function setDefaultHandler($name)
    {
        static::$_defaultHandler = $name;
    }

    public function send($data)
    {
        static $allowedHeadersRegex = '/^(to|from|cc|bcc|reply-to|return-path|content-type|list-unsubscribe|x-.*)$/';

        $data = array_change_key_case($data, CASE_LOWER);

        $body = trim($data['body']);
        unset($data['body']);

        $to      = '';
        $subject = '';
        $headers = [];
        $params  = [];
        $files   = [];

        foreach ($data as $k => $v) {
            if ($k == 'subject') {
                if ($this->BConfig->get('staging/email_subject_prepend')) {
                    $subject = $this->BConfig->get('staging/email_subject_prepend_prefix') . ' ' . $v;
                    $headers['x-staging-original-subject'] = 'X-Staging-Original-Subject: ' . $v;
                } else {
                    $subject = $v;
                }

            } elseif ($k == 'to') {
                if ($this->BConfig->get('staging/email_to_override')) {
                    $to = $this->BConfig->get('staging/email_to_override_address');
                    $headers['x-staging-original-to'] = 'X-Staging-Original-To: ' . $v;
                } else {
                    $to = $v;
                }

            } elseif ($k == 'attach') {
                foreach ((array)$v as $file) {
                    $files[] = $file;
                }

            } elseif ($k[0] === '-') {
                $params[$k] = $k . ' ' . $v;

            } elseif (preg_match($allowedHeadersRegex, $k)) {
                if (!empty($v) && $v !== '"" <>') {
                    $headers[$k] = $k . ': ' . $v;
                }
            }
        }

        $origBody = $body;

        $this->_formatAlternative($headers, $body);
        $body = trim(preg_replace('#<!--.*?-->#', '', $body));//strip comments

        if ($files) {
            // $body and $headers will be updated
            $this->_addAttachments($files, $headers, $body);
        }

        if (empty($headers['content-type'])) {
            $headers['content-type'] = 'Content-Type: text/plain; charset=utf-8';
        }

        $emailData = [
            'to' => &$to,
            'subject' => &$subject,
            'orig_body' => &$origBody,
            'body' => &$body,
            'headers' => &$headers,
            'params' => &$params,
            'files' => &$files,
            'orig_data' => $data,
        ];

        return $this->_dispatch($emailData);
    }

    protected function _dispatch($emailData)
    {
        try {
            $flags = $this->BEvents->fire('BEmail::send:before', ['email_data' => $emailData]);
            if ($flags === false) {
                return false;
            } elseif (is_array($flags)) {
                foreach ($flags as $f) {
                    if ($f === false) {
                        return false;
                    }
                }
            }
        } catch (BException $e) {
            BDebug::warning($e->getMessage());
            return false;
        }

        $callback = static::$_handlers[static::$_defaultHandler]['callback'];
        if (is_callable($callback)) {
            $result = $this->BUtil->call($callback, $emailData);
        } else {
            BDebug::warning('Default email handler is not callable');
            $result = false;
        }
        $emailData['result'] = $result;

        $this->BEvents->fire('BEmail::send:after', ['email_data' => $emailData]);

        return $result;
    }

    protected function _formatAlternative(&$headers, &$body)
    {
        if (!preg_match('#<!--=+-->#', $body)) {
            return $body;
        }
        $mimeBoundary = "==Multipart_Boundary_x" . md5(microtime()) . "x";

        // headers for attachment
        $headers['mime-version'] = "MIME-Version: 1.0";
        $headers['content-type'] = "Content-Type: multipart/alternative; boundary=\"{$mimeBoundary}\"";

        $parts = preg_split('#<!--=+-->#', $body);
        $message = "--{$mimeBoundary}\r\nContent-Type: text/plain; charset=utf-8\r\n\r\n" . trim($parts[0]);
        $message .= "\r\n--{$mimeBoundary}\r\nContent-Type: text/html; charset=utf-8\r\n\r\n" . trim($parts[1]);
        $message .= "\r\n--{$mimeBoundary}--";

        $body = $message;
        return true;
    }

    /**
     * Add email attachment
     *
     * @param $files
     * @param $mailheaders
     * @param $body
     */
    protected function _addAttachments($files, &$headers, &$body)
    {
        $body = trim($body);

        $mimeBoundary = "==Multipart_Boundary_x" . md5(microtime()) . "x";

        //headers and message for text
        $message = "--{$mimeBoundary}\r\n{$mailheaders['content-type']}\r\n\r\n{$body}\r\n\r\n";

        // headers for attachment
        $headers['mime-version'] = "MIME-Version: 1.0";
        $headers['content-type'] = "Content-Type: multipart/mixed; boundary=\"{$mimeBoundary}\"";

        // preparing attachments
        foreach ($files as $file) {
            if (is_file($file)) {
                $data = chunk_split(base64_encode(file_get_contents($file)));
                $name = basename($file);
                $message .= "--{$mimeBoundary}\r\n" .
                    "Content-Type: application/octet-stream; name=\"{$name}\"\r\n" .
                    "Content-Description: {$name}\r\n" .
                    "Content-Disposition: attachment; filename=\"{$name}\"; size=" . filesize($files[$i]) . ";\r\n" .
                    "Content-Transfer-Encoding: base64\r\n\r\n{$data}\r\n\r\n";
            }
        }
        $message .= "--{$mimeBoundary}--";

        $body = $message;
        return true;
    }

    public function defaultHandler($data)
    {
        return mail($data['to'], $data['subject'], $data['body'],
            join("\r\n", $data['headers']), join(' ', $data['params']));
    }
}

/**
* Helper class to designate a variable a custom type
*/
class BValue
{
    public $content;
    public $type;

    public function __construct($content, $type = 'string')
    {
        $this->content = $content;
        $this->type = $type;
    }

    public function toPlain()
    {
        return $this->content;
    }

    public function __toString()
    {
        return (string)$this->toPlain();
    }
}

/**
* @deprecated
*/
class BType extends BValue {}

class BData extends BClass implements ArrayAccess
{
    protected $_data;

    public function __construct($data, $recursive = false)
    {
        if (!is_array($data)) {
            $data = []; // not sure for here, should we try to convert data to array or do empty array???
        }
        if ($recursive) {
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    $data[$k] = new BData($v, true);
                }
            }
        }
        $this->_data = $data;
    }

    public function as_array($recursive = false)
    {
        $data = $this->_data;
        if ($recursive) {
            foreach ($data as $k => $v) {
                if (is_object($v) && $v instanceof BData) {
                    $data[$k] = $v->as_array();
                }
            }
        }
        return $data;
    }

    public function __get($name)
    {
        return isset($this->_data[$name]) ? $this->_data[$name] : null;
    }

    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }

    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->_data[] = $value;
        } else {
            $this->_data[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->_data[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->_data[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
    }

    public function get($name)
    {
        return isset($this->_data[$name]) ? $this->_data[$name] : null;
    }

    public function set($name, $value)
    {
        $this->_data[$name] = $value;
        return $this;
    }
}

class BErrorException extends Exception
{
    public $context;
    public $stackPop;

    public function __construct($code, $message, $file, $line, $context = null, $stackPop = 1)
    {
        parent::__construct($message, $code);
        $this->file = $file;
        $this->line = $line;
        $this->context = $context;
        $this->stackPop = $stackPop;
    }
}

/**
* Facility to log errors and events for development and debugging
*
* @todo move all debugging into separate plugin, and override core classes
*/
class BDebug extends BClass
{
    const EMERGENCY = 0,
        ALERT       = 1,
        CRITICAL    = 2,
        ERROR       = 3,
        WARNING     = 4,
        NOTICE      = 5,
        INFO        = 6,
        DEBUG       = 7;

    static protected $_levelLabels = [
        self::EMERGENCY => 'EMERGENCY',
        self::ALERT     => 'ALERT',
        self::CRITICAL  => 'CRITICAL',
        self::ERROR     => 'ERROR',
        self::WARNING   => 'WARNING',
        self::NOTICE    => 'NOTICE',
        self::INFO      => 'INFO',
        self::DEBUG     => 'DEBUG',
    ];

    const MEMORY  = 0,
        FILE      = 1,
        SYSLOG    = 2,
        EMAIL     = 4,
        OUTPUT    = 8,
        EXCEPTION = 16,
        STOP      = 4096;

    const MODE_DEBUG      = 'DEBUG',
        MODE_DEVELOPMENT  = 'DEVELOPMENT',
        MODE_STAGING      = 'STAGING',
        MODE_PRODUCTION   = 'PRODUCTION',
        MODE_MIGRATION    = 'MIGRATION',
        MODE_INSTALLATION = 'INSTALLATION',
        MODE_RECOVERY     = 'RECOVERY',
        MODE_DISABLED     = 'DISABLED'
    ;

    /**
    * Trigger levels for different actions
    *
    * - memory: remember in immediate script memory
    * - file: write to debug log file
    * - email: send email notification to admin
    * - output: display error in output
    * - exception: stop script execution by throwing exception
    *
    * Default are production values
    *
    * @var array
    */
    static protected $_level;

    static protected $_levelPreset = [
        self::MODE_PRODUCTION => [
            self::MEMORY    => false,
            self::SYSLOG    => false,
            self::FILE      => self::WARNING,
            self::EMAIL     => false, //self::ERROR,
            self::OUTPUT    => self::CRITICAL,
            self::EXCEPTION => self::ERROR,
            self::STOP      => self::CRITICAL,
        ],
        self::MODE_STAGING => [
            self::MEMORY    => false,
            self::SYSLOG    => false,
            self::FILE      => self::WARNING,
            self::EMAIL     => false, //self::ERROR,
            self::OUTPUT    => self::CRITICAL,
            self::EXCEPTION => self::ERROR,
            self::STOP      => self::CRITICAL,
        ],
        self::MODE_DEVELOPMENT => [
            self::MEMORY    => self::INFO,
            self::SYSLOG    => false,
            self::FILE      => self::WARNING,
            self::EMAIL     => false, //self::CRITICAL,
            self::OUTPUT    => self::NOTICE,
            self::EXCEPTION => self::ERROR,
            self::STOP      => self::CRITICAL,
        ],
        self::MODE_DEBUG => [
            self::MEMORY    => self::DEBUG,
            self::SYSLOG    => false,
            self::FILE      => self::WARNING,
            self::EMAIL     => false, //self::CRITICAL,
            self::OUTPUT    => self::NOTICE,
            self::EXCEPTION => self::ERROR,
            self::STOP      => self::CRITICAL,
        ],
        self::MODE_RECOVERY => [
            self::MEMORY    => self::DEBUG,
            self::SYSLOG    => false,
            self::FILE      => self::WARNING,
            self::EMAIL     => false, //self::CRITICAL,
            self::OUTPUT    => self::NOTICE,
            self::EXCEPTION => self::ERROR,
            self::STOP      => self::CRITICAL,
        ],
        self::MODE_MIGRATION => [
            self::MEMORY    => self::DEBUG,
            self::SYSLOG    => false,
            self::FILE      => self::WARNING,
            self::EMAIL     => false, //self::CRITICAL,
            self::OUTPUT    => self::NOTICE,
            self::EXCEPTION => self::ERROR,
            self::STOP      => self::CRITICAL,
        ],
        self::MODE_INSTALLATION => [
            self::MEMORY    => self::DEBUG,
            self::SYSLOG    => false,
            self::FILE      => self::WARNING,
            self::EMAIL     => false, //self::CRITICAL,
            self::OUTPUT    => self::NOTICE,
            self::EXCEPTION => self::ERROR,
            self::STOP      => self::CRITICAL,
        ],
        self::MODE_DISABLED => [
            self::MEMORY    => false,
            self::SYSLOG    => false,
            self::FILE      => false,
            self::EMAIL     => false,
            self::OUTPUT    => false,
            self::EXCEPTION => false,
            self::STOP      => false,
        ],
    ];

    static protected $_modules = [];

    static protected $_mode = 'PRODUCTION';

    static protected $_startTime;
    static protected $_events = [];

    static protected $_logDir = null;
    static protected $_logFile = [
        self::EMERGENCY => 'error.log',
        self::ALERT     => 'error.log',
        self::CRITICAL  => 'error.log',
        self::ERROR     => 'error.log',
        self::WARNING   => 'debug.log',
        self::NOTICE    => 'debug.log',
        self::INFO      => 'debug.log',
        self::DEBUG     => 'debug.log',
    ];

    static protected $_adminEmail = null;

    static protected $_phpErrorMap = [
        E_ERROR => self::ERROR,
        E_WARNING => self::WARNING,
        E_NOTICE => self::NOTICE,
        E_USER_ERROR => self::ERROR,
        E_USER_WARNING => self::WARNING,
        E_USER_NOTICE => self::NOTICE,
        E_STRICT => self::NOTICE,
        E_RECOVERABLE_ERROR => self::ERROR,
    ];

    static protected $_verboseBacktrace = [];

    static protected $_collectedErrors = [];

    static protected $_errorHandlerLog = [];

    /**
    * Constructor, remember script start time for delta timestamps
    *
    * @return BDebug
    */
    public function __construct()
    {
        static::$_startTime = microtime(true);
        $this->BEvents->on('BResponse::output:after', 'BDebug::afterOutput');
    }

    /**
     * Shortcut to help with IDE autocompletion
     *
     * @param bool  $new
     * @param array $args
     * @return BDebug
     */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    public function registerErrorHandlers()
    {
        set_error_handler('BDebug::errorHandler');
        set_exception_handler('BDebug::exceptionHandler');
        register_shutdown_function('BDebug::shutdownHandler');
    }

    public function startErrorLogger()
    {
        static::$_errorHandlerLog = [];
        set_error_handler('BDebug::errorHandlerLogger');
    }

    public function stopErrorLogger()
    {
        set_error_handler('BDebug::errorHandler');
        return static::$_errorHandlerLog;
    }

    public function errorHandlerLogger($code, $message, $file, $line, $context = null)
    {
        return static::$_errorHandlerLog[] = compact('code', 'message', 'file', 'line', 'context');
    }

    static public function errorHandler($code, $message, $file, $line, $context = null)
    {
        if (!(error_reporting() & $code)) {
            return;
        }
        static::trigger(static::$_phpErrorMap[$code], $message, 1);
        //throw new BErrorException(static::$_phpErrorMap[$code], $message, $file, $line, $context);
    }

    static public function exceptionHandler($e)
    {
#echo "<pre>"; print_r($e); exit;
        //static::trigger($e->getCode(), $e->getMessage(), $e->stackPop+1);
        static::trigger(static::ERROR, $e, 1, true);
    }

    static public function shutdownHandler()
    {
        $e = error_get_last();
        if ($e && ($e['type'] === E_ERROR || $e['type'] === E_PARSE || $e['type'] === E_COMPILE_ERROR || $e['type'] === E_COMPILE_WARNING)) {
            static::trigger(static::CRITICAL, $e['file'] . ':' . $e['line'] . ': ' . $e['message'], 1);
        }
    }

    static public function level($type, $level = null)
    {
        if (!isset(static::$_level[$type])) {
            //TODO: check back later
            #throw new BException('Invalid debug level type');
        }
        if (null === $level) {
            if (null === static::$_level) {
                static::$_level = static::$_levelPreset[static::$_mode];
            }
            return static::$_level[$type];
        }
        static::$_level[$type] = $level;
    }

    public function logDir($dir)
    {
        $this->BUtil->ensureDir($dir);
        static::$_logDir = $dir;
    }

    public function log($msg, $file = 'debug.log', $backtrace = false)
    {
        $file = static::$_logDir . '/' . $file;
        error_log($msg . "\n", 3, $file);
        if ($backtrace) {
            ob_start();
            debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            error_log(ob_get_clean(), 3, $file);
        }
    }

    public function logException($e)
    {
        static::log(print_r($e, 1), 'exceptions.log');
    }

    public function adminEmail($email)
    {
        static::$_adminEmail = $email;
    }

    static public function mode($mode = null, $setLevels = true)
    {
        if (null === $mode) {
            return static::$_mode;
        }
        static::$_mode = $mode;
        if ($setLevels && !empty(static::$_levelPreset[$mode])) {
            static::$_level = static::$_levelPreset[$mode];
        }
        return null;
    }

    public function backtraceOn($msg)
    {
        foreach ((array)$msg as $m) {
            static::$_verboseBacktrace[$m] = true;
        }
    }

    static public function trigger($level, $msg, $stackPop = 0, $backtrace = false)
    {
        if ($level !== static::DEBUG) {
            static::$_collectedErrors[$level][] = $msg;
        }
        if (is_scalar($msg)) {
            $e = ['msg' => $msg];
        } elseif (is_object($msg) && $msg instanceof Exception) {
            $bt = $msg->getTrace();
            $msgStr = $msg->getMessage();
            if ($msg instanceof PDOException) {
                $msgStr .= "\nQUERY: " . BORM::get_last_query();
            }
            $e = ['msg' => $msgStr];
        } elseif (is_array($msg)) {
            $e = $msg;
        } else {
            throw new Exception('Invalid message type: ' . print_r($msg, 1));
        }

        //$stackPop++;
        if (empty($bt)) {
            $bt = debug_backtrace(true);
        }
        $e['level'] = static::$_levelLabels[$level];
        if (isset($bt[$stackPop]['file'])) $e['file'] = $bt[$stackPop]['file'];
        if (isset($bt[$stackPop]['line'])) $e['line'] = $bt[$stackPop]['line'];
        //$o = $bt[$stackPop]['object'];
        //$e['object'] = is_object($o) ? get_class($o) : $o;

        $e['ts'] = BDb::i()->now();
        $e['t'] = microtime(true) - static::$_startTime;
        $e['d'] = null;
        $e['c'] = null;
        $e['mem'] = memory_get_usage();

        if ($backtrace || !empty(static::$_verboseBacktrace[$e['msg']])) {
            foreach ($bt as $t) {
                if (!empty($t['file'])) {
                    $e['msg'] .= "\n" . $t['file'] . ':' . $t['line'];
                } elseif (!empty($t['class'])) {
                    $e['msg'] .= "\n" . $t['class'] . $t['type'] . $t['function'];
                }
            }
        }

        $message = "{$e['level']}: {$e['msg']}" . (isset($e['file']) ? " ({$e['file']}:{$e['line']})" : '');
        if (($moduleName = BModuleRegistry::i()->currentModuleName())) {
            $e['module'] = $moduleName;
        }

        if (null === static::$_level && !empty(static::$_levelPreset[static::$_mode])) {
            static::$_level = static::$_levelPreset[static::$_mode];
        }

        $l = static::$_level[static::MEMORY];
        if (false !== $l && (is_array($l) && in_array($level, $l) || $l >= $level)) {
            //$e['msg'] = substr($e['msg'], 0, 50); $e['file'] = ''; $e['line'] = '';
            static::$_events[] = $e;
            $id = sizeof(static::$_events)-1;
        }

        $l = static::$_level[static::SYSLOG];
        if (false !== $l && (is_array($l) && in_array($level, $l) || $l >= $level)) {
            error_log($message, 0, static::$_logDir);
        }

        if (null !== static::$_logDir) { // require explicit enable of file log
            $l = static::$_level[static::FILE];
            if (false !== $l && (is_array($l) && in_array($level, $l) || $l >= $level)) {
                /*
                if (null === static::$_logDir) {
                    static::$_logDir = sys_get_temp_dir();
                }
                */
                $file = static::$_logDir . '/' . static::$_logFile[$level];
                if (is_writable(static::$_logDir) || is_writable($file)) {
                    error_log("{$e['ts']} {$e['mem']} {$e['t']} {$message}\n", 3, $file);
                } else {
                    //TODO: anything needs to be done here?
                }
            }
        }

        if (null !== static::$_adminEmail) { // require explicit enable of email
            $l = static::$_level[static::EMAIL];
            if (false !== $l && (is_array($l) && in_array($level, $l) || $l >= $level)) {
                error_log(print_r($e, 1), 1, static::$_adminEmail);
            }
        }

        $l = static::$_level[static::OUTPUT];
        if (false !== $l && (is_array($l) && in_array($level, $l) || $l >= $level)) {
            echo '<xmp style="text-align:left; border:solid 1px red; font-family:monospace;">';
            ob_start();
            echo htmlspecialchars($message) . "\n";
            debug_print_backtrace();
            $output = ob_get_clean();
            $output = str_replace(['\\', FULLERON_ROOT_DIR . '/'], ['/', ''], $output);
            echo $output;
            echo '</xmp>';
        }
/*
        $l = static::$_level[static::EXCEPTION];
        if (false!==$l && (is_array($l) && in_array($level, $l) || $l>=$level)) {
            if (is_object($msg) && $msg instanceof Exception) {
                throw $msg;
            } else {
                throw new Exception($msg);
            }
        }
*/
        $l = static::$_level[static::STOP];
        if (false !== $l && (is_array($l) && in_array($level, $l) || $l >= $level)) {
            static::i()->dumpLog();
            die;
        }

        return isset($id) ? $id : null;
    }

    static public function alert($msg, $stackPop = 0, $backtrace = false)
    {
        return static::trigger(static::ALERT, $msg, $stackPop + 1);
    }

    static public function critical($msg, $stackPop = 0, $backtrace = false)
    {
        return static::trigger(static::CRITICAL, $msg, $stackPop + 1);
    }

    static public function error($msg, $stackPop = 0, $backtrace = false)
    {
        return static::trigger(static::ERROR, $msg, $stackPop + 1);
    }

    static public function warning($msg, $stackPop = 0, $backtrace = false)
    {
        return static::trigger(static::WARNING, $msg, $stackPop + 1);
    }

    static public function notice($msg, $stackPop = 0, $backtrace = false)
    {
        return static::trigger(static::NOTICE, $msg, $stackPop + 1);
    }

    static public function info($msg, $stackPop = 0, $backtrace = false)
    {
        return static::trigger(static::INFO, $msg, $stackPop + 1);
    }

    static public function debug($msg, $stackPop = 0, $backtrace = false)
    {
        if ('DEBUG' !== static::$_mode) return; // to speed things up
        return static::trigger(static::DEBUG, $msg, $stackPop + 1);
    }

    public function getCollectedErrors($type = self::ERROR)
    {
        if (!empty(static::$_collectedErrors[$type])) {
            return static::$_collectedErrors[$type];
        }
    }

    static public function profile($id)
    {
        if ($id && !empty(static::$_events[$id])) {
            static::$_events[$id]['d'] = microtime(true) - static::$_startTime - static::$_events[$id]['t'];
            static::$_events[$id]['c']++;
        }
    }

    public function is($modes)
    {
        if (is_string($modes)) $modes = explode(',', $modes);
        return in_array(static::$_mode, $modes);
    }

    public function dumpLog($return = false)
    {
        if (!(static::$_mode === static::MODE_DEBUG || static::$_mode === static::MODE_DEVELOPMENT)
            || $this->BResponse->getContentType() !== 'text/html'
            || $this->BRequest->xhr()
        ) {
            return;
        }
        ob_start();
?><style>
#buckyball-debug-trigger { position:fixed; top:0; right:0; font:normal 10px Verdana; cursor:pointer; z-index:999999; background:#ffc; }
#buckyball-debug-console { position:fixed; overflow:auto; top:10px; left:10px; bottom:10px; right:10px; border:solid 2px #f00; padding:4px; text-align:left; opacity:1; background:#FFC; font:normal 10px Verdana; z-index:20000; }
#buckyball-debug-console table { border-collapse: collapse; }
#buckyball-debug-console th, #buckyball-debug-console td { font:normal 10px Verdana; border: solid 1px #ccc; padding:2px 5px;}
#buckyball-debug-console th { font-weight:bold; }
#buckuball-debug-console xmp { margin:0; }
</style>
<div id="buckyball-debug-trigger" onclick="var el=document.getElementById('buckyball-debug-console');el.style.display=el.style.display?'':'none'">[DBG]</div>
<div id="buckyball-debug-console" style="display:none"><?php
        echo "DELTA: " . BDebug::i()->delta() . ', PEAK: ' . memory_get_peak_usage(true) . ', EXIT: ' . memory_get_usage(true);
        echo "<pre>";
        print_r(array_map('htmlspecialchars', BORM::get_query_log()));
        //$this->BEvents->debug();
        echo "</pre>";
        //print_r(static::$_events);
?><table cellspacing="0"><tr><th>Message</th><th>Rel.Time</th><th>Profile</th><th>Memory</th><th>Level</th>
    <th>Relevant Location</th><th>Module</th></tr><?php
        foreach (static::$_events as $e) {
            if (empty($e['file'])) { $e['file'] = ''; $e['line'] = ''; }
            $profile = $e['d'] ? number_format($e['d'], 6) . ($e['c'] > 1 ? ' (' . $e['c'] . ')' : '') : '';
            echo "<tr><td>" . nl2br(htmlspecialchars($e['msg'])) . "</td><td>" . number_format($e['t'], 6)
                . "</td><td>" . $profile . "</td><td>" . number_format($e['mem'], 0)
                . "</td><td>{$e['level']}</td><td>{$e['file']}:{$e['line']}</td><td>"
                . (!empty($e['module']) ? $e['module'] : '') . "</td></tr>";
        }
?></table></div><?php
        $html = ob_get_clean();
        if ($return) {
            return $html;
        } else {
            echo $html;
        }
    }

    /**
    * Delta time from start
    *
    * @return float
    */
    public function delta()
    {
        return microtime(true) - static::$_startTime;
    }

    public function dump($var)
    {
        if (is_array($var) && current($var) instanceof Model) {
            foreach ($var as $k => $v) {
                echo '<hr>' . $k . ':';
                static::dump($v);
            }
        } elseif ($var instanceof Model) {
            echo '<pre>'; print_r($var->as_array()); echo '</pre>';
        } else {
            echo '<pre>'; print_r($var); echo '</pre>';
        }
    }

    public function afterOutput($args)
    {
        static::dumpLog();
        //$args['content'] = str_replace('</body>', static::dumpLog(true).'</body>', $args['content']);
    }
}

class BFtpClient extends BClass
{
    protected $_ftpDirMode = 0775;
    protected $_ftpFileMode = 0664;
    protected $_ftpHost = '';
    protected $_ftpPort = 21;
    protected $_ftpUsername = '';
    protected $_ftpPassword = '';

    public function __construct($config)
    {
        if (!empty($config['hostname'])) {
            $this->_ftpHost = $config['hostname'];
        }
        if (!empty($config['port'])) {
            $this->_ftpPort = $config['port'];
        }
        if (!empty($config['username'])) {
            $this->_ftpUsername = $config['username'];
        }
        if (!empty($config['password'])) {
            $this->_ftpPassword = $config['password'];
        }
    }

    public function upload($from, $to)
    {
        if (!extension_loaded('ftp')) {
            new BException('FTP PHP extension is not installed');
        }

        if (!($conn = ftp_connect($this->_ftpHost, $this->_ftpPort))) {
            throw new BException('Could not connect to FTP host');
        }

        if (!@ftp_login($conn, $this->_ftpUsername, $this->_ftpPassword)) {
            ftp_close($conn);
            throw new BException('Could not login to FTP host');
        }

        if (!ftp_chdir($conn, $to)) {
            ftp_close($conn);
            throw new BException('Could not navigate to ' . $to);
        }

        $errors = $this->uploadDir($conn, $from . '/');
        ftp_close($conn);

        return $errors;
    }

    public function uploadDir($conn, $source, $ftpPath = '')
    {
        $errors = [];
        $dir = opendir($source);
        while ($file = readdir($dir)) {
            if ($file == '.' || $file == "..") {
                continue;
            }

            if (!is_dir($source . $file)) {
                if (@ftp_put($conn, $file, $source . $file, FTP_BINARY)) {
                    // all is good
                    #ftp_chmod($conn, $this->_ftpFileMode, $file);
                } else {
                    $errors[] = ftp_pwd($conn) . '/' . $file;
                }
                continue;
            }
            if (@ftp_chdir($conn, $file)) {
                // all is good
            } elseif (@ftp_mkdir($conn, $file)) {
                ftp_chmod($conn, $this->_ftpDirMode, $file);
                ftp_chdir($conn, $file);
            } else {
                $errors[] = ftp_pwd($conn) . '/' . $file . '/';
                continue;
            }
            $errors += $this->uploadDir($conn, $source . $file . '/', $ftpPath . $file . '/');
            ftp_chdir($conn, '..');
        }
        return $errors;
    }
}

/**
* Throttle invalid login attempts and potentially notify user and admin
*
* Usage:
* - BEFORE AUTH: if (!$this->BLoginThrottle->init('FCom_Customer_Model_Customer', $username)) return false;
* - ON FAILURE:  $this->BLoginThrottle->failure();
* - ON SUCCESS:  $this->BloginThrottle->success();
*/
class BLoginThrottle extends BClass
{
    protected $_all;
    protected $_area;
    protected $_username;
    protected $_rec;
    protected $_config;
    protected $_blockedIPs = [];
    protected $_cachePrefix = 'BLoginThrottle/';

    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return BLoginThrottle
    */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    public function __construct()
    {
        $c = $this->BConfig->get('modules/BLoginThrottle');

        if (empty($c['sleep_sec'])) $c['sleep_sec'] = 2; // lock record for 2 secs after failed login
        if (empty($c['brute_attempts_max'])) $c['brute_attempts_max'] = 3; // after 3 fast attempts do something
        if (empty($c['reset_time'])) $c['reset_time'] = 10; // after 10 secs reset record

        $this->_config = $c;
    }

    public function config($config)
    {
        $this->_config = $this->BUtil->arrayMerge($this->_config, $config);
    }

    public function init($area, $username)
    {
        usleep(mt_rand(0, 10000)); // timing side channel attack protection, 10ms should be enough to cover db calls

        $now = time();
        $c = $this->_config;

        $this->_area = $area;
        $this->_username = $username;
        $this->_rec = $this->_load();

        if ($this->_rec) {
            if ($this->_rec['status'] === 'FAILED') {
                if (empty($this->_rec['brute_attempts_cnt'])) {
                    $this->_rec['brute_attempts_cnt'] = 1;
                } else {
                    $this->_rec['brute_attempts_cnt']++;
                }
                $this->_save();
                $this->_fire('init:brute');
                if ($this->_rec['brute_attempts_cnt'] == $c['brute_attempts_max']) {
                    $this->_fire('init:brute_max');
                }
                return false; // currently locked
            }
        }
        return true; // init OK
    }

    public function success()
    {
        $this->_fire('success');
        $this->_reset();
        return true;
    }

    public function failure($sleepSec = null)
    {
        $username = $this->_username;
        $now = time();
        $c = $this->_config;

        $this->_fire('fail:before');

        if (empty($this->_rec['attempt_cnt'])) {
            $this->_rec['attempt_cnt'] = 1;
        } else {
            $this->_rec['attempt_cnt']++;
        }
        $this->_rec['last_attempt'] = $now;
        $this->_rec['status'] = 'FAILED';
        $this->_save();
        $this->_fire('fail:wait');

        $this->_gc();
        sleep(null !== $sleepSec ? $sleepSec : $c['sleep_sec']);

        $this->_rec['status'] = '';
        $this->_save();
        $this->_fire('fail:after');

        return true; // normal response
    }

    protected function _fire($event)
    {
        $this->BEvents->fire('BLoginThrottle::' . $event, [
            'area'     => $this->_area,
            'username' => $this->_username,
            'rec'      => $this->_rec,
            'config'   => $this->_config,
        ]);
    }

    protected function _load()
    {
        $key = $this->_area . '/' . $this->_username;
        return $this->BCache->load($this->_cachePrefix . $key);
    }

    protected function _save()
    {
        $key = $this->_area . '/' . $this->_username;
        return $this->BCache->save($this->_cachePrefix . $key, $this->_rec, $this->_config['reset_time']);
    }

    protected function _reset()
    {
        $key = $this->_area . '/' . $this->_username;
        return $this->BCache->delete($key);
    }

    protected function _gc()
    {

        return true;
    }
}

/**
* Falls back to pecl extensions: yaml, syck
* Uses libraries: spyc, symphony\yaml (not included)
*/
class BYAML extends BCLass
{
    static protected $_peclYaml = null;
    static protected $_peclSyck = null;

    public function load($filename, $cache = true)
    {
        //$filename1 = realpath($filename);
        //if (!$filename1) {
        if (!file_exists($filename)) {
            BDebug::debug('BCache load: file does not exist: ' . $filename);
            return false;
        }
        //$filename = $filename1;

        $filemtime = filemtime($filename);

        if ($cache) {
            $cacheData = $this->BCache->load('BYAML--' . $filename);
            if (!empty($cacheData) && !empty($cacheData['v']) && $cacheData['v'] === $filemtime) {
                return $cacheData['d'];
            }
        }

        $yamlData = file_get_contents($filename);
        $yamlData = str_replace("\t", '    ', $yamlData); //TODO: make configurable tab size
        $arrayData = static::parse($yamlData);

        if ($cache) {
            $this->BCache->save('BYAML--' . $filename, ['v' => $filemtime, 'd' => $arrayData], false);
        }

        return $arrayData;
    }

    public function init()
    {
        if (null === static::$_peclYaml) {
            static::$_peclYaml = function_exists('yaml_parse');

            if (!static::$_peclYaml) {
                static::$_peclSyck = function_exists('syck_load');
            }

            if (!static::$_peclYaml && !static::$_peclSyck) {
                require_once(__DIR__ . '/lib/spyc.php');
                /*
                require_once(__DIR__.'/Yaml/Exception/ExceptionInterface.php');
                require_once(__DIR__.'/Yaml/Exception/RuntimeException.php');
                require_once(__DIR__.'/Yaml/Exception/DumpException.php');
                require_once(__DIR__.'/Yaml/Exception/ParseException.php');
                require_once(__DIR__.'/Yaml/Yaml.php');
                require_once(__DIR__.'/Yaml/Parser.php');
                require_once(__DIR__.'/Yaml/Dumper.php');
                require_once(__DIR__.'/Yaml/Escaper.php');
                require_once(__DIR__.'/Yaml/Inline.php');
                require_once(__DIR__.'/Yaml/Unescaper.php');
                */
            }
        }
        return true;
    }

    public function parse($yamlData)
    {
        static::init();

        if (static::$_peclYaml) {
            return yaml_parse($yamlData);
        } elseif (static::$_peclSyck) {
            return syck_load($yamlData);
        }

        if (class_exists('Spyc', false)) {
            return Spyc::YAMLLoadString($yamlData);
        } else {
            return Symfony\Component\Yaml\Yaml::parse($yamlData);
        }
    }

    public function dump($arrayData)
    {
        static::init();

        if (static::$_peclYaml) {
            return yaml_emit($arrayData);
        } elseif (static::$_peclSyck) {
            return syck_dump($arrayData);
        }

        if (class_exists('Spyc', false)) {
            return Spyc::YAMLDump($arrayData);
        } else {
            return Symfony\Component\Yaml\Yaml::dump($arrayData);
        }
    }
}

class BValidate extends BClass
{
    protected $_reRegex = '#^([/\#~&,%])(.*)(\1)[imsxADSUXJu]*$#';
    protected $_defaultRules = [
        'required' => [
            'rule'    => 'BValidate::ruleRequired',
            'message' => 'Missing field: :field',
        ],
        'url'       => [
            'rule'    => '#(([\w]+:)?//)?(([\d\w]|%[a-fA-f\d]{2,2})+(:([\d\w]|%[a-fA-f\d]{2,2})+)?@)?([\d\w][-\d\w]{0,253}[\d\w]\.)+[\w]{2,4}(:[\d]+)?(/([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)*(\?(&?([-+_~.\d\w]|%[a-fA-f\d]{2,2})=?)*)?(\#([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)?#',
            'message' => 'Invalid URL',
        ],
        'email'     => [
            'rule'    => 'BValidate::ruleEmail',
            'message' => 'Invalid Email',
        ],
        'string'    => [
            'rule'    => 'BValidate::ruleString',
            'message' => 'Invalid string length', // this is default, actual message supplied by callback
        ],
        'numeric'   => [
            'rule'    => '/^([+-]?)([0-9 ]+)(\.?,?)([0-9]*)$/',
            'message' => 'Invalid number: :field',
        ],
        'integer'   => [
            'rule'    => '/^[+-]?[0-9]+$/',
            'message' => 'Invalid integer: :field',
        ],
        'alphanum'  => [
            'rule'    => '/^[a-zA-Z0-9 ]+$/',
            'message' => 'Invalid alphanumeric: :field',
        ],
        'alpha'  => [
            'rule'    => '/^[a-zA-Z ]+$/',
            'message' => 'Invalid alphabet field: :field',
        ],
        'password_confirm' => [
            'rule'    => 'BValidate::rulePasswordConfirm',
            'message' => 'Password confirmation does not match',
            'args'    => ['original' => 'password'],
        ],
    ];

    protected $_defaultMessage = "Validation failed for: :field";
    protected $_expandedRules = [];

    protected $_validateErrors = [];

    /**
     * @param bool  $new
     * @param array $args
     * @return BValidate
     */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    public function addValidator($name, $rule)
    {
        $this->_defaultRules[$name] = $rule;
        return $this;
    }

    protected function _expandRules($rules)
    {
        $this->_expandedRules = [];
        foreach ($rules as $rule) {
            if (!empty($rule[0]) && !empty($rule[1])) {
                $r = $rule;
                $rule = ['field' => $r[0], 'rule' => $r[1]];
                if (isset($r[2])) $rule['message'] = $r[2];
                if (isset($r[3])) $rule['args'] = $r[3];
                if (isset($rule['args']) && is_string($rule['args'])) {
                    $rule['args'] = [$rule['args'] => true];
                }
            }
            if (is_string($rule['rule']) && $rule['rule'][0] === '@') {
                $ruleName = substr($rule['rule'], 1);
                if (empty($this->_defaultRules[$ruleName])) {
                    throw new BException('Invalid rule name: ' . $ruleName);
                }
                $defRule = $this->_defaultRules[$ruleName];
                $rule = $this->BUtil->arrayMerge($defRule, $rule);
                $rule['rule'] = $defRule['rule'];
            }
            if (empty($rule['message'])) $rule['message'] = $this->_defaultMessage;
            $this->_expandedRules[] = $rule;
        }
    }

    protected function _validateRules($data)
    {
        $this->_validateErrors = [];
        foreach ($this->_expandedRules as $r) {
            $args = !empty($r['args']) ? $r['args'] : [];
            $r['args']['field'] = $r['field']; // for callback and message vars

            if (is_string($r['rule']) && preg_match($this->_reRegex, $r['rule'], $m)) {

                $result = empty($data[$r['field']]) || preg_match($m[0], (string)$data[$r['field']]);

            } elseif ($r['rule'] instanceof Closure) {

                $result = $r['rule']($data, $r['args']);

            } elseif (is_callable($r['rule'])) {

                $result = $this->BUtil->call($r['rule'], [$data, $r['args']], true);

            } elseif (is_string($r['rule'])) {

                $callback = $this->BUtil->extCallback($r['rule']);
                if ($callback !== $r['rule']) {
                    $result = $this->BUtil->call($r['rule'], [$data, $r['args']], true);
                } else {
                    throw new BException('Invalid rule: ' . print_r($r['rule'], 1));
                }

            } else {

                throw new BException('Invalid rule: ' . print_r($r['rule'], 1));

            }

            if (is_string($result)) {
                $r['message'] = $result;
                $result = false;
            }

            if (!$result) {
                $this->_validateErrors[$r['field']][] = $this->BUtil->injectVars($r['message'], $r['args']);
                if (!empty($r['args']['break'])) {
                    break;
                }
            }
        }
    }

    /**
     * Validate passed data
     *
     * $data is an array of key value pairs.
     * Keys will be matched against rules.
     * <code>
     * // data
     * array (
     *  'firstname' => 'John',
     *  'lastname' => 'Doe',
     *  'email' => 'test@example.com',
     *  'url' => 'http://example.com/test?foo=bar#baz',
     *  'password' => '12345678',
     *  'password_confirm' => '12345678',
     * );
     *
     * // rules in format: ['field', 'rule', ['message'], [ 'break' | 'arg1' => 'val1' ] ]
     * $rules = array(
     *   array('email', '@required'),
     *   array('email', '@email'),
     *   array('url', '@url'),
     *   array('firstname', '@required', 'Missing First Name'),
     *   array('firstname', '/^[A-Za-z]+$/', 'Invalid First Name', 'break'),
     *   array('password', '@required', 'Missing Password'),
     *   array('password_confirm', '@password_confirm'),
     * );
     * </code>
     *
     * Rule can be either string that resolves to callback, regular expression or closure.
     * Allowed pattern delimiters for regular expression are: /\#~&,%
     * Allowed regular expression modifiers are: i m s x A D S U X J u
     * e and E modifiers are NOT allowed. Any exptression using them will not work.
     *
     * Callbacks can be either: Class::method for static method call or Class.method | Class->method for instance call
     *
     * @param array $data
     * @param array $rules
     * @param null  $formName
     * @return bool
     */
    public function validateInput($data, $rules, $formName = null)
    {
        $this->_expandRules($rules);

        $this->_validateRules($data);

        if ($this->_validateErrors && $formName) {
            $this->BSession->set('validator-data:' . $formName, $data);
            foreach ($this->_validateErrors as $field => $errors) {
                foreach ($errors as $error) {
                    $msg = compact('error', 'field');
                    $this->BSession->addMessage($msg, 'error', 'validator-errors:' . $formName);
                }
            }
        }
        return $this->_validateErrors ? false : true;
    }

    public function validateErrors()
    {
        return $this->_validateErrors;
    }

    public function ruleRequired($data, $args)
    {
        if (!isset($data[$args['field']])) {
            return false;
        }
        if (is_numeric($data[$args['field']])) {
            return true;
        }
        return !empty($data[$args['field']]);
    }

    public function rulePasswordConfirm($data, $args)
    {
        return empty($data[$args['original']])
            || !empty($data[$args['field']]) && $data[$args['field']] === $data[$args['original']];
    }

    public function ruleString($data, $args)
    {
        if (!isset($data[$args['field']])) {
            return true;
        }
        $value = $data[$args['field']];
        if (!empty($args['min']) && strlen($value) < $args['min']) {
            return 'The field should be at least ' . $args['min'] . ' characters long: :field';
        }
        if (!empty($args['max']) && strlen($value) > $args['max']) {
            return 'The field can not exceed ' . $args['max'] . ' characters: :field';
        }
        return true;
    }

    public function ruleEmail($data, $args)
    {
        if (!isset($data[$args['field']])) {
            return true;
        }
        $value = $data[$args['field']];
        $re = '/^([\w-\.\+]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/';
        if (strlen($value) > 255 || !preg_match($re, $value)) {
            return false;
        }
        return true;
    }
}

/**
 * Class BValidateViewHelper
 *
 *
 */
class BValidateViewHelper extends BClass
{
    protected $_errors = [];
    protected $_data = [];

    public function __construct($args)
    {
        if (!isset($args['form'])) {
            return;
        }
        if (isset($args['data'])) {
            if (is_object($args['data'])) {
                $args['data'] = $args['data']->as_array();
            }
            $this->_data = $args['data'];
        }

        $sessionHlp = $this->BSession;
        $errors     = $sessionHlp->messages('validator-errors:' . $args['form']);
        $formData   = $sessionHlp->get('validator-data:' . $args['form']);
        $this->_data = $this->BUtil->arrayMerge($this->_data, $formData);
        $sessionHlp->set('validator-data:' . $args['form'], null);

        foreach ($errors as $error) {
            $field                 = $error['msg']['field'];
            $error['value']        = !empty($formData[$field]) ? $formData[$field] : null;
            $this->_errors[$field] = $error;
        }
    }

    public function fieldClass($field)
    {
        if (empty($this->_errors[$field]['type'])) {
            return '';
        }
        return $this->_errors[$field]['type'];
    }

    public function fieldValue($field)
    {
        return !empty($this->_data[$field]) ? $this->_data[$field] : null;
    }

    public function messageClass($field)
    {
        if (empty($this->_errors[$field]['type'])) {
            return '';
        }
        return $this->_errors[$field]['type'];
    }

    public function messageText($field)
    {
        if (empty($this->_errors[$field]['msg']['error'])) {
            return '';
        }
        return $this->BLocale->_($this->_errors[$field]['msg']['error']);
    }

    /**
     * @param string $field form field name
     * @param string $fieldId form field ID
     * @return string
     */
    public function errorHtml($field, $fieldId)
    {
        $html = '';

        if (!empty($this->_errors[$field]['type'])) {
            $html .= $this->BUtil->tagHtml('label', ['for' => $fieldId, 'class' => $this->messageClass($field)],
                $this->messageText($field));
        }

        return $html;
    }
}

/*
class BEnv extends BClass
{
    public $app;
    public $autoload;
    public $config;
    public $db;
    public $debug;
    public $layout;
    public $modReg;
    public $request;
    public $response;
    public $session;
    public $util;

    public function __construct(BApp $app, BClassAutoload $autoload, BConfig $config, BDb $db, BDebug $debug,
        BLayout $layout, BModuleRegistry $modReg, BRequest $request, BResponse $response, BSession $session,
        BUtil $util)
    {
        $this->app = $app;
        $this->autoload = $autoload;
        $this->config = $config;
        $this->db = $db;
        $this->debug = $debug;
        $this->layout = $layout;
        $this->modReg = $modReg;
        $this->request = $request;
        $this->response = $response;
        $this->session = $session;
        $this->util = $util;
    }
}
*/

/**
 * If FISMA/FIPS/NIST compliance required, use PBKDF2
 *
 * @see http://stackoverflow.com/questions/4795385/how-do-you-use-bcrypt-for-hashing-passwords-in-php
 */
class Bcrypt extends BClass
{
    public function __construct()
    {
        if (CRYPT_BLOWFISH != 1 && !function_exists('password_hash')) {
            throw new Exception("bcrypt not supported in this installation. See http://php.net/crypt");
        }
    }

    public function hash($input)
    {
        $hash = crypt($input, $this->getSalt());
        return strlen($hash) > 13 ? $hash : false;
    }

    public function verify($input, $existingHash)
    {
        // md5 for protection against timing side channel attack (needed)
        return md5(crypt($input, $existingHash)) === md5($existingHash);
    }

    private function getSalt()
    {
        // The security weakness between 5.3.7 affects password with 8-bit characters only
        // @see: http://php.net/security/crypt_blowfish.php
        $salt = '$' . (version_compare(phpversion(), '5.3.7', '>=') ? '2y' : '2a') . '$12$';
        $salt .= $this->encodeBytes($this->getRandomBytes(16));
        return $salt;
    }

    private $randomState;
    private function getRandomBytes($count)
    {
        $bytes = '';

        if (function_exists('openssl_random_pseudo_bytes') &&
            (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')) { // OpenSSL slow on Win
            $bytes = openssl_random_pseudo_bytes($count);
        }

        if ($bytes === '' && is_readable('/dev/urandom') &&
            ($hRand = @fopen('/dev/urandom', 'rb')) !== FALSE) {
            $bytes = fread($hRand, $count);
            fclose($hRand);
        }

        if (strlen($bytes) < $count) {
            $bytes = '';

            if ($this->randomState === null) {
                $this->randomState = microtime();
                if (function_exists('getmypid')) {
                    $this->randomState .= getmypid();
                }
            }

            for ($i = 0; $i < $count; $i += 16) {
                $this->randomState = md5(microtime() . $this->randomState);

                $bytes .= md5($this->randomState, true);
            }

            $bytes = substr($bytes, 0, $count);
        }

        return $bytes;
    }

    private function encodeBytes($input)
    {
        // The following is code from the PHP Password Hashing Framework
        $itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

        $output = '';
        $i = 0;
        do {
            $c1 = ord($input[$i++]);
            $output .= $itoa64[$c1 >> 2];
            $c1 = ($c1 & 0x03) << 4;
            if ($i >= 16) {
                $output .= $itoa64[$c1];
                break;
            }

            $c2 = ord($input[$i++]);
            $c1 |= $c2 >> 4;
            $output .= $itoa64[$c1];
            $c1 = ($c2 & 0x0f) << 2;

            $c2 = ord($input[$i++]);
            $c1 |= $c2 >> 6;
            $output .= $itoa64[$c1];
            $output .= $itoa64[$c2 & 0x3f];
        } while (1);

        return $output;
    }
}

class BRSA extends BClass
{
    protected $_configPath = 'modules/BRSA';
    protected $_config = [];
    protected $_publicKey;
    protected $_privateKey;
    protected $_cache = [];

    public function __construct()
    {
        if (!function_exists('openssl_pkey_new')) {
            // TODO: integrate Crypt_RSA ?
            throw new BException('RSA encryption requires openssl module installed');
        }
        $defConf = [
            "digest_alg" => "sha512",
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];
        $conf = $this->BConfig->get($this->_configPath);
        $this->_config = array_merge($defConf, $conf);
    }

    public function generateKey()
    {
        $config = $this->BUtil->arrayMask($this->_config, 'digest_alg,x509_extensions,req_extensions,'
            . 'private_key_bits,private_key_type,encrypt_key,encrypt_key_cipher');
        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $this->_privateKey); // private key

        $pubKey = openssl_pkey_get_details($res); // public key
        $this->_publicKey = $pubKey["key"];

        $this->BConfig->set($this->_configPath . '/public_key', $this->_publicKey, false, true);

        file_put_contents($this->_getPrivateKeyFileName(), $this->_privateKey);

        return $this;
    }

    protected  function _getPublicKey()
    {
        if (!$this->_publicKey) {
            $this->_publicKey = $this->BConfig->get($this->_configPath . '/public_key');
            if (!$this->_publicKey) {
                throw new BException('No public key defined');
            }
        }
        return $this->_publicKey;
    }

    protected function _getPrivateKeyFileName()
    {
        $configDir = $this->BConfig->get('fs/config_dir');
        if (!$configDir) {
            $configDir = '.';
        }
        return $configDir . '/private-' . md5($this->_getPublicKey()) . '.key';
    }

    protected function _getPrivateKey()
    {
        if (!$this->_privateKey) {
            $filepath = $this->_getPrivateKeyFileName();
            if (!is_readable($filepath)) {
                throw new BException('No private key file found');
            }
            $this->_privateKey = file_get_contents($filepath);
        }
        return $this->_privateKey;
    }

    public function setPublicKey($key)
    {
        $this->_publicKey = $key;
        return $this;
    }

    public function setPrivateKey($key)
    {
        $this->_privateKey = $key;
        return $this;
    }

    public function encrypt($plain)
    {
        openssl_public_encrypt($plain, $encrypted, $this->_getPublicKey());
        return $encrypted;
    }

    /**
     * Decrypt data
     *
     * Use buckyball/ssl/offsite-decrypt.php script for much improved security
     *
     * @param string $encrypted
     * @return string
     */
    public function decrypt($encrypted)
    {
        $hash = sha1($encrypted);
        if (!empty($this->_cache[$hash])) {
            return $this->_cache[$hash];
        }
        // even though decrypt_url can potentially be overridden by extension, only encrypted data is sent over
        if (!empty($this->_config['decrypt_url'])) {
            $data = ['encrypted' => base64_encode($encrypted)];
            $result = $this->BUtil->remoteHttp('GET', $this->_config['decrypt_url'], $data);
            $decrypted = base64_decode($result);
            if (!empty($result['decrypted'])) {
                $decrypted = $result['decrypted'];
            } else {
                //TODO: handle exceptions
            }
        } else {
            openssl_private_decrypt($encrypted, $decrypted, $this->_getPrivateKey());
        }
        $this->_cache[$hash] = $decrypted;
        return $decrypted;
    }
}

if (!function_exists('xmlentities')) {
    /**
     * @see http://www.php.net/manual/en/function.htmlentities.php#106535
     */
    function xmlentities($string) {
        $not_in_list = "A-Z0-9a-z\s_-";
        return preg_replace_callback("/[^{$not_in_list}]/" , 'get_xml_entity_at_index_0' , $string);
    }
    function get_xml_entity_at_index_0($CHAR) {
        if (!is_string($CHAR[0]) || (strlen($CHAR[0]) > 1)) {
            die("function: 'get_xml_entity_at_index_0' requires data type: 'char' (single character). '{$CHAR[0]}' does not match this type.");
        }
        switch ($CHAR[0]) {
            case "'":    case '"':    case '&':    case '<':    case '>':
                return htmlspecialchars($CHAR[0], ENT_QUOTES);    break;
            default:
                return numeric_entity_4_char($CHAR[0]);                break;
        }
    }
    function numeric_entity_4_char($char) {
        return "&#" . str_pad(ord($char), 3, '0', STR_PAD_LEFT) . ";";
    }
}

if (!function_exists('password_hash')) {
    /**
     * If FISMA/FIPS/NIST compliance required, use PBKDF2
     *
     * @see http://stackoverflow.com/questions/4795385/how-do-you-use-bcrypt-for-hashing-passwords-in-php
     */
    function password_hash($password)
    {
        return $this->Bcrypt->hash($password);
    }

    function password_verify($password, $hash)
    {
        return $this->Bcrypt->verify($password, $hash);
    }
}

if (!function_exists('hash_hmac')) {
    /**
     * HMAC hash, works if hash extension is not installed
     *
     * Supports SHA1 and MD5 algos
     *
     * @see http://www.php.net/manual/en/function.hash-hmac.php#93440
     *
     * @param         $algo
     * @param string  $data Data to be hashed.
     * @param string  $key Hash key.
     * @param boolean $raw_output Return raw or hex
     *
     * @access public
     * @static
     *
     * @return string Hash
     */
    function hash_hmac($algo, $data, $key, $raw_output = false)
    {
        $algo = strtolower($algo);
        $pack = 'H' . strlen($algo('test'));
        $size = 64;
        $opad = str_repeat(chr(0x5C), $size);
        $ipad = str_repeat(chr(0x36), $size);

        if (strlen($key) > $size) {
            $key = str_pad(pack($pack, $algo($key)), $size, chr(0x00));
        } else {
            $key = str_pad($key, $size, chr(0x00));
        }

        for ($i = 0; $i < strlen($key) - 1; $i++) {
            $opad[$i] = $opad[$i] ^ $key[$i];
            $ipad[$i] = $ipad[$i] ^ $key[$i];
        }

        $output = $algo($opad . pack($pack, $algo($ipad . $data)));

        return ($raw_output) ? pack($pack, $output) : $output;
    }
}

if (!function_exists('hash_pbkdf2')) {
    /**
     * PBKDF2 key derivation function as defined by RSA's PKCS #5: https://www.ietf.org/rfc/rfc2898.txt
     *
     * Test vectors can be found here: https://www.ietf.org/rfc/rfc6070.txt
     *
     * This implementation of PBKDF2 was originally created by defuse.ca
     * With improvements by variations-of-shadow.com
     *
     * @see http://www.php.net/manual/en/function.hash-hmac.php#109260
     *
     * @param string $algorithm - The hash algorithm to use. Recommended: SHA256
     * @param string $password - The password.
     * @param string $salt - A salt that is unique to the password.
     * @param integer $count - Iteration count. Higher is better, but slower. Recommended: At least 1024.
     * @param integer $key_length - The length of the derived key in bytes.
     * @param boolean $raw_output - If true, the key is returned in raw binary format. Hex encoded otherwise.
     * @return A $key_length-byte key derived from the password and salt.
     */
    function hash_pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output = false)
    {
        if (function_exists('openssl_pbkdf2')) {
            return openssl_pbkdf2($password, $salt, $key_length, $count, $algorithm);
        }
        $algorithm = strtolower($algorithm);
        if (!in_array($algorithm, hash_algos(), true))
            die('PBKDF2 ERROR: Invalid hash algorithm.');
        if ($count <= 0 || $key_length <= 0)
            die('PBKDF2 ERROR: Invalid parameters.');

        $hash_length = strlen(hash($algorithm, "", true));
        $block_count = ceil($key_length / $hash_length);

        $output = "";
        for ($i = 1; $i <= $block_count; $i++) {
            // $i encoded as 4 bytes, big endian.
            $last = $salt . pack("N", $i);
            // first iteration
            $last = $xorsum = hash_hmac($algorithm, $last, $password, true);
            // perform the other $count - 1 iterations
            for ($j = 1; $j < $count; $j++) {
                $xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
            }
            $output .= $xorsum;
        }

        if ($raw_output)
            return substr($output, 0, $key_length);
        else
            return bin2hex(substr($output, 0, $key_length));
    }
}

if (!function_exists('oath_hotp')) {
    /**
     * Yet another OATH HOTP function. Has a 64 bit counter.
     *
     * @see http://www.php.net/manual/en/function.hash-hmac.php#108978
     *
     * @param string  $secret Shared secret
     * @param         $counter
     * @param integer $len OTP length
     * @return string
     */
    function oath_hotp($secret, $counter, $len = 8)
    {
        $binctr = pack ('NNC*', $counter>>32, $counter & 0xFFFFFFFF);
        $hash = hash_hmac ("sha1", $binctr, $secret);
        // This is where hashing stops and truncation begins
        $ofs = 2 * hexdec (substr ($hash, 39, 1));
        $int = hexdec (substr ($hash, $ofs, 8)) & 0x7FFFFFFF;
        $pin = substr ($int, - $len);
        $pin = str_pad ($pin, $len, "0", STR_PAD_LEFT);
        return $pin;
    }
}
