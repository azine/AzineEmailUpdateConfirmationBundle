<?php

namespace Azine\EmailUpdateConfirmationBundle\Services;

use Azine\EmailUpdateConfirmationBundle\AzineEmailUpdateConfirmationEvents;
use Azine\EmailUpdateConfirmationBundle\Mailer\EmailUpdateConfirmationMailerInterface;
use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Util\TokenGenerator;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmailUpdateConfirmation implements EmailUpdateConfirmationInterface
{
    const EMAIL_CONFIRMED = 'email_confirmed';

    /**
     * @var EmailUpdateConfirmationMailerInterface
     */
    private $mailer;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var TokenGenerator
     */
    private $tokenGenerator;

    /**
     * @var string
     */
    private $encryptionMode;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var string Route for confirmation link
     */
    private $confirmationRoute = 'user_update_email_confirm';

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var string
     */
    private $redirectRoute;

    public function __construct(
        Router $router,
        TokenGenerator $tokenGenerator,
        EmailUpdateConfirmationMailerInterface $mailer,
        EventDispatcherInterface $eventDispatcher,
        ValidatorInterface $validator,
        $redirectRoute,
        $mode = null
    ) {
        $this->router = $router;
        $this->tokenGenerator = $tokenGenerator;
        $this->mailer = $mailer;
        $this->eventDispatcher = $eventDispatcher;
        $this->validator = $validator;
        $this->redirectRoute = $redirectRoute;

        if (!$mode) {
            $mode = openssl_get_cipher_methods(false)[0];
        }
        $this->encryptionMode = $mode;
    }

    /**
     * Get $mailer.
     *
     * @return MailerInterface
     */
    public function getMailer()
    {
        return $this->mailer;
    }

    /**
     * Generate new confirmation link for new email based on user confirmation
     * token and hashed new user email.
     *
     * @param Request $request
     *
     * @return string
     */
    public function generateConfirmationLink(Request $request, UserInterface $user, $email)
    {
        if (!$user->getConfirmationToken()) {
            $user->setConfirmationToken(
                $this->tokenGenerator->generateToken()
            );
        }

        $encryptedEmail = $this->encryptEmailValue($user->getConfirmationToken(), $email);

        $confirmationParams = array('token' => $user->getConfirmationToken(), 'target' => $encryptedEmail, 'redirectRoute' => $this->redirectRoute);

        $event = new UserEvent($user, $request);

        $this->eventDispatcher->dispatch(AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_INITIALIZE, $event);

        return $this->router->generate(
            $this->confirmationRoute,
            $confirmationParams,
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * Fetch email value from hashed part of confirmation link.
     *
     * @param UserInterface $user
     * @param string        $hashedEmail
     *
     * @return string Encrypted email
     */
    public function fetchEncryptedEmailFromConfirmationLink($user, $hashedEmail)
    {
        //replace spaces with plus sign from hash, which could be replaced in url
        $hashedEmail = str_replace(' ', '+', $hashedEmail);

        $email = $this->decryptEmailValue($user->getConfirmationToken(), $hashedEmail);

        return $email;
    }

    /**
     * Get token which indicates that email was confirmed.
     *
     * @return string
     */
    public function getEmailConfirmedToken()
    {
        return base64_encode(self::EMAIL_CONFIRMED);
    }

    /**
     * Return IV size.
     *
     * @return int
     */
    protected function getIvSize()
    {
        return openssl_cipher_iv_length($this->encryptionMode);
    }

    /**
     * Encrypt email value with specified user confirmation token.
     *
     * @param string $userConfirmationToken
     * @param string $email
     *
     * @return string Encrypted email
     */
    public function encryptEmailValue($userConfirmationToken, $email)
    {
        if (!$userConfirmationToken || !is_string($userConfirmationToken)) {
            throw new \InvalidArgumentException(
                'Invalid user confirmation token value.'
            );
        }

        if (!is_string($email)) {
            throw new \InvalidArgumentException(
                'Email to be encrypted should a string. '
                .gettype($email).' given.'
            );
        }

        $iv = openssl_random_pseudo_bytes($this->getIvSize());

        $encryptedEmail = openssl_encrypt(
            $email,
            $this->encryptionMode,
            pack('H*', hash('sha256', $userConfirmationToken)),
            0,
            $iv
        );

        $encryptedEmail = base64_encode($iv.$encryptedEmail);

        return $encryptedEmail;
    }

    /**
     * Decrypt email value with specified user confirmation token.
     *
     * @param string $userConfirmationToken
     * @param string $encryptedEmail
     *
     * @return string Decrypted email
     */
    public function decryptEmailValue($userConfirmationToken, $encryptedEmail)
    {
        if (!$userConfirmationToken || !is_string($userConfirmationToken)) {
            throw new \InvalidArgumentException(
                'Invalid user confirmation token value.'
            );
        }

        $b64DecodedEmailHash = base64_decode($encryptedEmail);
        $ivSize = $this->getIvSize();

        // Select IV part from encrypted value
        $iv = substr($b64DecodedEmailHash, 0, $ivSize);

        // Select email part from encrypted value
        $preparedEncryptedEmail = substr($b64DecodedEmailHash, $ivSize);

        $decryptedEmail = openssl_decrypt(
            $preparedEncryptedEmail,
            $this->encryptionMode,
            pack('H*', hash('sha256', $userConfirmationToken)),
            0,
            $iv
        );

        // Trim decrypted email from nul byte before return
        $email = rtrim($decryptedEmail, "\0");

        /** @var ConstraintViolationList $violationList */
        $violationList = $this->validator->validate($email, new Email());
        if ($violationList->count() > 0) {
            throw new \InvalidArgumentException('Wrong email format was provided for decryptEmailValue function');
        }

        return $email;
    }
}
