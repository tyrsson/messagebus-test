<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\NoteListHandler;
use App\Query\ListNotesQuery;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\ResultInterface;

#[CoversClass(NoteListHandler::class)]
#[CoversMethod(NoteListHandler::class, 'handle')]
final class NoteListHandlerTest extends TestCase
{
    public function testHandleRendersNotesListFragment(): void
    {
        $rows = [['id' => 1, 'title' => 'first note']];

        $result = $this->createStub(ResultInterface::class);
        $result->method('getResult')->willReturn($rows);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('handle')
            ->with(self::isInstanceOf(ListNotesQuery::class))
            ->willReturn($result);

        $template = $this->createMock(TemplateRendererInterface::class);
        $template->expects(self::once())
            ->method('render')
            ->with('app::notes-list', ['notes' => $rows])
            ->willReturn('<section id="notes-list">list</section>');

        $handler  = new NoteListHandler($bus, $template);
        $response = $handler->handle($this->createStub(ServerRequestInterface::class));

        self::assertInstanceOf(HtmlResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('notes-list', (string) $response->getBody());
    }
}
