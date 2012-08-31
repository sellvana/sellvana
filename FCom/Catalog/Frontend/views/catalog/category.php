<?php $category = BApp::i()->get('current_category') ?>
		<header class="page-title category-title">
	    	<h1 class="title"><?=$this->q($category->node_name)?></h1>
	    </header>
        <?=$this->hook('main_products') ?>