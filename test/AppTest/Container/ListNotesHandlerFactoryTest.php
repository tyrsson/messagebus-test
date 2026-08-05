<?php

declare(strict_types=1);

namespace AppTest\Container;

use App\Container\ListNotesHandlerFactory;
use App\QueryHandler\ListNotesHandler;
use Laminas\ServiceManager\ServiceManager;
use PhpDb\TableGateway\TableGateway;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversClass(ListNotesHandlerFactory::class)]
#[CoversMethod(ListNotesHandlerFactory::class, '__invoke')]
final class ListNotesHandlerFactoryTest extends TestCase
{
    public function testFactoryReturnsListNotesHandler(): void
    {
        $gateway   = $this->createStub(TableGateway::class);
        $container = new ServiceManager([
            'factories' => [
                TableGateway::class => static fn(): TableGateway => $gateway,
            ],
        ]);

        $factory = new ListNotesHandlerFactory();
        $handler = $factory($container);

        self::assertInstanceOf(ListNotesHandler::class, $handler);
    }
}
