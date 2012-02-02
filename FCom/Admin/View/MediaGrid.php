<?php

class FCom_Admin_View_MediaGrid extends FCom_Admin_View_Grid
{
    public function initConfig($options=array())
    {
        $id = !empty($options['id']) ? $options['id'] : 'media_library';
        $folder = $options['folder'];
        $jsVar = 'js_'.$id;
        $url = BApp::url('FCom_Admin', '/media/grid');
        $vAutocompleteUrl = BApp::url('Denteva_Admin', '/vendors/autocomplete');
        $config = array(
            'grid' => array(
                'id' => $id,
                'caption' => 'Media Library',
                'datatype' => 'json',
                'url' => $url.'/data?folder='.urlencode($folder),
                'editurl' => $url.'/edit?folder='.urlencode($folder),
                'colModel' => array(
                    array('name'=>'id', 'label'=>'ID', 'width'=>400, 'hidden'=>true),
                    array('name'=>'file_name', 'label'=>'File Name', 'width'=>400, 'editable'=>true),
                    array('name'=>'manuf_vendor_name', 'label'=>'Manufacturer', 'width'=>150, 'index'=>'v.vendor_name', 'editable'=>true),
                    array('name'=>'promo_status', 'label'=>'Status', 'width'=>80, 'options'=>array(''=>'All', 'A'=>'Active', 'I'=>'Inactive'), 'editable'=>true, 'edittype'=>'select', 'searchoptions'=>array('defaultValue'=>'A')),
                    array('name'=>'file_size', 'label'=>'File Size', 'width'=>60, 'search'=>false, 'formatter'=>"function(val,opt,obj){ return Math.round(val/1024)+'k'}"),
                    array('name'=>'act', 'label'=>'Actions', 'width'=>70, 'search'=>false, 'sortable'=>false, 'resizable'=>false, 'formatter'=>"function(val,opt,obj) { return {$jsVar}.fmtActions(val,opt,obj); }"),
                ),
                'multiselect' => true,
                'multiselectWidth' => 30,
            ),
            'navGrid' => array('add'=>false, 'edit'=>false, 'search'=>false, 'del'=>false, 'refresh'=>true),
            'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true, 'defaultSearch'=>'cn'),
            array('navButtonAdd', 'id'=>'upload-btn', 'caption' => 'Upload', 'buttonicon'=>'ui-icon-plus', 'title' => 'Add Attachments to Library', 'cursor'=>'pointer'),
            array('navButtonAdd', 'caption' => 'Delete', 'buttonicon'=>'ui-icon-trash', 'title' => 'Delete Attachments from Library', 'onClickButton' => "function() { {$jsVar}.deleteAttachments() }", 'cursor'=>'pointer'),
            <<<EOT
; var {$jsVar} = new FCom_Admin.MediaLibrary({
    grid:'#{$id}',
    url:'{$url}',
    folder:'{$folder}',
    js_var:'{$jsVar}',
    oneditfunc:function(tr) { $('input[name=manuf_vendor_name]', tr).fcom_autocomplete({url:'{$vAutocompleteUrl}'}); }
});
$('#gs_manuf_vendor_name', '#{$id}').fcom_autocomplete({url:'{$vAutocompleteUrl}'});
EOT
        );
        if (!empty($options['config'])) {
            $config = BUtil::arrayMerge($config, $options['config']);
        }
        BPubSub::i()->fire(__METHOD__, array('config'=>&$config));
        $this->config = $config;
        return $this;
    }
}