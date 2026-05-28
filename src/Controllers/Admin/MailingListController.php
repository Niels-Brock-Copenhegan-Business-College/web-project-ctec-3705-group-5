<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Interest;
use App\Models\Programme;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class MailingListController
{
    public function __construct(private Twig $view) {}

    private function base(): string { return '/university_course_portal/public'; }

    // ── List all registrations ────────────────────────────────────────────
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $progId = (int)($params['programme_id'] ?? 0);

        $query = Interest::with('programme');
        if ($progId) $query->where('programme_id', $progId);
        $interests = $query->orderBy('registered_at', 'desc')->get();

        return $this->view->render($response, 'admin/mailing/index.twig', [
            'interests'      => $interests,
            'programmes'     => Programme::orderBy('title')->get(),
            'filterProg'     => $progId,
            'totalCount'     => $interests->count(),
            'uniqueEmails'   => $interests->pluck('email')->unique()->count(),
            'uniquePrograms' => $interests->pluck('programme_id')->unique()->count(),
        ]);
    }

    // ── Export CSV ────────────────────────────────────────────────────────
    public function export(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $progId = (int)($params['programme_id'] ?? 0);

        $query = Interest::with('programme');
        if ($progId) $query->where('programme_id', $progId);
        $rows = $query->get();

        $csv = "First Name,Last Name,Email,Phone,Programme,Registered At\n";
        foreach ($rows as $r) {
            $csv .= "\"{$r->first_name}\",\"{$r->last_name}\",\"{$r->email}\",\"{$r->phone}\",\"{$r->programme->title}\",\"{$r->registered_at}\"\n";
        }

        $response->getBody()->write($csv);
        return $response
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="mailing-list.csv"');
    }

    // ── Remove a registration ─────────────────────────────────────────────
    public function delete(Request $request, Response $response, array $args): Response
    {
        Interest::findOrFail((int)$args['id'])->delete();
        $_SESSION['flash_success'] = 'Registration removed.';
        return $response->withHeader('Location', $this->base() . '/admin/mailing-list')->withStatus(302);
    }

    // ── Compose bulk email form ───────────────────────────────────────────
    public function composeForm(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $progId = (int)($params['programme_id'] ?? 0);

        $query = Interest::with('programme');
        if ($progId) $query->where('programme_id', $progId);

        return $this->view->render($response, 'admin/mailing/compose.twig', [
            'programmes' => Programme::orderBy('title')->get(),
            'filterProg' => $progId,
            'recipients' => $query->get(),
        ]);
    }

    // ── Send bulk email via PHPMailer + SMTP ─────────────────────────────
    public function sendBulk(Request $request, Response $response): Response
    {
        $data      = $request->getParsedBody();
        $progId    = (int)($data['programme_id'] ?? 0);
        $subject   = trim($data['subject']    ?? '');
        $message   = trim($data['message']    ?? '');
        $fromName  = trim($data['from_name']  ?? $_ENV['MAIL_FROM_NAME']  ?? 'University Course Portal');
        // Use SMTP username as from address — Mailtrap requires a verified/sandbox-approved sender
        $fromEmail = trim($data['from_email'] ?? $_ENV['MAIL_FROM_EMAIL'] ?? $_ENV['MAIL_SMTP_USER'] ?? 'noreply@university.ac.uk');

        $errors = [];
        if (!$subject) $errors[] = 'Email subject is required.';
        if (!$message) $errors[] = 'Email message body is required.';
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid from email is required.';

        $query = Interest::with('programme');
        if ($progId) $query->where('programme_id', $progId);
        $recipients = $query->get();

        if ($recipients->isEmpty()) {
            $errors[] = 'No recipients found for the selected filter.';
        }

        if ($errors) {
            return $this->view->render($response->withStatus(422), 'admin/mailing/compose.twig', [
                'programmes' => Programme::orderBy('title')->get(),
                'filterProg' => $progId,
                'recipients' => $recipients,
                'errors'     => $errors,
                'old'        => $data,
            ]);
        }

        $sent      = 0;
        $failed    = 0;
        $lastError = '';

        foreach ($recipients as $r) {
            try {
                // Fresh PHPMailer instance per recipient — most reliable approach
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = $_ENV['MAIL_SMTP_HOST'] ?? 'sandbox.smtp.mailtrap.io';
                $mail->SMTPAuth   = true;
                $mail->Username   = $_ENV['MAIL_SMTP_USER'] ?? '';
                $mail->Password   = $_ENV['MAIL_SMTP_PASS'] ?? '';
                $mail->SMTPSecure = $_ENV['MAIL_SMTP_ENCRYPTION'] ?? 'tls';
                $mail->Port       = (int)($_ENV['MAIL_SMTP_PORT'] ?? 2525);
                $mail->isHTML(true);
                $mail->CharSet    = 'UTF-8';

                $personalised = str_replace(
                    ['{{first_name}}', '{{last_name}}', '{{programme}}'],
                    [$r->first_name, $r->last_name, $r->programme->title ?? ''],
                    $message
                );

                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress($r->email, trim("{$r->first_name} {$r->last_name}"));
                $mail->Subject = $subject;
                $mail->Body    = $this->buildEmailHtml($subject, $personalised, $fromName);
                $mail->AltBody = strip_tags($personalised);

                $mail->send();
                $sent++;

                sleep(1); // 1 second gap between sends
            } catch (MailException $e) {
                $failed++;
                $lastError = "Recipient: {$r->email} | Error: " . $e->getMessage() . " | Debug: " . $mail->ErrorInfo;
            }
        }

        if ($failed === 0) {
            $_SESSION['flash_success'] = "Bulk email sent successfully to {$sent} recipient(s).";
        } else {
            $_SESSION['flash_success'] = "Email sent to {$sent} recipient(s). {$failed} failed. Error: " . ($lastError ?? 'Unknown error');
        }

        return $response->withHeader('Location', $this->base() . '/admin/mailing-list')->withStatus(302);
    }

    // ── Build HTML email template ─────────────────────────────────────────
    private function buildEmailHtml(string $subject, string $message, string $fromName): string
    {
        $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>{$subject}</title></head>
<body style="font-family:Arial,sans-serif;background:#f5f5f0;margin:0;padding:2rem">
  <div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08)">
    <div style="background:#0b1f3a;padding:1.5rem 2rem;border-bottom:3px solid #b8963e">
      <h1 style="color:#d4af6a;margin:0;font-size:1.3rem;font-family:Georgia,serif">{$fromName}</h1>
    </div>
    <div style="padding:2rem">
      <div style="color:#374151;font-size:1rem;line-height:1.7;margin:1rem 0">{$safeMessage}</div>
      <hr style="border:none;border-top:1px solid #e5e7eb;margin:1.5rem 0">
      <p style="color:#6b7280;font-size:.85rem">
        You are receiving this because you registered interest in a programme at {$fromName}.<br>
        If you wish to stop receiving these emails, please contact us.
      </p>
    </div>
    <div style="background:#f9f9f7;padding:1rem 2rem;text-align:center;border-top:1px solid #e5e7eb">
      <p style="color:#9ca3af;font-size:.8rem;margin:0">&copy; {$fromName}. All rights reserved.</p>
    </div>
  </div>
</body>
</html>
HTML;
    }
}