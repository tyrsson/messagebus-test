<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Container\HomePageHandlerFactory;
use App\Handler\HomePageHandler;
use AppTest\InMemoryContainer;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversClass(HomePageHandlerFactory::class)]
#[CoversMethod(HomePageHandlerFactory::class, '__invoke')]
final class HomePageHandlerFactoryTest extends TestCase
{
    public function testFactoryReturnsHomePageHandler(): void
    {
        $container = new InMemoryContainer();
        $container->setService(
            TemplateRendererInterface::class,
            $this->createStub(TemplateRendererInterface::class),
        );

        $factory  = new HomePageHandlerFactory();
        $homePage = $factory($container);

        self::assertInstanceOf(HomePageHandler::class, $homePage);
    }
}
