# Plan: Add Bodies to Notes

Add an optional `body` (free text) to each note. Title stays required; body is optional, no rich
text, no per-note detail page. UI changes kept to two templates (one `<textarea>` each). All work
tested and run through `mago fmt` / `mago lint` / `mago analyze` per project convention.

## 1. Database

`src/App/src/Ddl/NotesTable.php`:

- Add after the title column:

  ```php
  $table->addColumn(new Column\Text('body', null, true));
  ```

  `PhpDb\Sql\Ddl\Column\Text` already exists in php-db. The `null` length keeps the type as plain
  `TEXT` and `true` marks the column nullable (an optional body); php-db length columns default to
  `NOT NULL`, hence the explicit `true`.

**Migration for existing databases**: `NotesTable::createIfNotExists()` only creates, it never
alters. The dev/integration databases already have a `notes` table without `body`. One-time fix for
each:

- dev database (docker MySQL): run `DROP TABLE notes;` once, then `php bin/init-db` (recreates with
  the new column). Documented in the plan; no ALTER logic added to the app.
- integration bootstrap: update `test/integration/Extension/IntegrationTestStartedListener.php` to
  `DROP TABLE IF EXISTS notes` before `NotesTable::createIfNotExists()` so the integration suite is
  deterministic. Update the README sentence that says the integration suite "deliberately leaves the
  data in place" — data still persists, but the table is now recreated per suite run.

## 2. Commands

- `src/App/src/Command/CreateNoteCommand.php`: add promoted `public ?string $body = null`.
- `src/App/src/Command/UpdateNoteCommand.php`: add promoted `public ?string $body = null`.
- `DeleteNoteCommand` unchanged.

## 3. Message handlers

- `CreateNoteHandler::createNoteCommand()`: `$insert->values(['title' => $command->title, 'body' => $command->body])`.
  Result array shape unchanged (`['id', 'title']`) — the fragment re-queries the list, so the body is
  not needed in the result.
- `UpdateNoteHandler::updateNoteCommand()`: `$update->set(['title' => $command->title, 'body' => $command->body])`.
- `ListNotesHandler` unchanged (`SELECT *` already returns the new column).

## 4. HTTP validation (`App\Handler\NoteCommandHandler`)

Keep the single Psl shape approach:

- `Type\shape(['title' => Type\non_empty_string(), 'body' => Type\optional(Type\string())], allowUnknownFields: true)`
- Normalize: empty-string body becomes `null` before constructing the command
  (`$body !== '' ? $body : null`). Empty body is not an error — only `title` is required, so the
  existing `title is required` error path stays exactly as is. `Type\optional(Type\string())` accepts
  absent or any string, so whitespace-only bodies pass through as-is (no trimming; keep minimal).

## 5. UI (absolute minimum)

Two edits, both a plain `<textarea name="body">`:

- `src/App/templates/app/home-page.phtml` — inside the create form's `<fieldset>`, above the button:
  `<textarea name="body" rows="3" placeholder="Body (optional)"></textarea>`.
- `src/App/templates/app/notes-list.phtml` — inside each update form, below the title input:
  `<textarea name="body" rows="3"><?= $this->escapeHtml($note['body'] ?? '') ?></textarea>`.

No layout, CSS, or HTMX attribute changes; the existing swap targets already handle the fragment.

## 6. Tests

- `test/AppTest/CommandHandler/CreateNoteHandlerTest.php`: extend to pass `body`, assert
  `insertWith` called once and result unchanged; add a case with `body: null` (default).
- `test/AppTest/CommandHandler/UpdateNoteHandlerTest.php`: pass `body` in the command, assertions
  unchanged.
- `test/AppTest/Handler/NoteCommandHandlerTest.php`:
  - existing cases stay green (body key absent → optional type passes),
  - add: POST with `body` produces a `CreateNoteCommand` whose `body` matches (assert via the bus
    callback), PATCH with `body` likewise, empty-string `body` normalizes to `null`.
- `test/integration/NotesEndToEndTest.php`: create a note with a body, assert the body round-trips
  through the list query.
- No new factory tests needed (no new constructor dependencies).

## 7. Docs

- `README.md`: add `body` to the create/update curl examples; note the one-time `DROP TABLE notes`
  for existing dev databases; adjust the integration-suite data note.
- `docs/project-plan.md`: Domain table gains a `body TEXT` row; the "note only has one field" Web UI
  wording updated to title + optional body.
- This file (`docs/note-bodies.md`) records the change.

## 8. mago / quality checks

- After implementation: `mago fmt` on changed files, then `mago lint`, then `mago analyze` — fix
  findings (project convention; no suppressions without confirmation).
- Validation stays on `Psl\Type` (no `assert()`, no type casts, per repo conventions).
- `composer test` + `composer test-integration` green; `composer clear-config-cache` after config
  changes (none expected here).

## 9. Verification

1. `docker compose up -d`, drop the old table once, `php bin/init-db`.
2. `composer test`, `composer test-integration`.
3. Smoke via `composer serve`: create note with body, update body, delete; body survives a page
   reload (`hx-trigger="load"` list) and appears in `curl -H "HX-Request: true" /notes`.

## Out of scope

- Rich text / markdown rendering, per-note pages, body search, pagination, required bodies,
  length limits beyond `TEXT`, and any CSS changes.
