<?php

namespace Azine\EmailUpdateConfirmationBundle\Tests;

use Azine\EmailUpdateConfirmationBundle\Services\EmailUpdateConfirmation;
use FOS\UserBundle\Mailer\MailerInterface;
use Azine\EmailUpdateConfirmationBundle\Tests\AzineTestCase;
use FOS\UserBundle\Model\User;
use FOS\UserBundle\Util\TokenGenerator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmailUpdateConfirmationTest extends AzineTestCase
{
    /** @var ExpressionFunctionProviderInterface */
    private $provider;
    /** @var RouterInterface */
    private $router;
    /** @var TokenGenerator */
    private $tokenGenerator;
    /** @var MailerInterface */
    private $mailer;
    /** @var EventDispatcher */
    private $eventDispatcher;
    /** @var string */
    private $redirectRoute = 'redirect_route';
    /** @var string */
    private $token = 'test_token';
    /** @var EmailUpdateConfirmation */
    private $emailUpdateConfirmation;
    /** @var string */
    private $emailTest = 'foo@example.com';
    /** @var User */
    private $user;
    private $cypher_method = 'AES-128-CBC';

    /** @var ValidatorInterface */
    private $emailValidator;
    /** @var ConstraintViolationList */
    private $constraintViolationList;

    protected function setUp()
    {
        $this->emailValidator = $this->getMockBuilder('Symfony\Component\Validator\Validator\RecursiveValidator')->disableOriginalConstructor()->getMock();
        $this->constraintViolationList = $this->getMockBuilder('Symfony\Component\Validator\ConstraintViolationList')->disableOriginalConstructor()->getMock();

        $this->provider = $this->getMockBuilder('Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface')->getMock();
        $this->user = $this->getMockBuilder('FOS\UserBundle\Model\User')
            ->disableOriginalConstructor()
            ->getMock();
        $this->router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $this->tokenGenerator = $this->getMockBuilder('FOS\UserBundle\Util\TokenGenerator')->disableOriginalConstructor()->getMock();
        $this->mailer = $this->getMockBuilder('Azine\EmailUpdateConfirmationBundle\Mailer\AzineEmailUpdateConfirmationMailer')->disableOriginalConstructor()->getMock();
        $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')->getMock();

        $this->emailUpdateConfirmation = new EmailUpdateConfirmation($this->router, $this->tokenGenerator, $this->mailer, $this->eventDispatcher, $this->emailValidator, $this->redirectRoute, $this->cypher_method);

        $this->user->expects($this->any())
            ->method('getConfirmationToken')
            ->will($this->returnValue($this->token));
    }

    public function testFetchEncryptedEmailFromConfirmationLinkMethod()
    {
        $this->emailValidator->expects($this->once())->method('validate')->will($this->returnValue($this->constraintViolationList));
        $encryptedEmail = $this->emailUpdateConfirmation->encryptEmailValue($this->token, $this->emailTest);

        $email = $this->emailUpdateConfirmation->fetchEncryptedEmailFromConfirmationLink($this->user, $encryptedEmail);
        $this->assertSame($this->emailTest, $email);
    }

    public function testEncryptDecryptEmail()
    {
        $this->emailValidator->expects($this->once())->method('validate')->will($this->returnValue($this->constraintViolationList));
        $encryptedEmail = $this->emailUpdateConfirmation->encryptEmailValue($this->user->getConfirmationToken(), $this->emailTest);
        $this->assertSame($this->emailTest, $this->emailUpdateConfirmation->decryptEmailValue($this->user->getConfirmationToken(), $encryptedEmail));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDecryptFromWrongEmailFormat()
    {
        $this->emailValidator->expects($this->once())->method('validate')->will($this->returnValue($this->constraintViolationList));

        $this->constraintViolationList->expects($this->once())->method('count')->will($this->returnValue(1));
        $wrongEmail = 'fooexample.com';

        $encryptedEmail = $this->emailUpdateConfirmation->encryptEmailValue($this->user->getConfirmationToken(), $wrongEmail);
        $this->emailUpdateConfirmation->decryptEmailValue($this->user->getConfirmationToken(), $encryptedEmail);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIntegerIsSetInsteadOfEmailString()
    {
        $this->emailUpdateConfirmation->encryptEmailValue($this->user->getConfirmationToken(), 123);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIntegerIsSetInsteadOfConfirmationTokenStringForEncryption()
    {
        $this->emailUpdateConfirmation->encryptEmailValue(123, $this->emailTest);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIntegerIsSetInsteadOfConfirmationTokenStringForDecryption()
    {
        $this->emailUpdateConfirmation->decryptEmailValue(123, $this->emailTest);
    }
}
