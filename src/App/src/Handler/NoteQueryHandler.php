<?php

declare(strict_types=1);

namespace App\Handler;

use App\Query\ListNotesQuery;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\MessageBus\MessageBusInterface;

final readonly class NoteQueryHandler implements RequestHandlerInterface
{
    public function __construct(
        private MessageBusInterface $bus,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $result = $this->bus->handle(new ListNotesQuery());

        return new JsonResponse(
            ['notes' => $result->getResult()],
            200,
            [],
            JsonResponse::DEFAULT_JSON_FLAGS | JSON_PRETTY_PRINT,
        );
    }
}
