<?php
declare(strict_types=1);
// Isolate partial variables from the including page.
(static function (): void {
    $links = social_media_links();
    if ($links === []) { return; }
?>
<nav class="easyway-social-links" aria-label="Easyway Logistics social media">
    <?php foreach ($links as $link): ?>
        <a href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e('Easyway Logistics on ' . $link['name'] . ' (opens in a new tab)') ?>"><i class="bi <?= e($link['icon']) ?>" aria-hidden="true"></i><span><?= e($link['name']) ?></span></a>
    <?php endforeach; ?>
</nav>
<?php })(); ?>
