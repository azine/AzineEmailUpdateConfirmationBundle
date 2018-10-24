<?php

namespace Azine\EmailUpdateConfirmationBundle;

/**
 * Contains all events thrown in the AzineEmailUpdateConfirmationBundle.
 */
final class AzineEmailUpdateConfirmationEvents
{
    /**
     * The EMAIL_UPDATE_INITIALIZE event occurs when the email update process is initialized.
     *
     * This event allows you to access the user and to add some behaviour after email update is initialized..
     *
     * @Event("FOS\UserBundle\Event\UserEvent")
     */
    const EMAIL_UPDATE_INITIALIZE = 'azine.email_update.initialize';

    /**
     * The EMAIL_UPDATE_SUCCESS event occurs when the email was successfully updated through confirmation link.
     *
     * This event allows you to access the user and to add some behaviour after email was confirmed and updated..
     *
     * @Event("FOS\UserBundle\Event\UserEvent")
     */
    const EMAIL_UPDATE_SUCCESS = 'azine.email_update.success';
}
