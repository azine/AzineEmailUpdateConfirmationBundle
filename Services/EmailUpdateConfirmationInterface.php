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
     * @param string $hashedEmail
     *
     * @return string
     */
    public function fetchEncryptedEmailFromConfirmationLink($user, $hashedEmail);
}
