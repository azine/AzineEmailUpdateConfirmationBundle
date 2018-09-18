<?php

namespace Azine\EmailUpdateConfirmationBundle\Controller;

use FOS\UserBundle\Controller\SecurityController as BaseSecurityController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller managing the confirmation of updated user email.
 */
class SecurityController extends BaseSecurityController
{
    /**
     * If a user is logged in already, then forward to the dashboard instead of showing the login-page.
     * (non-PHPdoc).
     *
     * @see FOS\UserBundle\Controller.SecurityController::loginAction()
     */
    public function loginAction(Request $request)
    {

    }
}