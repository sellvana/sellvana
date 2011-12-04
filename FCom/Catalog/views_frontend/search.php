<div class="main col3-layout">
    <div class="breadcrumbs">
        <ul>
            <li class="home">
                <a href="<?=BApp::m('fcom.frontend')->baseHref()?>" title="Go to Home Page">Home</a>
            </li>
            <li>
                <strong>Search: <?=$this->q($this->query)?></strong>
            </li>
        </ul>
    </div>
    <div class="col-left sidebar">
        <div class="block block-subscribe">
            <div class="block-title">
                <strong><span>Newsletter</span></strong>
            </div>
            <form action="http://dev.unirgy.com/denteva/newsletter/subscriber/new/" method="post" id="newsletter-validate-detail">
                <div class="block-content">
                    <label for="newsletter">Sign Up for Our Newsletter:</label>
                    <div class="input-box">
                        <input type="text" name="email" id="newsletter" title="Sign up for our newsletter" class="input-text required-entry validate-email">
                    </div>
                    <div class="actions">
                        <button type="submit" title="Subscribe" class="button"><span><span>Subscribe</span></span></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="col-main">
        <div class="page-title category-title">
            <h1>Search: <?=$this->q($this->query)?></h1>
        </div>

        <?=$this->view('product/list')?>

    </div>
    <div class="col-right sidebar">
        <div class="block block-list block-viewed">
            <div class="block-title">
                <strong><span>Recently Viewed Products</span></strong>
            </div>
            <div class="block-content">
                <ol id="recently-viewed-items">
                    <li class="item last odd">
                        <p class="product-name"><a href="http://dev.unirgy.com/denteva/tray-acrylic-pwd-3lb.html">Tray Acrylic Material white, pwd/liq, 3lb/16oz</a></p>
                    </li>
                </ol>
            </div>
        </div>
        <div class="block block-cart">
            <div class="block-title">
                <strong><span>My Cart</span></strong>
            </div>
            <div class="block-content">
                <p class="empty">You have no items in your shopping cart.</p>
            </div>
        </div>
        <div class="block block-list block-compare">
            <div class="block-title">
                <strong><span>Compare Products                    </span></strong>
            </div>
            <div class="block-content">
                <p class="empty">You have no items to compare.</p>
            </div>
        </div>
        <div class="block">
            <div class="block-title">Weekly Specials</div>
            <div class="block-content">
                <ul class="products-list">
                    <li class="item">
                        <a href="#" class="product-image"><img src="http://dev.unirgy.com/denteva/skin/frontend/denteva/default/images/fpo/product_img_sm.jpg" alt="A2/B2, Compact Tip Refills, 0.25 gm/ea, pk20, 7837" width="160" height="160"></a>
                        <h4 class="product-name"><a href="#">A2/B2, Compact Tip Refills, 0.25 gm/ea, pk20, 7837</a></h4>
                        <div class="price-box">
                            As low as $29.72
                        </div>
                        <p class="product-description">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum.</p>
                    </li>
                    <li class="item last">
                        <a href="#" class="product-image"><img src="http://dev.unirgy.com/denteva/skin/frontend/denteva/default/images/fpo/product_img_sm.jpg" alt="A2/B2, Compact Tip Refills, 0.25 gm/ea, pk20, 7837" width="160" height="160"></a>
                        <h4 class="product-name"><a href="#">A2/B2, Compact Tip Refills, 0.25 gm/ea, pk20, 7837</a></h4>
                        <div class="price-box">
                            As low as $29.72
                        </div>
                        <p class="product-description">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum.</p>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>