<?php

class FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm extends FCom_AdminSPA_AdminSPA_Controller_Abstract
{
    public function normalizeGridConfig($config)
    {
        foreach ($config['columns'] as $i => &$col) {
            if (!isset($col['sortable'])) {
                $col['sortable'] = true;
            }
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
                        if (empty($col['id_field'])) {
                            $col['id_field'] = 'id';
                        }
                        break;

                    case 'actions':
                        if (empty($col['header_cell_vm'])) {
                            //$col['header_cell_vm'] = 'GridHeaderCell_Actions';
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
                        if (empty($col['actions'])) {
                            $col['actions'] = [
                                ['type' => 'edit', 'link' => $config['edit_link']],
                                ['type' => 'delete', 'link' => $config['delete_link']],
                            ];
                        }
                        foreach ($col['actions'] as $j => $a) {
                            if (empty($a['icon_class'])) {
                                switch ($a['type']) {
                                    case 'edit': $col['actions'][$j]['icon_class'] = 'fa fa-pencil'; break;
                                    case 'delete': $col['actions'][$j]['icon_class'] = 'fa fa-trash'; break;
                                }
                            }
                        }
                        $col['sortable'] = false;
                        break;
                }
            }
        }
        unset($col);

        if (!empty($config['filters'])) {
            foreach ($config['filters'] as &$flt) {
                if (empty($flt['type'])) {
                    $flt['type'] = 'text';
                }
            }
            unset($flt);
        }

        if (empty($config['pagesize_options'])) {
            $config['pagesize_options'] = [5, 10, 20, 50, 100];
        }
        return $config;
    }
}