<?php

class FCom_Frontend_View_Root extends FCom_Core_View_Root
{
    public function setLayoutClass($layout)
    {
        $this->layout_class = $layout;
        $this->show_left_col = $layout == 'col2-layout-left' || $layout == 'col3-layout';
        $this->show_right_col = $layout == 'col2-layout-right' || $layout == 'col3-layout';
        return $this;
    }

    public function getCol($colName)
    {
        $cols = $this->get('col_' . $colName);
        $default = $this->get('col_' . $colName . '_default');
        if (!$default) {
            $default = 3;
        }
        if (!$cols) {
            return 0;
        }
        if (true === $cols) {
            return $default;
        }
        return $cols;
    }

}
