<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Frontend_ControlPanel
 */
class Sellvana_Catalog_Frontend_ControlPanel extends BClass
{
    /**
     * @var FCom_Core_Model_Abstract[]
     */
    static protected $_models = [];

    /**
     * @param $class
     * @param $id
     * @return mixed
     */
    public function getModel($class, $id)
    {
        if (empty(static::$_models[$class][$id])) {
            static::$_models[$class][$id] = $this->{$class}->load($id);
        }
        return static::$_models[$class][$id];
    }

    /**
     * @param $params
     * @return array
     */
    public function productEntityHandler($params)
    {
        $model = $this->getModel('Sellvana_Catalog_Model_Product', $params['data']['model_id']);
        if (!$model) {
            return ['error' => 'Product not found'];
        }
        $field = $params['data']['field'];
        $value = isset($params['value']) ? $params['value'] : null;
        if ($params['type'] === 'image') {
            //TODO: ugly, but is there a better way?
            if (preg_match('/resize\.php\?f=media%2F([^&]+)/', $params['attributes']['src'], $m)) {
                $src = urldecode($m[1]);
                if ($src !== 'image-not-found.jpg') {
                    $value = $src;
                }
            } else {
                return ['error' => 'Invalid image source'];
            }
        }
        $model->set($field, $value);

        return ['success' => true];
    }

    /**
     * @param $params
     * @return array
     */
    public function categoryEntityHandler($params)
    {
        $model = $this->getModel('Sellvana_Catalog_Model_Category', $params['data']['model_id']);
        if (!$model) {
            return ['error' => 'Category not found'];
        }
        $field = $params['data']['field'];
        $value = isset($params['value']) ? $params['value'] : null;
        $model->set($field, $value);
        return ['success' => true];
    }

    /**
     * @param $args
     */
    public function onAfterUpdate($args)
    {
        foreach (static::$_models as $entity => $models) {
            foreach ($models as $id => $model) {
                $model->save();
                unset(static::$_models[$entity][$id]);
            }
        }
    }
}
