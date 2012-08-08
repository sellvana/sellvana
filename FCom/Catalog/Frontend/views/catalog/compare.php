<? $xhr = BRequest::i()->xhr() ?>
<style>
ul.compare { border:solid 1px #aaa; }
ul.compare li { display:block; float:left; width:240px; border-left:solid 1px #aaa; text-align:center; }

</style>

<div class="main col1-layout">
    <? if (!$xhr) echo $this->view('breadcrumbs') ?>
    <div class="col-main">
        <? if (!$xhr): ?><a href="<?=$this->q(FCom_Core::lastNav())?>">&lt;&lt; <?= BLocale::_("Back to results"); ?></a><? endif ?>

        <div class="page-title category-title">
            <h1>Compare <span class="compare-num-products"><?=sizeof($this->products)?></span> products</h1>
        </div>

        <ul class="compare">
<? for ($i=0; $i<4; $i++): ?>
            <li>
<? if (!empty($this->products[$i])): $p = $this->products[$i]; ?>
                <p><a href="#" class="remove-trigger" onclick="$('.block-compare').data('compare').remove(<?=$p->id?>, this)"><?= BLocale::_("Remove"); ?></a></p>
                <p><img src="<?=$p->thumbUrl(85, 60)?>" width="85" height="60" class="product-img"/></p>
                <h3 class="product-name"><a href="<?=$this->q($p->url($this->category))?>"><?=$this->q($p->product_name)?></a></h3>
                <span class="manuf-name"><?=$this->q($p->manuf()->manuf_name)?></span>
                <span class="sku"><?= BLocale::_("Part"); ?> #: <?=$this->q($p->manuf_sku)?></span>
                <span class="rating">
                    <span class="rating-out"><span class="rating-in" style="width:35px"></span></span>
                    3.5 of 5 (<a href="#">16 reviews</a>)
                </span>
                <button class="button btn-add-to-cart" onclick="dentevaCart.add(<?=$p->id?>)">+ <?= BLocale::_("Add to Cart"); ?></button>
<? else: ?>
&nbsp;
<? endif ?>
            </li>
<? endfor ?>
        </ul>

    </div>
</div>
<? if (!$xhr): ?>
<script>
var compare = new FulleronCompare({emptyUrl:'<?=FCom_Core::lastNav()?>'});
</script>
<? endif ?>
