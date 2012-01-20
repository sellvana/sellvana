<?php
    $p = $this->product;
?>
<header class="adm-page-title">
    <span class="title">Edit Product</span>
</header>
<section class="adm-content-box info-view-mode">
    <section class="adm-product-summary adm-section-group">
        <button class="btn st2 sz2 btn-edit"><span>Edit</span></button>
        <a href="#" class="product-image"><img src="<?php echo $p->thumbUrl(118) ?>" width="118" height="118" alt=""/></a>
        <h1><?php echo $this->q($p->product_name) ?></h1>
        <span class="manuf-name attr-item"><?php echo $this->q($p->manuf()->vendor_name) ?></span>
        <span class="manuf-sku attr-item"># <?php echo $this->q($p->manuf_sku) ?></span>
    </section>
    <div class="adm-content-inner">
        <div class="adm-tabs-left-bg"></div>
        <nav class="adm-tabs-left">
            <ul>
                <li class="active"><a href="#tab-general-info">General Info</a></li>
                <li><a href="#tab-attributes">Attributes</a></li>
                <li><a href="#tab-related-products">Related Products</a></li>
                <li><a href="#tab-family-products">Family Products</a></li>
                <li><a href="#tab-similar-products">Similar Products</a></li>
                <li><a href="#tab-categories">Categories</a></li>
                <li><a href="#tab-attachments">Attachments</a></li>
                <li><a href="#tab-images">Images</a></li>
                <li><a href="#tab-vendors">Vendors</a></li>
                <li><a href="#tab-product-reviews">Product Reviews</a></li>
                <li><a href="#tab-promotions">Promotions</a></li>
            </ul>
        </nav>
        <div class="adm-tabs-container">
            <section id="tab-general-info" class="adm-tabs-content">
                <form method="#" action="#" class="adm-section-group">
                    <fieldset>
                        <button class="btn st2 sz2 btn-edit"><span>Edit</span></button>
                        <ul class="form-list">
                            <li>
                                <h4 class="label">Short Description</h4>
                                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus vestibulum convallis varius. Donec et odio quis est blandit mattis.
                            </li>
                            <li>
                                <h4 class="label">Long Description</h4>
                                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus vestibulum convallis varius. Donec et odio quis est blandit mattis. Aliquam ac nisl magna, sit amet vestibulum ipsum. Vestibulum ultrices justo sagittis ante interdum volutpat. Curabitur ullamcorper, neque pulvinar commodo gravida, augue tellus interdum nulla, a pulvinar leo nisi ac nisl. Nullam bibendum luctus sem, eget interdum leo blandit auctor. Integer ullamcorper tellus non justo ultrices tempor. Vivamus eu augue justo. Suspendisse ut neque nec neque ultrices aliquam dictum sed orci.
                            </li>
                            <li>
                                <h4 class="label">Unit of Measures</h4>
                            </li>
                        </ul>
                    </fieldset>
                </form>
            </section>
            <section id="tab-attributes" class="adm-tabs-content" hidden>
                <form method="#" action="#" class="adm-section-group">
                    <fieldset>
                        <button class="btn st2 sz2 btn-edit"><span>Edit</span></button>
                        <ul class="form-list">
                            <li>
                                <h4 class="label">Attribute 1</h4>
                                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus vestibulum convallis varius. Donec et odio quis est blandit mattis.
                            </li>
                            <li>
                                <h4 class="label">Attribute 2</h4>
                                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus vestibulum convallis varius. Donec et odio quis est blandit mattis. Aliquam ac nisl magna, sit amet vestibulum ipsum. Vestibulum ultrices justo sagittis ante interdum volutpat. Curabitur ullamcorper, neque pulvinar commodo gravida, augue tellus interdum nulla, a pulvinar leo nisi ac nisl. Nullam bibendum luctus sem, eget interdum leo blandit auctor. Integer ullamcorper tellus non justo ultrices tempor. Vivamus eu augue justo. Suspendisse ut neque nec neque ultrices aliquam dictum sed orci.
                            </li>
                            <li>
                                <h4 class="label">Attribute 3</h4>
                            </li>
                        </ul>
                    </fieldset>
                </form>
            </section>
            <section id="tab-related-products" class="adm-tabs-content" hidden
                data-src="<?=BApp::m('FCom_Catalog')->baseHref().'/products/view/'.$p->id.'/tab/related-products'?>"></section>
        </div>
    </div>
</section>
<script>
(function() {
    var lis = $('.adm-tabs-left li');
    var panes = $('.adm-tabs-content');
    var curLi = $('.adm-tabs-left li[class=active]');
    var curPane = $('.adm-tabs-content:not([hidden])');
    $('a', lis).click(function(ev) {
        curLi.removeClass('active');
        curPane.attr('hidden', 'hidden');

        var a = $(ev.currentTarget), li = a.parent('li');
        if (curLi===li) {
            return false;
        }
        var pane = $(a.attr('href')), src = pane.data('src');
        li.addClass('active');
        pane.removeAttr('hidden');
        curLi = li;
        curPane = pane;
        if (src && !pane.data('loaded')) {
            pane.load(src, function(data, status, req) {
                if (status=='success') {
                    pane.data('loaded', true);
                }
            });
        }
        return false;
    });
})();
</script>