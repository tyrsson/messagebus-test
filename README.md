# messagebus-test

A Mezzio demo application used to exercise, end-to-end, the
[`webware/message-bus`](https://github.com/webware/message-bus),
[`webware/messagebus-event`](https://github.com/webware/messagebus-event), and
[`php-db/phpdb`](https://github.com/php-db/phpdb) (+ `php-db/phpdb-mysql`) packages together
in a real HTTP application backed by MySQL.

The app is a small **Notes** CRUD API: commands/queries are dispatched through the message
bus, command handlers/query handlers talk to MySQL via `phpdb`'s `TableGateway`, and a
`NoteCreatedEvent` is fired (via `messagebus-event`) and logged by a listener whenever a note
is created.

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

(The integration test suite also creates this table automatically if it doesn't exist, and
deliberately leaves the data in place after running so it can be inspected via phpMyAdmin.)

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
| `GET`   | `/notes`         | List all notes                  |
| `POST`  | `/notes`         | Create a note                    |
| `PATCH` | `/notes/{id}`    | Update a note's title            |
| `DELETE`| `/notes/{id}`    | Delete a note                    |

### Web UI

The home page ([http://localhost:8080/](http://localhost:8080/)) renders a basic Pico-styled
form for creating and updating notes, so you don't have to use curl to try things out:

- An "Add a note" form `POST`s to `/notes`.
- Each listed note has its own inline update form and a "Delete" button. Native HTML forms
  can't submit `PATCH`/`DELETE`, so each submits a `POST` with a hidden `_method` field
  (`PATCH` or `DELETE`); `App\Middleware\MethodOverrideMiddleware` rewrites the request
  method before routing (the standard `_method` override convention). The "Delete" button
  uses the HTML `form` attribute to submit a separate hidden delete form for that note,
  since a single `<form>` can only carry one action/method.
- After a successful submission, `App\Middleware\NoteFormRedirectMiddleware` converts the
  handler's JSON response into a redirect back to `/` (Post/Redirect/Get), so the browser
  never shows raw JSON. On validation errors it redirects to `/?error=...` instead, which the
  home page displays as a banner. This only applies to `application/x-www-form-urlencoded`
  submissions — requests with an explicit `application/json` Content-Type (curl, tests, API
  clients) get the JSON responses shown below, unchanged.

### Examples

List notes:

```bash
curl http://localhost:8080/notes
```

Create a note:

```bash
curl -X POST http://localhost:8080/notes \
  -H "Content-Type: application/json" \
  -d '{"title":"my first note"}'
# -> 201 {"id":"1","title":"my first note"}
```

Update a note:

```bash
curl -X PATCH http://localhost:8080/notes/1 \
  -H "Content-Type: application/json" \
  -d '{"title":"updated title"}'
# -> 200 {"id":"1","title":"updated title"}
```

Delete a note:

```bash
curl -X DELETE http://localhost:8080/notes/1 \
  -H "Content-Type: application/json"
# -> 200 {"id":"1"}
```

Error responses:

- Missing/empty `title` on create or update returns `422 {"error":"title is required"}`.
- Updating or deleting a non-existent note id returns `404 {"error":"note not found"}`.

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
  `NoteQueryHandler`) that translate HTTP requests into bus commands/queries.
- `Middleware` — `MethodOverrideMiddleware` (rewrites POST to PATCH/DELETE via a `_method`
  form field) and `NoteFormRedirectMiddleware` (Post/Redirect/Get wrapper for HTML form
  submissions to the `/notes` routes), see [Web UI](#web-ui) above.
- `Container` — factories wiring the above into the Laminas ServiceManager container.
- `Event` / `Listener` — `NoteCreatedEvent`, dispatched via `webware/messagebus-event` and
  handled by `NoteCreatedListener`, which logs the event via Tracy.
- `Ddl` — `NotesTable::createIfNotExists()`, used by `bin/init-db` and the integration test
  suite to provision the `notes` table.

## Documentation

- [docs/failure-notes.md](docs/failure-notes.md) — detailed write-ups of upstream bugs found
  in dependent packages while building this app, along with workarounds applied here.
- [docs/project-plan.md](docs/project-plan.md) — original project plan.

## License

BSD 3-Clause. See [LICENSE](LICENSE).
