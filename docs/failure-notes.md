# General

Documentation/source discrepancies discovered while building the MessageBus + MessageBus-Event +
php-db demo described in [project-plan.md](project-plan.md). Recorded here so they can be reported
upstream and so the workarounds used in this codebase are understood.

## 1. `webware/messagebus-event`: documented `EventAware` pattern does not compile

`vendor/webware/messagebus-event/docs/v1/usage-examples.md`, section "Dispatching a custom event
alongside the result", shows:

```php
final class CreateUserResult extends CommandResult implements EventAwareInterface
{
    use EventAwareTrait;
}
```

But `Webware\MessageBus\Command\CommandResult` (`vendor/webware/message-bus/src/Command/CommandResult.php`)
is declared `final readonly class CommandResult implements CommandResultInterface`. A class cannot
extend a `final` class — this example is a fatal PHP error (`Class ... cannot extend final class`).

**Workaround used in this repo**: `App\Command\NoteCommandResult` implements `CommandResultInterface`
and `EventAwareInterface` directly (via `EventAwareTrait`), reimplementing the constructor and the three
getters (`getCommand()`, `getResult()`, `getStatus()`) itself instead of extending `CommandResult`.

**Superseded with the 2.0.0-beta.1 bump**: the v2 docs now instruct handlers to always return the
library `CommandResult` / `QueryResult` value objects and to dispatch custom domain events directly
from the handler via `Psr\EventDispatcher\EventDispatcherInterface` instead of attaching them to the
result. `App\Command\NoteCommandResult` has been removed; `App\CommandHandler\CreateNoteHandler` now
dispatches `NoteCreatedEvent` directly before returning a `CommandResult`.

## 2. `webware/message-bus`: inconsistent `@api`/`@internal` tagging between Command and Query results

`Command\CommandResultInterface` is tagged `@api`, while `Query\QueryResultInterface` is tagged
`@internal`, despite the docs describing them as exact mirrors of one another with no distinction
called out. Similarly, `Command\CommandResult` carries no `@api`/`@internal` tag while `Query\QueryResult`
is explicitly `@api`.

## 3. `webware/message-bus`: dead/unused `Exception\CommandException`

The docs describe `Exception\CommandException` as "reserved for signaling that no handler was found for
a given command class", but the actual code path for that case
(`Webware\MessageBus\MessageHandlerResolver::resolve()`) throws `InvalidConfigurationException` or
`ServiceNotFoundException` instead. `CommandException` does not appear to be thrown anywhere in the
current source.

## 4. `webware/messagebus-event`: getting-started.md omits a required peer ConfigProvider

The getting-started wiring example only lists `Webware\MessageBus\ConfigProvider` and
`Webware\MessageBus\Event\ConfigProvider`. It omits that `Phly\EventDispatcher\ConfigProvider` (from the
peer dependency `phly/phly-event-dispatcher`) must **also** be registered in the `ConfigAggregator` list.
`ListenerProviderAggregateFactory` depends on services (`PrioritizedListenerProvider`,
`AttachableListenerProvider`) that are only registered by phly's own `ConfigProvider`. Without it,
container resolution fails at runtime. `config/config.php` in this project registers all three.

## 5. `php-db/phpdb`: mezzio integration doc uses a stale top-level config key

`vendor/php-db/phpdb/docs/book/application-integration/usage-in-a-mezzio-application.md` documents
configuring the adapter under a top-level `'db'` key. The actual factory
(`PhpDb\Container\AdapterInterfaceFactory`) reads `$config[PhpDb\Adapter\AdapterInterface::class]`
(falling back to `$config[Adapter::class]`), not `$config['db']`. The same applies to named adapters:
the real lookup (`PhpDb\Container\AbstractAdapterInterfaceFactory::getConfig()`) is
`$config[AdapterInterface::class][PhpDb\ConfigProvider::NAMED_ADAPTER_KEY]`
(`NAMED_ADAPTER_KEY = 'adapters'`), not `$config['db']['adapters']`.

