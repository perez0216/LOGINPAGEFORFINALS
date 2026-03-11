<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// ─── SMTP Configuration ───────────────────────────────────────────────────────
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USERNAME', '404liver@gmail.com');
define('SMTP_PASSWORD', 'bpqqyiijvpnxxbyd');
define('SMTP_FROM',     '404liver@gmail.com');
define('SMTP_FROM_NAME','OTP Verification System');

/**
 * Send an OTP code via email.
 *
 * @param string $to   Recipient email address
 * @param string $otp  6-digit OTP code
 * @return bool        True on success, false on failure
 */
function send_otp_email(string $to, string $otp): bool {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your verification code';
        $mail->Body    = buildOtpEmailHtml($otp);
        $mail->AltBody = "Your verification code is: $otp\n\nThis code expires in 5 minutes. Do not share it with anyone.";

        $mail->send();
        return true;

    } catch (Exception $e) {
        $GLOBALS['_last_mail_error'] = $mail->ErrorInfo;
        error_log('PHPMailer error: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Build the HTML email body.
 */
function buildOtpEmailHtml(string $otp): string {
    // Split OTP into individual digits for styled display
    $digits = implode('', array_map(
        fn($d) => "<span style='display:inline-block;width:44px;height:52px;line-height:52px;
                   text-align:center;font-size:26px;font-weight:600;letter-spacing:0;
                   background:#1e1e28;border:1px solid rgba(201,169,110,0.3);
                   border-radius:8px;color:#e8e4dc;margin:0 3px;'>$d</span>",
        str_split($otp)
    ));

    return <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
    <body style="margin:0;padding:0;background:#0e0e12;font-family:'DM Sans',Arial,sans-serif;">
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#0e0e12;padding:40px 20px;">
        <tr><td align="center">
          <table width="480" cellpadding="0" cellspacing="0" style="
            background:#16161e;
            border:1px solid rgba(255,255,255,0.07);
            border-radius:16px;
            overflow:hidden;
            box-shadow:0 24px 60px rgba(0,0,0,0.5);
          ">
            <!-- Gold top bar -->
            <tr>
              <td style="height:3px;background:linear-gradient(to right,transparent,#c9a96e,transparent);"></td>
            </tr>

            <!-- Header -->
            <tr>
              <td align="center" style="padding:36px 40px 24px;">
                <p style="margin:0;font-size:11px;letter-spacing:0.25em;text-transform:uppercase;color:#c9a96e;opacity:0.8;font-family:Georgia,serif;">
                  Verification
                </p>
                <h1 style="margin:10px 0 0;font-size:28px;font-weight:500;color:#e8e4dc;font-family:Georgia,serif;letter-spacing:-0.01em;">
                  Your Sign-In Code
                </h1>
              </td>
            </tr>

            <!-- Body -->
            <tr>
              <td style="padding:0 40px 32px;">
                <p style="margin:0 0 24px;font-size:14px;color:#7a7880;line-height:1.6;text-align:center;">
                  Use the code below to complete your sign-in.<br>
                  It expires in <strong style="color:#e8e4dc;">5 minutes</strong>.
                </p>

                <!-- OTP digits -->
                <div style="text-align:center;margin:0 0 28px;">
                  {$digits}
                </div>

                <!-- Divider -->
                <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
                  <tr>
                    <td style="height:1px;background:linear-gradient(to right,transparent,rgba(201,169,110,0.2),transparent);"></td>
                  </tr>
                </table>

                <p style="margin:0;font-size:12px;color:#7a7880;line-height:1.7;text-align:center;">
                  If you didn't request this, you can safely ignore this email.<br>
                  <strong style="color:#e06c75;">Never share this code</strong> with anyone.
                </p>
              </td>
            </tr>

            <!-- Footer -->
            <tr>
              <td align="center" style="padding:20px 40px 32px;border-top:1px solid rgba(255,255,255,0.05);">
                <p style="margin:0;font-size:11px;color:#7a7880;">
                  © <?= date('Y') ?> OTP Verification System. All rights reserved.
                </p>
              </td>
            </tr>
          </table>
        </td></tr>
      </table>
    </body>
    </html>
    HTML;
}