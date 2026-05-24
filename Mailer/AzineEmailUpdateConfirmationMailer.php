<?php

namespace Azine\EmailUpdateConfirmationBundle\Mailer;

use FOS\UserBundle\Model\UserInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Twig\Environment;

class AzineEmailUpdateConfirmationMailer implements EmailUpdateConfirmationMailerInterface
{
    private MailerInterface $mailer;
    private Environment $twig;
    private array $parameters;

    public function __construct(MailerInterface $mailer, Environment $twig, array $parameters)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->parameters = $parameters;
    }

    public function sendUpdateEmailConfirmation(UserInterface $user, $confirmationUrl, $toEmail)
    {
        $email = (new TemplatedEmail())
            ->from($this->parameters['from_email'])
            ->to($toEmail)
            ->subject('Email update confirmation')
            ->textTemplate($this->parameters['template'])
            ->context([
                'user' => $user,
                'confirmationUrl' => $confirmationUrl,
            ]);

        $this->mailer->send($email);
    }
}
