<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\NoteQueryHandler;
use App\Query\ListNotesQuery;
use Laminas\Diactoros\Response\JsonResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\ResultInterface;

use function json_decode;

use const JSON_THROW_ON_ERROR;

#[CoversClass(NoteQueryHandler::class)]
#[CoversMethod(NoteQueryHandler::class, 'handle')]
final class NoteQueryHandlerTest extends TestCase
{
    public function testHandleReturnsNotesFromBus(): void
    {
        $rows = [['id' => 1, 'title' => 'first note']];

        $result = $this->createStub(ResultInterface::class);
        $result->method('getResult')->willReturn($rows);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('handle')
            ->with(self::isInstanceOf(ListNotesQuery::class))
            ->willReturn($result);

        $handler  = new NoteQueryHandler($bus);
        $response = $handler->handle($this->createStub(ServerRequestInterface::class));

        self::assertInstanceOf(JsonResponse::class, $response);
        $json = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(['notes' => $rows], $json);
    }
}
