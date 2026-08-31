<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;

final class RoutingTest extends TestCase
{
    public function testCanonicalRouteDoesNotAcceptAUserControlledRedirectRoute(): void
    {
        $routes = (new YamlFileLoader(new FileLocator(__DIR__.'/../../Resources/config')))
            ->load('routing.yml');

        $route = $routes->get('user_update_email_confirm');
        self::assertNotNull($route);
        self::assertSame('/confirm-email-update/{token}', $route->getPath());
        self::assertSame(['GET'], $route->getMethods());
        self::assertStringContainsString(
            'ConfirmEmailUpdateController::confirmEmailUpdateAction',
            (string) $route->getDefault('_controller'),
        );
    }

    public function testLegacyPendingLinksRemainRoutableButCannotChooseTheRedirect(): void
    {
        $routes = (new YamlFileLoader(new FileLocator(__DIR__.'/../../Resources/config')))
            ->load('routing.yml');

        $route = $routes->get('user_update_email_confirm_legacy');
        self::assertNotNull($route);
        self::assertSame('/confirm-email-update/{token}/{redirectRoute}', $route->getPath());
    }
}
