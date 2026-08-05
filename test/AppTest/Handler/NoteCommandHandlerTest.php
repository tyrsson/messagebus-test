<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Command\CreateNoteCommand;
use App\Command\DeleteNoteCommand;
use App\Command\UpdateNoteCommand;
use App\Handler\NoteCommandHandler;
use Laminas\Diactoros\Response\JsonResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\AssertException;
use Psr\Http\Message\ServerRequestInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\ResultInterface;

#[CoversClass(NoteCommandHandler::class)]
#[CoversMethod(NoteCommandHandler::class, 'handle')]
final class NoteCommandHandlerTest extends TestCase
{
    public function testHandleDeleteRemovesNoteAndReturns200(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('DELETE');
        $request->method('getAttribute')->willReturn('1');

        $result = $this->createStub(ResultInterface::class);
        $result->method('getStatus')->willReturn(MessageStatus::Success);
        $result->method('getResult')->willReturn(['id' => '1']);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('handle')
            ->with(self::isInstanceOf(DeleteNoteCommand::class))
            ->willReturn($result);

        $handler  = new NoteCommandHandler($bus);
        $response = $handler->handle($request);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testHandleDeleteReturns404WhenNoteNotFound(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('DELETE');
        $request->method('getAttribute')->willReturn('999');

        $result = $this->createStub(ResultInterface::class);
        $result->method('getStatus')->willReturn(MessageStatus::Failure);

        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturn($result);

        $handler  = new NoteCommandHandler($bus);
        $response = $handler->handle($request);

        self::assertSame(404, $response->getStatusCode());
    }

    public function testHandleDeleteThrowsWhenIdAttributeMissing(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('DELETE');
        $request->method('getAttribute')->willReturn(null);

        $handler = new NoteCommandHandler($this->createStub(MessageBusInterface::class));

        $this->expectException(AssertException::class);
        $handler->handle($request);
    }

    public function testHandlePatchReturns404WhenNoteNotFound(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn(['title' => 'renamed']);
        $request->method('getMethod')->willReturn('PATCH');
        $request->method('getAttribute')->willReturn('999');

        $result = $this->createStub(ResultInterface::class);
        $result->method('getStatus')->willReturn(MessageStatus::Failure);

        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturn($result);

        $handler  = new NoteCommandHandler($bus);
        $response = $handler->handle($request);

        self::assertSame(404, $response->getStatusCode());
    }

    public function testHandlePatchThrowsWhenIdAttributeMissing(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn(['title' => 'renamed']);
        $request->method('getMethod')->willReturn('PATCH');
        $request->method('getAttribute')->willReturn(null);

        $handler = new NoteCommandHandler($this->createStub(MessageBusInterface::class));

        $this->expectException(AssertException::class);
        $handler->handle($request);
    }

    public function testHandlePatchUpdatesNoteAndReturns200(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn(['title' => 'renamed']);
        $request->method('getMethod')->willReturn('PATCH');
        $request->method('getAttribute')->willReturn('1');

        $result = $this->createStub(ResultInterface::class);
        $result->method('getStatus')->willReturn(MessageStatus::Success);
        $result->method('getResult')->willReturn(['id' => '1', 'title' => 'renamed']);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('handle')
            ->with(self::isInstanceOf(UpdateNoteCommand::class))
            ->willReturn($result);

        $handler  = new NoteCommandHandler($bus);
        $response = $handler->handle($request);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testHandlePostCreatesNoteAndReturns201(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn(['title' => 'first note']);
        $request->method('getMethod')->willReturn('POST');

        $result = $this->createStub(ResultInterface::class);
        $result->method('getResult')->willReturn(['id' => 1, 'title' => 'first note']);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('handle')
            ->with(self::isInstanceOf(CreateNoteCommand::class))
            ->willReturn($result);

        $handler  = new NoteCommandHandler($bus);
        $response = $handler->handle($request);

        self::assertSame(201, $response->getStatusCode());
    }

    public function testHandleReturns422WhenTitleMissing(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([]);
        $request->method('getMethod')->willReturn('POST');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('handle');

        $handler  = new NoteCommandHandler($bus);
        $response = $handler->handle($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(422, $response->getStatusCode());
    }
}
