# Guide: Upgrading the PHP Docker Image

This guide describes step-by-step how to safely and correctly upgrade the PHP Docker image in this project, including updating dependencies and testing the new version. Follow these instructions carefully to ensure compatibility and stability.

---

## 1. Introduction

This procedure is intended for upgrading the PHP version used in the Docker environment of this platform. This is required for bug fixes, security updates, or framework version bumps.

**Note:** Always test thoroughly after an upgrade, and commit changes in a separate branch!

---

## 2. Preparation

- Check which PHP version you want to upgrade to.
- Create a new feature branch:

```bash
git checkout -b upgrade/php-image-<version>
```

---

## 3. Adjust the Dockerfile

- Open: `docker/php/Dockerfile`
- Change the `FROM php:...` line. It pins a full tag, of the shape:

```Dockerfile
FROM php:<major>.<minor>.<patch>-apache-<debian-codename>
```

Adjust this to the desired version/tag. **Be sure to include the full tag, including the patch version.** See the
[official PHP image tags](https://hub.docker.com/_/php/tags) for what is available.

> This guide deliberately does not name concrete versions — they go stale. Check `docker/php/Dockerfile`,
> `compose.yml` and `composer.json` for what is actually pinned right now.

---

## 4. Build the Image & Resolve Conflicts

- Build the new PHP image:

```bash
task build:php
```

- Resolve any build errors/conflicts immediately in the Dockerfile or dependency lists.

---

## 5. Set PHP_IMAGE_TAG

- Set a unique tag for the new image (for example `dev`, or the new image version) as an environment variable:

```bash
export PHP_IMAGE_TAG=dev
```

Or update the `.env`/compose config if needed.

> When running `task build:php` it will always tag the image as `ghcr.io/minvws/nl-rdo-woo-web-private/php:dev`. To actually test your image,
> you can temporarily adjust your local `.env` file and add `PHP_IMAGE_TAG=dev`. **This change should NOT be committed and is just for local testing purposes.**

---

## 6. Reset Containers

- Rebuild and reset all containers so they use the new image:

```bash
task reset
```

---

## 7. Check PHP Version

- Start a shell in the PHP container:

```bash
task shell
```

- Check the PHP version:

```bash
php -v
```

**Output should match the desired version, e.g.**

```bash
PHP <version> (cli) (built: ...)
...
Zend Engine <version> ...
```

---

## 8. Update compose.yml

- Adjust the PHP image tag in `compose.yml` so it points to the new image. There is a single YAML anchor, reused by the
  `public`, `admin`, `publication_api`, `worker_minvws` and `worker_minfin` services, so only one line changes:

```yml
image: &PHP_IMAGE "ghcr.io/minvws/nl-rdo-woo-web-private/php:${PHP_IMAGE_TAG:-<image-version>}"
```

The default after `:-` is the tag to bump. `PHP_IMAGE_TAG` lets you test another tag without editing the file.

---

## 9. Bump & Update composer.json

- Set the correct PHP version constraint in `composer.json`, if the minimum has changed. It is a caret constraint on the
  `php` key:

```json
"php": "^<major>.<minor>"
```

- Run an update (note the `-W` for dependency tree!):

```bash
composer update -W
```

- Resolve any dependency errors/conflicts immediately.

---

## 10. Run Rector for Code Style and Language Features

Run Rector to automatically update your codebase to use new code styles and language features available in the new PHP version. This helps ensure
compatibility and leverages improvements in the language. From inside the container (`task shell`):

```bash
composer rector-preview   # dry run, shows what would change
composer rector           # apply the changes
```

`rector.php` calls `->withPhpSets()` without arguments, which means Rector takes the target PHP version from the `php`
constraint in `composer.json`. So do step 9 before this step, otherwise Rector will still be working against the old
version.

Then re-run the static analysis and code style checks, since a PHP bump often surfaces new findings:

```bash
composer checktype    # PHPStan
composer checkstyle   # phpcs + php-cs-fixer, --dry-run
```

---

## 11. Commit and Push

Commit all relevant files in the branch:

- Dockerfile
- compose.yml
- composer.json + composer.lock

---

## 12. PR & Trigger Build

- Create a Pull Request.
- Trigger the `Build PHP image` workflow ([.github/workflows/build-php-image.yml](../../.github/workflows/build-php-image.yml))
  with the correct version, so the new tag exists in ghcr.io before anything tries to pull it.
- Check that tests pass in CI.
