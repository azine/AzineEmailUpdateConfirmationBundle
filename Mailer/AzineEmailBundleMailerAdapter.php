<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\Mailer;

use FOS\UserBundle\Model\UserInterface;

/**
 * Optional adapter for azine/email-bundle 5.0.1+.
 *
 * The dependency remains optional at runtime; the configured service only needs
 * to expose sendEmailUpdateConfirmationMessage().
 */
final class AzineEmailBundleMailerAdapter implements EmailUpdateConfirmationMailerInterface
{
    public function __construct(private readonly object $mailer)
    {
        if (!is_callable([$this->mailer, 'sendEmailUpdateConfirmationMessage'])) {
            throw new \InvalidArgumentException(sprintf(
                'The configured Azine Email Bundle mailer "%s" does not expose sendEmailUpdateConfirmationMessage().',
                $this->mailer::class,
            ));
        }
    }

    public function sendUpdateEmailConfirmation(
        UserInterface $user,
        string $confirmationUrl,
        string $toEmail,
    ): void {
        $this->mailer->sendEmailUpdateConfirmationMessage($user, $confirmationUrl, $toEmail);
    }
}
