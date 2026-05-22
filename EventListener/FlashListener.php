<?php

namespace Azine\EmailUpdateConfirmationBundle\EventListener;

use Azine\EmailUpdateConfirmationBundle\AzineEmailUpdateConfirmationEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FlashListener implements EventSubscriberInterface
{
    /**
     * @var string[]
     */
    private static $successMessages = array(
        AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_SUCCESS => 'email_update.flash.success',
        AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_INITIALIZE => 'email_update.flash.info',
    );

    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * FlashListener constructor.
     *
     * @param SessionInterface    $session
     * @param TranslatorInterface $translator
     */
    public function __construct(SessionInterface $session, TranslatorInterface $translator)
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
            AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_SUCCESS => 'addSuccessFlash',
            AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_INITIALIZE => 'addInfoFlash',
        );
    }

    /**
     * @param Event  $event
     * @param string $eventName
     */
    public function addSuccessFlash($event, $eventName = AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_SUCCESS)
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
    public function addInfoFlash($event, $eventName = AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_INITIALIZE)
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
        return $this->translator->trans($message, $params);
    }
}
