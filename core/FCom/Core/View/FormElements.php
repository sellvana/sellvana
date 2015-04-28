<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Core_View_FormElements
 */
class FCom_Core_View_FormElements extends FCom_Core_View_Abstract
{
    /**
     * @param array $p1
     * @param array $p2
     * @return array
     */
    public function merge(array $p1, array $p2 = null)
    {
        if (!$p2) {
            return $p1;
        }
        return array_merge_recursive($p1, $p2);
    }

    /**
     * @param $p
     * @return string
     */
    public function getInputId($p)
    {
        //p.id|default(p.id_prefix|default('model') ~ '-' ~ p.field)
        if (!empty($p['id'])) {
            return $p['id'];
        }
        if (empty($p['field'])) {
            return '';
        }
        if (!empty($p['settings_module']) && empty($p['id_prefix'])) {
            return 'modules-' . $p['settings_module'] . '-' . $p['field'];
        }
        return (!empty($p['id_prefix']) ? $p['id_prefix'] : 'model') . '-' . $p['field'];
    }

    /**
     * @param $p
     * @return string
     */
    public function getInputName($p)
    {
        if (!empty($p['name'])) {
            return $p['name'];
        }
        if (empty($p['field'])) {
            return '';
        }
        if (!empty($p['settings_module']) && empty($p['name_prefix'])) {
            $name = 'config[modules][' . $p['settings_module'] . '][' . $p['field'] . ']';
        } else {
            $name = (!empty($p['name_prefix']) ? $p['name_prefix'] : 'model') . '[' . $p['field'] . ']';
        }
        if (!empty($p['multiple'])) {
            $name .= '[]';
        }
        return $name;
    }

    /**
     * @param $p
     * @return string
     */
    public function getInputValue($p)
    {
        if (isset($p['value'])) {
            return $p['value'];
        }
        if (empty($p['field'])) {
            return '';
        }
        if (!empty($p['validator'])) {
            return $p['validator']->fieldValue($p['field']);
        }
        if (!empty($p['model'])) {
            if (!empty($p['settings_module']) && empty($p['get_prefix'])) {
                $prefix = 'modules/' . $p['settings_module'] . '/';
            } elseif (!empty($p['get_prefix'])) {
                $prefix = $p['get_prefix'] . '/';
            } else {
                $prefix = '';
            }
            $model = $p['model'];
            $path = $prefix . $p['field'];

            if ($model instanceof BModel || $model instanceof BClass) {
                return $p['model']->get($path);
            } elseif (is_array($model)) {
                $node = $model;
                foreach (explode('/', $path) as $key) {
                    if (!isset($node[$key])) {
                        return null;
                    }
                    $node = $node[$key];
                }
                return $node;
            }
        }
        return '';
    }

    public function attributes($attrs)
    {
        return $this->BUtil->tagAttributes($attrs);
    }
}
