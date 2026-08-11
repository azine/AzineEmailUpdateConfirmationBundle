<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\Tests\Routing;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;

class RoutingTest extends TestCase
{
    #[DataProvider('loadRoutingProvider')]
    public function testLoadRouting(
        string $routeName,
        string $path,
        array $methods,
        string $controller,
    ): void {
        $loader = new YamlFileLoader(new FileLocator(__DIR__.'/../../Resources/config'));
        $collection = $loader->load('routing.yml');
        $route = $collection->get($routeName);

        self::assertNotNull($route, sprintf('The route "%s" should exist.', $routeName));
        self::assertSame($path, $route->getPath());
        self::assertSame($methods, $route->getMethods());
        self::assertSame($controller, $route->getDefault('_controller'));
    }

    public static function loadRoutingProvider(): iterable
    {
        yield 'confirmation route' => [
            'user_update_email_confirm',
            '/confirm-email-update/{token}/{redirectRoute}',
            ['GET'],
            'Azine\\EmailUpdateConfirmationBundle\\Controller\\ConfirmEmailUpdateController::confirmEmailUpdateAction',
        ];
    }
}
