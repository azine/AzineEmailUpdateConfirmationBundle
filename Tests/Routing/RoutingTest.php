<?php

namespace Azine\EmailUpdateConfirmationBundle\Routing;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;

class RoutingTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider loadRoutingProvider
     *
     * @param string $routeName
     * @param string $path
     * @param array  $methods
     */
    public function testLoadRouting($routeName, $path, array $methods)
    {
        $locator = new FileLocator();
        $loader = new YamlFileLoader($locator);

        $collection = new RouteCollection();
        $subCollection = $loader->load(__DIR__.'/../../Resources/config/routing.yml');
        $collection->addCollection($subCollection);

        $route = $collection->get($routeName);
        $this->assertNotNull($route, sprintf('The route "%s" should exists', $routeName));
        $this->assertSame($path, $route->getPath());
        $this->assertSame($methods, $route->getMethods());
    }

    /**
     * @return array
     */
    public function loadRoutingProvider()
    {
        return array(
            array('user_update_email_confirm', '/{_locale}/confirm-email-update/{token}/{redirectRoute}', array('GET')),
        );
    }
}
