<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Core_Model_Config extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_core_config';
    protected $_instance_id_column = 'path';
    protected static $_importExportProfile = [
        'unique_key' => ['path'],
    ];

    public function fetch($path)
    {
        return ($row = $this->load($path)) ? $row->value : null;
    }

    public function store($path, $value)
    {
        if (($row = $this->load($path))) {
            $row->set('value', $value)->save();
        } else {
            $this->create(['path' => $path, 'value' => $value])->save();
        }
    }

    public function install()
    {
        $this->BDb->run("
CREATE TABLE IF NOT EXISTS " . $this->table() . " (
  `path` varchar(100)  NOT NULL,
  `value` text ,
  PRIMARY KEY (`path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;
        ");
    }
}
