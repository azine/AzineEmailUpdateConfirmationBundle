<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\Mailer;

use FOS\UserBundle\Model\UserInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

class AzineEmailUpdateConfirmationMailer implements EmailUpdateConfirmationMailerInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly TranslatorInterface $translator,
        private readonly array $parameters,
    ) {
    }

    public function sendUpdateEmailConfirmation(
        UserInterface $user,
        string $confirmationUrl,
        string $toEmail,
    ): void {
        $email = (new TemplatedEmail())
            ->from($this->createFromAddress($this->parameters['from_email']))
            ->to(new Address($toEmail))
            ->subject($this->translator->trans('email_update.email.subject'))
            ->textTemplate((string) $this->parameters['template'])
            ->context([
                'user' => $user,
                'confirmationUrl' => $confirmationUrl,
            ]);

        $this->mailer->send($email);
    }

    private function createFromAddress(string|array $fromEmail): Address
    {
        if (is_string($fromEmail)) {
            return new Address($fromEmail);
        }

        if (isset($fromEmail['address'])) {
            return new Address(
                (string) $fromEmail['address'],
                (string) ($fromEmail['sender_name'] ?? $fromEmail['name'] ?? ''),
            );
        }

        if (isset($fromEmail['email'])) {
            return new Address(
                (string) $fromEmail['email'],
                (string) ($fromEmail['name'] ?? $fromEmail['sender_name'] ?? ''),
            );
        }

        $address = array_key_first($fromEmail);
        if (null === $address) {
            throw new \InvalidArgumentException('The email update confirmation from-address is empty.');
        }

        return new Address((string) $address, (string) $fromEmail[$address]);
    }
}
