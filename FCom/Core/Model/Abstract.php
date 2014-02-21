<?php

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
    static protected $_dataFieldsMap = array();

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
        if (is_null($this->get(static::$_dataCustomField))) {
            $dataJson = $this->get(static::$_dataSerializedField);
            $this->set(static::$_dataCustomField, $dataJson ? BUtil::fromJson($dataJson) : array());
        }
        $data = $this->get(static::$_dataCustomField);
        if (is_null($path)) {
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
            $node = BUtil::arrayMerge((array)$node, (array)$value);
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

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        foreach (static::$_dataFieldsMap as $k => $v) {
            if (is_numeric($k)) {
                $k = $v;
            }
            $this->setData($v, $this->get($k));
        }

        if (($data = $this->get(static::$_dataCustomField))) {
            $this->set(static::$_dataSerializedField, BUtil::toJson($data));
        }

        return true;
    }
}
