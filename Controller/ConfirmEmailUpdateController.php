<?php

namespace Azine\EmailUpdateConfirmationBundle\Controller;

use Azine\EmailUpdateConfirmationBundle\EventListener\FlashListener;
use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\User;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Azine\EmailUpdateConfirmationBundle\Services\EmailUpdateConfirmation;
use FOS\UserBundle\Util\CanonicalFieldsUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Controller managing the confirmation of changed user email.
 *
 * @author Dominik Businger <git@azine.me>
 */
class ConfirmEmailUpdateController extends Controller
{
    private $eventDispatcher;
    private $userManager;
    private $emailUpdateConfirmation;
    private $translator;
    private $canonicalFieldsUpdater;

    public function __construct(EventDispatcherInterface $eventDispatcher, UserManagerInterface $userManager, EmailUpdateConfirmation $emailUpdateConfirmation, TranslatorInterface $translator, CanonicalFieldsUpdater $canonicalFieldsUpdater)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->userManager = $userManager;
        $this->emailUpdateConfirmation = $emailUpdateConfirmation;
        $this->translator = $translator;
        $this->canonicalFieldsUpdater = $canonicalFieldsUpdater;
    }

    /**
     * Confirm user`s email update.
     *
     * @param Request $request
     * @param string  $token
     * @param string  $redirectRoute
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function confirmEmailUpdateAction(Request $request, $token, $redirectRoute)
    {
        /** @var User $user */
        $user = $this->userManager->findUserByConfirmationToken($token);

        // If user was not found throw 404 exception
        if (!$user) {
            throw $this->createNotFoundException($this->translator->trans('email_update.error.message', array(), 'FOSUserBundle'));
        }

        // Show invalid token message if the user id found via token does not match the current users id (e.g. anon. or other user)
        if (!($this->getUser() instanceof UserInterface) || ($user->getId() !== $this->getUser()->getId())) {
            throw new AccessDeniedException($this->translator->trans('email_update.error.message', array(), 'FOSUserBundle'));
        }

        $this->emailUpdateConfirmation->setUser($user);

        $newEmail = $this->emailUpdateConfirmation->fetchEncryptedEmailFromConfirmationLink($request->get('target'));

        // Update user email
        if ($newEmail) {
            $user->setConfirmationToken($this->emailUpdateConfirmation->getEmailConfirmedToken());
            $user->setEmail($newEmail);
            $user->setEmail($this->canonicalFieldsUpdater->canonicalizeEmail($newEmail));
        }

        $this->userManager->updateUser($user);

        $event = new UserEvent($user, $request);
        $this->eventDispatcher->dispatch(FlashListener::EMAIL_UPDATE_SUCCESS, $event);

        return $this->redirect($this->generateUrl($redirectRoute));
    }
}