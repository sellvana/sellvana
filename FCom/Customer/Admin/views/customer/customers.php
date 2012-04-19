<header class="adm-page-title">
    <span class="title">CMS Pages</span>
    <div class="btns-set">
        <button class="st1 sz2 btn" onclick="location.href='<?php echo BApp::href('customers/form/')?>'"><span>New Customer</span></button>
    </div>
</header>
<?php echo $this->view('jqgrid') ?>