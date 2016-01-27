<?php

/**
 * Class FCom_Core_View_Text
 */
class FCom_Core_View_Text extends FCom_Core_View_Abstract
{
    /**
     * @var array
     */
    protected $_parts = [];

    public function addText($name, $text, $params = [])
    {
        if (!empty($params['reset'])) {
            $this->_parts = [];
        }

        $this->_parts[$name] = [
            'description' => !empty($params['description']) ? !empty($params['description']) : null,
            'module_name' => !empty($params['module_name']) ? $params['module_name'] : $this->BModuleRegistry->currentModuleName(),
            'text' => $text,
        ];
        return $this;
    }

    public function render(array $args = [], $retrieveMetaData = false)
    {
        $output = '';
        $isDebug = $this->BDebug->is(['DEBUG', 'DEVELOPMENT']);
        foreach ($this->_parts as $name => $params) {
            if ($isDebug && !empty($params['debug'])) {
                if (empty($params['comments'])) {
                    $params['comments'] = ['/*', '*/'];
                }
                $output .= "\n{$params['comments'][0]} {$name}";
                if (!empty($params['module_name'])) {
                    $output .= "; Module: " .$params['module_name'];
                }
                if (!empty($params['description'])) {
                    $output .= "; " . $params['description'];
                }
                $output .= " {$params['comments'][1]}";
            }
            $output .= "\n\n" . $params['text'];
        }
        return $output;
    }
}