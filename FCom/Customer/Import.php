<?php

class FCom_Customer_Import extends BClass
{
    public function getFieldData()
    {
        $fields = array(
            'customer.firstname' => array('pattern'=>'first.*name'),
            'customer.lastname' => array('pattern'=>'last.*name'),
            'customer.email' => array('pattern'=>'e[ -]?mail'),
            'customer.password' => array('pattern'=>'pass[ -]?(word|phrase)'),
            'address.street1' => array('pattern'=>'(address|street)'),
            'address.street2' => array('pattern'=>'(address|street) ?2'),
            'address.street3' => array('pattern'=>'(address|street) ?3'),
            'address.city' => array('pattern'=>'city'),
            'address.region' => array('pattern'=>'(state|province|region)'),
            'address.postcode' => array('pattern'=>'(zip|post(al)?([ -]?code)?)'),
            'address.country' => array('pattern'=>'country'),
            'address.phone' => array('pattern'=>'(tele)?phone'),
            'address.fax' => array('pattern'=>'fax'),
        );
        BPubSub::i()->fire(__METHOD__, array('fields'=>&$fields));
        return $fields;
    }

    public function getFieldOptions()
    {
        $options = array();
        foreach ($this->getFieldData() as $f=>$_) {
            $options[$f] = $f;
        }
        return $options;
    }

    public function getImportDir()
    {
        return FCom_Core::i()->dir('storage/import/customers');
    }

    public function getFileInfo($file)
    {
        // assume we know nothing about the file
        $info = array();
        // open file for reading
        $fp = fopen($file, 'r');
        // get first line in the file
        $r = fgets($fp);
        fclose($fp);
        foreach (array("\t", ',', ';', '|') as $chr) {
            $row = str_getcsv($r, $chr);
            if (sizeof($row)>1) {
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
        $fieldPatterns = $this->getFieldData();
        foreach ($row as $i=>$v) {
            foreach ($fieldPatterns as $f=>$fd) {
                if (preg_match("#{$fd['pattern']}#i", $v)) {
                    $info['columns'][$i] = $f;
                    break;
                }
            }
        }
        // if no column names found, do not skip first row
        $info['skip_first'] = !empty($info['columns']);
        return $info;
    }

    public function config($config=null, $update=false)
    {
        $dir = FCom_Core::i()->dir('storage/run/customer_import');
        $file = BSession::i()->sessionId().'.json';
        $filename = $dir.'/'.$file;
        if ($config) { // create config lock
            if ($update) {
                $old = $this->config();
                $config = array_replace_recursive($old, $config);
            }
            if (empty($config['status'])) {
                $config['status'] = 'idle';
            }
            file_put_contents($filename, BUtil::toJson($config));
            return true;
        } elseif ($config===false) { // remove config lock
            unlink($filename);
            return true;
        } elseif (!file_exists($filename)) { // no config
            return false;
        } else { // config exists
            $contents = file_get_contents($filename);
            $config = BUtil::fromJson($contents);
            return $config;
        }
    }

    public function run()
    {
        session_write_close();
        ignore_user_abort(true);
        set_time_limit(0);
        ob_implicit_flush();

        $model = FCom_Customer_Model_Customer::i();
        $config = $this->config();
        $filename = $this->getImportDir().'/'.$config['filename'];
        $totalRows = sizeof(file($filename));
        $rowsDone = 0;
        $this->config(array('status'=>'running', 'total_rows'=>$totalRows), true);
        $fp = fopen($filename, 'r');
        if (!empty($config['skip_first'])) {
            for ($i=0; $i<$config['skip_first']; $i++) {
                fgets($fp);
            }
        }
        while (($r = fgetcsv($fp, 4096, $config['delim']))) {
            $row = array_combine($config['columns'], $r);
            $cust = $model->import($row);

            if (++$rowsDone % 50 === 0) {
                $update = $this->config();
                if (!$update || $update['status']!=='running') {
                    return false;
                }
                $this->config(array('rows_done'=>$rowsDone), true);
            }
        }
        fclose($fp);
        $this->config(array('status'=>'done'), true);
        return true;
    }
}