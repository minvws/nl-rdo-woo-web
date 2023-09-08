# Woopie Console Commands

## Global commands

Global commands that can be run on either production or development platforms.

| command                   | description                                                     |
|---------------------------|-----------------------------------------------------------------|
| `woopie:check:production` | Checks if the current environment is ready for the application. |
| `woopie:document:upload`  | Triggers the ingestion of an uploaded document                  |
| `woopie:index:regenerate` | Regenerates the search index                                    |
| `woopie:page:check`       | Checks if there are pages not yet indexed                       |
| `woopie:user:create`      | Creates a new admin user                                        |
| `woopie:user:reset`       | Reset a user's password and 2fa                                 |
| `woopie:user:view`        | View a user's details                                           |
| `woopie:ingest:dossier`   | Ingests a dossier into the system                               |
| `woopie:ingest:document`  | Ingests a document into the system                              |
| `woopie:index:alias`      | Creates an alias for the current index                          |
| `woopie:index:create`     | Creates a new index                                             |
| `woopie:index:delete`     | Deletes an index                                                |

## Development commands

These commands are used solely in a development environment, and are not meant to be used in production.

| command                     | description                                                                                |
|-----------------------------|--------------------------------------------------------------------------------------------|
| `woopie:sql:dump`           | Generates migrations SQL files based on the doctrine migrations php files                  |
| `woopie:load:fixture`       | Loads a fixture file into the system. This can be used for testing specific scenarios.     |
| `woopie:generate:documents` | Generates dossiers / documents / page content that can be used for testing and development |
| `woopie:dev:clean-sheet`    | Removes all dossier/document data from the database, elasticsearch and message queue.      |

## One-off commands

These commands are not meant to be run on a regular basis, but are used to perform a one-off task. They might be removed
from the codebase after their usage.

| command                 | description                                                                  |
|-------------------------|------------------------------------------------------------------------------|
| `translation:convert`   | Finds dutch messages in twig files, and converts them to the english variant |
| `generate:database-key` | Generates a database key that can be used in the .env file                   |

## Cron commands

Cron commands are meant to be run on a regular basis, and are used to perform a recurring task.

| command                      | description                                        |
|------------------------------|----------------------------------------------------|
| `woopie:cron:clean-archives` | Removes expired generated archives from the system |
