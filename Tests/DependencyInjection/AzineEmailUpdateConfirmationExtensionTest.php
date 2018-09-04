<?php

namespace Azine\EmailUpdateConfirmationBundle\Tests\DependencyInjection;

use Azine\EmailUpdateConfirmationBundle\DependencyInjection\AzineEmailUpdateConfirmationExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AzineEmailUpdateConfirmationExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testDisableEmailUpdateConfirmation()
    {
        $configuration = new ContainerBuilder();
        $loader = new AzineEmailUpdateConfirmationExtension();
        $config = array();
        $config['enabled'] = false;
        $loader->load(array($config), $configuration);

        $this->assertFalse($configuration->hasDefinition('email_update_confirmation'));
        $this->assertFalse($configuration->hasDefinition('email_encryption'));
        $this->assertFalse($configuration->hasDefinition('azine.email_update.mailer'));
        $this->assertFalse($configuration->hasDefinition('email_update_listener'));
        $this->assertFalse($configuration->hasDefinition('email_update_flash_subscriber'));
        $this->assertFalse($configuration->hasParameter('azine_email_update_confirmation.template'));
        $this->assertFalse($configuration->hasParameter('azine_email_update_confirmation.cypher_method'));
        $this->assertFalse($configuration->hasParameter('azine_email_update_confirmation.redirect_route'));
    }

    public function testEnableEmailUpdateConfirmation()
    {
        $configuration = new ContainerBuilder();
        $loader = new AzineEmailUpdateConfirmationExtension();
        $config = array();
        $config['enabled'] = true;
        $loader->load(array($config), $configuration);

        $this->assertTrue($configuration->hasDefinition('email_update_confirmation'));
        $this->assertTrue($configuration->hasDefinition('email_encryption'));
        $this->assertTrue($configuration->hasDefinition('azine.email_update.mailer'));
        $this->assertTrue($configuration->hasDefinition('email_update_listener'));
        $this->assertTrue($configuration->hasDefinition('email_update_flash_subscriber'));
        $this->assertTrue($configuration->hasParameter('azine_email_update_confirmation.template'));
        $this->assertTrue($configuration->hasParameter('azine_email_update_confirmation.cypher_method'));
        $this->assertTrue($configuration->hasParameter('azine_email_update_confirmation.redirect_route'));
    }
}
