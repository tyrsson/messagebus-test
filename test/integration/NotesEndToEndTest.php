<?php

declare(strict_types=1);

namespace IntegrationTest;

use App\Command\CreateNoteCommand;
use App\Command\DeleteNoteCommand;
use App\Command\UpdateNoteCommand;
use App\Query\ListNotesQuery;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResultInterface;

use function array_column;
use function dirname;

/**
 * End to end test exercising the real MessageBus + messagebus-event + php-db/phpdb-mysql
 * stack against the docker-compose mysql instance. Requires `docker compose up -d`.
 *
 * The notes table itself is created (if missing) once per suite run by
 * IntegrationTest\Extension\IntegrationTestStartedListener, not here.
 */
#[CoversNothing]
final class NotesEndToEndTest extends TestCase
{
    private static ContainerInterface $container;

    public static function setUpBeforeClass(): void
    {
        self::$container = require dirname(__DIR__, 2) . '/config/container.php';
    }

    public function testCreateUpdateAndListNote(): void
    {
        $bus = self::$container->get(MessageBusInterface::class);

        $createResult = $bus->handle(new CreateNoteCommand('integration test note', 'integration body'));
        self::assertInstanceOf(CommandResultInterface::class, $createResult);
        self::assertSame(MessageStatus::Success, $createResult->getStatus());

        $created = $createResult->getResult();
        self::assertIsArray($created);
        $id = $created['id'];

        $updateResult = $bus->handle(
            new UpdateNoteCommand(
                (string) $id,
                'integration test note (updated)',
                'integration body (updated)',
            ),
        );
        self::assertSame(MessageStatus::Success, $updateResult->getStatus());

        $listResult = $bus->handle(new ListNotesQuery());
        self::assertInstanceOf(QueryResultInterface::class, $listResult);

        $notes = $listResult->getResult();
        self::assertIsArray($notes);

        $updatedTitles = array_column($notes, 'title');
        self::assertContains('integration test note (updated)', $updatedTitles);
        self::assertContains('integration body (updated)', array_column($notes, 'body'));
    }

    public function testDeleteOfMissingNoteFails(): void
    {
        $bus = self::$container->get(MessageBusInterface::class);

        $result = $bus->handle(new DeleteNoteCommand('999999999'));

        self::assertSame(MessageStatus::Failure, $result->getStatus());
    }

    public function testDeleteRemovesNote(): void
    {
        $bus = self::$container->get(MessageBusInterface::class);

        $createResult = $bus->handle(new CreateNoteCommand('note to delete'));
        $created      = $createResult->getResult();
        self::assertIsArray($created);
        $id = $created['id'];

        $deleteResult = $bus->handle(new DeleteNoteCommand((string) $id));
        self::assertSame(MessageStatus::Success, $deleteResult->getStatus());

        $listResult = $bus->handle(new ListNotesQuery());
        $notes      = $listResult->getResult();
        self::assertIsArray($notes);

        self::assertNotContains('note to delete', array_column($notes, 'title'));
    }

    public function testUpdateOfMissingNoteFails(): void
    {
        $bus = self::$container->get(MessageBusInterface::class);

        $result = $bus->handle(new UpdateNoteCommand('999999999', 'no such note'));

        self::assertSame(MessageStatus::Failure, $result->getStatus());
    }
}
