<?php

declare(strict_types=1);

namespace App\Handler;

use App\Command\CreateNoteCommand;
use App\Command\DeleteNoteCommand;
use App\Command\UpdateNoteCommand;
use Fig\Http\Message\RequestMethodInterface as HttpMethod;
use Laminas\Diactoros\Response\JsonResponse;
use Psl\Type;
use Psl\Type\Exception\AssertException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;

final readonly class NoteCommandHandler implements RequestHandlerInterface
{
    private const int JSON_FLAGS = JsonResponse::DEFAULT_JSON_FLAGS | JSON_PRETTY_PRINT;

    public function __construct(
        private MessageBusInterface $bus,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return match ($request->getMethod()) {
            HttpMethod::METHOD_DELETE => $this->handleDelete($request),
            HttpMethod::METHOD_PATCH  => $this->handlePatch($request),
            default                   => $this->handlePost($request),
        };
    }

    private function handleDelete(ServerRequestInterface $request): ResponseInterface
    {
        $id = Type\non_empty_string()->assert($request->getAttribute('id'));

        $result = $this->bus->handle(new DeleteNoteCommand($id));

        if ($result->getStatus() === MessageStatus::Failure) {
            return new JsonResponse(['error' => 'note not found'], 404, [], self::JSON_FLAGS);
        }

        return new JsonResponse($result->getResult(), 200, [], self::JSON_FLAGS);
    }

    private function handlePatch(ServerRequestInterface $request): ResponseInterface
    {
        $id = Type\non_empty_string()->assert($request->getAttribute('id'));
        try {
            $title = Type\shape(['title' => Type\non_empty_string()], allowUnknownFields: true)->assert(
                $request->getParsedBody(),
            )['title'];
        } catch (AssertException) {
            return new JsonResponse(['error' => 'title is required'], 422, [], self::JSON_FLAGS);
        }

        $result = $this->bus->handle(new UpdateNoteCommand($id, $title));

        if ($result->getStatus() === MessageStatus::Failure) {
            return new JsonResponse(['error' => 'note not found'], 404, [], self::JSON_FLAGS);
        }

        return new JsonResponse($result->getResult(), 200, [], self::JSON_FLAGS);
    }

    private function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $title = Type\shape(['title' => Type\non_empty_string()], allowUnknownFields: true)->assert(
                $request->getParsedBody(),
            )['title'];
        } catch (AssertException) {
            return new JsonResponse(['error' => 'title is required'], 422, [], self::JSON_FLAGS);
        }

        $result = $this->bus->handle(new CreateNoteCommand($title));

        return new JsonResponse($result->getResult(), 201, [], self::JSON_FLAGS);
    }
}
