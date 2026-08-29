<?php declare(strict_types=1); ?>
        </main>
    </div>
</div>
<script src="<?= e(url('assets/js/bootstrap.min.js')) ?>"></script>
<script>
document.querySelector('[data-staff-menu]')?.addEventListener('click', () => {
    document.querySelector('[data-staff-sidebar]')?.classList.toggle('is-open');
});
</script>
</body>
</html>

