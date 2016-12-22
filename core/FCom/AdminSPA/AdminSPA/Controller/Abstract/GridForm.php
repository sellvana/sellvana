<?php

class FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm extends FCom_AdminSPA_AdminSPA_Controller_Abstract
{
    public function normalizeGridConfig($config)
    {
        foreach ($config['columns'] as $i => &$col) {
            if (!empty($col['type'])) {
                switch ($col['type']) {
                    case 'row-select':
                        if (empty($col['header_cell_vm'])) {
                            $col['header_cell_vm'] = 'GridHeaderCell_RowSelect';
                        }
                        if (empty($col['data_cell_vm'])) {
                            $col['data_cell_vm'] = 'GridDataCell_RowSelect';
                        }
                        if (empty($col['field'])) {
                            $col['field'] = 'row-select';
                        }
                        if (empty($col['label'])) {
                            $col['label'] = 'Selection';
                        }
                        break;

                    case 'actions':
                        if (empty($col['header_cell_vm'])) {
                            //$col['header_cell_vm'] = 'GridHeaderCell_Checkbox';
                        }
                        if (empty($col['data_cell_vm'])) {
                            $col['data_cell_vm'] = 'GridDataCell_Actions';
                        }
                        if (empty($col['field'])) {
                            $col['field'] = 'actions';
                        }
                        if (empty($col['label'])) {
                            $col['label'] = 'Actions';
                        }
                        break;
                }
            }
        }
        unset($col);
        if (empty($config['pagesize_options'])) {
            $config['pagesize_options'] = [5, 10, 20, 50, 100];
        }
        return $config;
    }
}