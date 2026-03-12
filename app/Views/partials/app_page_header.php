<?php
$title = $title ?? '';
$desc  = $desc ?? '';
$actions = $actions ?? '';
?>

<div class="app-page-head">
    <div class="app-page-head-left">
        <h4 class="mb-1"><?= esc($title) ?></h4>
        <?php if ($desc !== ''): ?>
            <div class="app-page-head-desc"><?= esc($desc) ?></div>
        <?php endif; ?>
    </div>

    <?php if ($actions !== ''): ?>
        <div class="app-page-tools"><?= $actions ?></div>
    <?php endif; ?>
</div>