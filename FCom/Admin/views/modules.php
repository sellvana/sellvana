<form id="form-modules" method="post" name="form-modules" action="<?php echo BApp::href('modules')?>">
<header class="adm-page-title">
    <span class="title">Modules</span>
    <div class="btns-set">
        <button type="button" class="st1 sz2 btn" onclick="location.href='<?php echo BApp::href('modules/market')?>'"><span>Download New Modules</span></button>
        <button type="button" class="st1 sz2 btn" onclick="return runMigrationScripts()"><span>Run Migration Scripts</span></button>
        <button type="button" class="st1 sz2 btn" onclick="this.form.submit()"><span>Save Changes</span></button>
    </div>
</header>
<?php echo $this->messagesHtml() ?>
<style>
tr.module-disabled td { background:#DDD; }
tr.module-requested {}
</style>
<script>

var runLevelColors = {'DISABLED':'#CCC', 'ONDEMAND':'#FFF', '':'#FFF', 'REQUESTED':'#CCF', 'REQUIRED':'#CFC'};
var bypassModules = {'FCom_Core':1,'FCom_Admin':1,'FCom_Frontend':1,'FCom_Install':1};
var runStatusColors = {'IDLE':'', 'LOADED':'#CFC', 'ERROR':'#FCC'};

function runMigrationScripts() {
    $('#form-modules').attr('action', '<?php echo BApp::href('modules/migrate') ?>').submit();
}

function fmtRunLevel(area) {
    return function(val,opt,row) {
        if (!area || bypassModules[opt.rowId]) {
            return [
                runStatusColors[val] ? '<div style="padding:3px; color:#000; background:'+runLevelColors[val]+'">' : '<div>'
                ,val,
                ,'</div>'
            ].join('');
        }
        var options = opt.colModel.editoptions.value.split(';');
        var html = [
            '<select id="module_run_level-', area, '-', escape(opt.rowId), '"',
            ' title="', escape(opt.rowId), '"',
            ' name="module_run_level[', area, '][', escape(opt.rowId),']"',
            ' style="background:', runLevelColors[val], '"',
            ' onchange="return fmtRunLevelChange(this)">'
        ];
        for (var i=0, l=options.length, a; i<l; i++) {
            a = options[i].split(':');
            html.push([
                '<option value="', escape(a[0]), '"',
                ' style="background:', runLevelColors[a[1]], '"',
                (val==a[0] ? ' selected' : ''),
                '>', escape(a[1]), '</option>'
            ].join(''));
        }
        html.push('</select>');
        return html.join('');
    }
}

function fmtRunLevelChange(el) {
    $(el).css({background:runLevelColors[el.value]});
}

function fmtRunStatus(val,opt,row) {
    return [
        runStatusColors[val] ? '<div style="padding:3px; color:#000; background:'+runStatusColors[val]+'">' : '<div>'
        ,val,
        ,'</div>'
    ].join('');
}

function fmtSchemaVersion(val,opt,row) {
    console.log(val,row);
    if (val===null) {
        return '';
    } else if (val==row.version) {
        return val;
    } else {
        return '<div style="padding:3px; color:000; background:#FCC">'+val+'</div>';
    }
}
</script>
<?=$this->view('jqgrid')?>
</form>