<?php

namespace Azine\EmailUpdateConfirmationBundle\Mailer;

use FOS\UserBundle\Mailer\TwigSwiftMailer;
use FOS\UserBundle\Model\UserInterface;

class AzineTwigSwiftMailer extends TwigSwiftMailer
{
    //I am not sure such extending of Mailer is a good decision

    /**
     * Send confirmation link to specified new user email.
     *
     * @param UserInterface $user
     * @param $confirmationUrl
     * @param $toEmail
     *
     * @return bool
     */
    public function sendUpdateEmailConfirmation(UserInterface $user, $confirmationUrl, $toEmail)
    {
        $template = $this->parameters['template']['email_updating'];
        $context = array(
            'user' => $user,
            'confirmationUrl' => $confirmationUrl,
        );

        $this->sendMessage($template, $context, $this->parameters['from_email']['confirmation'], $toEmail);
    }
}
