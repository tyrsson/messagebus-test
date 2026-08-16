<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Command\CreateNoteCommand;
use App\Command\DeleteNoteCommand;
use App\Command\UpdateNoteCommand;
use App\Handler\NoteCommandHandler;
use App\Query\ListNotesQuery;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
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
    public function testHandleDeleteReturnsErrorFragmentWhenNoteNotFound(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('DELETE');
        $request->method('getAttribute')->willReturn('999');

        $deleteResult = $this->createStub(ResultInterface::class);
        $deleteResult->method('getStatus')->willReturn(MessageStatus::Failure);

        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturn($deleteResult);

        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects(self::once())
            ->method('render')
            ->with('app::notes-errors', ['error' => 'note not found'])
            ->willReturn('<p role="alert">note not found</p>');

        $handler  = new NoteCommandHandler($bus, $template);
        $response = $handler->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('#notes-errors', $response->getHeaderLine('HX-Target'));
    }

    public function testHandleDeleteReturnsListFragment(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('DELETE');
        $request->method('getAttribute')->willReturn('1');

        $deleteResult = $this->createStub(ResultInterface::class);
        $deleteResult->method('getStatus')->willReturn(MessageStatus::Success);

        $rows = [['id' => 1, 'title' => 'first note']];

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::exactly(2))
            ->method('handle')
            ->willReturnCallback(
                fn(object $message) => $message instanceof DeleteNoteCommand
                    ? $deleteResult
                    : $this->listResult($rows),
            );

        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects(self::once())
            ->method('render')
            ->with('app::notes-list', ['notes' => $rows])
            ->willReturn('<section id="notes-list">list</section>');

        $handler  = new NoteCommandHandler($bus, $template);
        $response = $handler->handle($request);

        self::assertInstanceOf(HtmlResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('notes-list', (string) $response->getBody());
    }

    public function testHandleDeleteThrowsWhenIdAttributeMissing(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('DELETE');
        $request->method('getAttribute')->willReturn(null);

        $handler = new NoteCommandHandler(
            $this->createStub(MessageBusInterface::class),
            $this->createStub(TemplateRendererInterface::class),
        );

        $this->expectException(AssertException::class);
        $handler->handle($request);
    }

    public function testHandlePatchReturnsErrorFragmentWhenNoteNotFound(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn(['title' => 'renamed']);
        $request->method('getMethod')->willReturn('PATCH');
        $request->method('getAttribute')->willReturn('999');

        $updateResult = $this->createStub(ResultInterface::class);
        $updateResult->method('getStatus')->willReturn(MessageStatus::Failure);

        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturn($updateResult);

        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects(self::once())
            ->method('render')
            ->with('app::notes-errors', ['error' => 'note not found'])
            ->willReturn('<p role="alert">note not found</p>');

        $handler  = new NoteCommandHandler($bus, $template);
        $response = $handler->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('#notes-errors', $response->getHeaderLine('HX-Target'));
    }

    public function testHandlePatchReturnsListFragment(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn(['title' => 'renamed']);
        $request->method('getMethod')->willReturn('PATCH');
        $request->method('getAttribute')->willReturn('1');

        $updateResult = $this->createStub(ResultInterface::class);
        $updateResult->method('getStatus')->willReturn(MessageStatus::Success);

        $rows = [['id' => 1, 'title' => 'renamed']];

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::exactly(2))
            ->method('handle')
            ->willReturnCallback(
                fn(object $message) => $message instanceof UpdateNoteCommand
                    ? $updateResult
                    : $this->listResult($rows),
            );

        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects(self::once())
            ->method('render')
            ->with('app::notes-list', ['notes' => $rows])
            ->willReturn('<section id="notes-list">list</section>');

        $handler  = new NoteCommandHandler($bus, $template);
        $response = $handler->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('notes-list', (string) $response->getBody());
    }

    public function testHandlePatchThrowsWhenIdAttributeMissing(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn(['title' => 'renamed']);
        $request->method('getMethod')->willReturn('PATCH');
        $request->method('getAttribute')->willReturn(null);

        $handler = new NoteCommandHandler(
            $this->createStub(MessageBusInterface::class),
            $this->createStub(TemplateRendererInterface::class),
        );

        $this->expectException(AssertException::class);
        $handler->handle($request);
    }

    public function testHandlePostReturnsErrorFragmentWhenTitleMissing(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([]);
        $request->method('getMethod')->willReturn('POST');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('handle');

        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects(self::once())
            ->method('render')
            ->with('app::notes-errors', ['error' => 'title is required'])
            ->willReturn('<p role="alert">title is required</p>');

        $handler  = new NoteCommandHandler($bus, $template);
        $response = $handler->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('#notes-errors', $response->getHeaderLine('HX-Target'));
    }

    public function testHandlePostReturnsListFragment(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn(['title' => 'first note']);
        $request->method('getMethod')->willReturn('POST');

        $createResult = $this->createStub(ResultInterface::class);

        $rows = [['id' => 1, 'title' => 'first note']];

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::exactly(2))
            ->method('handle')
            ->willReturnCallback(
                fn(object $message) => $message instanceof CreateNoteCommand
                    ? $createResult
                    : $this->listResult($rows),
            );

        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects(self::once())
            ->method('render')
            ->with('app::notes-list', ['notes' => $rows])
            ->willReturn('<section id="notes-list">list</section>');

        $handler  = new NoteCommandHandler($bus, $template);
        $response = $handler->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('notes-list', (string) $response->getBody());
    }

    private function listResult(array $rows): ResultInterface
    {
        $result = $this->createStub(ResultInterface::class);
        $result->method('getResult')->willReturn($rows);

        return $result;
    }
}
