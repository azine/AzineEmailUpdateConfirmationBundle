<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\Services;

use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\HttpFoundation\Request;

interface EmailUpdateConfirmationInterface
{
    public function generateConfirmationLink(Request $request, UserInterface $user, string $email): string;

    public function fetchEncryptedEmailFromConfirmationLink(UserInterface $user, string $encryptedEmail): string;

    public function encryptEmailValue(string $confirmationToken, string $email): string;

    public function decryptEmailValue(string $confirmationToken, string $encryptedEmail): string;

    public function getEmailConfirmedToken(): string;
}
