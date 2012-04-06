<header class="adm-page-title">
    <span class="title">Users</span>
    <div class="btns-set">
        <button class="st1 sz2 btn" onclick="location.href='<?php echo BApp::href('users/form/')?>'"><span>New User</span></button>
    </div>
</header>
<?=$this->view('jqgrid')?>
