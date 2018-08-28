<?php

namespace Azine\EmailUpdateConfirmationBundle\Services;

use FOS\UserBundle\Model\UserInterface;

/**
 * Interface EmailUpdateConfirmationInterface.
 */
interface EmailUpdateConfirmationInterface
{
    /**
     * @param string $hashedEmail
     *
     * @return string
     */
    public function fetchEncryptedEmailFromConfirmationLink($hashedEmail);

    /**
     * @param UserInterface $user
     *
     * @return $this
     */
    public function setUser(UserInterface $user);

    /**
     * @param string $email
     *
     * @return $this
     */
    public function setEmail($email);

    /**
     * @param string $confirmationRoute
     *
     * @return $this
     */
    public function setConfirmationRoute($confirmationRoute);
}
