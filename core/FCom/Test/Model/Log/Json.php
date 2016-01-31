<?php

/**
 * Created by pp
 *
 * @project sellvana_core
 */
class FCom_Test_Model_Log_Json extends PHPUnit_Util_Log_JSON
{
    protected $msgs;
    public function write($buffer)
    {
        $this->msgs[] = $buffer;
        //parent::write($buffer);
    }

    public function getResults()
    {
        $result = '[';
        $parts = [];
        foreach ($this->msgs as &$buffer) {
            array_walk_recursive($buffer, function (&$input) {
                if (is_string($input) || is_object($input) && method_exists($input, '__toString')) {
                    $input = PHPUnit_Util_String::convertToUtf8($input);
                }
            });
            $parts[] = json_encode($buffer);
        }
        $result .= join(",", $parts);
        $result .= ']';
        return $result;
    }
}
