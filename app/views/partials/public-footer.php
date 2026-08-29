<?php declare(strict_types=1); ?>
</main>
<footer class="stage1-footer">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4">
                <img class="stage1-footer-logo" src="<?= e(url('assets/img/easyway/logo.jpg')) ?>" alt="Easyway Logistics">
                <p>Careful handling, clear communication and dependable delivery support for individuals and businesses.</p>
                <p><i class="bi bi-geo-alt"></i> <?= e(company_address()) ?></p>
                <a class="stage1-inline-link" href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-whatsapp"></i> Chat with our team</a>
            </div>
            <div class="col-6 col-lg-2">
                <h2>Company</h2>
                <a href="<?= e(url('about.php')) ?>">About Us</a>
                <a href="<?= e(url('contact.php')) ?>">Contact</a>
                <a href="<?= e(url('staff/login.php')) ?>">Staff Portal</a>
                <a href="<?= e(url(App\CustomerAuth::check() ? 'customer/index.php' : 'customer/login.php')) ?>">Customer Account</a>
            </div>
            <div class="col-6 col-lg-3">
                <h2>Solutions</h2>
                <a href="<?= e(url('services.php')) ?>">Delivery Services</a>
                <a href="<?= e(url('destinations.php')) ?>">International Destinations</a>
                <a href="<?= e(url('cargo-services.php')) ?>">Cargo Services</a>
                <a href="<?= e(url('packaging-materials.php')) ?>">Packaging Materials</a>
            </div>
            <div class="col-lg-3">
                <h2>Get support</h2>
                <a href="<?= e(url('tracking.php')) ?>">Track a shipment</a>
                <a href="<?= e(url('quote.php')) ?>">Request a quote</a>
                <a href="<?= e(url('calculator.php')) ?>">Calculate a rate</a>
                <a href="mailto:<?= e(support_email()) ?>"><?= e(support_email()) ?></a>
                <?php foreach (support_phones() as $phone): ?>
                    <a href="tel:<?= e(phone_href($phone)) ?>"><?= e($phone) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="stage1-footer-bottom">
            <span>&copy; <?= date('Y') ?> Easyway Logistics. All rights reserved.</span>
            <span>Built for transparent, trackable delivery.</span>
        </div>
    </div>
</footer>
<a class="whatsapp-float" href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener noreferrer" aria-label="Chat with Easyway Logistics on WhatsApp">
    <i class="bi bi-whatsapp"></i><span>WhatsApp</span>
</a>
<script src="<?= e(url('assets/js/bootstrap.min.js')) ?>"></script>
<script src="<?= e(url('assets/js/stage1.js')) ?>"></script>
</body>
</html>
