<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\Controller;

use Azine\EmailUpdateConfirmationBundle\AzineEmailUpdateConfirmationEvents;
use Azine\EmailUpdateConfirmationBundle\Services\EmailUpdateConfirmation;
use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Util\CanonicalFieldsUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfirmEmailUpdateController extends AbstractController
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UserManagerInterface $userManager,
        private readonly EmailUpdateConfirmation $emailUpdateConfirmation,
        private readonly TranslatorInterface $translator,
        private readonly CanonicalFieldsUpdater $canonicalFieldsUpdater,
    ) {
    }

    public function confirmEmailUpdateAction(
        Request $request,
        string $token,
        string $redirectRoute,
    ): RedirectResponse {
        $user = $this->userManager->findUserByConfirmationToken($token);
        if (!$user instanceof UserInterface) {
            throw $this->createNotFoundException($this->translator->trans('email_update.error.message'));
        }

        $authenticatedUser = $this->getUser();
        if (
            !$authenticatedUser instanceof UserInterface
            || (string) $user->getId() !== (string) $authenticatedUser->getId()
        ) {
            throw new AccessDeniedException($this->translator->trans('email_update.error.message'));
        }

        $target = $request->query->getString('target');
        if ('' === $target) {
            throw $this->createNotFoundException($this->translator->trans('email_update.error.message'));
        }

        $newEmail = $this->emailUpdateConfirmation->fetchEncryptedEmailFromConfirmationLink($user, $target);
        $user->setConfirmationToken($this->emailUpdateConfirmation->getEmailConfirmedToken());
        $user->setEmail($newEmail);
        $user->setEmailCanonical($this->canonicalFieldsUpdater->canonicalizeEmail($newEmail));
        $this->userManager->updateUser($user);

        $this->eventDispatcher->dispatch(
            new UserEvent($user, $request),
            AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_SUCCESS,
        );

        return $this->redirectToRoute($redirectRoute);
    }
}
