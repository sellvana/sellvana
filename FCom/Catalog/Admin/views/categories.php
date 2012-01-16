<style>
    #categories-container ui-layout-north { overflow:hidden; }
    #toolbar {
        display:block;
        width:100%;
        padding: 2px 5px;
    }
    .ui-widget-header { padding:2px 5px; }
    .details-pane { height:100%; }
</style>
<div id="main-layout" style="height:700px">
    <div id="categories-container" class="ui-layout-west">
        <div class="ui-layout-north">
            <div class="ui-widget-header">
                <input type="checkbox" id="category-tree-lock"/>
                <input type="checkbox" id="category-expand-collapse"/>
            </div>
        </div>
        <div id="categories" class="ui-layout-center"></div>
    </div>
    <div id="details-pane" class="ui-layout-center" >
        <div class="ui-layout-north">
            <div id="details-tabs" class="ui-widget-header">
                <input type="radio" title="Category Info" value="details-info"/><input type="radio" title="Products" value="details-products"/><input type="radio" title="Attribute Sets" value="details-attrsets"/><input type="radio" title="Merge Aliases" value="details-aliases"/>
            </div>
        </div>
        <div id="details-pane-inner" class="ui-layout-center">
            <div id="details-info" class="details-pane"></div>
            <div id="details-products" class="details-pane grid-container" rel="products"></div>
            <div id="details-attrsets" class="details-pane"></div>
            <div id="details-aliases" class="details-pane grid-container" rel="category-aliases"></div>
        </div>
    </div>
</div>
<script>
    $(function() {
        Admin.layout('#main-layout', {root:{margin:30}, layout:{west: {resizable:true, size:400}}}).resizeAll();
        Admin.layout('#categories-container', {layout:{north:{spacing_open:0}}});
        Admin.layout('#details-pane', {layout:{north:{spacing_open:0}}, pub:{resize:{center:1}}});

        Admin.checkboxButton('#category-tree-lock', {def:true, off:{icon:'unlocked', label:'Unlocked'}, on:{icon:'locked', label:'Locked'}});
        Admin.checkboxButton('#category-expand-collapse', {
            off:{icon:'triangle-1-e', label:'Expand All'}, on:{icon:'triangle-1-s', label:'Collapse All'},
            click:function(ev) { $('#categories').jstree(this.checked?'open_all':'close_all', $('#1>ul>li')); }
            //TODO: fetch ancestors only for root node
        });

        Admin.buttonsetTabs('#details-tabs');

        Admin.ajaxCache('<?=BApp::m('Denteva_Merge')->baseHref()?>/categories/config', function(config) {
            Admin.tree('#categories', {url:'<?=BApp::m('FCom_Catalog')->baseHref()?>/api/category_tree', lock_flag:'#category-tree-lock'});

            Admin.slick('#details-products', config.products_grid);
            Admin.slick('#details-aliases', config.aliases_grid);

            $.subscribe('select.jstree', function(n) {
                //$('#details-pane-inner').load(
            });
        });
    });
</script>