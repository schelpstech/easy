<?php
/** @var string $subject @var string $message @var string $label @var array $action */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= e($subject) ?></title>
</head>
<body style="margin:0;padding:0;width:100%;background-color:#f1f5f8;color:#263d50;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;">
    <div style="display:none;font-size:1px;line-height:1px;color:#f1f5f8;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;"><?= e($preheader) ?></div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f1f5f8;border-collapse:collapse;">
        <tr><td align="center" style="padding:24px 12px;">
            <!--[if mso]><table role="presentation" width="640" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:640px;table-layout:fixed;background-color:#ffffff;border:1px solid #e0e7ed;border-radius:12px;border-spacing:0;">
                <tr><td style="padding:28px 28px 24px;border-bottom:4px solid #f59b20;">
                    <?php if ($logo !== null): ?>
                        <img src="cid:<?= e(\App\EmailTemplate::LOGO_CID) ?>" width="190" height="72" alt="Easyway Logistics" style="display:block;width:190px;max-width:100%;height:auto;border:0;color:#063f59;font-size:20px;font-weight:bold;">
                    <?php else: ?>
                        <p style="margin:0;color:#063f59;font-size:26px;line-height:32px;font-weight:700;">Easyway <span style="color:#bd6500;">Logistics</span></p>
                    <?php endif; ?>
                    <p style="margin:12px 0 0;color:#657889;font-size:10px;line-height:16px;font-weight:700;letter-spacing:1.8px;">LOGISTICS &nbsp; / &nbsp; CARGO &nbsp; / &nbsp; DELIVERY</p>
                </td></tr>
                <tr><td style="padding:30px 28px 32px;overflow-wrap:anywhere;word-wrap:break-word;">
                    <p style="margin:0 0 12px;color:#a45a00;font-size:11px;line-height:18px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;"><?= e($label) ?></p>
                    <h1 style="margin:0 0 24px;color:#063f59;font-size:26px;line-height:34px;font-weight:700;overflow-wrap:anywhere;word-wrap:break-word;"><?= e($subject) ?></h1>
                    <?php foreach ($paragraphs as $paragraph): ?>
                        <p style="margin:0 0 18px;color:#263d50;font-size:15px;line-height:25px;overflow-wrap:anywhere;word-wrap:break-word;"><?= nl2br(e($paragraph), false) ?></p>
                    <?php endforeach; ?>
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:24px;border-collapse:separate;">
                        <tr><td align="center" bgcolor="#f59b20" style="border-radius:6px;mso-padding-alt:14px 22px;">
                            <a href="<?= e($action['url']) ?>" style="display:inline-block;padding:14px 22px;border:1px solid #f59b20;border-radius:6px;color:#102f43;font-size:14px;line-height:20px;font-weight:700;text-decoration:none;text-align:center;mso-padding-alt:0;"><?= e($action['label']) ?> &rarr;</a>
                        </td></tr>
                    </table>
                </td></tr>
                <tr><td style="padding:26px 28px;background-color:#f6f8fa;border-top:1px solid #e0e7ed;overflow-wrap:anywhere;word-wrap:break-word;">
                    <h2 style="margin:0 0 6px;color:#063f59;font-size:17px;line-height:24px;font-weight:700;">Need a hand?</h2>
                    <p style="margin:0 0 18px;color:#536b7c;font-size:13px;line-height:21px;">Our team is here to help with your delivery.</p>
                    <p style="margin:0 0 10px;color:#263d50;font-size:13px;line-height:23px;">
                        <?php if ($emailLink !== null): ?><a href="<?= e($emailLink) ?>" style="color:#063f59;text-decoration:underline;"><?= e($email) ?></a><?php else: ?><?= e($email) ?><?php endif; ?><br>
                        <?php foreach ($phones as $phone): ?>
                            <a href="tel:<?= e(phone_href($phone)) ?>" style="display:inline-block;padding:3px 0;color:#063f59;text-decoration:underline;"><?= e($phone) ?></a><br>
                        <?php endforeach; ?>
                        <a href="<?= e($whatsapp) ?>" style="display:inline-block;padding:3px 0;color:#063f59;text-decoration:underline;">Chat on WhatsApp</a>
                    </p>
                    <p style="margin:0;color:#536b7c;font-size:12px;line-height:20px;"><strong style="color:#263d50;">Visit our office</strong><br><?= e($address) ?></p>
                    <?php if ($website !== null): ?>
                        <p style="margin:12px 0 0;font-size:13px;line-height:21px;"><a href="<?= e($website) ?>" style="color:#063f59;text-decoration:underline;">Visit our website &rarr;</a></p>
                    <?php endif; ?>
                </td></tr>
                <?php if ($socials !== []): ?>
                    <tr><td style="padding:20px 28px;background-color:#063f59;border-radius:0 0 11px 11px;">
                        <p style="margin:0 0 8px;color:#cfdee7;font-size:10px;line-height:16px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;">Connect with Easyway</p>
                        <p style="margin:0;font-size:13px;line-height:24px;">
                            <?php foreach ($socials as $social): ?>
                                <a href="<?= e($social['url']) ?>" style="display:inline-block;margin-right:18px;padding:4px 0;color:#ffffff;text-decoration:underline;"><?= e($social['name']) ?></a>
                            <?php endforeach; ?>
                        </p>
                    </td></tr>
                <?php endif; ?>
            </table>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:640px;table-layout:fixed;">
                <tr><td align="center" style="padding:20px 20px 0;color:#657889;font-size:11px;line-height:18px;">
                    <p style="margin:0 0 8px;"><?= e($reason) ?></p>
                    <p style="margin:0;">&copy; <?= e($year) ?> Easyway Logistics.</p>
                </td></tr>
            </table>
            <!--[if mso]></td></tr></table><![endif]-->
        </td></tr>
    </table>
</body>
</html>
