<?php

class FCom_Catalog_Frontend_ControlPanel extends BClass
{
    static protected $_models = [];

    static public function getModel($class, $id)
    {
        if (empty(static::$_models[$class][$id])) {
            static::$_models[$class][$id] = $class::i()->load($id);
        }
        return static::$_models[$class][$id];
    }

    static public function productEntityHandler($params)
    {
        $model = static::getModel('FCom_Catalog_Model_Product', $params['data']['model_id']);
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

    static public function categoryEntityHandler($params)
    {
        $model = static::getModel('FCom_Catalog_Model_Category', $params['data']['model_id']);
        if (!$model) {
            return ['error' => 'Category not found'];
        }
        $field = $params['data']['field'];
        $value = isset($params['value']) ? $params['value'] : null;
        $model->set($field, $value);
        return ['success' => true];
    }

    static public function onAfterUpdate($args)
    {
        foreach (static::$_models as $entity => $models) {
            foreach ($models as $id => $model) {
                $model->save();
                unset(static::$_models[$entity][$id]);
            }
        }
    }
}