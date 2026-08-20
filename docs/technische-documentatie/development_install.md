# Woo Publication Platform

<!-- TOC -->
- [Woo Publication Platform](#woo-publication-platform)
  - [Step 1: Clone the repository](#step-1-clone-the-repository)
  - [Step 2: Install dependencies](#step-2-install-dependencies)
  - [Step 3: Authenticate with GitHub Container Registry](#step-3-authenticate-with-github-container-registry)
  - [Step 4: Setup and start docker containers](#step-4-setup-and-start-docker-containers)
    - [Note: The Docker containers in this repository are for development purposes only and they are not meant for production use](#note-the-docker-containers-in-this-repository-are-for-development-purposes-only-and-they-are-not-meant-for-production-use)
  - [Multiple tenants](#multiple-tenants)
  - [Step 5: Setup initial user](#step-5-setup-initial-user)
    - [a. Using Task](#a-using-task)
    - [b. Using the shell](#b-using-the-shell)
  - [Step 6: Browse to the site](#step-6-browse-to-the-site)
  - [Misc](#misc)
    - [Developing frontend](#developing-frontend)
    - [Tasks](#tasks)
    - [Keeping up to date](#keeping-up-to-date)
<!-- TOC -->

> [!WARNING]
> The Docker containers in this repository are for development purposes only and they are not meant for production use

## Step 1: Clone the repository

Clone the repository to your local machine. Development happens in the private repository:

```shell
git clone git@github.com:minvws/nl-rdo-woo-web-private.git
```

The source is also published to the public mirror `git@github.com:minvws/nl-rdo-woo-web.git`.

## Step 2: Install dependencies

Install the dependencies for the project.

- [Docker](https://docs.docker.com/install/)
- [Task](https://taskfile.dev/#/installation)

That is all you need to get the platform running. PHP, Composer, Node and npm are all baked into the PHP image (see
`docker/php/Dockerfile` for the versions), and the tasks run inside containers. The one exception is the front-end
watcher, which needs Node on the host — see [Developing frontend](#developing-frontend).

<details>
<summary>Set a <code>CR_PAT</code> env variable:</summary>

This project currently needs to access private Composer and NPM packages hosted on Github. When you try to setup
the project, it will prompt you for the Github Access Token, if the `CR_PAT` env variable is not set.

Instead of it prompting you everytime you "reset" the project you can instead set the `CR_PAT` env variable so it will
automatically use that instead.

The token can be created at <https://github.com/settings/tokens>. It will at least need the following scopes:

- repo
- read:packages
- read:org

You can add more scopes, but the list contains the absolute minimal scopes needed.

Then depending on what shell you are using, you need to set your variable in a different file. You can find out which
file by running the following command in your terminal:

```shell
echo $SHELL
```

This will output something like `/bin/bash` or `/bin/zsh` in most cases. For bash it's useally `~/.bash_profile` (or
`~/.bashrc`) and for zsh it is going to be `~/.zshrc` (the default for MacOS). If the file does not exist you can
create it yourself.

Open the file and add the following line:

```shell
export CR_PAT="<replace this with your token>"
```

Instead of manually opening the file and adding the line you can run one of the below commands instead. It will append
the line to the file for you:

```shell
# For BASH
echo "export CR_PAT='<replace this with your token>'" >> ~/.bash_profile

# For ZSH
echo "export CR_PAT='<replace this with your token>'" >> ~/.zshrc
```

</details>

## Step 3: Authenticate with GitHub Container Registry

Before starting the Docker containers, you need to authenticate with the GitHub Container Registry to pull the private container images.

Follow the instructions in the [GitHub Packages documentation](https://docs.github.com/en/packages/working-with-a-github-packages-registry/working-with-the-container-registry#authenticating-with-a-personal-access-token-classic):

1. Create a Personal Access Token (PAT) with `read:packages` and `read:org` scope at <https://github.com/settings/tokens>
2. Authenticate Docker with the token:

```shell
export CR_PAT=YOUR_TOKEN
echo $CR_PAT | docker login ghcr.io -u USERNAME --password-stdin
```

## Step 4: Setup and start docker containers

```shell
task up
```

You can replace `up` with `stop`, `down` and `restart`.

On a fresh checkout `task up` delegates to `task setup`, which does considerably more than starting containers:
generates the local mTLS certificates (`task certs:gen`), installs the Composer and npm dependencies, creates the MinIO
buckets, runs the database migrations and loads the example fixtures for each local tenant, creates the Elasticsearch
index, builds the front-end assets and builds the user manual. Expect it to take a while the first time.

This starts the following services:

| Service                          | Purpose                                       |
|----------------------------------|-----------------------------------------------|
| `public`                         | The public website, all tenants               |
| `admin`                          | The admin (balie), all tenants                |
| `publication_api`                | The Publication API                           |
| `worker_minvws`, `worker_minfin` | Message queue consumers, one per local tenant |
| `postgres`                       | Database                                      |
| `elasticsearch`                  | Search index                                  |
| `redis`                          | Cache and sessions (KeyDB)                    |
| `rabbitmq`                       | Message queues                                |
| `tika`                           | Content extraction                            |
| `clamav`                         | Virus scanning of uploads                     |
| `minio`                          | S3-compatible file storage                    |

There are also containers behind compose profiles, which only run when a task needs them: `sphinx` (user manual),
`certs` (certificate generation), `spectral` (API linting), `robot` (E2E tests) and `schemathesis` (API fuzzing).

### Note: The Docker containers in this repository are for development purposes only and they are not meant for production use

> To administer your elasticsearch instance, you can use <https://app.elasticvue.com>
> To administer your rabbitmq instance, you can use <http://rabbitmq-woo.local> (guest/guest)
> To administer your MinIO buckets, you can use <http://localhost:9001>

## Multiple tenants

The platform is multi-tenant. `minvws` and `minfin` are started locally; `minbuza` exists but is only built and tested
in CI. Each local tenant gets its own database, Elasticsearch index and worker container, which is why almost every
console command needs `--tenant` (see [commands.md](commands.md)).

## Step 5: Setup initial user

To set up an initial user, you can use one of the following methods:

### a. Using Task

Run this command:

```shell
task console:user:create -- "email@example.org" "full name" --super-admin
```

### b. Using the shell

Run this command to shell into the admin container: `task shell`. Use `task shell:<service>` for any other service, for
example `task shell:worker_minvws`.

And run the command to add an user:

```shell
bin/console --tenant=minvws woopie:user:create "email@example.org" "full name" --super-admin
```

This works because `task shell` puts you in the `admin` container, which sets `APP_ID=ADMIN`, and this command belongs to
the admin application. From another container you would need `--id=admin`; see
[commands.md](commands.md#selecting-a-tenant-and-an-application).

Both methods will generate a password and a 2FA token with which you can log into the website.

## Step 6: Browse to the site

How you reach the site depends on `compose.override.yml`, which is committed and which Docker Compose loads
automatically. It configures [OrbStack](https://orbstack.dev/) domains and **resets the published ports** for `public`,
`admin`, `elasticsearch`, `redis`, `rabbitmq` and `postgres`. So with the override in place you use host names:

| What            | minvws                                  | minfin                                  |
|-----------------|-----------------------------------------|-----------------------------------------|
| Public website  | <http://public-minvws.local>            | <http://public-minfin.local>            |
| Admin (balie)   | <http://admin-minvws.local/balie/login> | <http://admin-minfin.local/balie/login> |
| Publication API | <https://localhost:8443>                | <https://localhost:8444>                |

The public and admin host names can be changed with `<TENANT>_PUBLIC_HOST` and `<TENANT>_ADMIN_HOST`. The supporting
services get `elasticsearch-woo.local`, `redis-woo.local`, `rabbitmq-woo.local` and `postgres-woo.local`.

The Publication API and MinIO keep their published ports, so they are on localhost either way: the API on `8443` /
`8444` (mTLS, so it needs a client certificate from `task certs:gen`) and MinIO on `9000` with its console on `9001`.

If you are not using OrbStack, remove or replace `compose.override.yml` and the port mappings from `compose.yml` apply
instead: public on `8000` / `8100`, admin on `8001` / `8101`, Elasticsearch on `9200`, RabbitMQ management on `15672` and
PostgreSQL on `5432`. All of these can be overridden with the `DOCKER_*_PORT` environment variables, which is handy if
you run more than one checkout.

You can log into the balie with your generated credentials.

See [usage](usage.md) for more information on how to use the application.

## Misc

### Developing frontend

When developing frontend code (either CSS or JS), you can run the following command to watch for changes:

```shell
npm run watch
```

This one does need Node and npm **on your machine** — `package.json` requires Node `>=20`. The Vite dev server binds to
`localhost:8010` and the container does not publish that port, so running the watcher inside a container leaves it
unreachable from the browser.

For a one-off asset build you do not need Node on the host, since the image ships with it:

```shell
task npm:install
task npm:script:build
```

### Tasks

There are multiple tasks available. You can display all available tasks with `task --list`.

### Keeping up to date

After pulling new changes or switching branches, see [update.md](update.md).