Fixed correctly in this project's [config/autoload/mysql.local.php](../config/autoload/mysql.local.php),
which is keyed by `AdapterInterface::class` per the actual source, with an inline note pointing back
to this discrepancy.

## 6. `php-db/phpdb-mysql`: `Statement` is missing `__clone()`, so `$parameterContainer` (and bound state) leaks across every query run through the same `Driver`

### Symptom

`ArgumentCountError: The number of variables must match the number of parameters in the prepared statement`,
thrown from `bind_param()` inside `PhpDb\Mysql\Statement::bindParametersFromContainer()`
(`vendor/php-db/phpdb-mysql/src/Statement.php:225`). It surfaces intermittently and only after **more than
one query** has been executed through the same `TableGateway`/`Adapter`/`Driver` — a single, isolated query
against a fresh adapter never triggers it.

Confirmed via temporary instrumentation (dumping the SQL string + bound parameter keys immediately before
`bind_param()` is called) across one INSERT, one UPDATE, and one SELECT executed in sequence in the same
process:

```
INSERT INTO `notes` (`title`) VALUES (?)                              paramCount=1  keys=title
UPDATE `notes` SET `title` = ? WHERE `id` = ?                         paramCount=2  keys=title,where1
SELECT `notes`.* FROM `notes` ORDER BY `created_at` DESC LIMIT ?      paramCount=3  keys=title,where1,limit  <-- BOOM (only 1 `?` in this SQL)
```

Every query's bound parameters keep **accumulating** — `title` and `where1` from the earlier
INSERT/UPDATE are still present by the time the unrelated SELECT (with only a single `LIMIT ?`
placeholder) runs, giving it 3 bound values for 1 placeholder.

### Root cause

`PhpDb\Mysql\Driver::createStatement()` (`vendor/php-db/phpdb-mysql/src/Driver.php:111`) creates every
`Statement` by cloning a single, `readonly`, DI-injected prototype:

```php
$statement = clone $this->statementPrototype;
```

`PhpDb\Mysql\Statement` (`vendor/php-db/phpdb-mysql/src/Statement.php`) does **not** define a `__clone()`
method. PHP's default clone is shallow, so the cloned `Statement`'s `protected ParameterContainer
$parameterContainer` property still points at the **exact same `ParameterContainer` object instance** as
the prototype — and therefore the same instance is shared by every `Statement` ever cloned from that
`Driver` for the lifetime of the process/connection.

This also defeats the guard in `PhpDb\Sql\AbstractPreparableSql::prepareStatement()`
(`vendor/php-db/phpdb/src/Sql/AbstractPreparableSql.php`):

```php
$parameterContainer = $statementContainer->getParameterContainer();
if (! $parameterContainer instanceof ParameterContainer) {
    $parameterContainer = new ParameterContainer();
    $statementContainer->setParameterContainer($parameterContainer);
}
```

Because the shared container is never `null`, this branch never runs, so a fresh container is never
substituted — every subsequent query keeps writing into (and accumulating on top of) the same one.

### Confirmation the fix pattern is already established elsewhere in phpdb

`PhpDb\Adapter\Driver\Pdo\Statement` (`vendor/php-db/phpdb/src/Adapter/Driver/Pdo/Statement.php:244-250`),
which is cloned from a prototype the same way, **does** implement this correctly:

```php
/** Perform a deep clone */
public function __clone(): void
{
    $this->isPrepared         = false;
    $this->parametersBound    = false;
    $this->resource           = null;
    $this->parameterContainer = clone $this->parameterContainer;
}
```

`php-db/phpdb-mysql`'s `Statement` never got the equivalent `__clone()` method.

**Fix required upstream** (`php-db/phpdb-mysql`): add to `PhpDb\Mysql\Statement`:

```php
public function __clone(): void
{
    $this->isPrepared         = false;
    $this->parameterContainer = clone $this->parameterContainer;
}
```

