<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\Tests\Mailer;

use Azine\EmailUpdateConfirmationBundle\Mailer\AzineEmailBundleMailerAdapter;
use FOS\UserBundle\Model\UserInterface;
use PHPUnit\Framework\TestCase;

final class AzineEmailBundleMailerAdapterTest extends TestCase
{
    public function testDelegatesWithTheExplicitPendingAddress(): void
    {
        $delegate = new class {
            /** @var array<int, mixed> */
            public array $arguments = [];

            public function sendEmailUpdateConfirmationMessage(
                UserInterface $user,
                string $confirmationUrl,
                ?string $toEmail = null,
            ): void {
                $this->arguments = [$user, $confirmationUrl, $toEmail];
            }
        };

        $user = $this->createStub(UserInterface::class);
        $adapter = new AzineEmailBundleMailerAdapter($delegate);
        $adapter->sendUpdateEmailConfirmation(
            $user,
            'https://example.test/confirm',
            'new@example.test',
        );

        self::assertSame(
            [$user, 'https://example.test/confirm', 'new@example.test'],
            $delegate->arguments,
        );
    }

    public function testRejectsAnIncompatibleConfiguredService(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not expose sendEmailUpdateConfirmationMessage');

        new AzineEmailBundleMailerAdapter(new \stdClass());
    }
}
