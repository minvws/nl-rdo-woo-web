# Woo Publication Platform

<!-- TOC -->
- [Woo Publication Platform](#woo-publication-platform)
  - [After pulling new changes or switching branches](#after-pulling-new-changes-or-switching-branches)
  - [Local setup troubleshooting](#local-setup-troubleshooting)
<!-- TOC -->

## After pulling new changes or switching branches

Whenever you pull new changes or switch branches, you should run the following command:

```shell
task refresh
```

It is also available as `task update`. It restarts the environment and brings everything back in sync:

1. stops the containers and removes `var/` (caches and logs);
2. starts the environment again;
3. installs the Composer dependencies;
4. runs the database migrations for each local tenant, for both the dev and the test database;
5. installs the npm dependencies and builds the front-end assets;
6. builds the user manual;
7. creates the MinIO buckets;
8. runs `woopie:post-deploy` for the `worker`, `publication_api` and `admin` applications.

Note that it does **not** touch your `.env.dev.local`; that file is yours to manage. The only automated dotenv handling
is in CI, where `task env` copies `.env.e2e` over it.

## Local setup troubleshooting

If you still get errors after running `task refresh`, try the following:

```shell
task reset
```

This is the bigger hammer. It removes all project-related Docker containers, volumes and networks (including those
behind compose profiles), deletes `var/`, re-pulls and rebuilds the images, and then starts the environment again from
scratch — which means it also re-runs the full `task setup`, so migrations and fixtures are reapplied.

It also deletes `tests/robot_framework/secrets/`, so the Robot Framework credentials are gone afterwards. `task rf:init`
recreates them; see [test.md](test.md#general-setup).

> `--volumes` removes the `postgres_data`, `es_data` and `minio_data` volumes, so the database, the search index and the
> stored files all go. Any local dossiers you created by hand are lost; only the fixtures come back. The `woo_composer`
> and `woo_npm` caches are declared `external` and survive.