(`$resource` (a `mysqli_stmt`) and `$sql` should presumably also be reset the same way the PDO variant
resets its equivalents, but at minimum `$parameterContainer` must be deep-cloned to fix this bug.)

No workaround is possible from consumer code — `$parameterContainer` is `protected` and only ever
touched inside the vendor `Statement` class itself; the bug is only avoidable in application code by
never running more than one query through the same `Adapter`/`Driver`/`TableGateway`, which isn't a
realistic constraint.

**UPDATE**: still present as of `php-db/phpdb-mysql` `0.4.x-dev` — confirmed by re-reading the installed
source after bumping this project's dependency; `PhpDb\Mysql\Statement` still has no `__clone()` method.

**UPDATE 2**: still present on the `0.5.x` branch (checked 2026-08-16). This project now tracks
`php-db/phpdb-mysql` `0.5.x-dev` and keeps the PDO-driver workaround below.

**Workaround currently in effect in this repo**: `config/autoload/mysql.local.php` switched
`AdapterInterface::class`'s `'driver'` from `PhpDb\Mysql\Driver` (mysqli) to `PhpDb\Mysql\Pdo\Driver`
(PDO), sidestepping the mysqli `Statement`'s missing `__clone()` entirely since the PDO `Statement`
(`vendor/php-db/phpdb/src/Adapter/Driver/Pdo/Statement.php`) already deep-clones its `$parameterContainer`
correctly (see above). Config keys (`hostname`, `username`, `password`, `database`, `port`, `charset`,
`driver_options`) are identical between the mysqli and PDO connection classes, so this was a one-line
change (`use PhpDb\Mysql\Driver;` → `use PhpDb\Mysql\Pdo\Driver;`).

Note this workaround was blocked by a separate bug in `0.3.0`: `PhpDb\Mysql\Pdo\Driver` (and its parent
`PhpDb\Adapter\Driver\Pdo\AbstractPdo`) had no constructor at all, so `PdoDriverInterfaceFactory`'s
`new Driver($connection, $statementPrototype, $resultPrototype, $features)` silently discarded all four
constructor arguments (PHP does not error on extra arguments to a class with no declared `__construct`),
leaving `$connection` uninitialized and throwing `Error: Typed property
PhpDb\Adapter\Driver\Pdo\AbstractPdo::$connection must not be accessed before initialization` on first
use. **This is now fixed as of `0.4.x-dev`** — `PhpDb\Mysql\Pdo\Driver` now declares a proper
`__construct()` that assigns `$connection`, `$statementPrototype`, and `$resultPrototype`. Confirmed via a
full test suite run (25 tests, 0 errors) after the dependency bump.

## 7. PHPUnit 12: mocks with no configured expectations now error, not just notice

Since a recent PHPUnit 12.x release, calling `createMock()` and only configuring a return value via
`->method()->willReturn()` (i.e. never calling `->expects()`) triggers:

```
No expectations were configured for the mock object for X.
Consider refactoring your test code to use a test stub instead.
```

This is a `PHPUnit Notice`, but it fails the build the same as an error/warning would under this
project's `phpunit.xml.dist` settings.

**Convention adopted in this repo**: for any test double that never has `->expects()` called on it (i.e.
it's only used to control return values, not to assert it was called), use `$this->createStub()` instead
of `$this->createMock()`. Reserve `createMock()` exclusively for doubles that have at least one
`->expects()` set. Do **not** use the `#[AllowMockObjectsWithoutExpectations]` attribute to silence this —
it papers over doubles that should be stubs.

One related gotcha: `->with(...)` has no effect on a stub (only mocks track/verify call arguments) and
using it on a stub is itself deprecated as of PHPUnit 12 (removed entirely in 13):
```
Using with*() on a test stub has no effect and is deprecated.
```
When converting a `createMock()` to `createStub()`, also drop any `->with(...)` calls chained onto it.
