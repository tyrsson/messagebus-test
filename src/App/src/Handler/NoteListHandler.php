<?php

declare(strict_types=1);

namespace App\Handler;

use App\Query\ListNotesQuery;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\MessageBus\MessageBusInterface;

final readonly class NoteListHandler implements RequestHandlerInterface
{
    public function __construct(
        private MessageBusInterface $bus,
        private TemplateRendererInterface $template,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $result = $this->bus->handle(new ListNotesQuery());

        return new HtmlResponse(
            $this->template->render('app::notes-list', ['notes' => $result->getResult()]),
        );
    }
}
