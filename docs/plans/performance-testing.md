# Performance testing

<!-- TOC -->
- [Performance testing](#performance-testing)
  - [Goal](#goal)
  - [Tool choice](#tool-choice)
  - [Percentiles explained](#percentiles-explained)
  - [Prerequisites](#prerequisites)
  - [Phase 0 — Baseline](#phase-0--baseline)
  - [Phase 1 — Read path stress](#phase-1--read-path-stress)
  - [Phase 2 — Write path stress](#phase-2--write-path-stress)
  - [Phase 3 — Mixed realistic load](#phase-3--mixed-realistic-load)
  - [What to measure](#what-to-measure)
<!-- TOC -->

## Goal

Find the system's breaking point: which layer saturates first (PHP-FPM pool, database connections, message queue depth, disk I/O) and at what load level.

We have no baseline expectation for production traffic, so the initial aim is purely exploratory stress testing.

## Tool choice

**Locust** (MIT, free, self-hosted) or **Artillery** (MPL-2.0, free, self-hosted) are both suitable. The API uses mTLS client certificate authentication on every request, which both tools support.

- Locust: Python scenarios, mTLS is straightforward via `requests` (`session.cert = ("client.crt", "client.key")`), good for complex multi-step flows.
- Artillery: YAML/JS config, has an OpenAPI import plugin — useful since this API generates an OpenAPI spec.

## Percentiles explained

All response times are reported as percentiles across all requests in a test run. For 1000 requests sorted fastest to slowest:

| Metric | Meaning |
| -------- | ------- |
| p50 | Median — half of requests were faster. Typical user experience. |
| p95 | 95% of requests were faster. What a slow-but-not-worst-case user sees. |
| p99 | Near-worst case. Only 1% of requests were slower. |

Averages hide outliers. A spike visible at p99 (e.g. 8s) may not be visible in an average (e.g. 130ms).

p95 is the most common threshold for SLAs: "95% of requests must complete within Xms".

## Prerequisites

Before running any phase:

1. **Test environment** that mirrors production sizing, or document the difference (results on an underpowered env still show *which* layer breaks first, just not absolute numbers).
2. **Seed data** — existing organisations and dossiers in the database, so GET collection endpoints return real results rather than empty pages.
3. **Valid mTLS client certificate** for the test runner.
4. **Worker monitoring** — a way to observe RabbitMQ queue depth during tests (management UI or a simple queue consumer-count check). The upload scenarios stress two systems independently; the API may stay healthy while the worker queue silently saturates.
5. **Representative file sizes** agreed before writing upload scenarios — include both the typical main-document size in production *and* atypical large outliers (e.g. a single oversized PDF).
A file that takes disproportionately longer to process can cause cascading stalls if the implementation does not isolate slow work; atypical sizes surface this before production does.
6. **Teardown plan** — the write phases generate a large number of dossiers and uploaded files.
Agree in advance how to remove only the data created by the performance tests after each run (e.g. a targeted purge script keyed on a test-run identifier, or resetting from a pre-seeded snapshot),
so the seed data remains intact and the environment is ready for the next run without leftover data skewing results.

## Phase 0 — Baseline

**Purpose**: establish reference response times under no load.

Run with **1 virtual user** for 2–3 minutes. Record p50/p95/p99 and error rate. This is your healthy baseline for all subsequent comparisons.

| Scenario | Method | Endpoint |
| ---------- | -------- | ---------- |
| List dossiers | GET | `/organisation/{id}/dossiers/woo-decision` |
| Get single dossier | GET | `/organisation/{id}/dossiers/woo-decision/external/{extId}` |
| Upsert dossier | PUT | `/organisation/{id}/dossiers/woo-decision/external/{extId}` |
| Upload small file (~10 KB) | PUT | `.../uploads/main-document` |

## Phase 1 — Read path stress

**Purpose**: find the ceiling of read throughput (database queries, ORM, serialisation).

Ramp: 1 → 5 → 10 → 25 → 50 → 100 concurrent users. Hold each step for 2 minutes.

| Scenario | Method | Endpoint |
| ---------- | -------- | ---------- |
| List dossiers (paginated) | GET | `/organisation/{id}/dossiers/woo-decision` |
| Get single dossier | GET | `/organisation/{id}/dossiers/woo-decision/external/{extId}` |

**Breaking signal**: p95 > 2s, or error rate > 1%.

Watch for: response times climbing at a specific concurrency step, PHP-FPM worker pool exhaustion (503s), database connection pool errors.

## Phase 2 — Write path stress

**Purpose**: find the ceiling of the write path (database transactions, validation, ORM flush) under a realistic creation flow.

Same ramp profile as Phase 1. Each virtual user runs the following sequence as a single scenario:

### Step 1 — Upsert dossier metadata

```text
PUT /organisation/{organisationId}/dossiers/woo-decision/external/{dossierExternalId}
```

Include a realistic payload: populated `mainDocument`, `documents` array with ~20 entries
(each with `documentId`, `externalId`, `fileName`, `grounds`, `judgement`, etc.),
and at least one `attachment`. The system supports up to 50 000 documents;
20 is a representative starting point — adjust once production data is available.

### Step 2 — Upload main document

```text
PUT /organisation/{organisationId}/dossiers/woo-decision/external/{dossierExternalId}/uploads/main-document
```

### Step 3 — Upload each woo document

Repeat for every document declared in step 1.

```text
PUT /organisation/{organisationId}/dossiers/woo-decision/external/{dossierExternalId}/uploads/document/external/{documentExternalId}
```

Use realistic file sizes, including at least one atypical large file (see [Prerequisites](#prerequisites)). Empty or minimal payloads skip validation and ORM work, producing misleading results.

Watch for: database lock contention on concurrent upserts of large document arrays, ORM flush time scaling with document count, worker queue depth growing as uploads are enqueued.

## Phase 3 — Mixed realistic load

**Purpose**: simulate actual API consumer behaviour and find interaction effects between read and write paths.

Ramp slowly to 50 concurrent users, hold for 10 minutes.

| Scenario | Share |
| ---------- | ------- |
| Read dossier list | 40% |
| Read single dossier | 30% |
| Upsert dossier metadata | 20% |
| Upload a file | 10% |

Adjust the traffic mix once production usage patterns are known.

## What to measure

Collect the following for every phase:

**From the test runner (per endpoint):**

- p50, p95, p99 response times
- Error rate (4xx = test/data error; 5xx = system error)
- Throughput (requests/second)

**From the server (during the test):**

- PHP-FPM active worker count
- Database connection pool usage
- RabbitMQ queue depth and consumer count
- CPU and memory on API and worker hosts

Server-side metrics matter most — the API may return 200s while the worker queue is silently overwhelmed.
