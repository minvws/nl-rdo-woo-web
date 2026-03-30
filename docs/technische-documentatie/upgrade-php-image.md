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
git checkout -b upgrade/php-image-0.9.2
```

---

## 3. Adjust the Dockerfile

- Open: `docker/php/Dockerfile`
- Change the image line, for example:

```Dockerfile
FROM php:8.4.17-apache-trixie
```

Adjust this to the desired version/tag. **Be sure to include the full tag, including the patch version.**

---

## 4. Build the Image & Resolve Conflicts

- Build the new PHP image:

```bash
task build:php
```

- Resolve any build errors/conflicts immediately in the Dockerfile or dependency lists.

---

## 5. Set PHP_IMAGE_TAG

- Set a unique tag for the new image (for example, dev or 0.9.2) as an environment variable:

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
PHP 8.4.17 (cli) (built: Feb  3 2026 ...)
...
Zend Engine v4.4.17 ...
```

---

## 8. Update compose.yml

- Adjust the PHP image tag in `compose.yml` (or similar compose config) so it points to the new image:

```yml
image: registry.example.com/your-php-image:0.9.2
```

---

## 9. Bump & Update composer.json

- Set the correct PHP version in `composer.json`:

```json
"php": ">=8.4"
```

- Run an update (note the `-W` for dependency tree!):

```bash
composer update -W
```

- Resolve any dependency errors/conflicts immediately.

---

## 10. Run Rector for Code Style and Language Features

Run Rector to automatically update your codebase to use new code styles and language features available in the new PHP version. This helps ensure
compatibility and leverages improvements in the language.

---

## 11. Commit and Push

Commit all relevant files in the branch:

- Dockerfile
- compose.yml
- composer.json + composer.lock

---

## 12. PR & Trigger Build

- Create a Pull Request.
- Make sure to trigger the `Build PHP image` with the correct version.
- Check that tests pass in CI.
