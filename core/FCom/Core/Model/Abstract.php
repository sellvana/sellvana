<?php

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
 * @property BCurrencyValue $BCurrencyValue
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

    protected $_importing = false;

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
        $pathArr = explode('/', trim($path, '/'));
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
                $this->setData($p, $v, $merge);
            }
            return $this;
        }
        $data = $this->getData();
        $node =& $data;
        foreach (explode('/', trim($path, '/')) as $key) {
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

        $createAt = $this->get('create_at');
        if (null !== $createAt && '' !== $createAt && !$this->BUtil->isValidDate($createAt)) {
            $this->BDebug->log("Reverting invalid create_at date: {$createAt} (" . get_class($this) . ".{$this->id()}");
            $this->set('create_at', $this->old_values('create_at'));
        }
        $now = $this->BDb->now();
        if (null === $createAt || '' === $createAt) {
            $this->set('create_at', $now);
        }
        $this->set('update_at', $now);

        return true;
    }

    public function registerImportExport(&$config)
    {
        if (!empty(static::$_importExportProfile)) {
            $key = static::$_origClass ?: __CLASS__;
            $ieProfile = static::$_importExportProfile;
            if (is_string($ieProfile) && preg_match('#^THIS\.(.+)$#', $ieProfile, $m)) {
                $ieProfile = $key . '.' . $m[1];
            }
            $config[$key] = $this->BUtil->maybeCallback($ieProfile, 'ieprofile/' . $key);
            $config[$key]['model'] = $key;
        }
        return $this;
    }

    public function saveImport()
    {
        $this->_importing = true;
        $result = $this->save();
        $this->_importing = false;
        return $result;
    }

    public function getIdField()
    {
        $class = static::$_origClass ? static::$_origClass : get_called_class();

        return $this->_get_id_column_name($class);
    }

    public function updateManyToManyIds(FCom_Core_Model_Abstract $mainModel, $mainIdField, $relIdField, array $newRelIds)
    {
        $mId = $mainModel->id();

        $existingRelIds = $this->orm()->where($mainIdField, $mainModel->id())->find_many_assoc('id', $relIdField);

        if ($existingRelIds) {
            $idsToDelete = array_diff($existingRelIds, $newRelIds);
            if ($idsToDelete) {
                $this->delete_many([$mainIdField => $mId, $relIdField => $idsToDelete]);
            }
        }

        if ($newRelIds) {
            $idsToCreate = array_diff($newRelIds, $existingRelIds);
            if ($idsToCreate) {
                foreach ($idsToCreate as $rId) {
                    $this->create([$mainIdField => $mId, $relIdField => $rId])->save();
                }
            }
        }

        return $this;
    }

    public function getDataFieldsMap()
    {
        return static::$_dataFieldsMap;
    }

    public function mapDataFields()
    {
        $fieldMap = $this->getDataFieldsMap();

        foreach ($fieldMap as $k => $v) {
            if (is_numeric($k)) {
                $k = $v;
            }
            $this->set($k, $this->getData($v));
        }
    }

    /**
     * Get language field in data_serialized base on current locale
     *
     * @param string $field
     * @return string
     */
    public function getLangField($field)
    {
        $orgVal       = $this->get($field);
        $langFieldKey = $field . "_lang_fields";
        $mapFieldKey  = array_search($langFieldKey, $this->getDataFieldsMap());

        if ($mapFieldKey !== false) {
            $langFieldKey = $mapFieldKey;
        }

        $langData     = $this->getData($langFieldKey);
        $curLangKey   = $this->BSession->get('current_locale');

        if (!$langData || !$curLangKey) {
            return $orgVal;
        }

        if (is_string($langData)) {
            $langData = json_decode($langData);
        }

        if (is_array($langData) && count($langData)) {
            foreach ($langData as $lang) {
                if (isset($lang->lang_code) && $lang->lang_code == $curLangKey) {
                    if (isset($lang->value) && $lang->value != '') {
                        return $lang->value;
                    }

                    break;
                }
            }
        }

        return $orgVal;
    }
}
