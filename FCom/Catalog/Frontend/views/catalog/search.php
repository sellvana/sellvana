<div class="main col3-layout">
    <div class="breadcrumbs">
        <ul>
            <li class="home">
                <a href="<?=BApp::baseUrl()?>" title="<?= BLocale::_("Go to Home Page") ?>"><?= BLocale::_("Home") ?></a>
            </li>
            <li>
                <strong><?= BLocale::_("Search") ?>: <?=$this->q($this->query)?></strong>
            </li>
        </ul>
    </div>

    <div class="col-main">
        <div class="page-title category-title">
            <h1><?= BLocale::_("Search") ?>: <?=$this->q($this->query)?></h1>
        </div>

        <?=$this->view('catalog/product/list')?>

    </div>
    <div class="col-right sidebar">
        <div class="block block-list block-viewed">
            <div class="block-title">
                <strong><span><?= BLocale::_("Recently Viewed Products") ?></span></strong>
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
                <strong><span><?= BLocale::_("My Cart") ?></span></strong>
            </div>
            <div class="block-content">
                <p class="empty"><?= BLocale::_("You have no items in your shopping cart") ?>.</p>
            </div>
        </div>
        <div class="block block-list block-compare">
            <div class="block-title">
                <strong><span><?= BLocale::_("Compare Products") ?>                    </span></strong>
            </div>
            <div class="block-content">
                <p class="empty"><?= BLocale::_("You have no items to compare") ?>.</p>
            </div>
        </div>
        <div class="block">
            <div class="block-title"><?= BLocale::_("Weekly Specials") ?></div>
            <div class="block-content">
                <ul class="products-list">
                    <li class="item">
                        <a href="#" class="product-image"><img src="" alt="A2/B2, Compact Tip Refills, 0.25 gm/ea, pk20, 7837" width="160" height="160"></a>
                        <h4 class="product-name"><a href="#">A2/B2, Compact Tip Refills, 0.25 gm/ea, pk20, 7837</a></h4>
                        <div class="price-box">
                            <?= BLocale::_("As low as") ?> $29.72
                        </div>
                        <p class="product-description">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum.</p>
                    </li>
                    <li class="item last">
                        <a href="#" class="product-image"><img src="" alt="A2/B2, Compact Tip Refills, 0.25 gm/ea, pk20, 7837" width="160" height="160"></a>
                        <h4 class="product-name"><a href="#">A2/B2, Compact Tip Refills, 0.25 gm/ea, pk20, 7837</a></h4>
                        <div class="price-box">
                            <?= BLocale::_("As low as") ?> $29.72
                        </div>
                        <p class="product-description">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum.</p>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>


<script type='text/javascript'>
var publicApiUrl = "<?=$this->public_api_url?>";
var indexName = "<?=$this->index_name?>";
var elementId = "#query";
var remoteSource = publicApiUrl + "/v1/indexes/" + indexName + "/autocomplete";
</script>

<script src='https://www.google.com/jsapi' type='text/javascript'></script>

<script type='text/javascript'>
			var theme = "flick";
			google.load("jqueryui", "1.8.17");
			google.loader.writeLoadTag("css", "https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.17/themes/" + theme + "/jquery-ui.css");
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