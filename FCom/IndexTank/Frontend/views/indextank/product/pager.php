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
			    data: { query: request.term, field: 'description' },
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
if(empty($s['p'])) $s['p'] = 0;
//$price_ranges = $this->price_ranges;

$psOptions = array(2, 25, 50, 100, 500, 30000);
$sortOptions = $this->sort_options ? $this->sort_options : array(
    '' => 'Sort...',
    'relevance' => 'Relevance',
    'base_price_asc' => 'Price (Lower first)',
    'base_price_desc' => 'Price (Higher first)',
);

?>


<div class="pager">
    <form id="product_list_pager" name="product_list_pager" autocomplete="off" method="get" action="">
    <strong class="count"><?=!empty($s['c'])?$s['c']:0?> found.</strong>
    <input type="text" name="q" id="query" autocomplete="off" value="<?=$this->q(BRequest::i()->get('q'))?>"/>
    <input type="submit" value="Search">

    <br/>
    <div class="pages">
    <label>Page:</label>

    <? if ($s['p']>1): ?><a href="#" class="arrow-left" onclick="$(this).siblings('input[name=p]').val(<?=$s['p']-1?>); $(this).parents('form').submit()">&lt;</a><? endif ?>
    <!--<select name="p" onchange="this.form.submit()">
<? for ($i=1; $i<=$s['mp']; $i++): ?>
        <option value="<?=$i?>" <?=$s['p']==$i?'selected':''?>><?=$i?></option>
<? endfor ?>
    </select>-->
    <input type="text" id="p" name="p" value="<?=$s['p']?>"/> of <?=$s['mp']?>
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
    <br/>
        <br/>
        <?=$this->view('indextank/product/_pager_categories')->set('s', $s)?>
        <br/>

        <a href="/indextank/search?q=<?=$this->q(BRequest::i()->get('q'))?>">Clear filters</a>
        <br/>

<?php foreach($s['available_facets'] as $label => $data):?>
        <label><?=$label?>:</label><br/>
        <? foreach ($data as $obj): ?>
                <? if(!empty($s['filter_selected'][$obj->key]) && in_array($obj->name, $s['filter_selected'][$obj->key])):?>
                    <a style="color:grey;" href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array($obj->param => ''))?>"><?=$obj->name?> (<?=$obj->count?>)</a>
                    <?php if(true == $s['save_filter']):?>
                        <input type="hidden" name="<?=$obj->param?>" value="<?=$obj->name?>" />
                    <?php endif; ?>
                <?php else:?>
                    <a href="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array($obj->param => $obj->name))?>"><?=$obj->name?> (<?=$obj->count?>)</a>
                <?php endif; ?>
                <br/>
        <? endforeach ?>
                <br/>
<?php endforeach; ?>

</div>

</form>