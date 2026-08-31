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

final class EmailUpdateConfirmation implements EmailUpdateConfirmationInterface
{
    public const EMAIL_CONFIRMED = 'email_confirmed';

    private const DEFAULT_CIPHER = 'AES-128-CBC';
    private const CONFIRMATION_ROUTE = 'user_update_email_confirm';
    private const PAYLOAD_PREFIX = 'v2.';
    private const MAC_BYTES = 32;

    private readonly string $encryptionMode;
    private readonly \Closure $nowProvider;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly TokenGeneratorInterface $tokenGenerator,
        private readonly EmailUpdateConfirmationMailerInterface $mailer,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ValidatorInterface $validator,
        private readonly string $redirectRoute,
        ?string $mode = null,
        private readonly int $confirmationTtl = 86400,
        private readonly bool $allowLegacyPayloads = true,
        ?\Closure $nowProvider = null,
    ) {
        $this->encryptionMode = $mode ?: self::DEFAULT_CIPHER;
        $this->nowProvider = $nowProvider ?? static fn (): int => time();

        if ($this->confirmationTtl < 60) {
            throw new \InvalidArgumentException('The email confirmation TTL must be at least 60 seconds.');
        }

        $supportedCiphers = array_map('strtolower', openssl_get_cipher_methods(false));
        if (!in_array(strtolower($this->encryptionMode), $supportedCiphers, true)) {
            throw new \InvalidArgumentException(sprintf(
                'The OpenSSL cipher "%s" is not supported by this PHP runtime.',
                $this->encryptionMode,
            ));
        }

        $this->getIvSize();
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
        $this->assertValidEmail($email);

        $plaintext = json_encode([
            'email' => $email,
            'expires_at' => ($this->nowProvider)() + $this->confirmationTtl,
        ], JSON_THROW_ON_ERROR);

        $iv = random_bytes($this->getIvSize());
        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->encryptionMode,
            $this->deriveEncryptionKey($confirmationToken),
            OPENSSL_RAW_DATA,
            $iv,
        );

        if (false === $ciphertext) {
            throw new \RuntimeException('OpenSSL could not encrypt the email confirmation payload.');
        }

        $mac = hash_hmac(
            'sha256',
            $iv.$ciphertext,
            $this->deriveMacKey($confirmationToken),
            true,
        );

        return self::PAYLOAD_PREFIX.$this->base64UrlEncode($iv.$mac.$ciphertext);
    }

    public function decryptEmailValue(string $confirmationToken, string $encryptedEmail): string
    {
        $this->assertToken($confirmationToken);

        if (str_starts_with($encryptedEmail, self::PAYLOAD_PREFIX)) {
            return $this->decryptAuthenticatedPayload(
                $confirmationToken,
                substr($encryptedEmail, strlen(self::PAYLOAD_PREFIX)),
            );
        }

        if (!$this->allowLegacyPayloads) {
            throw new \InvalidArgumentException('Legacy email confirmation payloads are disabled.');
        }

        return $this->decryptLegacyPayload($confirmationToken, $encryptedEmail);
    }

    private function decryptAuthenticatedPayload(string $confirmationToken, string $payload): string
    {
        $decoded = $this->base64UrlDecode($payload);
        $ivSize = $this->getIvSize();

        if (strlen($decoded) <= $ivSize + self::MAC_BYTES) {
            throw new \InvalidArgumentException('The encrypted email payload is malformed.');
        }

        $iv = substr($decoded, 0, $ivSize);
        $mac = substr($decoded, $ivSize, self::MAC_BYTES);
        $ciphertext = substr($decoded, $ivSize + self::MAC_BYTES);

        $expectedMac = hash_hmac(
            'sha256',
            $iv.$ciphertext,
            $this->deriveMacKey($confirmationToken),
            true,
        );

        if (!hash_equals($expectedMac, $mac)) {
            throw new \InvalidArgumentException('The email confirmation payload has been modified.');
        }

        $plaintext = openssl_decrypt(
            $ciphertext,
            $this->encryptionMode,
            $this->deriveEncryptionKey($confirmationToken),
            OPENSSL_RAW_DATA,
            $iv,
        );

        if (false === $plaintext) {
            throw new \InvalidArgumentException('The email confirmation payload could not be decrypted.');
        }

        try {
            $data = json_decode($plaintext, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException('The decrypted email payload is malformed.', 0, $exception);
        }

        if (!is_array($data) || !is_string($data['email'] ?? null) || !is_int($data['expires_at'] ?? null)) {
            throw new \InvalidArgumentException('The decrypted email payload is malformed.');
        }

        if (($this->nowProvider)() > $data['expires_at']) {
            throw new \InvalidArgumentException('The email confirmation link has expired.');
        }

        $this->assertValidEmail($data['email']);

        return $data['email'];
    }

    private function decryptLegacyPayload(string $confirmationToken, string $encryptedEmail): string
    {
        $decoded = base64_decode($encryptedEmail, true);
        $ivSize = $this->getIvSize();
        if (false === $decoded || strlen($decoded) <= $ivSize) {
            throw new \InvalidArgumentException('The encrypted email payload is malformed.');
        }

        $decryptedEmail = openssl_decrypt(
            substr($decoded, $ivSize),
            $this->encryptionMode,
            $this->deriveLegacyKey($confirmationToken),
            0,
            substr($decoded, 0, $ivSize),
        );

        if (false === $decryptedEmail) {
            throw new \InvalidArgumentException('The email confirmation payload could not be decrypted.');
        }

        $email = rtrim($decryptedEmail, "\0");
        $this->assertValidEmail($email);

        return $email;
    }

    private function assertToken(string $confirmationToken): void
    {
        if ('' === $confirmationToken) {
            throw new \InvalidArgumentException('The user confirmation token must not be empty.');
        }
    }

    private function assertValidEmail(string $email): void
    {
        if ($this->validator->validate($email, new Email())->count() > 0) {
            throw new \InvalidArgumentException('The value is not a valid email address.');
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

    private function deriveEncryptionKey(string $confirmationToken): string
    {
        return hash('sha256', "encryption\0".$confirmationToken, true);
    }

    private function deriveMacKey(string $confirmationToken): string
    {
        return hash('sha256', "authentication\0".$confirmationToken, true);
    }

    private function deriveLegacyKey(string $confirmationToken): string
    {
        return pack('H*', hash('sha256', $confirmationToken));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;
        if (0 !== $padding) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if (false === $decoded) {
            throw new \InvalidArgumentException('The encrypted email payload is malformed.');
        }

        return $decoded;
    }
}
