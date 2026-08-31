<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\EventListener;

use Azine\EmailUpdateConfirmationBundle\AzineEmailUpdateConfirmationEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class FlashListener implements EventSubscriberInterface
{
    private const MESSAGES = [
        AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_SUCCESS => ['success', 'email_update.flash.success'],
        AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_INITIALIZE => ['info', 'email_update.flash.info'],
    ];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_SUCCESS => 'addSuccessFlash',
            AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_INITIALIZE => 'addInfoFlash',
        ];
    }

    public function addSuccessFlash(object $event, string $eventName = AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_SUCCESS): void
    {
        $this->addFlashFor($eventName);
    }

    public function addInfoFlash(object $event, string $eventName = AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_INITIALIZE): void
    {
        $this->addFlashFor($eventName);
    }

    private function addFlashFor(string $eventName): void
    {
        if (!isset(self::MESSAGES[$eventName])) {
            throw new \InvalidArgumentException('This event does not correspond to a known flash message.');
        }

        [$type, $message] = self::MESSAGES[$eventName];
        $this->requestStack->getSession()->getFlashBag()->add(
            $type,
            $this->translator->trans($message),
        );
    }
}
