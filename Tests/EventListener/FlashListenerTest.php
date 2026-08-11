<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\Tests\EventListener;

use Azine\EmailUpdateConfirmationBundle\AzineEmailUpdateConfirmationEvents;
use Azine\EmailUpdateConfirmationBundle\EventListener\FlashListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Contracts\Translation\TranslatorInterface;

class FlashListenerTest extends TestCase
{
    private FlashListener $listener;
    private Session $session;

    protected function setUp(): void
    {
        $this->session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($this->session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(static fn (string $message): string => 'translated:'.$message);

        $this->listener = new FlashListener($requestStack, $translator);
    }

    public function testAddSuccessFlash(): void
    {
        $this->listener->addSuccessFlash(
            new \stdClass(),
            AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_SUCCESS,
        );

        self::assertSame(
            ['translated:email_update.flash.success'],
            $this->session->getFlashBag()->peek('success'),
        );
    }

    public function testAddInfoFlash(): void
    {
        $this->listener->addInfoFlash(
            new \stdClass(),
            AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_INITIALIZE,
        );

        self::assertSame(
            ['translated:email_update.flash.info'],
            $this->session->getFlashBag()->peek('info'),
        );
    }
}
