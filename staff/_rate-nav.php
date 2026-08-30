<?php declare(strict_types=1); ?>
<nav class="settings-tabs mb-4" aria-label="Rate management">
    <?php foreach (['rate' => ['Rate cards','staff/rates.php'], 'zone' => ['Origins & destinations','staff/rate-options.php?kind=zone'], 'service' => ['Services','staff/rate-options.php?kind=service']] as $rateNavKey => [$rateNavLabel,$rateNavPath]): ?>
    <a href="<?= e(url($rateNavPath)) ?>" class="<?= $rateTab === $rateNavKey ? 'active' : '' ?>" <?= $rateTab === $rateNavKey ? 'aria-current="page"' : '' ?>><?= e($rateNavLabel) ?></a>
    <?php endforeach; ?>
</nav>
