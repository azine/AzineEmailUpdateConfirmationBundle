<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\Services;

use Azine\EmailUpdateConfirmationBundle\AzineEmailUpdateConfirmationEvents;
use Azine\EmailUpdateConfirmationBundle\Mailer\EmailUpdateConfirmationMailerInterface;
use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmailUpdateConfirmation implements EmailUpdateConfirmationInterface
{
    public const EMAIL_CONFIRMED = 'email_confirmed';
    private const DEFAULT_CIPHER = 'AES-128-CBC';
    private const CONFIRMATION_ROUTE = 'user_update_email_confirm';

    private readonly string $encryptionMode;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly TokenGeneratorInterface $tokenGenerator,
        private readonly EmailUpdateConfirmationMailerInterface $mailer,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ValidatorInterface $validator,
        private readonly string $redirectRoute,
        ?string $mode = null,
    ) {
        $this->encryptionMode = $mode ?: self::DEFAULT_CIPHER;

        $supportedCiphers = array_map('strtolower', openssl_get_cipher_methods(false));
        if (!in_array(strtolower($this->encryptionMode), $supportedCiphers, true)) {
            throw new \InvalidArgumentException(sprintf(
                'The OpenSSL cipher "%s" is not supported by this PHP runtime.',
                $this->encryptionMode,
            ));
        }
    }

    public function getMailer(): EmailUpdateConfirmationMailerInterface
    {
        return $this->mailer;
    }

    public function generateConfirmationLink(Request $request, UserInterface $user, string $email): string
    {
        if (!$user->getConfirmationToken()) {
            $user->setConfirmationToken($this->tokenGenerator->generateToken());
        }

        $token = (string) $user->getConfirmationToken();
        $confirmationParams = [
            'token' => $token,
            'target' => $this->encryptEmailValue($token, $email),
            'redirectRoute' => $this->redirectRoute,
        ];

        $this->eventDispatcher->dispatch(
            new UserEvent($user, $request),
            AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_INITIALIZE,
        );

        return $this->router->generate(
            self::CONFIRMATION_ROUTE,
            $confirmationParams,
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }

    public function fetchEncryptedEmailFromConfirmationLink(
        UserInterface $user,
        string $encryptedEmail,
    ): string {
        return $this->decryptEmailValue(
            (string) $user->getConfirmationToken(),
            str_replace(' ', '+', $encryptedEmail),
        );
    }

    public function getEmailConfirmedToken(): string
    {
        return base64_encode(self::EMAIL_CONFIRMED);
    }

    public function encryptEmailValue(string $confirmationToken, string $email): string
    {
        $this->assertToken($confirmationToken);

        $iv = random_bytes($this->getIvSize());
        $encryptedEmail = openssl_encrypt(
            $email,
            $this->encryptionMode,
            $this->deriveKey($confirmationToken),
            0,
            $iv,
        );

        if (false === $encryptedEmail) {
            throw new \RuntimeException('OpenSSL could not encrypt the email address.');
        }

        return base64_encode($iv.$encryptedEmail);
    }

    public function decryptEmailValue(string $confirmationToken, string $encryptedEmail): string
    {
        $this->assertToken($confirmationToken);

        $decoded = base64_decode($encryptedEmail, true);
        $ivSize = $this->getIvSize();
        if (false === $decoded || strlen($decoded) <= $ivSize) {
            throw new \InvalidArgumentException('The encrypted email payload is malformed.');
        }

        $decryptedEmail = openssl_decrypt(
            substr($decoded, $ivSize),
            $this->encryptionMode,
            $this->deriveKey($confirmationToken),
            0,
            substr($decoded, 0, $ivSize),
        );

        if (false === $decryptedEmail) {
            throw new \InvalidArgumentException('The email confirmation payload could not be decrypted.');
        }

        $email = rtrim($decryptedEmail, "\0");
        if ($this->validator->validate($email, new Email())->count() > 0) {
            throw new \InvalidArgumentException('The decrypted value is not a valid email address.');
        }

        return $email;
    }

    private function assertToken(string $confirmationToken): void
    {
        if ('' === $confirmationToken) {
            throw new \InvalidArgumentException('The user confirmation token must not be empty.');
        }
    }

    private function getIvSize(): int
    {
        $ivSize = openssl_cipher_iv_length($this->encryptionMode);
        if (false === $ivSize || $ivSize < 1) {
            throw new \RuntimeException(sprintf(
                'Unable to determine the IV size for cipher "%s".',
                $this->encryptionMode,
            ));
        }

        return $ivSize;
    }

    private function deriveKey(string $confirmationToken): string
    {
        return pack('H*', hash('sha256', $confirmationToken));
    }
}
