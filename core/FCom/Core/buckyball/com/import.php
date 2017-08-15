<?php

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
 * Class BImport
 *
 * @property FCom_Core_Main $FCom_Core_Main
 */

class BImport extends BClass
{
    protected $_fields = [];
    protected $_dir = 'shared';
    protected $_model = '';

    const STATUS_IDLE = 'idle';

    const STATUS_STOPPED = 'stopped';

    const STATUS_RUNNING = 'running';

    const STATUS_DONE = 'done';

    public function getFieldData()
    {
        $this->BEvents->fire(__METHOD__, ['fields' => &$this->_fields]);
        return $this->_fields;
    }

    public function getFieldOptions()
    {
        $options = [];
        foreach ($this->getFieldData() as $f => $_) {
            $options[$f] = $f;
        }
        return $options;
    }

    public function getImportDir()
    {
        $dir = $this->BApp->storageRandomDir() . '/import/' . $this->_dir;
        $this->BUtil->ensureDir($dir);
        return $dir;
    }

    public function updateFieldsDueToInfo($info)
    {
        //use in child classes
    }

    public function getFileInfo($file)
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if(isset($this->allowedFileTypes) && !in_array($ext, $this->allowedFileTypes, false)){
            return false;
        }
        // assume we know nothing about the file
        $info = [];

        $importDir = str_replace('\\', '/', $this->getImportDir());
        $realFile = str_replace('\\', '/', realpath($file));
        if (!preg_match('#^' . preg_quote($importDir, '#') . '#', $realFile)) {
            return false;
        }

