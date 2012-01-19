	<header class="adm-page-title">
		<span class="title">Products</span>
	</header>
	<table id="grid"></table><div id="grid-pager"></div>
<script>
//jqgrid('test', {parent:'products-grid', grid:{url:'<?=BApp::m('FCom_Catalog')->baseHref()?>/products/grid/data'}});
var lastse13;
$("#grid").jqGrid({
       url:'products/grid/data',
       editurl:'products/grid/data',
    datatype: "json",
       jsonReader: {root:'rows', page:'p', total:'mp', records:'c', repeatitems:false, id:'id'},
       colNames:['ID','Name', 'Mfr Part #'],
       colModel:[
           {name:'id',index:'id', width:55, editable:true},
           {name:'product_name',index:'product_name', width:200, editable:true},
           {name:'manuf_sku',index:'manuf_sku', width:100, editable:true}
       ],
        onSelectRow: function(id){
            if(id && id!==lastsel3){
                jQuery('#grid').jqGrid('restoreRow',lastsel3);
                jQuery('#grid').jqGrid('editRow',id,true,pickdates);
                lastsel3=id;
            }
        },
       rowNum:10,
       rowList:[10,20,30],
       pager: '#grid-pager',
       sortname: 'id',
       height:'100%',
       width:800,
    viewrecords: true,
    sortorder: "desc",
    caption:"Dynamic hide/show column groups"
}).navGrid("#grid-pager",{edit:false,add:false,del:false});

$("#hcg").click( function() {
    jQuery("#grid").jqGrid('hideCol',["amount","tax"]);
});
$("#scg").click( function() {
    jQuery("#grid").jqGrid('showCol',["amount","tax"]);
});

</script>