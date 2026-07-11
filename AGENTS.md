# AGENTS.md

## Project

QRHunt is a WordPress plugin.

The official project specification is contained in the `docs/` directory.

The documents in `docs/` are the authoritative source for the project.

If implementation and documentation differ, assume the documentation is correct.

---

## Development workflow

Before implementing any feature:

1. Read the relevant documentation in `docs/`.
2. Verify architectural consistency.
3. Implement only the requested scope.

Do not skip these steps.

---

## Development rules

Always:

- work in small, reviewable commits;
- keep every commit installable and working;
- keep the repository in a compilable state;
- implement only the requested functionality;
- follow the existing architecture;
- prefer simplicity and maintainability;
- avoid unnecessary abstractions;
- avoid code and data duplication.

Never:

- change the architecture without explicit approval;
- introduce features not required by the documentation;
- perform large unrelated refactorings;
- modify unrelated files.

---

## WordPress

Use only official WordPress APIs.

Follow:

- WordPress Coding Standards;
- Plugin Check requirements;
- WordPress best practices.

Do not introduce frameworks, ORMs or unnecessary dependencies.

---

## Architecture

Respect the architecture described in `docs/ARCHITECTURE.md`.

Use the Repository and Service layers as defined by the project.

Keep responsibilities separated.

Prefer dependency injection where the architecture requires it.

---

## Code style

Write readable, maintainable code.

Keep classes focused on a single responsibility.

Prefer explicit code over clever code.

Avoid premature optimization.

---

## When uncertain

Do not guess.

Stop and ask for clarification before making architectural or functional decisions.

---

## Commits

Each task should normally produce a single logical commit.

Do not create additional commits unless explicitly requested.

---

## Local development environment

Local WordPress installation:

D:\Local-Sites\app\public

The plugin is available through a symbolic link in:

wp-content/plugins/qrhunt

For local testing, always use the Local Site Shell.

The Local Site Shell automatically provides:

- PHP
- php.ini
- MySQL
- Composer
- WP-CLI

The WordPress root directory is:

D:\Local-Sites\app\public

Use this environment whenever local verification is required.

Do not modify WordPress core, the database, installed plugins, or the active theme unless explicitly requested.

Whenever possible, verify changes using:

- PHP lint
- Composer validation
- WP-CLI
- Plugin Check

Known Plugin Check findings that may be ignored during development:

- .gitkeep placeholder files
- AGENTS.md
- .gitignore

These files are part of the development repository and are not intended for the final distribution package.

## Working methodology

Do not make architectural decisions.

If the documentation is incomplete, stop and ask for clarification before implementing.

Implement only the requested scope.

Do not start the next roadmap task automatically.

After completing the requested implementation:

1. run the available automated checks;
2. show the final diff;
3. wait for approval;
4. create the commit only after explicit approval.

Do not start a new task after creating a commit.