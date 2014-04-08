<?php foreach ($this->getMessages() as $msg): ?>
    <div class="alert alert-dismissable alert-<?= !empty($msg['class']) ? $this->q($msg['class']) : '' ?>">
        <a class="close" data-dismiss="alert" href="#">&times;</a>
        <?php if (!empty($msg['title'])): ?>
            <h4>
                <i class="<?= !empty($msg['icon']) ? 'icon-'.$this->q($msg['icon']).'-sign' : '' ?>"></i>
                <?= $this->q($msg['title']) ?>
            </h4>
        <?php else: ?>
            <i class="<?= !empty($msg['icon']) ? 'icon-'.$this->q($msg['icon']).'-sign' : '' ?>"></i>
        <?php endif ?>
        <?php if (!empty($msg['msgs'])): ?>
            <?php foreach ($msg['msgs'] as $m): ?>
                <?= $this->q($m) ?><br>
            <?php endforeach ?>
        <?php else: ?>
            <?= $this->q($msg['msg']) ?>
        <?php endif ?>
    </div>
<?php endforeach ?>
