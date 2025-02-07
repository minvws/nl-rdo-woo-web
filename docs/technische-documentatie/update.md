# Woo Platform

<!-- TOC -->
- [Woo Platform](#woo-platform)
  - [After pulling new changes or switching branches](#after-pulling-new-changes-or-switching-branches)
  - [Local setup troubleshooting](#local-setup-troubleshooting)
<!-- TOC -->

## After pulling new changes or switching branches

Whenever you pull new changes or switch branches, you should run the following command:

```shell
task app:update
```

This will pull Composer and NPM dependencies, update the database (migrations), build the front-end and sync the `.env`-file.

## Local setup troubleshooting

If you still get errors after running `task app:update`, try the following:

```shell
task reset
```

This will remove all project related (Docker) containers, volumes and networks.

In case you are still having issues you can try running the more extreem `task cleanup:all` command. Besides cleaning up the Docker related resources it will also remove all locally generated files (including the `.env`-file) and all untracked files:

```shell
task cleanup:all
task up
```

And as a last resort, if you still have issues after running the above commands, you could run these commands:

```shell
task cleanup:all
# This will delete ALL (including non-project related) current non-running containers, volumes, networks and images that are not used by any containers!
docker system prune --all --volumes
docker compose pull
task up
```
