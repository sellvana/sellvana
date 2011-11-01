<? $cat = $this->category; $children = $cat->children(); ?>
<div class="main col2-layout-left">
    <?=$this->view('breadcrumbs')?>
    <div class="col-left sidebar">
<? if ($children): ?>
         <div class="block block-layered-nav">
           <div class="block-title">
                <strong><span>Browse By</span></strong>
            </div>
            <div class="block-content">
<? if ($children): ?>
                <dl id="narrow-by-list2">
                    <dt class="last odd">Category</dt>
                    <dd class="last odd">
                        <ol>
<? foreach ($children as $c): ?>
                            <li>
                                <a href="<?=$this->q($c->url())?>"><span class="count"><?=(int)$c->num_products?></span><?=$this->q($c->node_name)?></a>
                            </li>
<? endforeach ?>
                        </ol>
                    </dd>
                </dl>
<? endif ?>
            </div>
        </div>

<? endif ?>
        <?=$this->view('newsletter/block')?>
        <?=$this->view('promo/recently_viewed')?>
        <?=$this->view('cart/block')?>
        <?=$this->view('promo/weekly_specials')->set('mode', 'sidebar')?>
    </div>
    <div class="col-main">
        <div class="page-title category-title">
            <h1><?=$this->q($cat->node_name)?></h1>
        </div>

        <?=$this->view('product/list')->set('productsORM', $cat->productsORM())?>

    </div>
</div>