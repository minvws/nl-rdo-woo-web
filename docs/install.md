# Woo Platform

<!-- TOC -->
* [Woo Platform](#woo-platform)
* [Step 1: Clone the repository](#step-1-clone-the-repository)
* [Step 2: Install dependencies](#step-2-install-dependencies)
* [Step 3: Setup and start docker containers](#step-3-setup-and-start-docker-containers)
* [Step 4: Setup .env.local](#step-4-setup-envlocal)
* [Step 5: Composer install](#step-5-composer-install)
* [Step 6: Configure .env.local](#step-6-configure-envlocal)
* [Step 7: Setup database and elastic](#step-7-setup-database-and-elastic)
* [Step 8: Compile frontend code](#step-8-compile-frontend-code)
* [Step 9: Setup initial user](#step-9-setup-initial-user)
* [Step 10: Browse to the site](#step-10-browse-to-the-site)
<!-- TOC -->

## Step 1: Clone the repository

Clone the repository to your local machine:

```shell
    git clone git@github.com:minvws/nl-rdo-woo-web.git
```

## Step 2: Install dependencies

Install the dependencies for the project. Currently you only need to have docker installed.

## Step 3: Setup and start docker containers

Start the docker containers we need to run (app, elasticsearch, tika, postgres, rabbitmq):

```shell
    docker-compose up -d
```

> To administer your elasticsearch instance, you can use <https://app.elasticvue.com>
> To administer your rabbitmq instance, you can use <https://localhost:15672> (guest/guest)

## Step 4: Setup .env.local

Copy the `.env.development` file to `.env.local`. Most settings are correct for docker development.

```shell
    cp .env.development .env.local
```

## Step 5: Composer install

Next step is to install application dependencies using Composer:

```shell
    docker-compose exec app composer install
```

## Step 6: Configure .env.local

The only thing you MUST change, is the database encryption key. You can generate one through the commandline:

```shell
    docker-compose exec app bin/console generate:database-key
```

Copy the key to the `.env.local` file in the `DATABASE_ENCRYPTION_KEY` variable.

Also in case you work in the Docker environment it is recommended to add `COOKIE_NAME=WOOPID` to the `.env.local` file.
This gives you the opportunity to actually login to the platform.

> It might be possible to use commands like `bin/console` locally, as your local directory is shared with the docker
> container. However you might run into permission issues. Just run the commands without the `docker-compose exec app`.

## Step 7: Setup database and elastic

Next step is to generate the database structure and fill with initial data:

```shell
    docker-compose exec app bin/console doctrine:migrations:migrate
    docker-compose exec app bin/console doctrine:fixtures:load
    docker-compose exec app bin/console woopie:index:create woopie latest --read --write
```

## Step 8: Compile frontend code
>
> This application contains a private npm dependency to conform to the Rijksoverheid style guide:
> [Rijksoverheid Theme](https://github.com/minvws/nl-rdo-rijksoverheid-ui-theme). It requires you to add a file (`~/.npmrc`) on your
> machine containing a Github access token. Please follow
> [these instructions](https://github.com/minvws/nl-rdo-rijksoverheid-ui-theme#installation) to do so.

Next step is to compile the frontend code:

```shell
    docker-compose exec app npm install
    docker-compose exec app npm run build
```

When developing frontend code (either CSS or JS), you can run the following command to watch for changes:

```shell
    docker-compose exec app npm run watch
```

## Step 9: Setup initial user

Create an initial user to admin the site:

```shell
    docker-compose exec app bin/console woopie:user:create "email@example.org" "full name" --super-admin
```

This will generate a password and 2fa token with which you can log into the website.

## Step 10: Browse to the site

When this is all done, you can goto the website at `http://localhost:8000/login`. You can log in with your
generated credentials.

See [usage](usage.md) for more information on how to use the application.
