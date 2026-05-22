<?php

namespace Azine\EmailUpdateConfirmationBundle\Tests\EventListener;

use Azine\EmailUpdateConfirmationBundle\AzineEmailUpdateConfirmationEvents;
use Azine\EmailUpdateConfirmationBundle\EventListener\FlashListener;

class FlashListenerTest extends \PHPUnit\Framework\TestCase
{
        /** @var FlashListener */
    private $listener;

    public function setUp(): void
    {
        $flashBag = $this->createMock(\Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface::class);

        $session = $this->createMock(\Symfony\Component\HttpFoundation\Session\SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $translator = $this->createMock(\Symfony\Contracts\Translation\TranslatorInterface::class);

        $this->listener = new FlashListener($session, $translator);
    }

    public function testAddSuccessFlash()
    {
        $this->listener->addSuccessFlash(new \stdClass(), AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_SUCCESS);
    }

    public function testAddInfoFlash()
    {
        $this->listener->addInfoFlash(new \stdClass(), AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_INITIALIZE);
    }
}
