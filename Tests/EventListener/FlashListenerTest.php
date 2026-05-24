<?php

namespace Azine\EmailUpdateConfirmationBundle\Tests\EventListener;

use Azine\EmailUpdateConfirmationBundle\AzineEmailUpdateConfirmationEvents;
use Azine\EmailUpdateConfirmationBundle\EventListener\FlashListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

class FlashListenerTest extends TestCase
{
    private FlashListener $listener;

    public function setUp(): void
    {
        $flashBag = $this->createMock(FlashBagInterface::class);

        $session = $this->createMock(Session::class);
        $session
            ->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $translator = $this->createMock(TranslatorInterface::class);

        $this->listener = new FlashListener($session, $translator);
    }

    public function testAddSuccessFlash(): void
    {
        $this->listener->addSuccessFlash(new \stdClass(), AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_SUCCESS);
    }

    public function testAddInfoFlash(): void
    {
        $this->listener->addInfoFlash(new \stdClass(), AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_INITIALIZE);
    }
}
