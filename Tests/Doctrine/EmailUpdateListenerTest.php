<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\Tests\Doctrine;

use Azine\EmailUpdateConfirmationBundle\Doctrine\EmailUpdateListener;
use Azine\EmailUpdateConfirmationBundle\Mailer\EmailUpdateConfirmationMailerInterface;
use Azine\EmailUpdateConfirmationBundle\Services\EmailUpdateConfirmationInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Util\CanonicalFieldsUpdater;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[AllowMockObjectsWithoutExpectations]
final class EmailUpdateListenerTest extends TestCase
{
    public function testPersistsTokenAndRestoresOldEmailBeforeSendingAfterFlush(): void
    {
        $email = 'new@example.test';
        $canonical = 'new@example.test';
        $token = null;

        $user = $this->statefulUser($email, $canonical, $token);

        $confirmation = $this->createMock(EmailUpdateConfirmationInterface::class);
        $confirmation->method('getEmailConfirmedToken')->willReturn('confirmed-sentinel');
        $confirmation
            ->expects(self::once())
            ->method('generateConfirmationLink')
            ->willReturnCallback(static function (Request $request, UserInterface $changedUser, string $newEmail) use (&$token): string {
                self::assertSame('new@example.test', $newEmail);
                $token = 'persisted-token';
                $changedUser->setConfirmationToken($token);

                return 'https://example.test/confirm';
            });

        $mailer = $this->createMock(EmailUpdateConfirmationMailerInterface::class);
        $mailer
            ->expects(self::once())
            ->method('sendUpdateEmailConfirmation')
            ->with($user, 'https://example.test/confirm', 'new@example.test');

        $canonicalizer = $this->createMock(CanonicalFieldsUpdater::class);
        $canonicalizer->method('canonicalizeEmail')->willReturnCallback(
            static fn (?string $value): ?string => null === $value ? null : strtolower($value),
        );

        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/profile'));

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn([$user]);
        $unitOfWork->method('getEntityChangeSet')->with($user)->willReturn([
            'email' => ['old@example.test', 'new@example.test'],
            'emailCanonical' => ['old@example.test', 'new@example.test'],
        ]);
        $unitOfWork
            ->expects(self::once())
            ->method('recomputeSingleEntityChangeSet')
            ->with(self::isInstanceOf(ClassMetadata::class), $user);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getUnitOfWork')->willReturn($unitOfWork);
        $entityManager->method('getClassMetadata')->willReturn(new ClassMetadata($user::class));

        $listener = new EmailUpdateListener($confirmation, $requestStack, $canonicalizer, $mailer);
        $listener->onFlush(new OnFlushEventArgs($entityManager));

        self::assertSame('old@example.test', $email);
        self::assertSame('old@example.test', $canonical);
        self::assertSame('persisted-token', $token);

        $listener->postFlush(new PostFlushEventArgs($entityManager));
    }

    public function testConfirmedSentinelAllowsTheNewEmailAndIsCleared(): void
    {
        $email = 'confirmed@example.test';
        $canonical = 'confirmed@example.test';
        $token = 'confirmed-sentinel';
        $user = $this->statefulUser($email, $canonical, $token);

        $confirmation = $this->createMock(EmailUpdateConfirmationInterface::class);
        $confirmation->method('getEmailConfirmedToken')->willReturn('confirmed-sentinel');
        $confirmation->expects(self::never())->method('generateConfirmationLink');

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn([$user]);
        $unitOfWork->method('getEntityChangeSet')->with($user)->willReturn([
            'email' => ['old@example.test', 'confirmed@example.test'],
        ]);
        $unitOfWork
            ->expects(self::once())
            ->method('recomputeSingleEntityChangeSet');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getUnitOfWork')->willReturn($unitOfWork);
        $entityManager->method('getClassMetadata')->willReturn(new ClassMetadata($user::class));

        $mailer = $this->createMock(EmailUpdateConfirmationMailerInterface::class);
        $mailer->expects(self::never())->method('sendUpdateEmailConfirmation');

        $listener = new EmailUpdateListener(
            $confirmation,
            new RequestStack(),
            $this->createStub(CanonicalFieldsUpdater::class),
            $mailer,
        );
        $listener->onFlush(new OnFlushEventArgs($entityManager));
        $listener->postFlush(new PostFlushEventArgs($entityManager));

        self::assertSame('confirmed@example.test', $email);
        self::assertNull($token);
    }

    private function statefulUser(string &$email, string &$canonical, ?string &$token): UserInterface
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getEmail')->willReturnCallback(static fn () => $email);
        $user->method('setEmail')->willReturnCallback(static function (?string $value) use (&$email): void {
            $email = (string) $value;
        });
        $user->method('getEmailCanonical')->willReturnCallback(static fn () => $canonical);
        $user->method('setEmailCanonical')->willReturnCallback(static function (?string $value) use (&$canonical): void {
            $canonical = (string) $value;
        });
        $user->method('getConfirmationToken')->willReturnCallback(static fn () => $token);
        $user->method('setConfirmationToken')->willReturnCallback(static function (?string $value) use (&$token): void {
            $token = $value;
        });

        return $user;
    }
}
