<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.min.js"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.11/jquery-ui.min.js"></script>
<script type='text/javascript'>
var publicApiUrl = "http://enavev.api.indexden.com";
var indexName = "products";
var elementId = "#query";
var remoteSource = publicApiUrl + "/v1/indexes/" + indexName + "/autocomplete";
</script>
<script src='https://www.google.com/jsapi' type='text/javascript'></script>
			<script type='text/javascript'>
			var theme = "flick";
			google.load("jqueryui", "1.8.7");
			google.loader.writeLoadTag("css", "https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.7/themes/" + theme + "/jquery-ui.css");
			</script>
<script type='text/javascript'>
			google.setOnLoadCallback(function() {
			$(function() {

			var sourceCallback = function( request, responseCallback ) {
			  $.ajax( {
			    url: remoteSource,
			    dataType: "jsonp",
			    data: { query: request.term },
			    success: function( data ) { responseCallback( data.suggestions ); }
			  } );
			};

			var selectCallback = function( event, ui ) {
			  event.target.value = ui.item.value;
			  event.target.form.submit();
			};

			$( elementId ).autocomplete( {
			  source: sourceCallback,
			  minLength: 2,
			  delay: 100,
			  select: selectCallback
			} );

			}); // $ fun
			}); // g callback

</script>

<?php
$s = $this->state;
$price_ranges = $this->price_ranges;

$psOptions = array(25, 50, 100, 500, 30000);
$sortOptions = $this->sort_options ? $this->sort_options : array(
    '' => 'Sort...',
    'relevance' => 'Relevance',
    'base_price_asc' => 'Price (Lower first)',
    'base_price_desc' => 'Price (Higher first)',
);

?>
<form id="product_list_pager" name="product_list_pager" method="get" action="">

<div class="pager">
    <strong class="count"><?=$s['c']?> found.</strong>
    <input type="text" name="q" id="query" autocomplete="off" value="<?=$this->q(BRequest::i()->get('q'))?>"/>
    <input type="submit" value="Search">
    
    <label>Query mode: <?=$s['info']['query_mode']?></label>
    <br/>
    <div class="pages">
    <label>Page:</label>

    <? if ($s['p']>1): ?><a href="#" class="arrow-left" onclick="$(this).siblings('input[name=p]').val(<?=$s['p']-1?>); $(this).parents('form').submit()">&lt;</a><? endif ?>
    <!--<select name="p" onchange="this.form.submit()">
<? for ($i=1; $i<=$s['mp']; $i++): ?>
        <option value="<?=$i?>" <?=$s['p']==$i?'selected':''?>><?=$i?></option>
<? endfor ?>
    </select>-->
    <input type="text" name="p" value="<?=$s['p']?>"/> of <?=$s['mp']?>
    <? if ($s['p']<$s['mp']): ?><a href="#" class="arrow-right" onclick="$(this).siblings('input[name=p]').val(<?=$s['p']+1?>); $(this).parents('form').submit()">&gt;</a><? endif ?>
	</div>
	<div class="rows f-right">
    <label>Rows:</label> <select name="ps" onchange="this.form.submit()">
<? foreach ($psOptions as $i): ?>
        <option value="<?=$i?>" <?=$s['ps']==$i?'selected':''?>><?=$i?></option>
<? endforeach ?>
    </select>
	</div>
    <div class="sort-by f-right">
    <label>Sort:</label> <select name="sc" onchange="this.form.submit()">
<? foreach ($sortOptions as $k=>$v): ?>
        <option value="<?=$k?>" <?=$s['sc']==$k?'selected':''?>><?=$v?></option>
<? endforeach ?>
    </select>
    </div>
    <br/><br/>
    <div class="sort-by f-left">
    <label>Filter by price:</label><br/>
<? foreach ($s['filter'][FCom_IndexTank_Index_Product::CT_PRICE_RANGE] as $price): ?>
        <input type="checkbox" name="f[<?=FCom_IndexTank_Index_Product::CT_PRICE_RANGE?>][]"
               value="<?=$price->name?>" onclick="this.form.submit()"
               <?=(in_array($price->name, $s['filter_selected'][FCom_IndexTank_Index_Product::CT_PRICE_RANGE]))?'checked':''?>
               >  <?=$price->name?>
                (<?=$price->count?>) <br/>
<? endforeach ?>
                <br/>
                <label>Price range</label><br/>
From: <input type="text" size="5" name="v[price][from]" value="<?=$s['filter']['price']['from']?>">
    to <input type="text" size="5" name="v[price][to]" value="<?=$s['filter']['price']['to']?>">
    <br/><br/>
    <label>Filter by brand:</label><br/>
<? foreach ($s['filter'][FCom_IndexTank_Index_Product::CT_BRAND] as $brand): ?>
        <input type="checkbox" name="f[<?=FCom_IndexTank_Index_Product::CT_BRAND?>][]"
               value="<?=$brand->name?>" onclick="this.form.submit()"
               <?=(in_array($brand->name, $s['filter_selected'][FCom_IndexTank_Index_Product::CT_BRAND]))?'checked':''?>
               >  <?=$brand->name?>
        (<?=$brand->count?>)<br/>
<? endforeach ?>
        <br/>
    <label>Categories:</label><br/>
<? foreach ($s['filter'][FCom_IndexTank_Index_Product::CT_CATEGORY_PREFIX] as $cat_name => $cat_obj): ?>
    <?php for($i = 2; $i < strlen($cat_name); $i++) echo "+"; ?>
        <input type="checkbox" name="f[<?=FCom_IndexTank_Index_Product::CT_CATEGORY_PREFIX.$cat_name?>]"
               value="<?=$cat_obj->name?>" onclick="this.form.submit()"
               <?=(!empty($_GET['f'][FCom_IndexTank_Index_Product::CT_CATEGORY_PREFIX.$cat_name]))?'checked':''?>
               >
        <?=$cat_obj->name?> (<?=$cat_obj->count?>) <br/>
<? endforeach ?>

         <br/>
<? foreach ($s['filter'][FCom_IndexTank_Index_Product::CT_CUSTOM_FIELD_PREFIX] as $cat_name => $cat_obj_list): ?>
    <label><?=$cat_name?></label><br/>
    <?php foreach($cat_obj_list as $cat_obj): ?>
        <input type="checkbox" name="f[<?=FCom_IndexTank_Index_Product::CT_CUSTOM_FIELD_PREFIX.$cat_obj->path?>][]"
               value="<?=$cat_obj->name?>" onclick="this.form.submit()"
               <?=(in_array($cat_obj->name, $_GET['f'][FCom_IndexTank_Index_Product::CT_CUSTOM_FIELD_PREFIX.$cat_obj->path]))?'checked':''?>
               >
        <?=$cat_obj->name?> (<?=$cat_obj->count?>) <br/>
    <?php endforeach ?>
<? endforeach ?>
    </div>
</div>

</form>