# Woo Platform on Kubernetes

## Dependencies

- [k3d](https://k3d.io/stable/#releases)
  - Quick install: `task kube:install`
- Docker, logged in to `ghcr.io` (see [Registry credentials](#registry-credentials))

## Managing the cluster

The local cluster is a single-node [k3d](https://k3d.io) cluster named `woo-cluster`, configured by [k3d-woo.yml](k3d-woo.yml).

| Action  | Command           | What it does                                                           |
|---------|-------------------|------------------------------------------------------------------------|
| Start   | `task kube:up`    | Creates and starts the cluster. Does nothing if it already exists.     |
| Restart | `task kube:reset` | Deletes the cluster and creates a fresh one (`down` followed by `up`). |
| Stop    | `task kube:stop`  | Stops the cluster's containers. The cluster and its state are kept.    |
| Down    | `task kube:down`  | Deletes the cluster, its state and the generated `kubeconfig`.         |
| Status  | `task kube:ps`    | Lists the k3d clusters and nodes.                                      |

`task kube:up` only creates a cluster when none exists, so it won't start a stopped cluster. To resume a cluster stopped with `task kube:stop`, run:

```shell
k3d cluster start woo-cluster
```

`task kube:reset` throws away everything inside the cluster. Use it to start clean, or to pick up changes to `k3d-woo.yml` or to your registry credentials.

### Using kubectl

The tasks write the cluster's kubeconfig to `kube/kubeconfig` (git-ignored) instead of `~/.kube/config`. Point `kubectl` at it:

```shell
export KUBECONFIG="$PWD/kube/kubeconfig"
kubectl get nodes
```

## Registry credentials

The Woo images are private on `ghcr.io`, so the cluster node has to authenticate to pull them. `task kube:up` reuses the `ghcr.io` login of your host's Docker, so there is nothing to configure in the cluster itself.

1. Before creating the cluster, the task runs [scripts/ghcr-credential.sh](scripts/ghcr-credential.sh) twice, to resolve `GHCR_USERNAME` and `GHCR_TOKEN`.
2. The script looks up each value in this order:
   1. The `GHCR_USERNAME` or `GHCR_TOKEN` environment variable, if set and not empty.
   2. The credential helper for `ghcr.io` in your Docker config (`credHelpers`, falling back to `credsStore`, for example `osxkeychain` or `desktop`), queried through `docker-credential-<helper> get`.
   3. The base64-encoded `auths["ghcr.io"].auth` entry in your Docker config, when no helper is configured.

   The Docker config is read from `$DOCKER_CONFIG/config.json`, or `~/.docker/config.json` when `DOCKER_CONFIG` isn't set. If `jq` isn't installed on the host, the script runs it in the `utils` service from `compose.yml`.
3. k3d expands both variables in the `registries` section of [k3d-woo.yml](k3d-woo.yml) and writes the result into the node's `/etc/rancher/k3s/registries.yaml`. containerd uses it for every pull from `ghcr.io`, so pods need no `imagePullSecrets`.

If no credentials are found, `task kube:up` fails with `No ghcr.io credentials found`. Log in with a GitHub personal access token that has the `read:packages` scope:

```shell
docker login ghcr.io --username <github-username>
```

Or skip the Docker config and pass the credentials directly:

```shell
GHCR_USERNAME=<github-username> GHCR_TOKEN=<token> task kube:up
```

Keep in mind that:

- The credentials are copied into the node when the cluster is created. After rotating your token, run `task kube:reset` to apply the new one.
- The token is stored in plain text in the node container, so anyone with access to your Docker daemon can read it.
