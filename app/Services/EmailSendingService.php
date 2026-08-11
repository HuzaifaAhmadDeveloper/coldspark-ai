<?php

namespace App\Services;

use App\Exceptions\Mail\PermanentMailException;
use App\Exceptions\Mail\TransientMailException;
use App\Services\Mail\SendResult;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Single entry point for outbound campaign email. Campaign code (jobs,
 * Livewire components) never chooses a provider directly — it calls
 * send() and this service decides: try the primary mailer (config
 * services.email.primary, default "ses"), and on failure transparently
 * retry via the fallback mailer (services.email.fallback, default "smtp").
 *
 * Both "primary" and "fallback" are just Laravel mailer names already
 * defined in config/mail.php — adding SendGrid/Mailgun/Postmark later is a
 * config change, not a code change, as long as Laravel has a mailer for it.
 *
 * Future: per-user provider selection (a user's own connected SES/Google
 * Workspace/M365 account) plugs in here by having resolveMailer() consult
 * the campaign's owner instead of the global config — nothing calling
 * send() needs to change.
 */
class EmailSendingService
{
    // Substrings from provider exception messages that mean "don't bother
    // retrying this recipient/provider combination again" — matched
    // case-insensitively against the caught exception's message.
    private const PERMANENT_PATTERNS = [
        'not verified',           // SES sandbox: recipient/sender identity not verified
        'messagerejected',
        'invalid parameter value',
        'address rejected',
        'invalid recipient',
        'suppressed',             // SES account-level suppression list
        'complaint',
        'domain does not exist',
        'no such user',
    ];

    public function send(Mailable $mailable, string $to): SendResult
    {
        $primary  = config('services.email.primary', 'ses');
        $fallback = config('services.email.fallback', 'smtp');

        // With no AWS keys set, the SDK's default credential chain still tries
        // reaching the EC2 instance-metadata service before giving up — free on
        // real AWS infrastructure (that's how IAM role credentials work there),
        // but a ~1s timeout per send on any non-AWS host. Skip straight to
        // fallback instead of paying that cost on every single campaign email.
        if ($primary === 'ses' && $fallback && !$this->sesCredentialsConfigured()) {
            Log::info("EmailSendingService: SES has no AWS credentials configured, sending via [{$fallback}] directly");
            $primary  = $fallback;
            $fallback = null;
        }

        try {
            return $this->attempt($primary, $mailable, $to);
        } catch (\Throwable $primaryError) {
            Log::warning("EmailSendingService: primary mailer [{$primary}] failed for {$to}, trying fallback [{$fallback}]", [
                'error' => $primaryError->getMessage(),
            ]);

            if (!$fallback || $fallback === $primary) {
                throw $this->classify($primaryError, $primary);
            }

            try {
                return $this->attempt($fallback, $mailable, $to);
            } catch (\Throwable $fallbackError) {
                Log::error("EmailSendingService: fallback mailer [{$fallback}] also failed for {$to}", [
                    'error' => $fallbackError->getMessage(),
                ]);
                throw $this->classify($fallbackError, $fallback);
            }
        }
    }

    private function sesCredentialsConfigured(): bool
    {
        return filled(config('services.ses.key')) && filled(config('services.ses.secret'));
    }

    private function attempt(string $mailer, Mailable $mailable, string $to): SendResult
    {
        $sentMessage = Mail::mailer($mailer)->to($to)->send($mailable);

        return new SendResult($mailer, $sentMessage?->getMessageId());
    }

    private function classify(\Throwable $e, string $mailer): \Throwable
    {
        $message = strtolower($e->getMessage());

        foreach (self::PERMANENT_PATTERNS as $pattern) {
            if (str_contains($message, $pattern)) {
                return new PermanentMailException("[{$mailer}] " . $e->getMessage(), previous: $e);
            }
        }

        return new TransientMailException("[{$mailer}] " . $e->getMessage(), previous: $e);
    }
}
