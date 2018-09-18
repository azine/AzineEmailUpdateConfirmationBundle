<?php

namespace Azine\EmailUpdateConfirmationBundle\Doctrine;

use Azine\EmailUpdateConfirmationBundle\Mailer\EmailUpdateConfirmationMailerInterface;
use Azine\EmailUpdateConfirmationBundle\Services\EmailUpdateConfirmation;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Util\CanonicalFieldsUpdater;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class EmailUpdateListener.
 */
class EmailUpdateListener
{
    /**
     * @var EmailUpdateConfirmation
     */
    private $emailUpdateConfirmation;
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var CanonicalFieldsUpdater
     */
    private $canonicalFieldsUpdater;
    /**
     * @var EmailUpdateConfirmationMailerInterface
     */
    private $mailer;

    /**
     * Constructor.
     *
     * @param EmailUpdateConfirmation $emailUpdateConfirmation
     * @param RequestStack            $requestStack
     * @param CanonicalFieldsUpdater  $canonicalFieldsUpdater
     * @param EmailUpdateConfirmationMailerInterface         $mailer
     */
    public function __construct(EmailUpdateConfirmation $emailUpdateConfirmation, RequestStack $requestStack, CanonicalFieldsUpdater $canonicalFieldsUpdater, EmailUpdateConfirmationMailerInterface $mailer)
    {
        $this->emailUpdateConfirmation = $emailUpdateConfirmation;
        $this->requestStack = $requestStack;
        $this->canonicalFieldsUpdater = $canonicalFieldsUpdater;
        $this->mailer = $mailer;
    }

    /**
     * Pre update listener based on doctrine common.
     *
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $object = $args->getObject();
        if ($object instanceof UserInterface) {
            $user = $object;
            if ($user->getConfirmationToken() != $this->emailUpdateConfirmation->getEmailConfirmedToken() && isset($args->getEntityChangeSet()['email'])) {
                $oldEmail = $args->getEntityChangeSet()['email'][0];
                $newEmail = $args->getEntityChangeSet()['email'][1];
                $user->setEmail($oldEmail);
                $user->setEmailCanonical($this->canonicalFieldsUpdater->canonicalizeEmail($oldEmail));
                // Configure email confirmation
                $this->emailUpdateConfirmation->setUser($user);
                $this->emailUpdateConfirmation->setEmail($newEmail);
                $this->emailUpdateConfirmation->setConfirmationRoute('user_update_email_confirm');
                $this->mailer->sendUpdateEmailConfirmation(
                    $user,
                    $this->emailUpdateConfirmation->generateConfirmationLink($this->requestStack->getCurrentRequest()),
                    $newEmail
                );
            }

            if ($user->getConfirmationToken() == $this->emailUpdateConfirmation->getEmailConfirmedToken()) {
                $user->setConfirmationToken(null);
            }
        }
    }
}
