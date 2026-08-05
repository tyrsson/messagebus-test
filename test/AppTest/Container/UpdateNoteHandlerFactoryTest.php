<?php

declare(strict_types=1);

namespace AppTest\Container;

use App\CommandHandler\UpdateNoteHandler;
use App\Container\UpdateNoteHandlerFactory;
use Laminas\ServiceManager\ServiceManager;
use PhpDb\TableGateway\TableGateway;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversClass(UpdateNoteHandlerFactory::class)]
#[CoversMethod(UpdateNoteHandlerFactory::class, '__invoke')]
final class UpdateNoteHandlerFactoryTest extends TestCase
{
    public function testFactoryReturnsUpdateNoteHandler(): void
    {
        $gateway   = $this->createStub(TableGateway::class);
        $container = new ServiceManager([
            'factories' => [
                TableGateway::class => static fn(): TableGateway => $gateway,
            ],
        ]);

        $factory = new UpdateNoteHandlerFactory();
        $handler = $factory($container);

        self::assertInstanceOf(UpdateNoteHandler::class, $handler);
    }
}
