<?php

declare(strict_types=1);

namespace AppTest\Container;

use App\CommandHandler\DeleteNoteHandler;
use App\Container\DeleteNoteHandlerFactory;
use Laminas\ServiceManager\ServiceManager;
use PhpDb\TableGateway\TableGateway;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversClass(DeleteNoteHandlerFactory::class)]
#[CoversMethod(DeleteNoteHandlerFactory::class, '__invoke')]
final class DeleteNoteHandlerFactoryTest extends TestCase
{
    public function testFactoryReturnsDeleteNoteHandler(): void
    {
        $gateway   = $this->createStub(TableGateway::class);
        $container = new ServiceManager([
            'factories' => [
                TableGateway::class => static fn(): TableGateway => $gateway,
            ],
        ]);

        $factory = new DeleteNoteHandlerFactory();
        $handler = $factory($container);

        self::assertInstanceOf(DeleteNoteHandler::class, $handler);
    }
}
