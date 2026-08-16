# messagebus-test

A Mezzio demo application used to exercise, end-to-end, the
[`webware/message-bus`](https://github.com/webware/message-bus),
[`webware/messagebus-event`](https://github.com/webware/messagebus-event), and
[`php-db/phpdb`](https://github.com/php-db/phpdb) (+ `php-db/phpdb-mysql`) packages together
in a real HTTP application backed by MySQL.

The app is a small **Notes** CRUD demo: commands/queries are dispatched through the message
bus, command handlers/query handlers talk to MySQL via `phpdb`'s `TableGateway`, the web UI
is HTMX-powered (real `POST`/`PATCH`/`DELETE` requests, fragment responses), and the
messagebus-event pipeline pre/post-handle events plus a `NoteCreatedEvent` are logged to
Tracy's `data/log/info.log` by listeners.

## Requirements

- PHP `~8.4.0` or `~8.5.0`, with the `pdo_mysql` extension
- [Composer](https://getcomposer.org/)
- Docker + Docker Compose (for MySQL and phpMyAdmin)

## Installation

```bash
composer install
```

## Starting the database

The MySQL server and phpMyAdmin are provided via Docker Compose (there is no PHP
application container — the app itself runs directly via PHP, see below).

```bash
docker compose up -d
```

- MySQL is exposed on `127.0.0.1:3306` (database/user/password default to
  `messagebus_event_test`; override via the `MYSQL_DB`, `MYSQL_USER`, `MYSQL_PASSWORD`,
  `MYSQL_ROOT_PASSWORD` environment variables — if you change these, also update
  [config/autoload/mysql.local.php](config/autoload/mysql.local.php) to match).
- phpMyAdmin is available at [http://localhost:8082](http://localhost:8082).

Once the database container is healthy, create the `notes` table:

```bash
php bin/init-db
```

`config/autoload/mysql.local.php` is gitignored, so it won't exist after a fresh clone/install
— `bin/init-db` automatically creates it from
[config/autoload/mysql.local.php.dist](config/autoload/mysql.local.php.dist) if it's missing,
using connection settings matching the docker-compose defaults above.

(The integration test suite drops and recreates the `notes` table at the start of each run, so DDL
changes are picked up automatically; rows written by the tests are left in place for inspection via
phpMyAdmin.)

> **Migration note**: `bin/init-db` only creates the table if it does not exist. If your database
> was created before the `body` column was added, drop the table once (`DROP TABLE notes;` via
> phpMyAdmin or a MySQL client) and re-run `php bin/init-db`.

## Running the app

The app is served with PHP's built-in web server:

```bash
composer serve
```

This starts the app at [http://0.0.0.0:8080](http://0.0.0.0:8080).

### Development mode

Development mode controls the `debug` config flag (enables the Tracy debug bar/error pages
and application-level `Tracy\Debugger::log()` calls) and disables config caching. Because
this project uses newer dependencies than `mezzio-tooling` currently supports, the standard
`laminas-development-mode` composer plugin isn't used — instead, [bin/development-mode](bin/development-mode)
re-implements the same `--enable` / `--disable` / `--status` behavior, wired up via Composer
scripts:

```bash
composer development-enable   # enable debug mode, disable config caching
composer development-disable  # disable debug mode
composer development-status   # show current status
```

> **Note:** Some application code (e.g. `App\Listener\NoteCreatedListener`) calls
> `Tracy\Debugger::log()` directly. Tracy is only initialized (`Debugger::enable()`) when
> development mode is enabled, via `Webware\Traccio\Middleware\TracyDebuggerMiddleware`. If
> development mode is disabled, calls that write to the Tracy log will fail. Make sure
> development mode is enabled before testing in a browser.

If you change configuration under `config/autoload/` while a cached config exists, clear it:

```bash
composer clear-config-cache
```

## API

All endpoints are registered in [src/App/src/RouteProvider.php](src/App/src/RouteProvider.php).

| Method  | Path             | Description                     |
|---------|------------------|----------------------------------|
| `GET`   | `/`              | Home page                       |
| `GET`   | `/ping`          | Health check, returns `{"ack": <unix timestamp>}` |
| `GET`   | `/notes`         | Notes list HTML fragment (HTMX)      |
| `POST`  | `/notes`         | Create a note, returns list fragment |
| `PATCH` | `/notes/{id}`    | Update a note, returns list fragment |
| `DELETE`| `/notes/{id}`    | Delete a note, returns list fragment |

### Web UI

The home page ([http://localhost:8080/](http://localhost:8080/)) renders a Pico-styled form for
adding notes plus the notes list. The page is [HTMX](https://htmx.org)-powered, so every
interaction is a partial page update — no full reloads, no `_method` fields, and no custom
middleware:

- The "Add a note" form issues `hx-post` to `/notes`. On success the handler returns the
  updated `#notes-list` fragment, which HTMX swaps in (`hx-swap="outerHTML"`); the form
  resets itself.
- The notes list is loaded on page load via `hx-get` to `/notes` (`hx-trigger="load"`).
- Each note row is an update form issuing `hx-patch` to `/notes/{id}`; its "Delete" button
  issues `hx-delete` to `/notes/{id}`. Both target `#notes-list` with an `outerHTML` swap.
- `App\Middleware\DetectAjaxRequestMiddleware` detects HTMX requests via the `HX-Request`
  header and disables the layout for that request, so the handlers' fragments are returned bare
  for swapping.
- Validation failures (missing title, unknown note id) return the `notes-errors` fragment
  with an `HX-Target: #notes-errors` response header, so HTMX swaps the error banner instead
  of the list.
- A note has a required `title` and an optional `body` (a `<textarea>` in each form). Clicking
  "Update" without changing anything is a no-op update (no columns actually
  change), which relies on the database reporting matched rows rather than changed rows
  for `PDOStatement::rowCount()` — see the `driver_options` comment in
  [config/autoload/mysql.local.php.dist](config/autoload/mysql.local.php.dist). Without
  that setting, a no-op update is misreported as "note not found" even though the note
  exists and matched.

### Examples

The `/notes` endpoints return HTML fragments (they are the HTMX endpoints); there is no
separate JSON API for notes. `/ping` remains JSON.

List notes:

```bash
curl http://localhost:8080/notes
# -> 200 <section id="notes-list"> ... </section>
```

Create a note:

```bash
curl -X POST http://localhost:8080/notes \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "title=my first note&body=optional body"
# -> 200 notes-list fragment with the new note listed
```

Update a note:

```bash
curl -X PATCH http://localhost:8080/notes/1 \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "title=updated title&body=updated body"
# -> 200 notes-list fragment
```

Delete a note:

```bash
curl -X DELETE http://localhost:8080/notes/1
# -> 200 notes-list fragment
```

Error responses:

- Missing/empty `title` on create or update returns `200` with the error fragment and an
  `HX-Target: #notes-errors` header ("title is required").
- Updating or deleting a non-existent note id returns `200` with the error fragment and the
  same header ("note not found").

## Testing

```bash
composer test              # unit tests
composer test-integration  # integration tests (requires the database to be running)
composer test-all          # both of the above
composer test-coverage     # unit tests with code coverage (clover.xml)
```

## Configuration

- [config/autoload/mysql.local.php](config/autoload/mysql.local.php) — database adapter
  connection settings. Uses `PhpDb\Mysql\Pdo\Driver` (PDO) rather than the mysqli driver, to
  work around a confirmed upstream bug in `php-db/phpdb-mysql`'s mysqli `Statement` (missing
  `__clone()`, causing bound parameters to leak across queries on a shared adapter/table
  gateway) — see [docs/failure-notes.md](docs/failure-notes.md) for full details.
- [config/autoload/tracy.global.php](config/autoload/tracy.global.php) — Tracy debugger
  configuration (log directory, theme, hidden keys, etc).
- [config/autoload/mezzio.global.php](config/autoload/mezzio.global.php) — `debug` flag
  default (`false`) and config-cache toggle, plus Mezzio error-handler templates.
- [config/development.config.php.dist](config/development.config.php.dist) — template copied
  to `config/development.config.php` by `composer development-enable` to turn on debug mode
  and disable config caching.

## Architecture

The application lives entirely in the `App` module ([src/App](src/App)):

- `Command` / `CommandHandler` — `CreateNoteCommand` / `UpdateNoteCommand` / `DeleteNoteCommand`
  and their handlers, dispatched through `Webware\MessageBus\MessageBusInterface`.
- `Query` / `QueryHandler` — `ListNotesQuery` and its handler.
- `Handler` — PSR-15 request handlers (`HomePageHandler`, `PingHandler`, `NoteCommandHandler`,
  `NoteListHandler`) that translate HTTP requests into bus commands/queries and render HTMX
  fragments.
- `Middleware` — `DetectAjaxRequestMiddleware` (piped early in `config/pipeline.php`) disables
  the view layout for requests carrying the HTMX `HX-Request` header, so handlers return bare
  fragments.
- `Container` — factories wiring the above into the Laminas ServiceManager container.
- `Event` / `Listener` — `NoteCreatedEvent` (dispatched from `CreateNoteHandler`) plus the
  command/query pipeline pre/post-handle events wired by `webware/messagebus-event`.
  `NoteCreatedListener` and `MessageLifecycleListener` log them via Tracy to
  `data/log/info.log`.
- `Strategy` — the bus is wired to `Webware\MessageBus\Strategy\ClassnameStrategy`, so each
  handler declares a method named after the message's short class name
  (`createNoteCommand`, `listNotesQuery`, etc).
- `Ddl` — `NotesTable::createIfNotExists()`, used by `bin/init-db` and the integration test
  suite to provision the `notes` table.

## Documentation

- [docs/failure-notes.md](docs/failure-notes.md) — detailed write-ups of upstream bugs found
  in dependent packages while building this app, along with workarounds applied here.
- [docs/project-plan.md](docs/project-plan.md) — original project plan.

## License

BSD 3-Clause. See [LICENSE](LICENSE).
