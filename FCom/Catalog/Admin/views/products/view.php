<?php
    $p = $this->product;
?>
<script>
var editors = {};
$(function() {
    CKEDITOR.config.autoUpdateElement = true;
    CKEDITOR.config.toolbarStartupExpanded = false;
});

var url_get = '<?php echo BApp::m('FCom_Catalog')->baseHref().'/products/view_tab/'.$p->id ?>';
var url_post = '<?php echo BApp::m('FCom_Catalog')->baseHref().'/products/edit/'.$p->id ?>';

function loadTabs(data) {
    for (var i in data.tabs) {
        $('#tab-'+i).html(data.tabs[i]).data('loaded', true);
        //var m = data.tabs[i].match(/<script[^>]>(.*?)<\/script>/i);
        //for (var i=0; i<m.
    }
}

$(function() {
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
        var pane = $(a.attr('href'));
        li.addClass('active');
        pane.removeAttr('hidden');
        curLi = li;
        curPane = pane;
        if (!pane.data('loaded')) {
            var tabId = a.attr('href').replace(/^#tab-/,'');
            $.getJSON(url_get+'?tabs='+tabId, function(data, status, req) {
                loadTabs(data);
            });
        }
        return false;
    });
});

function wysiwygCreate(id) {
    if (!editors[id]) {
        editors[id] = CKEDITOR.replace(id);
    }
}

function wysiwygDestroy(id) {
    if (editors[id]) {
        try {
            editors[id].destroy();
        } catch (e) {
            editors[id].destroy();
        }
        editors[id] = null;
    }
}

function tabClass(id, cls) {
    var tab = $('.adm-tabs-left a[href=#tab-'+id+']').parent('li');
    tab.removeClass('dirty error');
    if (cls) tab.addClass(cls);
}

function tabAction(action, el) {
    var pane = $(el).parents('.adm-tabs-content');
    var tabId = pane.attr('id').replace(/^tab-/,'');
    switch (action) {
    case 'edit':
        $.get(url_get+'?tabs='+tabId+'&mode=edit', function(data, status, req) {
            loadTabs(data);
            tabClass(tabId, 'dirty');
        });
        break;

    case 'cancel':
        $.get(url_get+'?tabs='+tabId+'&mode=view', function(data, status, req) {
            loadTabs(data);
            tabClass(tabId);
        });
        break;

    case 'save':
        $.post(url_post+'?tabs='+tabId+'&mode=view', function(data, status, req) {
            loadTabs(data);
            tabClass(tabId);
        });
        break;

    case 'dirty':
        $('.adm-tabs-left a[href=#'+tabId+']').addClass('changed');
        break;

    case 'clean':
        $('.adm-tabs-left a[href=#'+tabId+']').removelass('changed');
        break;
    }
    return false;
}
</script>
<header class="adm-page-title">
	<span class="title">View Product</span>
</header>
<section class="adm-content-box info-view-mode">
	<section class="adm-product-summary adm-section-group">
		<div class="btns-set"><button class="btn st2 sz2 btn-edit"><span>Edit</span></button></div>
		<a href="#" class="product-image"><img src="<?php echo $p->thumbUrl(98) ?>" width="98" height="98" alt="<?php echo $this->q($p->product_name) ?>"/></a>
		<h1><?php echo $this->q($p->product_name) ?></h1>
		<span class="manuf-name attr-item"><?php echo $this->q($p->manuf()->vendor_name) ?></span>
		<span class="manuf-sku attr-item"># <?php echo $this->q($p->manuf_sku) ?></span>
	</section>
	<div class="adm-content-inner">
		<div class="adm-tabs-left-bg"></div>
		<nav class="adm-tabs-left">
			<ul>
<?php foreach ($this->tabs as $k=>$tab): ?>
				<li <?php if ($k===$this->tab): ?>class="active"<?php endif ?>>
                    <a href="#tab-<?php echo $this->q($k) ?>"><?php echo $this->q($tab['label']) ?></a>
                </li>
<?php endforeach ?>
			</ul>
		</nav>
        <div class="adm-tabs-container">
<?php foreach ($this->tabs as $k=>$tab): ?>
            <section id="tab-<?php echo $this->q($k) ?>" class="adm-tabs-content"
                <?php if ($k!==$this->tab): ?>hidden<?php endif ?>
                <?php if (empty($tab['async'])): ?>data-loaded="true"<?php endif ?>
            >
<?php if (empty($tab['async'])) echo $this->view($tab['view']) ?>
            </section>
<?php endforeach ?>
        </div>
	</div>
</section>