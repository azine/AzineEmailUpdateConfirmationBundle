<?php

namespace Azine\EmailUpdateConfirmationBundle\Tests\EventListener;

use Azine\EmailUpdateConfirmationBundle\AzineEmailUpdateConfirmationEvents;
use Azine\EmailUpdateConfirmationBundle\EventListener\FlashListener;
use Symfony\Component\EventDispatcher\Event;

class FlashListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var Event */
    private $event;

    /** @var FlashListener */
    private $listener;

    public function setUp()
    {
        $this->event = new Event();

        $flashBag = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Flash\FlashBag')->getMock();

        $session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')->disableOriginalConstructor()->getMock();
        $session
            ->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')->getMock();

        $this->listener = new FlashListener($session, $translator);
    }

    public function testAddSuccessFlash()
    {
        $this->listener->addSuccessFlash($this->event, AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_SUCCESS);
    }

    public function testAddInfoFlash()
    {
        $this->listener->addInfoFlash($this->event, AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_INITIALIZE);
    }
}
