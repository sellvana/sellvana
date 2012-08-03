<div class="main col3-layout">
    <div class="breadcrumbs">
        <ul>
            <li class="home">
                <a href="<?=BApp::baseUrl()?>" title="<?= BLocale::_("Go to Home Page"); ?>"><?= BLocale::_("Home"); ?></a>
            </li>
            <li>
                <strong><?= BLocale::_("Search"); ?>: <?=$this->q($this->query)?></strong>
            </li>
        </ul>
    </div>

    <div class="col-main">
        <div class="page-title category-title">
            <h1><?= BLocale::_("Search"); ?>: <?=$this->q($this->query)?></h1>
        </div>

        <?=$this->view('catalog/product/list')?>

    </div>
    <div class="col-right sidebar">
        <div class="block block-list block-viewed">
            <div class="block-title">
                <strong><span><?= BLocale::_("Recently Viewed Products"); ?></span></strong>
            </div>
            <div class="block-content">
                <ol id="recently-viewed-items">
                    <li class="item last odd">
                        <p class="product-name"><a href="">Tray Acrylic Material white, pwd/liq, 3lb/16oz</a></p>
                    </li>
                </ol>
            </div>
        </div>
        <div class="block block-cart">
            <div class="block-title">
                <strong><span><?= BLocale::_("My Cart"); ?></span></strong>
            </div>
            <div class="block-content">
                <p class="empty"><?= BLocale::_("You have no items in your shopping cart"); ?>.</p>
            </div>
        </div>
        <div class="block block-list block-compare">
            <div class="block-title">
                <strong><span><?= BLocale::_("Compare Products"); ?>                    </span></strong>
            </div>
            <div class="block-content">
                <p class="empty"><?= BLocale::_("You have no items to compare"); ?>.</p>
            </div>
        </div>
        <div class="block">
            <div class="block-title"><?= BLocale::_("Weekly Specials"); ?></div>
            <div class="block-content">
                <ul class="products-list">
                    <li class="item">
                        <a href="#" class="product-image"><img src="" alt="A2/B2, Compact Tip Refills, 0.25 gm/ea, pk20, 7837" width="160" height="160"></a>
                        <h4 class="product-name"><a href="#">A2/B2, Compact Tip Refills, 0.25 gm/ea, pk20, 7837</a></h4>
                        <div class="price-box">
                            <?= BLocale::_("As low as"); ?> $29.72
                        </div>
                        <p class="product-description">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum.</p>
                    </li>
                    <li class="item last">
                        <a href="#" class="product-image"><img src="" alt="A2/B2, Compact Tip Refills, 0.25 gm/ea, pk20, 7837" width="160" height="160"></a>
                        <h4 class="product-name"><a href="#">A2/B2, Compact Tip Refills, 0.25 gm/ea, pk20, 7837</a></h4>
                        <div class="price-box">
                            <?= BLocale::_("As low as"); ?> $29.72
                        </div>
                        <p class="product-description">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum.</p>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>