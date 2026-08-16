<?php

declare(strict_types=1);

namespace App\Handler;

use App\Command\CreateNoteCommand;
use App\Command\DeleteNoteCommand;
use App\Command\UpdateNoteCommand;
use App\Query\ListNotesQuery;
use Fig\Http\Message\RequestMethodInterface as HttpMethod;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Override;
use Psl\Type;
use Psl\Type\Exception\AssertException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;

final readonly class NoteCommandHandler implements RequestHandlerInterface
{
    public function __construct(
        private MessageBusInterface $bus,
        private TemplateRendererInterface $template,
    ) {}

    /**
     * @throws AssertException when the route id attribute is missing or empty.
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return match ($request->getMethod()) {
            HttpMethod::METHOD_DELETE => $this->handleDelete($request),
            HttpMethod::METHOD_PATCH  => $this->handlePatch($request),
            default                   => $this->handlePost($request),
        };
    }

    /**
     * HTMX does not swap non-2xx bodies, so errors are returned as 200 with an
     * HX-Target header routing the fragment to the #notes-errors container.
     */
    private function error(string $message): ResponseInterface
    {
        return new HtmlResponse(
            $this->template->render('app::notes-errors', ['error' => $message]),
            200,
            ['HX-Target' => '#notes-errors'],
        );
    }

    /** @throws AssertException when the route id attribute is missing or empty. */
    private function handleDelete(ServerRequestInterface $request): ResponseInterface
    {
        $id = Type\non_empty_string()->assert($request->getAttribute('id'));

        $result = $this->bus->handle(new DeleteNoteCommand($id));

        if ($result->getStatus() === MessageStatus::Failure) {
            return $this->error('note not found');
        }

        return $this->renderList();
    }

    /** @throws AssertException when the route id attribute is missing or empty. */
    private function handlePatch(ServerRequestInterface $request): ResponseInterface
    {
        $id = Type\non_empty_string()->assert($request->getAttribute('id'));
        try {
            $data = Type\shape(
                [
                    'title' => Type\non_empty_string(),
                    'body'  => Type\optional(Type\string()),
                ],
                allowUnknownFields: true,
            )->assert($request->getParsedBody());
            $title     = $data['title'];
            $bodyValue = $data['body'] ?? '';
            $body      = $bodyValue !== '' ? $bodyValue : null;
        } catch (AssertException) {
            return $this->error('title is required');
        }

        $result = $this->bus->handle(new UpdateNoteCommand($id, $title, $body));

        if ($result->getStatus() === MessageStatus::Failure) {
            return $this->error('note not found');
        }

        return $this->renderList();
    }

    private function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $data = Type\shape(
                [
                    'title' => Type\non_empty_string(),
                    'body'  => Type\optional(Type\string()),
                ],
                allowUnknownFields: true,
            )->assert($request->getParsedBody());
            $title     = $data['title'];
            $bodyValue = $data['body'] ?? '';
            $body      = $bodyValue !== '' ? $bodyValue : null;
        } catch (AssertException) {
            return $this->error('title is required');
        }

        $this->bus->handle(new CreateNoteCommand($title, $body));

        return $this->renderList();
    }

    private function renderList(): ResponseInterface
    {
        $result = $this->bus->handle(new ListNotesQuery());

        return new HtmlResponse(
            $this->template->render('app::notes-list', ['notes' => $result->getResult()]),
        );
    }
}
