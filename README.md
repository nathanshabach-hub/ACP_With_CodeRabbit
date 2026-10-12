# CakePHP Application Skeleton

[![Build Status](https://img.shields.io/travis/cakephp/app/master.svg?style=flat-square)](https://travis-ci.org/cakephp/app)
[![License](https://img.shields.io/packagist/l/cakephp/app.svg?style=flat-square)](https://packagist.org/packages/cakephp/app)

A legacy convention portal currently being migrated to CakePHP 5 for PHP 8.5
deployment.

The framework source code can be found here: [cakephp/cakephp](https://github.com/cakephp/cakephp).

## Runtime targets

- Current local runtime: PHP 8.5 / CakePHP 5.4
- Migration target: PHP 8.5 / CakePHP 5.x

## Installation

1. Download [Composer](http://getcomposer.org/doc/00-intro.md) or update `composer self-update`.
2. Run `php composer.phar create-project --prefer-dist cakephp/app [app_name]`.

If Composer is installed globally, run
```bash
composer create-project --prefer-dist cakephp/app [app_name]
```

You should now be able to visit the path to where you installed the app and see
the setup traffic lights.

## Configuration

Read and edit `config/app.php` and setup the 'Datasources' and any other
configuration relevant for your application.

Judge registration normally follows the school registration closing date.
`JudgesRegistration.closingDates` in `config/app.php` extends judge registration
for specific convention-season slugs through the configured date (inclusive,
Australia/Brisbane time). Indonesia Student Convention 2026 remains available
through 9 October 2026. This does not reopen school registration or submissions.

The admin season-events listing now resolves from
`templates/element/Admin/Conventions/events.php` (lowercase `element`).
The uppercase `Element` copy forwards to this file for compatibility.

## Workspace organization

- Active application/runtime files remain in the project root (`src/`,
  `templates/`, `config/`, `plugins/`, `webroot/`, `vendors/`, `tmp/`,
  `logs/`).
- Non-runtime root artifacts (db dumps, ad-hoc imports, temp files, phpinfo
  outputs, installer leftovers) are archived under timestamped folders in
  `Backup/root-artifacts/`.
- In-tree backup/edit artifacts (`*.bak*`, `*.new`) from `src/` and
  `templates/` should be archived under timestamped folders in
  `Backup/in-tree-artifacts/` instead of being kept beside active source files.
- Plugin backup copies should also stay outside `plugins/` to avoid duplicate
  autoload namespaces.
- `templates/Element` vs `templates/element` (and `Layout/layout`,
  `Email/email`) currently contain intentionally mixed compatibility content.
  Do not bulk-merge or delete one side without a route-by-route render test.
  Keep case-duplicate audits in `Backup/reports/` before any consolidation.

## CakePHP 5 migration notes

- Dependency targets are now set for CakePHP 5 / PHP 8.5 in `composer.json`,
  and lock resolution is updated in `composer.lock`.
- Template path has been migrated from `src/Template` to `templates` via
  `config/app.php` and `config/app.default.php`.
- PHP view templates are generated in `templates/` for CakePHP 5 `.php`
  template resolution.
- A compatibility auth component exists at
  `src/Controller/Component/AuthComponent.php` for legacy `$this->Auth` usage.
- A compatibility mail wrapper exists at `src/Utility/LegacyEmail.php` for
  legacy `new Email()` call patterns while transitioning to CakePHP 5 mailer
  behavior.
- Duplicate plugin backups should be kept outside `plugins/` (for example
  `Backup/plugin-copies/`) so Composer autoload doesn't register them as
  active plugin namespaces.

## Deploy on PHP 8.5 (WHM/cPanel)

Run these in the project root on the target server using PHP 8.5 CLI:

```bash
composer install --no-dev --optimize-autoloader
bin/cake migrations migrate
```

Then clear caches and warm route cache:

```bash
bin/cake cache clear_all
bin/cake routes >/dev/null
```