        // open file for reading
        if (!file_exists($file)) {
            return false;
        }
        $fp = fopen($file, 'rb');
        // get first line in the file
        $r = fgets($fp);
        fclose($fp);
        $row = [];
        foreach (["\t", ',', ';', '|'] as $chr) {
            $row = str_getcsv($r, $chr);
            if (count($row) > 1) {
                $info['delim'] = $chr;
                break;
            }
        }
        // if delimiter not known, can't parse the file
        if (empty($info['delim'])) {
            return false;
        }
        // save first row data
        $info['first_row'] = $row;
        // find likely column names
        $this->updateFieldsDueToInfo($info);
        $fields = $this->getFieldData();
        foreach ($row as $i => $v) {
            foreach ($fields as $f => $fd) {
                if (!empty($fd['pattern']) && empty($fd['used'])
                    && preg_match("#{$fd['pattern']}#i", $v)
                ) {
                    $info['columns'][$i] = $f;
                    $fields[$f]['used'] = true;
                    break;
                }
            }
        }
        // if no column names found, do not skip first row
        $info['skip_first'] = !empty($info['columns']);
        return $info;
    }

    /**
     * @param null|false|array $config
     * @param bool $update
     * @return array|bool|mixed
     */
    public function config($config = null, $update = false)
    {
        $dir = $this->BConfig->get('fs/storage_dir') . '/run/' . $this->_dir;
        $file = $this->BSession->sessionId() . '.json';
        $filename = $dir . '/' . $file;

        if (!$this->BUtil->isPathWithinRoot($filename, '@storage_dir/run')) {
            return false;
        }
        $this->BUtil->ensureDir($dir);

        if ($config) { // create config lock
            if ($update) {
                $old = $this->config();
                $config = array_replace_recursive($old, $config);
            }
            if (empty($config['status'])) {
                $config['status'] = self::STATUS_IDLE;
            }
            return (boolean) file_put_contents($filename, $this->BUtil->toJson($config));
        } else if ($config === false) { // remove config lock
            unlink($filename);
            return true;
        } elseif (!file_exists($filename)) { // no config
            return false;
        } else { // config exists
            $contents = file_get_contents($filename);
            $config = $this->BUtil->fromJson($contents);
            return $config;
        }
    }

    public function run()
    {
        #$this->BSession->close();
        session_write_close();
        ignore_user_abort(true);
        set_time_limit(0);
        ob_implicit_flush();
        //gc_enable();
        $this->BDb->connect();

        //disable debug
        $oldDebugMode = $this->BDebug->mode();
        $this->BDebug->mode('DISABLED');

        $timer = microtime(true);

        if (empty($this->_model)) {
            throw new BException("Model is required");
        }

        $modelClass = $this->_model;
        $model = $modelClass::i();

        if (!method_exists($model, 'import')) {
            throw new BException("Model should implement import method");
        }
        $config = $this->config();
        $importDir = $this->getImportDir();
        $filename = $importDir . '/' . $config['filename'];
        if (!$this->BUtil->isPathWithinRoot($filename, $importDir)) {
            throw new BException('Invalid file location');
        }
        $fp = fopen($filename, 'rb');
        $status = [
            'start_time' => time(),
            'status' => self::STATUS_RUNNING,
            'rows_total' => $this->getLinesCount($fp),// file() will load entire file in memory, may be not good idea???
            'rows_processed' => 0,
            'rows_skipped' => 0,
            'rows_warning' => 0,
            'rows_error' => 0,
            'rows_nochange' => 0,
            'rows_created' => 0,
            'rows_updated' => 0,
            'memory_usage' => memory_get_usage(),
            'memory_peak_usage' => memory_get_peak_usage(),
            'run_time' => 0,
            'errors' => '', 'current_file' => $config['filename']
        ];
        $this->config($status, true);
        if (!empty($config['skip_first'])) {
            for ($i = 0; $i < $config['skip_first']; $i++) {
                fgets($fp);
                $status['rows_skipped']++;
            }
        }

        $importConfig = [];

        $statusUpdate = 50;
        if ($config['batch_size']) {
            $statusUpdate = $config['batch_size'];
        } else {
            $config['batch_size'] = $statusUpdate;
        }

        if ($config['multivalue_separator']) {
            $importConfig['format']['multivalue_separator'] = $config['multivalue_separator'];
        }

        if ($config['nesting_separator']) {
            $importConfig['format']['nesting_separator'] = $config['nesting_separator'];
        }

        $dataBatch = [];
        while (($r = fgetcsv($fp, 0, $config['delim']))) {

            if (empty($config['columns']) || count($r) !== count($config['columns'])) {
                continue;
            }
            $row = array_combine($config['columns'], $r);
            foreach ($config['defaults'] as $k => $v) {
                if (null !== $v && $v !== '' && (!isset($row[$k]) || $row[$k] === '')) {
                    $row[$k] = $v;
                }
            }

            $data = [];
            foreach ($row as $k => $v) {
                $f = explode('.', $k, 2);
                if (empty($f[0]) || empty($f[1])) {
                    continue;
                }
                $data[$f[0]][$f[1]] = $v;
            }


            if ($config['batch_size'] && !empty($data)) {
                $dataBatch[] = array_pop($data);
                if (count($dataBatch) % $config['batch_size'] === 0) {
                    $resultBatch = $model->import($dataBatch, $importConfig);
                    if (!empty($resultBatch['errors'])) {
                        $status['errors'] = $resultBatch['errors'];
                    }
                    foreach ($resultBatch as $result) {
                        $resultStatus = 'rows_' . $result['status'];
                        if (isset($status[$resultStatus])) {
                            $status[$resultStatus]++;
                        }
                    }
                    $dataBatch = [];
                }
            } else {
                $result = $model->import($data, $importConfig);
                if (!empty($result['errors'])) {
                    $status['errors'] = $result['errors'];
                }
                $resultStatus = 'rows_' . $result['status'];
                if (isset($status[$resultStatus])) {
                    $status[$resultStatus]++;
                }
            }

            //$result = array('status'=>'skipped');


            if (++$status['rows_processed'] % $statusUpdate === 0) {
                //gc_collect_cycles();
                $update = $this->config();
                $status['memory_usage'] = memory_get_usage();
                $status['memory_peak_usage'] = memory_get_peak_usage();
                $status['run_time'] = microtime(true) - $timer;
                $this->config($status, true);
                if (!$update || $update['status'] !== self::STATUS_RUNNING || $update['start_time'] !== $status['start_time']) {
                    return false;
                }
            }
        }
        fclose($fp);

        //upload last data
        if ($config['batch_size'] && !empty($dataBatch)) {
            $resultBatch = $model->import($dataBatch, $importConfig);
            if (!empty($resultBatch['errors'])) {
                $status['errors'] = $resultBatch['errors'];
            }
            foreach ($resultBatch as $result) {
                $resultStatus = 'rows_' . $result['status'];
                if (isset($status[$resultStatus])) {
                    $status[$resultStatus]++;
                } else {
                    $status[$resultStatus] = 1;
                }
            }
            $status['memory_usage'] = memory_get_usage();
            $status['run_time'] = microtime(true) - $timer;
            $this->config($status, true);
        }

        $status['memory_usage'] = memory_get_usage();
        $status['memory_peak_usage'] = memory_get_peak_usage();
        $status['run_time'] = microtime(true) - $timer;
        $status['status'] = self::STATUS_DONE;
        $status['rows_processed'] = $status['rows_total'];
        $this->config($status, true);

        $this->BDebug->mode($oldDebugMode);
        return true;
    }

    /**
     * @param resource $fh
     * @return int
     */
    public function getLinesCount($fh)
    {
        $c = 0;
        while (fgetcsv($fh)) {
            $c++;
        }
        fseek($fh, 0);
        return $c;
    }
}
