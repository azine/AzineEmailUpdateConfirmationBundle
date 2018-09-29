<?php

namespace Azine\EmailUpdateConfirmationBundle\Services;

use FOS\UserBundle\Model\UserInterface;

/**
 * Interface EmailUpdateConfirmationInterface.
 */
interface EmailUpdateConfirmationInterface
{
    /**
     * @param UserInterface $user
     * @param string        $hashedEmail
     *
     * @return string
     */
    public function fetchEncryptedEmailFromConfirmationLink($user, $hashedEmail);

    /**
     * @param string $confirmationToken
     * @param string $email
     *
     * @return string Encrypted email value
     */
    public function encryptEmailValue($confirmationToken, $email);

    /**
     * @param string $confirmationToken
     * @param string $encryptedEmail
     *
     * @return string Decrypted email value
     */
    public function decryptEmailValue($confirmationToken, $encryptedEmail);
}
