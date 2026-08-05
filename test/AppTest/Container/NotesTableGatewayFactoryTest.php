<?php

declare(strict_types=1);

namespace AppTest\Container;

use App\Container\NotesTableGatewayFactory;
use AppTest\InMemoryContainer;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\TableGateway\TableGateway;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversClass(NotesTableGatewayFactory::class)]
#[CoversMethod(NotesTableGatewayFactory::class, '__invoke')]
final class NotesTableGatewayFactoryTest extends TestCase
{
    public function testFactoryReturnsTableGatewayForNotesTable(): void
    {
        $container = new InMemoryContainer();
        $container->setService(AdapterInterface::class, $this->createStub(AdapterInterface::class));

        $factory = new NotesTableGatewayFactory();
        $gateway = $factory($container);

        self::assertInstanceOf(TableGateway::class, $gateway);
        self::assertSame('notes', $gateway->getTable());
    }
}
