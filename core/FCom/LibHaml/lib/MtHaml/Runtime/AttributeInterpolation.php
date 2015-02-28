<?php defined('BUCKYBALL_ROOT_DIR') || die();

namespace MtHaml\Runtime;

class AttributeInterpolation
{
    public $value;

    static public function create($value)
    {
        $instance = new AttributeInterpolation;
        $instance->value = $value;
        return $instance;
    }
}
