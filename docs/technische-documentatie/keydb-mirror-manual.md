# Mirroring KeyDB to GitHub Container Registry (GHCR)

This manual explains how to pull KeyDB images from Docker Hub, push them to GHCR, and create a multi-arch manifest —
based on a real session for the `minvws` organization.

---

## Prerequisites

- Docker installed and running
- A GitHub Personal Access Token (PAT) with the `write:packages` scope
- Write access to the target GHCR namespace (e.g. `ghcr.io/minvws`)

---

## Step 1: Pull the Source Images from Docker Hub

Pull both the ARM64 and x86_64 variants of the KeyDB image:

```bash
# ARM64 (for Apple Silicon / ARM servers)
docker pull eqalpha/keydb:arm64_v6.3.4

# x86_64 (for Intel/AMD servers)
docker pull eqalpha/keydb:x86_64_v6.3.4
```

---

## Step 2: Re-tag the Images for GHCR

Tag each image with the GHCR target path:

```bash
docker tag eqalpha/keydb:arm64_v6.3.4  ghcr.io/minvws/keydb:arm64_v6.3.4
docker tag eqalpha/keydb:x86_64_v6.3.4 ghcr.io/minvws/keydb:x86_64_v6.3.4
```

---

## Step 3: Authenticate with GHCR

> ⚠️ **Important:** Using `docker login ghcr.io` with cached credentials or via the `CR_PAT` environment variable alone
> may silently succeed but still result in `permission_denied` errors when pushing. Always authenticate explicitly with
> your username and PAT via `--password-stdin`.

```bash
echo YOUR_PAT | docker login ghcr.io -u YOUR_USERNAME --password-stdin
```

You should see:

```bash
Login Succeeded
```

---

## Step 4: Push the Images to GHCR

```bash
docker push ghcr.io/minvws/keydb:arm64_v6.3.4
docker push ghcr.io/minvws/keydb:x86_64_v6.3.4
```

Each push should complete with a digest confirmation, e.g.:

```bash
arm64_v6.3.4: digest: sha256:acf2f4c... size: 1573
x86_64_v6.3.4: digest: sha256:21d5e82... size: 1573
```

---

## Step 5: Create a Multi-Arch Manifest

A multi-arch manifest allows Docker to automatically pull the correct image for the host architecture.

> ⚠️ **Important:** The manifest must reference images already hosted on GHCR. Docker cannot combine images from
> different registries (`docker.io` vs `ghcr.io`) in a single manifest — make sure you pushed both images in Step 4 first.

```bash
docker manifest create ghcr.io/minvws/keydb:6.3.4-multi \
    ghcr.io/minvws/keydb:x86_64_v6.3.4 \
    ghcr.io/minvws/keydb:arm64_v6.3.4
```

If a manifest with this name already exists locally, add the `--amend` flag:

```bash
docker manifest create --amend ghcr.io/minvws/keydb:6.3.4-multi \
    ghcr.io/minvws/keydb:x86_64_v6.3.4 \
    ghcr.io/minvws/keydb:arm64_v6.3.4
```

To start fresh, remove the existing local manifest first:

```bash
docker manifest rm ghcr.io/minvws/keydb:6.3.4-multi
```

---

## Step 6: Push the Manifest to GHCR

```bash
docker manifest push ghcr.io/minvws/keydb:6.3.4-multi
```

A successful push returns the manifest digest:

```bash
sha256:4b7a1b20b66e77213bc32fe778d5eed39c0f48da50d9a9b9f2ef4acf249b65a7
```

---

## Step 7: Verify the Manifest

Inspect the pushed manifest to confirm both architectures are present:

```bash
docker manifest inspect ghcr.io/minvws/keydb:6.3.4-multi
```

Expected output:

```json
{
    "schemaVersion": 2,
    "mediaType": "application/vnd.docker.distribution.manifest.list.v2+json",
    "manifests": [
        {
            "mediaType": "application/vnd.docker.distribution.manifest.v2+json",
            "size": 1573,
            "digest": "sha256:acf2f4c443276bf071ab90861fd9644b22c9b6440e544fa655d287981dd8cd0a",
            "platform": {
                "architecture": "arm64",
                "os": "linux",
                "variant": "v8"
            }
        },
        {
            "mediaType": "application/vnd.docker.distribution.manifest.v2+json",
            "size": 1573,
            "digest": "sha256:21d5e82e8a4b30a0623036ef9745720c9d727a82d126faa4c7c2a9be232b3f1b",
            "platform": {
                "architecture": "amd64",
                "os": "linux"
            }
        }
    ]
}
```

Both `arm64` and `amd64` platforms should be listed. ✅

---

## Troubleshooting

| Error                                                                  | Cause                                  | Fix                                                                                  |
|------------------------------------------------------------------------|----------------------------------------|--------------------------------------------------------------------------------------|
| `permission_denied: The token provided does not match expected scopes` | Cached credentials used instead of PAT | Re-login explicitly: `echo PAT \| docker login ghcr.io -u USERNAME --password-stdin` |
| `cannot use source images from a different registry`                   | Manifest references `docker.io` images | Ensure all images are pushed to GHCR before creating the manifest                    |
| `refusing to amend an existing manifest list with no --amend flag`     | Local manifest already exists          | Add `--amend` flag, or run `docker manifest rm` first                                |
| `manifest unknown`                                                     | Image doesn't exist on that registry   | Double-check the registry prefix and that the push completed successfully            |

---

## Summary

```bash
docker pull eqalpha/keydb:arm64_v6.3.4
docker pull eqalpha/keydb:x86_64_v6.3.4

docker tag eqalpha/keydb:arm64_v6.3.4  ghcr.io/minvws/keydb:arm64_v6.3.4
docker tag eqalpha/keydb:x86_64_v6.3.4 ghcr.io/minvws/keydb:x86_64_v6.3.4

echo YOUR_PAT | docker login ghcr.io -u YOUR_USERNAME --password-stdin

docker push ghcr.io/minvws/keydb:arm64_v6.3.4
docker push ghcr.io/minvws/keydb:x86_64_v6.3.4

docker manifest create ghcr.io/minvws/keydb:6.3.4-multi \
    ghcr.io/minvws/keydb:x86_64_v6.3.4 \
    ghcr.io/minvws/keydb:arm64_v6.3.4

docker manifest push ghcr.io/minvws/keydb:6.3.4-multi
docker manifest inspect ghcr.io/minvws/keydb:6.3.4-multi
```
