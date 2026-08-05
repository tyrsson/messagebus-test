<?php

declare(strict_types=1);

namespace AppTest\QueryHandler;

use App\Query\ListNotesQuery;
use App\QueryHandler\ListNotesHandler;
use PhpDb\ResultSet\ResultSetInterface;
use PhpDb\TableGateway\TableGateway;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;

#[CoversClass(ListNotesHandler::class)]
#[CoversMethod(ListNotesHandler::class, 'handle')]
final class ListNotesHandlerTest extends TestCase
{
    public function testHandleReturnsQueryResultWithRows(): void
    {
        $rows = [['id' => 1, 'title' => 'first note']];

        $resultSet = $this->createStub(ResultSetInterface::class);
        $resultSet->method('toArray')->willReturn($rows);

        $gateway = $this->createStub(TableGateway::class);
        $gateway->method('selectWith')->willReturn($resultSet);

        $handler = new ListNotesHandler($gateway);
        $result  = $handler->handle(new ListNotesQuery());

        self::assertInstanceOf(QueryResult::class, $result);
        self::assertSame(MessageStatus::Success, $result->getStatus());
        self::assertSame($rows, $result->getResult());
    }
}
