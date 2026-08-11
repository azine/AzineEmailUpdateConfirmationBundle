<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\Doctrine;

use Azine\EmailUpdateConfirmationBundle\Mailer\EmailUpdateConfirmationMailerInterface;
use Azine\EmailUpdateConfirmationBundle\Services\EmailUpdateConfirmation;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Util\CanonicalFieldsUpdater;
use Symfony\Component\HttpFoundation\RequestStack;

class EmailUpdateListener
{
    public function __construct(
        private readonly EmailUpdateConfirmation $emailUpdateConfirmation,
        private readonly RequestStack $requestStack,
        private readonly CanonicalFieldsUpdater $canonicalFieldsUpdater,
        private readonly EmailUpdateConfirmationMailerInterface $mailer,
    ) {
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $user = $args->getObject();
        if (!$user instanceof UserInterface) {
            return;
        }

        if ($user->getConfirmationToken() === $this->emailUpdateConfirmation->getEmailConfirmedToken()) {
            $user->setConfirmationToken(null);
            if ($args->hasChangedField('confirmationToken')) {
                $args->setNewValue('confirmationToken', null);
            }

            return;
        }

        if (!$args->hasChangedField('email')) {
            return;
        }

        $oldEmail = (string) $args->getOldValue('email');
        $newEmail = (string) $args->getNewValue('email');
        $oldCanonicalEmail = $this->canonicalFieldsUpdater->canonicalizeEmail($oldEmail);

        $user->setEmail($oldEmail);
        $user->setEmailCanonical($oldCanonicalEmail);
        $args->setNewValue('email', $oldEmail);
        if ($args->hasChangedField('emailCanonical')) {
            $args->setNewValue('emailCanonical', $oldCanonicalEmail);
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new \LogicException('Email-address changes requiring confirmation need an active HTTP request.');
        }

        $this->mailer->sendUpdateEmailConfirmation(
            $user,
            $this->emailUpdateConfirmation->generateConfirmationLink($request, $user, $newEmail),
            $newEmail,
        );
    }
}
