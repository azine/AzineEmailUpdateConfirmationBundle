<?php

namespace Azine\EmailUpdateConfirmationBundle\Tests;

use Azine\EmailUpdateConfirmationBundle\Services\EmailEncryption;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmailEncryptionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ValidatorInterface */
    private $emailValidator;
    /** @var ConstraintViolationList */
    private $constraintViolationList;

    protected function setUp()
    {
        $this->emailValidator = $this->getMockBuilder('Symfony\Component\Validator\Validator\RecursiveValidator')->disableOriginalConstructor()->getMock();
        $this->constraintViolationList = new ConstraintViolationList(array($this->getMockBuilder('Symfony\Component\Validator\ConstraintViolation')->disableOriginalConstructor()->getMock()));
    }

    public function testEncryptDecryptEmail()
    {
        $this->emailValidator->expects($this->once())->method('validate')->will($this->returnValue($this->constraintViolationList));
        $this->constraintViolationList->remove(0);
        $emailEncryption = new EmailEncryption($this->emailValidator);
        $emailEncryption->setEmail('foo@example.com');
        $emailEncryption->setUserConfirmationToken('test_token');

        $encryptedEmail = $emailEncryption->encryptEmailValue();
        $this->assertSame('foo@example.com', $emailEncryption->decryptEmailValue($encryptedEmail));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDecryptFromWrongEmailFormat()
    {
        $this->emailValidator->expects($this->once())->method('validate')->will($this->returnValue($this->constraintViolationList));
        $emailEncryption = new EmailEncryption($this->emailValidator);
        $emailEncryption->setEmail('fooexample.com');
        $emailEncryption->setUserConfirmationToken('test_token');

        $encryptedEmail = $emailEncryption->encryptEmailValue();
        $emailEncryption->decryptEmailValue($encryptedEmail);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIntegerIsSetInsteadOfEmailString()
    {
        $emailEncryption = new EmailEncryption($this->emailValidator);
        $emailEncryption->setEmail(123);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIntegerIsSetInsteadOfConfirmationTokenString()
    {
        $emailEncryption = new EmailEncryption($this->emailValidator);
        $emailEncryption->setUserConfirmationToken(123);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNullIsSetInsteadOfConfirmationTokenString()
    {
        $emailEncryption = new EmailEncryption($this->emailValidator);
        $emailEncryption->setUserConfirmationToken(null);
    }

    public function testGetConfirmationToken()
    {
        $this->constraintViolationList->remove(0);
        $emailEncryption = new EmailEncryption($this->emailValidator);
        $emailEncryption->setUserConfirmationToken('test_token');

        $confirmationToken = $emailEncryption->getConfirmationToken();
        $expectedConfirmationToken = pack('H*', hash('sha256', 'test_token'));
        $this->assertSame($expectedConfirmationToken, $confirmationToken);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetConfirmationTokenIfUserConfirmationTokenIsNotSet()
    {
        $emailEncryption = new EmailEncryption($this->emailValidator);
        $emailEncryption->getConfirmationToken();
    }
}
