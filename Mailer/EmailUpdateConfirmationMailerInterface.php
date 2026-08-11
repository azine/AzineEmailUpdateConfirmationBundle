<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\Mailer;

use FOS\UserBundle\Model\UserInterface;

interface EmailUpdateConfirmationMailerInterface
{
    public function sendUpdateEmailConfirmation(
        UserInterface $user,
        string $confirmationUrl,
        string $toEmail,
    ): void;
}
