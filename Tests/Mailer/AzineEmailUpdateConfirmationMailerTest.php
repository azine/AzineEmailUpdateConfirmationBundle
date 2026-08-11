<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\Tests\Mailer;

use Azine\EmailUpdateConfirmationBundle\Mailer\AzineEmailUpdateConfirmationMailer;
use FOS\UserBundle\Model\UserInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AzineEmailUpdateConfirmationMailerTest extends TestCase
{
    public function testBuildsTranslatedTemplatedEmail(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (TemplatedEmail $email): bool {
                return 'Confirm your new email address' === $email->getSubject()
                    && 'no-reply@azine.me' === $email->getFrom()[0]->getAddress()
                    && 'azine.me support' === $email->getFrom()[0]->getName()
                    && 'new@example.com' === $email->getTo()[0]->getAddress()
                    && '@AzineEmailUpdateConfirmation/Email/email_update_confirmation.txt.twig' === $email->getTextTemplate()
                    && 'https://azine.me/confirm' === $email->getContext()['confirmationUrl'];
            }));

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects(self::once())
            ->method('trans')
            ->with('email_update.email.subject')
            ->willReturn('Confirm your new email address');

        $service = new AzineEmailUpdateConfirmationMailer(
            $mailer,
            $translator,
            [
                'template' => '@AzineEmailUpdateConfirmation/Email/email_update_confirmation.txt.twig',
                'from_email' => [
                    'address' => 'no-reply@azine.me',
                    'sender_name' => 'azine.me support',
                ],
            ],
        );

        $service->sendUpdateEmailConfirmation(
            $this->createMock(UserInterface::class),
            'https://azine.me/confirm',
            'new@example.com',
        );
    }
}
