<?php

namespace Azine\EmailUpdateConfirmationBundle\Mailer;

use FOS\UserBundle\Model\UserInterface;

interface EmailUpdateConfirmationMailerInterface
{
    /**
     * Send confirmation link to specified new user email.
     *
     * @param UserInterface $user
     * @param $confirmationUrl
     * @param $toEmail
     */
    public function sendUpdateEmailConfirmation(UserInterface $user, $confirmationUrl, $toEmail);
}
