<header class="adm-page-title">
    <span class="title"><?=$this->q($this->title)?></span>
    <div class="btns-set"><?=join(' ', (array)$this->actions)?></div>
</header>
<?=$this->messagesHtml('admin')?>
<?=$this->view('jqgrid')?>
