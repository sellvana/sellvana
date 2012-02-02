<?php
$m = $this->model;
$promoCtrl = Denteva_Admin_Controller_Promo::i();
?>
<div id="attachments-layout">
    <div class="ui-layout-west">

        <input type="hidden" name="_add_attachments" value=""/>
        <input type="hidden" name="_del_attachments" value=""/>
        <?=$this->view('jqgrid')->set('config', array(
            'grid' => array(
                'id' => 'promo_attachments',
                'caption' => 'Promotion Attachments',
                'datatype' => 'local',
                'data' => BDb::many_as_array($m->attachmentsORM()->select('a.id')->select('a.file_name')->find_many()),
                'colModel' => array(
                    array('name'=>'id', 'label'=>'ID', 'width'=>400, 'hidden'=>true),
                    array('name'=>'file_name', 'label'=>'File Name', 'width'=>400, 'formatter'=>"function(val,opt,obj) {
                        return val+'<input type=\"hidden\" name=\"attachments[]\" value=\"'+obj.file_name+'\"/>';
                    }"),
                ),
                'multiselect' => true,
                'multiselectWidth' => 30,
            ),
            'navGrid' => array('add'=>false, 'edit'=>false, 'search'=>false, 'del'=>false, 'refresh'=>false),
            array('navButtonAdd', 'caption' => 'Add', 'buttonicon'=>'ui-icon-plus', 'title' => 'Add Attachments to Promotion', 'onClickButton' => "function() { addAttachments() }", 'cursor'=>'pointer'),
            array('navButtonAdd', 'caption' => 'Remove', 'buttonicon'=>'ui-icon-trash', 'title' => 'Remove Attachments From Promotion', 'onClickButton' => "function() { removeAttachments() }", 'cursor'=>'pointer'),
        )) ?>
    </div>

    <div class="ui-layout-center">
        <?=$this->view('jqgrid')->set('config', array(
            'grid' => array(
                'id' => 'all_attachments',
                'caption' => 'All Attachments',
                'datatype' => 'json',
                'url' => BApp::url('Denteva_Admin', '/promo/attachments'),
                'editurl' => BApp::url('Denteva_Admin', '/promo/attachments/edit'),
                'colModel' => array(
                    array('name'=>'id', 'label'=>'ID', 'width'=>400, 'hidden'=>true),
                    array('name'=>'file_name', 'label'=>'File Name', 'width'=>400, 'editable'=>true),
                    array('name'=>'manuf_vendor_name', 'label'=>'Manufacturer', 'width'=>150, 'index'=>'v.vendor_name', 'editable'=>true),
                    array('name'=>'promo_status', 'label'=>'Status', 'width'=>80, 'options'=>array(''=>'All', 'A'=>'Active', 'I'=>'Inactive'), 'editable'=>true, 'edittype'=>'select', 'searchoptions'=>array('defaultValue'=>'A')),
                    array('name'=>'file_size', 'label'=>'File Size', 'width'=>60, 'search'=>false, 'formatter'=>"function(val,opt,obj){ return Math.round(val/1024)+'k'}"),
                    array('name'=>'act', 'label'=>'Actions', 'width'=>70, 'search'=>false, 'sortable'=>false, 'resizable'=>false, 'formatter'=>"function(val,opt,obj) { return fmtActions(val,opt,obj); }"),
                ),
                'multiselect' => true,
                'multiselectWidth' => 30,
            ),
            'navGrid' => array('add'=>false, 'edit'=>false, 'search'=>false, 'del'=>false, 'refresh'=>true),
            'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true, 'defaultSearch'=>'cn'),
            array('navButtonAdd', 'id'=>'upload-btn', 'caption' => 'Upload', 'buttonicon'=>'ui-icon-plus', 'title' => 'Add Attachments to Library', 'cursor'=>'pointer'),
            array('navButtonAdd', 'caption' => 'Delete', 'buttonicon'=>'ui-icon-trash', 'title' => 'Delete Attachments from Library', 'onClickButton' => "function() { deleteAttachments() }", 'cursor'=>'pointer'),
        )) ?>
<input type="file" name="upload[]" id="upload-input" value="Upload Attachments" multiple style="position:absolute; z-index:999; top:0; left:0; margin:-1px; padding:0; opacity:0">
<iframe id="upload-target" name="upload-target" src="" style="width:0;height:0;border:0"></iframe>
    </div>
</div>
<script>

function fmtActions(val,opt,obj) {

    var html = '', url = '<?=BApp::url('Denteva_Admin', '/promo/attachments/download')?>?file='+encodeURI(obj.file_name);
    if (!obj.status) {
        html += '<span class=\"ui-icon ui-icon-pencil\" onclick=\"return editAttachment(event,this)\">'
            +'</span><span class=\"ui-icon ui-icon-disk\" onclick=\"return editAttachmentSave(event,this)\" style=\"display:none\">'
            +'</span><span class=\"ui-icon ui-icon-cancel\" onclick=\"return editAttachmentCancel(event,this)\" style=\"display:none\"></span>'
            +'<span class="ui-icon ui-icon-arrowthickstop-1-s" onclick="return downloadAttachment(event, \''+url+'\')"></span>';
            +'<span class="ui-icon ui-icon-arrowreturnthick-1-e" onclick="return downloadAttachment(event, \''+url+'&inline=1\', true)"></span>';
    } else {
        html = obj.status;
    }
    return html;
}

