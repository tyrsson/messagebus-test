<?php

declare(strict_types=1);

namespace App\Query;

use Webware\MessageBus\Query\QueryInterface;

final readonly class ListNotesQuery implements QueryInterface
{
    public function __construct(
        public int $limit = 50,
    ) {}
}
