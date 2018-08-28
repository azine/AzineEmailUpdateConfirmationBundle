<?php

namespace Azine\EmailUpdateConfirmationBundle\EventListener;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

class FlashListener implements EventSubscriberInterface
{
    // In FOsUSerBundle these constants are the part of a separate Class with all events, should I create a class for them?
    const EMAIL_UPDATE_SUCCESS = 'fos_user.update_email.success';
    const EMAIL_UPDATE_INITIALIZE = 'fos_user.update_email.initialize';

    /**
     * @var string[]
     */
    private static $successMessages = array(
        self::EMAIL_UPDATE_SUCCESS => 'email_update.flash.success',
        self::EMAIL_UPDATE_INITIALIZE => 'email_update.flash.info',
    );

    /**
     * @var Session
     */
    private $session;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * FlashListener constructor.
     *
     * @param Session             $session
     * @param TranslatorInterface $translator
     */
    public function __construct(Session $session, TranslatorInterface $translator)
    {
        $this->session = $session;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            self::EMAIL_UPDATE_SUCCESS => 'addSuccessFlash',
            self::EMAIL_UPDATE_INITIALIZE => 'addInfoFlash',
        );
    }

    /**
     * @param Event  $event
     * @param string $eventName
     */
    public function addSuccessFlash(Event $event, $eventName)
    {
        if (!isset(self::$successMessages[$eventName])) {
            throw new \InvalidArgumentException('This event does not correspond to a known flash message');
        }
        $this->session->getFlashBag()->add('success', $this->trans(self::$successMessages[$eventName]));
    }

    /**
     * @param Event  $event
     * @param string $eventName
     */
    public function addInfoFlash(Event $event, $eventName)
    {
        if (!isset(self::$successMessages[$eventName])) {
            throw new \InvalidArgumentException('This event does not correspond to a known flash message');
        }

        $this->session->getFlashBag()->add('info', $this->trans(self::$successMessages[$eventName]));
    }

    /**
     * @param string$message
     * @param array $params
     *
     * @return string
     */
    private function trans($message, array $params = array())
    {
        return $this->translator->trans($message, $params, 'FOSUserBundle');
    }
}
