<?php

namespace Azine\EmailUpdateConfirmationBundle\Mailer;

use FOS\UserBundle\Mailer\Mailer;
use FOS\UserBundle\Model\UserInterface;

class AzineUpdateEmailMailer extends Mailer
{

    //I am not sure such extending of Mailer is a good decision
    /**
     * Send confirmation link to specified new user email.
     *
     * @param UserInterface $user
     * @param string        $confirmationUrl
     * @param string        $toEmail
     */
    public function sendUpdateEmailConfirmation(UserInterface $user, $confirmationUrl, $toEmail)
    {
        $template = $this->parameters['azine_email_update_confirmation.template'];
        $rendered = $this->templating->render($template, array(
            'user' => $user,
            'confirmationUrl' => $confirmationUrl,
        ));

        $this->sendEmailMessage($rendered, $this->parameters['from_email'], $toEmail);
    }
}