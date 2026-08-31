<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\Doctrine;

use Azine\EmailUpdateConfirmationBundle\Mailer\EmailUpdateConfirmationMailerInterface;
use Azine\EmailUpdateConfirmationBundle\Services\EmailUpdateConfirmation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Util\CanonicalFieldsUpdater;
use Symfony\Component\HttpFoundation\RequestStack;

final class EmailUpdateListener
{
    /**
     * @var array<int, array{
     *     user: UserInterface,
     *     confirmation_url: string,
     *     new_email: string
     * }>
     */
    private array $pendingNotifications = [];

    public function __construct(
        private readonly EmailUpdateConfirmation $emailUpdateConfirmation,
        private readonly RequestStack $requestStack,
        private readonly CanonicalFieldsUpdater $canonicalFieldsUpdater,
        private readonly EmailUpdateConfirmationMailerInterface $mailer,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        if (!$entityManager instanceof EntityManagerInterface) {
            return;
        }

        $unitOfWork = $entityManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof UserInterface) {
                continue;
            }

            $changeSet = $unitOfWork->getEntityChangeSet($entity);
            if (!array_key_exists('email', $changeSet)) {
                continue;
            }

            if ($entity->getConfirmationToken() === $this->emailUpdateConfirmation->getEmailConfirmedToken()) {
                $entity->setConfirmationToken(null);
                $unitOfWork->recomputeSingleEntityChangeSet(
                    $entityManager->getClassMetadata($entity::class),
                    $entity,
                );

                continue;
            }

            [$oldEmail, $newEmail] = $changeSet['email'];
            $oldEmail = (string) $oldEmail;
            $newEmail = (string) $newEmail;

            if ('' === $newEmail || $oldEmail === $newEmail) {
                continue;
            }

            $request = $this->requestStack->getCurrentRequest();
            if (null === $request) {
                throw new \LogicException('Email-address changes requiring confirmation need an active HTTP request.');
            }

            $oldCanonicalEmail = isset($changeSet['emailCanonical'][0])
                ? (string) $changeSet['emailCanonical'][0]
                : $this->canonicalFieldsUpdater->canonicalizeEmail($oldEmail);

            $entity->setEmail($oldEmail);
            $entity->setEmailCanonical($oldCanonicalEmail);

            $confirmationUrl = $this->emailUpdateConfirmation->generateConfirmationLink(
                $request,
                $entity,
                $newEmail,
            );

            $unitOfWork->recomputeSingleEntityChangeSet(
                $entityManager->getClassMetadata($entity::class),
                $entity,
            );

            $this->pendingNotifications[] = [
                'user' => $entity,
                'confirmation_url' => $confirmationUrl,
                'new_email' => $newEmail,
            ];
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $notifications = $this->pendingNotifications;
        $this->pendingNotifications = [];

        foreach ($notifications as $notification) {
            $this->mailer->sendUpdateEmailConfirmation(
                $notification['user'],
                $notification['confirmation_url'],
                $notification['new_email'],
            );
        }
    }
}
