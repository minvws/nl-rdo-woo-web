# Compose overrides

Docker Compose automatically loads `compose.override.yml` from the repository root, on top of `compose.yml`, and Symfony
loads `.env.dev.local` on top of `.env.dev`. Both files are **git-ignored**, so every developer picks their own. This
directory holds the ready-made variants you can copy into place.

| File                     | Copy to                | What it does                                                                                     |
|--------------------------|------------------------|--------------------------------------------------------------------------------------------------|
| `compose.orbstack.yml`   | `compose.override.yml` | Gives every service an [OrbStack](https://orbstack.dev/) domain and drops the published ports    |
| `.env.dev.local.example` | `.env.dev.local`       | Tells the application about those host names, over HTTPS                                         |
| `compose.two.yml`        | `compose.override.yml` | Same, but with `-two` domains and shifted ports so a second checkout can run alongside the first |
| `.env.two`               | `.env.dev.local`       | Tells the application about those `-two` host names, over HTTPS                                  |

Each variant needs **both** a compose file and an env file: the compose file publishes the OrbStack domains, the env
file tells Symfony which host names belong to which tenant. See [Why two files are needed](#why-two-files-are-needed).

Nothing in this directory is loaded automatically — copying is the activation step.

## OrbStack domains (single instance)

From the repository root:

```shell
cp ./docker/compose/compose.orbstack.yml compose.override.yml
cp ./docker/compose/.env.dev.local.example .env.dev.local
```

Then set the environment up (see [development_install.md](../../docs/technische-documentatie/development_install.md)):

```shell
task reset
```

`task reset` tears everything down, pulls and rebuilds the images and runs the full setup, so the new override is
guaranteed to be picked up.

The override sets `dev.orbstack.domains` labels and resets (`ports: !reset []`) the published ports of `public`,
`admin`, `elasticsearch`, `redis`, `rabbitmq` and `postgres`, so you reach everything by host name instead of by port:

| What            | minvws                                   | minfin                                   |
|-----------------|------------------------------------------|------------------------------------------|
| Public website  | <https://public-minvws.local>            | <https://public-minfin.local>            |
| Admin (balie)   | <https://admin-minvws.local/balie/login> | <https://admin-minfin.local/balie/login> |
| Publication API | <https://localhost:8443>                 | <https://localhost:8444>                 |

Supporting services get `elasticsearch-woo.local`, `redis-woo.local`, `rabbitmq-woo.local` and `postgres-woo.local`.
The Publication API and MinIO keep their published ports, so they stay on localhost either way.

`.env.dev.local.example` sets the four `*_HOST` variables for those domains, plus `PUBLIC_BASE_URL_PROTOCOL=https`:
OrbStack terminates TLS on its `.local` domains, so generated links have to be `https://`. The default in `.env.dev` is
`http`, which is what the port mappings below need.

Without any `compose.override.yml` the port mappings from `compose.yml` apply instead: public on `8000` / `8100`, admin
on `8001` / `8101`, Elasticsearch on `9200`, RabbitMQ management on `15672` and PostgreSQL on `5432`. Those are all
overridable with the `DOCKER_*_PORT` variables. In that case you need no `.env.dev.local` at all — `.env.dev` already
points at `localhost:<port>` over `http`.

## Running a second instance

Useful when you want to compare branches side by side, or keep a stable checkout running while you break another one.

The two instances are separated by the **Compose project name**, which defaults to the directory name of the checkout.
So the only hard requirement is that the second clone lives in a *differently named directory*; containers, networks and
volumes are then namespaced automatically.

```shell
git clone <repo-url> nl-rdo-woo-web-two
cd nl-rdo-woo-web-two

cp ./docker/compose/compose.two.yml compose.override.yml
cp ./docker/compose/.env.two .env.dev.local

task reset
```

The second instance is reachable on the same host names with a `-two` suffix, and on shifted ports for the services that
keep a port mapping:

| What            | minvws                                       | minfin                                       |
|-----------------|----------------------------------------------|----------------------------------------------|
| Public website  | <https://public-minvws-two.local>            | <https://public-minfin-two.local>            |
| Admin (balie)   | <https://admin-minvws-two.local/balie/login> | <https://admin-minfin-two.local/balie/login> |
| Publication API | <https://localhost:8543>                     | <https://localhost:8544>                     |

Supporting services get `elasticsearch-woo-two.local`, `redis-woo-two.local`, `rabbitmq-woo-two.local` and
`postgres-woo-two.local`. MinIO is shared between tenants and moves to <http://localhost:9100>, with its console on
`9101`.

### Why two files are needed

Both variants come as a pair of files, because they address two different consumers, which read different sets of `.env`
files:

- The **compose file** is for Docker Compose: it publishes the OrbStack domains, and in the `-two` case `!override`s the
  ports of the services that would otherwise collide with the first instance (`publication_api`, `minio`).
- The **env file** is for the application: Symfony needs to know which host names belong to which tenant, which base
  URLs to put in generated links, and where the Publication API lives.

Compose only reads the root `.env` file, never `.env.dev.local`. That is why `compose.two.yml` hardcodes its domains
instead of interpolating `${MINVWS_PUBLIC_HOST}` the way `compose.orbstack.yml` does — the interpolation there would
silently fall back to the non-`-two` defaults.

The env files only set `PUBLIC_BASE_URL_PROTOCOL` and the `*_HOST` variables. Everything derived from them —
`<TENANT>_PUBLIC_BASE_URL` and `HTTP_HOST_TO_TENANT_MAPPING` — is defined once in `.env.dev` and resolved by Symfony's
Dotenv *after* all `.env` files have been loaded, so it automatically picks up the overridden host names.

### Adding a third instance

Copy `compose.two.yml` and `.env.two`, replace the `-two` suffix and pick another free port range, and clone into a
directory with yet another name.

## Note on the Robot Framework tasks

`task rf:run-docker` and `task rf:run-ci` temporarily replace `.env.dev.local` with `.env.e2e`, moving yours to
`.env.dev.local.bak` and restoring it afterwards. If a run is interrupted, restore it by hand.
