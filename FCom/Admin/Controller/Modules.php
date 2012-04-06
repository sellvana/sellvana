<?php

class FCom_Admin_Controller_Modules extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'admin/modules';

    public function gridConfig()
    {
        $modules = BModuleRegistry::i()->debug();
        $data = array();
        foreach ($modules as $mod) {
            $r = (array)$mod;
            $deps = array();
            foreach ($r['depends'] as $dep) {
                $deps[] = $dep['name'];
            }
            $r['depends'] = join(', ', $deps);
            $data[] = $r;
        }
#echo "<pre>"; print_r($data); exit;
        $config = array(
            'grid' => array(
                'id'          => 'modules',
                'datatype'    => 'local',
                'data'        => $data,
                'columns'     => array(
                    'name'        => array('label' => 'Name'),
                    'description' => array('label' => 'Description'),
                    'version'     => array('label' => 'Code Version'),
                    'run_level'   => array('label' => 'Run Level'),
                    'run_status'  => array('label' => 'Run Status'),
                    'depends'     => array('label' => 'Dependencies'),

                ),
                'sortname'    => 'name',
                'sortorder'   => 'asc',
                'multiselect' => true,
            ),
            'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true),
            'custom' => array('personalize'=>true),
        );
        BPubSub::i()->fire('FCom_Admin_Controller_Modules::gridConfig', array('config'=>&$config));
        return $config;
    }

    public function action_index()
    {
        $grid = BLayout::i()->view('jqgrid')->set('config', $this->gridConfig());
        BPubSub::i()->fire('FCom_Admin_Controller_Modules::action_index', array('grid'=>$grid));
        $this->layout('/modules');
    }
}