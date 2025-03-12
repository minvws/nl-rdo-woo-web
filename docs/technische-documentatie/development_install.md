# Woo Platform

<!-- TOC -->
- [Woo Platform](#woo-platform)
  - [Step 1: Clone the repository](#step-1-clone-the-repository)
  - [Step 2: Install dependencies](#step-2-install-dependencies)
  - [Step 3: Setup and start docker containers](#step-3-setup-and-start-docker-containers)
    - [Note: The Docker containers in this repository are for development purposes only and they are not meant for production use](#note-the-docker-containers-in-this-repository-are-for-development-purposes-only-and-they-are-not-meant-for-production-use)
  - [Step 3: Setup initial user](#step-3-setup-initial-user)
    - [a. Using Task](#a-using-task)
    - [b. Using the shell](#b-using-the-shell)
  - [Step 4: Browse to the site](#step-4-browse-to-the-site)
  - [Misc](#misc)
    - [Developing frontend](#developing-frontend)
    - [Tasks](#tasks)
<!-- TOC -->

> [!WARNING]
> The Docker containers in this repository are for development purposes only and they are not meant for production use

## Step 1: Clone the repository

Clone the repository to your local machine:

```shell
git clone git@github.com:minvws/nl-rdo-woo-web.git
```

## Step 2: Install dependencies

Install the dependencies for the project.

- [Docker](https://docs.docker.com/install/)
- [Task](https://taskfile.dev/#/installation)

<details>
<summary>Optionally, but recommended, set a <code>CR_PAT</code> env variable:</summary>

This project currently needs to access private Composer and NPM packages hosted on Github. When you try to setup
the project, it will prompt you for the Github Access Token, if the `CR_PAT` env variable is not set.

Instead of it prompting you everytime you "reset" the project you can instead set the `CR_PAT` env variable so it will
automatically use that instead.

The token can be created at <https://github.com/settings/tokens>. It will at least need the following scopes:

- repo
- read:packages

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

## Step 3: Setup and start docker containers

Start the docker containers we need to run (app, elasticsearch, tika, postgres, rabbitmq):

```shell
task up
```

You can replace `up` with `stop`, `down` and `restart`.

### Note: The Docker containers in this repository are for development purposes only and they are not meant for production use

> To administer your elasticsearch instance, you can use <https://app.elasticvue.com>
> To administer your rabbitmq instance, you can use <https://localhost:15672> (guest/guest)

## Step 3: Setup initial user

To set up an initial user, you can use one of the following methods:

### a. Using Task

Run this command:

```shell
task app:user:create -- "email@example.org" "full name" --super-admin
```

### b. Using the shell

Run this command to shell into a container: `task shell`

And run the command to add an user:

```shell
bin/console woopie:user:create "email@example.org" "full name" --super-admin
```

Both methods will generate a password and a 2FA token with which you can log into the website.

## Step 4: Browse to the site

- Open the Website at `http://localhost:8000`
- Open the Balie at `http://localhost:8000/balie/login`
  - You can log in with your generated credentials.

See [usage](usage.md) for more information on how to use the application.

## Misc

### Developing frontend

When developing frontend code (either CSS or JS), you can run the following command to watch for changes:

```shell
docker-compose exec app npm run watch
```

### Tasks

There are multiple tasks available. You can display all available tasks with `task --list`.
