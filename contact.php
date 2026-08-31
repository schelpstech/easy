<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Validator;

$pageTitle = 'Contact';
$pageDescription = 'Contact Easyway Logistics for delivery support, cargo, packaging or shipment questions.';
$contactState = pull_form_state('contact');
$requestedSubject = Validator::text($_GET['subject'] ?? '', 160);
$product = Validator::text($_GET['product'] ?? '', 160);
if ($product !== '' && empty($contactState['data']['message'])) {
    $contactState['data']['message'] = 'I would like to ask about ' . $product . '.';
}
require __DIR__ . '/app/views/partials/public-header.php';
?>
<section class="page-hero">
    <div class="container"><span class="page-eyebrow"><i class="bi bi-chat-dots"></i> Contact Easyway</span>
        <h1>Tell us what you need to move.</h1>
        <p>Send a clear message and keep the reference we provide. Our team will respond through the contact details you enter.</p>
    </div>
</section>
<section class="content-section soft">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5">
                <aside class="contact-panel">
                    <h2>Talk to our team</h2>
                    <p>For urgent pickup availability or a quick packaging question, WhatsApp is the fastest starting point.</p>
                    <div class="contact-row"><i class="bi bi-whatsapp"></i>
                        <div><strong>WhatsApp</strong><br><a href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener noreferrer"><?= e(support_phone()) ?></a></div>
                    </div>
                    <div class="contact-row"><i class="bi bi-telephone"></i>
                        <div><strong>Call support</strong><br><?php foreach (support_phones() as $phone): ?><a class="d-block" href="tel:<?= e(phone_href($phone)) ?>"><?= e($phone) ?></a><?php endforeach; ?></div>
                    </div>
                    <div class="contact-row"><i class="bi bi-envelope"></i>
                        <div><strong>Email support</strong><br><a href="mailto:<?= e(support_email()) ?>"><?= e(support_email()) ?></a></div>
                    </div>
                    <div class="contact-row"><i class="bi bi-geo-alt"></i>
                        <div><strong>Location</strong><br><?= e(company_address()) ?></div>
                    </div>
                    <?php if (social_media_links() !== []): ?>
                    <div class="contact-social">
                        <h3>Follow Easyway</h3>
                        <p>Connect with us on social media.</p>
                        <?php require __DIR__ . '/app/views/partials/social-links.php'; ?>
                    </div>
                    <?php endif; ?>
                </aside>
            </div>
            <div class="col-lg-7">
                <div class="easy-form-card">
                    <h2 class="mb-4">Send a message</h2>
                    <form method="post" action="<?= e(url('controller/router.php?action=contact.submit')) ?>" novalidate><?= csrf_field() ?><input type="hidden" name="_return" value="contact.php">
                        <div class="honeypot" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                        <div class="row g-4">
                            <div class="col-md-6"><label for="contact-name">Full name *</label><input class="form-control" id="contact-name" name="full_name" value="<?= form_value($contactState, 'full_name') ?>" required><?= form_error($contactState, 'full_name') ?></div>
                            <div class="col-md-6"><label for="contact-company">Company name</label><input class="form-control" id="contact-company" name="company_name" value="<?= form_value($contactState, 'company_name') ?>"></div>
                            <div class="col-md-6"><label for="contact-email">Email *</label><input class="form-control" type="email" id="contact-email" name="email" value="<?= form_value($contactState, 'email') ?>" required><?= form_error($contactState, 'email') ?></div>
                            <div class="col-md-6"><label for="contact-phone">Phone number</label><input class="form-control" type="tel" id="contact-phone" name="phone" value="<?= form_value($contactState, 'phone') ?>"><?= form_error($contactState, 'phone') ?></div>
                            <div class="col-12"><label for="contact-subject">Subject *</label><input class="form-control" id="contact-subject" name="subject" value="<?= form_value($contactState, 'subject', $requestedSubject ?: 'General enquiry') ?>" required></div>
                            <div class="col-12"><label for="contact-message">How can we help? *</label><textarea class="form-control" id="contact-message" name="message" required><?= form_value($contactState, 'message') ?></textarea><?= form_error($contactState, 'message') ?></div>
                            <div class="col-12"><button class="easy-btn" type="submit">Send message <i class="bi bi-arrow-right"></i></button></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/app/views/partials/public-footer.php'; ?>
