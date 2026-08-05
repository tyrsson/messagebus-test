<?php

declare(strict_types=1);

namespace App\Event;

use Webware\MessageBus\Event\Event;

final class NoteCreatedEvent extends Event
{
    public function __construct(int|string $id, string $title)
    {
        parent::__construct(
            name: 'note.created',
            params: ['id' => $id, 'title' => $title],
        );
    }
}
