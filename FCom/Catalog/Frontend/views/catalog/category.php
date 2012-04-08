<? $category = BApp::i()->get('current_category') ?>
<div class="page-title category-title">
    <h1><?=$this->q($category->node_name)?></h1>
</div>
<?=$this->view('catalog/product/list')
    ->set('category', $category) ?>