var layout = $('#attachments-layout').height($('.adm-wrapper').height()).layout({
    useStateCookie: true,
    west__minWidth:400,
    west__spacing_open:20,
    west__closable:false,
    triggerEventsOnLoad: true,
    onresize:function(pane, $Pane, paneState) {
        $('.ui-jqgrid-btable:visible', $Pane).each(function(index) {
            $(this).setGridWidth(paneState.innerWidth - 20);
        });
    }
});

$('#gs_manuf_vendor_name').fcom_autocomplete({
    url:'<?php echo BApp::url('Denteva_Admin', '/vendors/autocomplete') ?>'/*,
    field:'#model-manuf_vendor_id'*/
})

$('#upload-btn').unbind('click').find('.ui-pg-div').css({overflow:'hidden'}).prepend($('#upload-input'));
$('#upload-input').change(function(ev) {
    console.log(this.files);
    var form = $(this).parents('form'), action = form.attr('action'), i, file;
    for (i=0; i<this.files.length; i++) {
        file = this.files[i];
console.log(file);
        $('#all_attachments').jqGrid('addRowData', file.fileName, {file_name:file.fileName, file_size:file.fileSize, status:'...'});
    }
    form.attr('action', '<?=BApp::url('Denteva_Admin', '/promo/attachments/upload')?>?grid=all_attachments')
        .attr('target', 'upload-target')
        .attr('enctype', 'multipart/form-data')
        .submit();
    setTimeout(function() { form.attr('target', '').attr('enctype', '').attr('action', action); }, 100);
});

function addAttachments() {
    var src = $('#all_attachments'), sel = src.jqGrid('getGridParam', 'selarrrow'), i;
    var target = $('#promo_attachments');
    if (!sel.length) {
        alert('Please select some attachments to add.');
        return;
    }
    for (i=0; i<sel.length; i++) {
        target.jqGrid('addRowData', sel[i], src.jqGrid('getRowData', sel[i]));
    }
}

function removeAttachments() {
    var grid = $('#promo_attachments'), sel = grid.jqGrid('getGridParam', 'selarrrow'), i;
    if (!sel.length) {
        alert('Please select some attachments to remove.');
        return;
    }
    var attEl = $('input[name=_del_attachments]'), attData = attEl.val().split(',');
    for (i=sel.length-1; i>=0; i--) {
        attData.push(sel[i]);
        grid.jqGrid('delRowData', sel[i]);
    }
    attEl.val(attData.join(','));
}

function editAttachmentRestore(tr) {
    $('.ui-icon-disk,.ui-icon-cancel', tr).hide('fast'); $('.ui-icon-pencil', tr).show('fast');
}

function editAttachment(ev, el) {
    var el = $(el), grid = el.parents('.ui-jqgrid-btable'), tr = el.parents('tr'), rowid = tr.attr('id');
    el.hide('fast'); $('.ui-icon-disk,.ui-icon-cancel', tr).show('fast');
    ev.stopPropagation();
    grid.jqGrid('editRow', rowid, {
        keys:true,
        oneditfunc:function() { console.log('oneditfunc');
            //grid.jqGrid('resetSelection');
            $('input[name=manuf_vendor_name]', tr).fcom_autocomplete({
                url:'<?php echo BApp::url('Denteva_Admin', '/vendors/autocomplete') ?>'/*,
                field:'#model-manuf_vendor_id'*/
            });
        },
        successfunc:function(xhr) { console.log('successfunc');
            editAttachmentRestore(tr);
            return true;
        },
        errorfunc:function() { console.log('errorfunc'); el.show();  },
        aftersavefunc:function() { console.log('aftersavefunc'); el.show(); },
        afterrestorefunc:function() { console.log('afterrestorefunc');
            editAttachmentRestore(tr);
            return true;
        }
    });
    return false;
}

function editAttachmentSave(ev, el) {
    var el = $(el), grid = el.parents('.ui-jqgrid-btable'), tr = el.parents('tr'), rowid = tr.attr('id');
    ev.stopPropagation();
    grid.jqGrid('saveRow', rowid);
    editAttachmentRestore(tr);
    return false;
}

function editAttachmentCancel(ev, el) {
    var el = $(el), grid = el.parents('.ui-jqgrid-btable'), tr = el.parents('tr'), rowid = tr.attr('id');
    ev.stopPropagation();
    grid.jqGrid('restoreRow', rowid);
    editAttachmentRestore(tr);
    return false;
}

function downloadAttachment(ev, href, inline) {
    ev.stopPropagation();
    if (!inline) {
        $('#upload-target')[0].contentWindow.location.href = href;
    } else {
        window.open(href);
    }
    return false;
}

function deleteAttachments() {
    if (!confirm('Are you sure?')) {
        return false;
    }
    var grid = $('#all_attachments'), sel = grid.jqGrid('getGridParam', 'selarrrow'), i, postData = {'delete[]':[]};
    if (!sel.length) {
        alert('Please select some attachments to delete.');
        return;
    }
    for (i=sel.length-1; i>=0; i--) {
        grid.jqGrid('setRowData', sel[i], {status:'...'});
        postData["delete[]"].push(grid.jqGrid('getRowData', sel[i]).file_name);
    }
    $.post('<?=BApp::url('Denteva_Admin', '/promo/attachments/delete')?>?grid=all_attachments', postData, function(data, status, xhr) {
        for (i=sel.length-1; i>=0; i--) {
            grid.jqGrid('delRowData', sel[i]);
        }
    });
}

</script>