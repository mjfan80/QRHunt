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

When useful, use this environment to perform local verification.

Do not modify WordPress core, the database, or other plugins unless explicitly requested.