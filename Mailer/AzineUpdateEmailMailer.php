<?php

namespace Azine\EmailUpdateConfirmationBundle\Mailer;

use FOS\UserBundle\Mailer\Mailer;
use FOS\UserBundle\Model\UserInterface;

class AzineUpdateEmailMailer extends Mailer implements EmailUpdateConfirmationMailerInterface
{
    /**
     * Send confirmation link to specified new user email.
     *
     * @param UserInterface $user
     * @param string        $confirmationUrl
     * @param string        $toEmail
     */
    public function sendUpdateEmailConfirmation(UserInterface $user, $confirmationUrl, $toEmail)
    {
        $template = $this->parameters['template']['email_updating'];
        $rendered = $this->templating->render($template, array(
            'user' => $user,
            'confirmationUrl' => $confirmationUrl,
        ));

        $this->sendEmailMessage($rendered, $this->parameters['from_email']['confirmation'], $toEmail);
    }
}
