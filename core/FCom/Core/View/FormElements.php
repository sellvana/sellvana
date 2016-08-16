<?php

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
        return $this->BUtil->arrayMerge($p1, $p2);
    }

    /**
     * @param array $p
     * @return array
     */
    public function getOptions(array $p)
    {
        $options = !empty($p['options']) ? $p['options'] : [];
        if (!empty($p['add_empty'])) {
            $options = ['' => ''] + $options;
        }
        return $options;
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

    public function getInputNameRemoveOld($p)
    {
        if (preg_match_all('#((^[^\[]+)|\[([^\]]+)\])#', $this->getInputName($p), $keyArr)) {
            return 'remove_old[' . $keyArr[2][0] . '][' . trim(join('/', $keyArr[3]), '/') . ']';
        }
        return false;
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

    public function jsVisibleConditions($p)
    {
        if (!empty($p['js_visible'])) {
            $conditions = preg_replace_callback('#\{([a-zA-Z0-9_]+)\}#', function($m) use ($p) {
                $p['field'] = $m[1];
                return "\$('#{$this->getInputId($p)}').val()";
            }, $p['js_visible']);
        } elseif (!empty($p['js_toggle'])) {
            $conditions = '';
            if ($p['js_toggle'][0] === '!') {
                $p['js_toggle'] = substr($p['js_toggle'], 1);
                $conditions = '!';
            }
            $conditions .= "(\$('#{$this->jsVisibleToggleId($p)}').val() == 1)";
        } else {
            $conditions = false;
        }

        return $conditions;
    }

    public function jsVisibleToggleId($p)
    {
        if ($p['js_toggle'][0] === '#') {
            return substr($p['js_toggle'], 1);
        }
        $p1 = $p;
        $p1['field'] = ltrim($p1['js_toggle'], '!');
        return $this->getInputId($p1);
    }

    public function getSelect2ArgsText($p)
    {
        if (empty($p['select2'])) {
            return '{}';
        }
        $args = $p['select2'];

        return $this->BUtil->toJson($args);
    }
}
