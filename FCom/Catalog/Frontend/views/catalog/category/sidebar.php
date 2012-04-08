<?
$category = $this->category ? $this->category : BApp::i()->get('current_category');
$children = $category->children();
?>
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