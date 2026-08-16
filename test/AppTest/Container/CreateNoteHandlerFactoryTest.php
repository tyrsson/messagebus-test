<?php

declare(strict_types=1);

namespace AppTest\Container;

use App\CommandHandler\CreateNoteHandler;
use App\Container\CreateNoteHandlerFactory;
use Laminas\ServiceManager\ServiceManager;
use PhpDb\TableGateway\TableGateway;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

#[CoversClass(CreateNoteHandlerFactory::class)]
#[CoversMethod(CreateNoteHandlerFactory::class, '__invoke')]
final class CreateNoteHandlerFactoryTest extends TestCase
{
    public function testFactoryReturnsCreateNoteHandler(): void
    {
        $gateway   = $this->createStub(TableGateway::class);
        $container = new ServiceManager([
            'factories' => [
                TableGateway::class             => static fn(): TableGateway => $gateway,
                EventDispatcherInterface::class => fn(): EventDispatcherInterface => $this->createStub(EventDispatcherInterface::class),
            ],
        ]);

        $factory = new CreateNoteHandlerFactory();
        $handler = $factory($container);

        self::assertInstanceOf(CreateNoteHandler::class, $handler);
    }
}
