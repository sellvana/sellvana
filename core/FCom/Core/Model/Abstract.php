<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Core_Model_Abstract
 *
 * core class
 * @property FCom_Core_Main $FCom_Core_Main
 * @property FCom_Core_Model_ImportExport_Site $FCom_Core_Model_ImportExport_Site
 * @property FCom_Core_Model_ImportExport_Model $FCom_Core_Model_ImportExport_Model
 * @property FCom_Core_Model_ImportExport_Id $FCom_Core_Model_ImportExport_Id
 * @property FCom_Core_Model_Config $FCom_Core_Model_Config
 * @property FCom_Core_Model_MediaLibrary $FCom_Core_Model_MediaLibrary
 * @property FCom_Core_Model_Module $FCom_Core_Model_Module
 * @property FCom_Core_Model_Seq $FCom_Core_Model_Seq
 *
 * common
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 *
 * admin core class
 * @property FCom_Admin_Main $FCom_Admin_Main
 *
 * frontend core class
 * @property FCom_Frontend_Main $FCom_Frontend_Main
 */
class FCom_Core_Model_Abstract extends BModel
{
    /**
     * Field name for serialized data
     *
     * Access using static::$_dataSerializedField to allow overrides
     *
     * @var string
     */
    static protected $_dataSerializedField = 'data_serialized';

    /**
     * Field name for custom data storage
     *
     * @var string
     */
    static protected $_dataCustomField = 'data_custom';

    /**
     * Mapping object properties to custom data paths
     *
     * array(
     *      'prop1' => 'custom/data/path', // different property name and data path mapping
     *      'prop2', // same name mapping
     * )
     *
     * @var array
     */
    static protected $_dataFieldsMap = [];

    static protected $_importExportProfile;

    protected $_readOnly = false;

    /**
     * Get custom data from serialized field
     *
     * Lazy `data` initialization from `data_serialized`
     * Works only for models with `data_serialized` field existing
     *
     * @param string $path slash separated path to the data within structured array
     * @return mixed
     */
    public function getData($path = null)
    {
        if (null === $this->get(static::$_dataCustomField)) {
            $dataJson = $this->get(static::$_dataSerializedField);
            $this->set(static::$_dataCustomField, $dataJson ? $this->BUtil->fromJson($dataJson) : []);
        }
        $data = $this->get(static::$_dataCustomField);
        if (null === $path) {
            return $data;
        }
        $pathArr = explode('/', $path);
        foreach ($pathArr as $k) {
            if (!isset($data[$k])) {
                return null;
            }
            $data = $data[$k];
        }
        return $data;
    }

    /**
     * Set custom data to serialized field
     *
     * Works only for models with `data_serialized` field existing
     *
     * @param string $path slash separated path to the data within structured array
     * @param        $value mixed
     * @param bool   $merge
     * @return FCom_Core_Model_Abstract
     */
    public function setData($path, $value = null, $merge = false)
    {
        if (is_array($path)) {
            foreach ($path as $p => $v) {
                $this->setData($p, $v);
            }
            return $this;
        }
        $data = $this->getData();
        $node =& $data;
        foreach (explode('/', $path) as $key) {
            $node =& $node[$key];
        }
        if ($merge) {
            $node = $this->BUtil->arrayMerge((array)$node, (array)$value);
        } else {
            $node = $value;
        }
        $this->set(static::$_dataCustomField, $data);
        return $this;
    }

    public function onAfterLoad()
    {
        parent::onAfterLoad();

        foreach (static::$_dataFieldsMap as $k => $v) {
            if (is_numeric($k)) {
                $k = $v;
            }
            $this->set($k, $this->getData($v));
        }
    }

    public function setReadOnly($flag = true)
    {
        $this->_readOnly = $flag;
        return $this;
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        if ($this->_readOnly) {
            return false;
        }

        foreach (static::$_dataFieldsMap as $k => $v) {
            if (is_numeric($k)) {
                $k = $v;
            }
            $this->setData($v, $this->get($k));
        }

        if (($data = $this->get(static::$_dataCustomField))) {
            $this->set(static::$_dataSerializedField, $this->BUtil->toJson($data));
        }

        $now = $this->BDb->now();
        $this->set('create_at', $now, 'IFNULL')->set('update_at', $now);

        return true;
    }

    public function registerImportExport(&$config)
    {
        if (!empty(static::$_importExportProfile)) {
            $key = static::$_origClass? :__CLASS__;
            $config[$key] = static::$_importExportProfile;
            $config[$key]['model'] = $key;
        }
    }

    public function getIdField()
    {
        $class = static::$_origClass ? static::$_origClass : get_called_class();

        return $this->_get_id_column_name($class);
    }
}
