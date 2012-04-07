<?php

class FCom_Admin_Controller_Modules extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'admin/modules';

    public function getModulesData()
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
        return $data;
    }

    public function gridConfig()
    {
        $config = array(
            'grid' => array(
                'id'          => 'modules',
                'datatype'    => 'local',
                'data'        => $this->getModulesData(),
                'editurl'     => BApp::href('/modules/grid_data'),
                'columns'     => array(
                    'name'        => array('label' => 'Name', 'key'=>true),
                    'description' => array('label' => 'Description'),
                    'version'     => array('label' => 'Code Version'),
                    'run_level'   => array('label' => 'Run Level', 'editable'=>true, 'options'=>array(
                        BModule::DISABLED  => 'DISABLED',
                        BModule::ONDEMAND  => 'ONDEMAND',
                        BModule::REQUESTED => 'REQUESTED',
                        BModule::REQUIRED  => 'REQUIRED',
                    )),
                    'run_status'  => array('label' => 'Run Status', 'options'=>array(
                        BModule::IDLE    => 'IDLE',
                        BModule::PENDING => 'PENDING',
                        BModule::LOADED  => 'LOADED',
                        BModule::ERROR   => 'ERROR'
                    )),
                    'depends'     => array('label' => 'Dependencies'),
                ),
                'sortname'    => 'name',
                'sortorder'   => 'asc',
                //'multiselect' => true,

            ),
            'inlineNav' => array('add'=>false),
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

    public function action_grid_data()
    {
        BResponse::i()->json($this->getModulesData());
    }

    public function action_grid_data__POST()
    {
        $r = BRequest::i();
        if ($r->post('oper')!=='edit') {
            $result = array('error'=>'Invalid request');
        } else {
            BConfig::i()->set('modules/'.$r->post('id').'/run_level', $r->post('run_level'), false, true);
            FCom_Core::i()->writeLocalConfig();
echo "<pre>"; print_r(BConfig::i()->get()); exit;
        }

    }
}