<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud Gmbh
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

## Overview

Share Review (`sharereview`) is a Nextcloud app for auditing and managing file shares. It enables data loss prevention by letting users review, confirm, and delete shares across types: files, Talk, Deck, Circles, Federation, and email.

- App ID: `sharereview` | Namespace: `OCA\ShareReview`
- No frontend build step — vanilla JS + PHP templates, no npm/webpack

## Release Process

Releases are handled via GitHub Actions (`.github/workflows/appstore-release.yml`), triggered on GitHub release publication. The workflow signs the app, creates a tarball (excluding tests, screenshots, `.git`), and uploads to GitHub Releases and the Nextcloud App Store.

## Architecture

### Backend (PHP)

Three-layer structure: Controllers → Services → Helpers/Mapper.

- **Controllers** (`lib/Controller/`): Thin — parse requests, return JSON/HTML. Three controllers:
    - `PageController`: renders the main page, injects initial state via `IInitialState`
    - `OutputController`: CRUD for share data (read, delete, confirm reviewed)
    - `ReportController`: CSV/PDF exports and report schedule settings

- **Services** (`lib/Service/`):
    - `ShareService`: core logic — queries all share types, formats display names, handles the "last reviewed" filter. Most business logic lives here.
    - `ReportService`: formats and writes CSV/PDF reports to the filesystem

- **Helpers** (`lib/Helper/`): One helper per external integration (User, Group, Talk, Deck, Circle). Each caches display name lookups and degrades gracefully if the target app is absent.

- **Mapper** (`lib/Db/ShareMapper.php`): Direct QueryBuilder queries on `oc_share`. Excludes share_type 2. Returns raw DB rows; `ShareService` handles normalization.

- **BackgroundJob** (`lib/BackgroundJob/GenerateReportJob.php`): `TimedJob` for scheduled report generation (daily/weekly/monthly), configured via `IAppConfig`.

- **Event** (`lib/Sources/SourceEvent.php`): Event dispatcher hook allowing third-party apps to inject custom share sources into the share list.

### Frontend (JS)

Four vanilla JS files — no build tooling:

- `js/app.js`: UI event handlers, navigation, all backend API calls
- `js/visualization.js`: DataTables initialization and column definitions
- `js/userGuidance.js`: Help tooltips and guided tour
- `js/3rdParty/datatables.min.js`: bundled DataTables library

Templates are PHP files in `templates/` using Nextcloud's `Util::imagePath()` and `p()`/`l()` helpers.

### API Routes

Defined in `appinfo/routes.php`:

| Method | Path | Action |
|--------|------|--------|
| GET | `/` | `PageController#index` |
| GET | `/data` | `OutputController#read` |
| GET | `/data/new` | `OutputController#readNew` |
| DELETE | `/delete/{shareId}` | `OutputController#delete` |
| POST | `/confirm` | `OutputController#confirm` |
| POST | `/confirmReset` | `OutputController#confirmReset` |
| POST | `/showTalk` | `OutputController#showTalk` |
| POST | `/report/export` | `ReportController#export` |
| POST | `/report/settings` | `ReportController#saveSettings` |

### State & Config

- Per-user state (last review timestamp, Talk visibility): `IConfig` (`setUserValue`/`getUserValue`)
- Per-app config (report folder, schedule, format): `IAppConfig`
- Page-load data injected via `IInitialState` in `PageController`

### Access Control

`ShareService::isSecured()` checks that the current user belongs to the configured app group before any data is returned. The app enforces group-based restriction rather than per-share ownership.

### Nextcloud Integration Notes

- Shares are queried from `oc_share` via `OCP\DB\QueryBuilder`; deleted via `OCP\Share\IManager::deleteShare()`
- File names resolved via `IRootFolder` by file source ID
- Optional apps (Talk, Deck, Circles) are detected at runtime; missing ones are silently skipped
- Localization uses `IL10N`; translation files live in `l10n/` (30+ languages, managed via Transifex)

## Commits

- All commits must be signed off (`git commit -s`) per the Developer Certificate of Origin (DCO). All PRs target `main`.

- Commit messages must follow the [Conventional Commits v1.0.0 specification](https://www.conventionalcommits.org/en/v1.0.0/#specification) — e.g. `feat(report): add PDF scheduled export`, `fix(share): handle deleted file owner gracefully`.

## Pull Requests

- Include a short summary of what changed. When the agent creates a PR, include a description summarizing the changes and why they were made. If a GitHub issue exists, reference it (e.g., "Closes #123").

## Code Style

- Do not use decorative section-divider comments of any kind (e.g. `// ── Title ───`, `// ------`, `// ======`).
- Every new file must end with exactly one empty trailing line (no more, no less).
- After writing or modifying any PHP file, run `php -l <file>` to verify there are no syntax errors.

### Clean Code

- **Single responsibility** — each class and method does one thing. Split large methods if they handle multiple concerns.
- **Meaningful names** — variables, parameters, and methods must describe their purpose. Avoid abbreviations and generic names like `$data`, `$arr`, or `$tmp`.
- **No dead code** — do not leave commented-out code, unused variables, or unreachable branches.
- **Early returns** — prefer guard clauses over deeply nested `if/else` trees.
- **Avoid double negatives** — name booleans positively (`isEnabled`, `hasShares`) rather than negatively (`isNotDisabled`).

## Architecture Patterns

### Boolean getters on Nextcloud DB entities

Do not implement an explicit `isXxx(): bool` method on a class that extends `Entity`. The base class handles `isXxx` calls via `__call` magic for any `protected bool $xxx` property. Instead, declare the method in the class-level `@method` docblock so that static analysis and IDE completion still work:

```php
 * @method isArchived(): bool
```

### Database queries inside loops

Never build an `IQueryBuilder` query inside a loop. Construct the query once before the loop using `$qb->createParameter('name')` as a placeholder. Inside the loop, call `$qb->setParameter('name', $value, IQueryBuilder::PARAM_*)` to bind the new value per iteration.