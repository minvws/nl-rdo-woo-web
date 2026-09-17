# ADR-005: Tenant Styling Via Mandatory Per-Tenant Stylesheets

## Status

Accepted

## Context

The Woo Publication Platform is multi-tenant: each tenant serves the application under its own
domain name. Tenants may want to deviate from the default styling of the public website; the
first concrete case is a tenant that wants a different homepage hero image.

The backend is already separated per tenant (each tenant gets its own container, configuration
and translations), but the frontend assets are built once and shared by all tenants: there is a
single build producing one shared CSS bundle.

Deviations are expressed as overrides of design tokens (CSS custom properties): the default
theme defines a token such as `--img-hero`, and a tenant redefines it. This keeps deviations
small and declarative. The question this ADR answers is how such overrides are packaged and
delivered to the browser.

## Options

Three options present themselves:

- A stylesheet per tenant, living alongside the other per-tenant configuration, built as its
  own entry in the shared frontend build and loaded on every page after the main bundle. Every
  tenant has one — a tenant without deviations has a (nearly) empty stylesheet — so no
  conditional loading logic is needed: there is nothing to check, just an unconditional link
  that for most tenants returns a tiny cacheable response.
- A single shared CSS bundle for all tenants, in which tenant deviations are scoped to a tenant
  identifier rendered as a `data-tenant` attribute on the `<html>` element
  (`[data-tenant='some-tenant'] { --img-hero: url(...); }`).
- Adding CMS capabilities to the platform, allowing admin users to upload images and provide
  tenant-specific CSS that is loaded in addition to the base styles, without developer
  intervention.

The second option needs no extra HTTP request, but it ships every tenant's overrides to every
tenant: visitors — including the IT staff of the various departments inspecting their own
instance — would see unrelated tenant identifiers and CSS rules show up in their instance,
which is hard to explain and blurs the separation between tenants.

The first option keeps each tenant's styling in the place where per-tenant customization
already lives, and each tenant only receives its own overrides. Its classic costs are avoided
by making the stylesheet mandatory rather than conditional: no "does this tenant have a
stylesheet?" lookup and no conditional loading logic. A flash of the default styling is not a
concern either, because the stylesheet is referenced as a regular render-blocking `<link>` in
the `<head>`: the browser does not render — and does not start fetching background images —
until all blocking stylesheets are loaded, so the cascade is resolved before anything is
painted and only the winning image is ever downloaded. What remains is one extra small HTTP
request per page and one build entry per tenant.

The third option is the most strategic one: as the number of tenants grows, small styling tweaks
will compound, and a self-service image upload and CSS editor would remove the need for developer
intervention. We reject it for now for two reasons. First, free-form CSS provided by CMS users is
hard to constrain: in theory it allows restyling the entire website, hiding elements, and so on,
which conflicts with the uniformity and integrity we want to guarantee for the public website.
Second, it is far more than what we need to solve today — a single hero image override for a
single tenant. Should tenant styling requests compound to the point where developer intervention
becomes a bottleneck, this option can be revisited.

## Decision

Give every tenant a mandatory stylesheet, living alongside the tenant's other configuration and
built as its own entry in the shared frontend build. The public website loads it
unconditionally as a render-blocking `<link>` after the main bundle. Tenant deviations are
expressed in it as unscoped overrides of the design tokens (CSS custom properties) defined by
the default theme.

## Consequences

- Adding a tenant deviation is editing that tenant's stylesheet; no backend or template changes
  are needed. Adding a *tenant* means also adding its stylesheet and build entry — the
  stylesheet is mandatory, so a missing one fails the build rather than silently falling back.
- Tenants never receive each other's overrides, and no tenant identifier needs to be exposed in
  the markup for styling purposes.
- Each page load performs one extra HTTP request for the tenant stylesheet. For tenants without
  deviations this is a tiny, cacheable response. The build tool drops assets that minify to
  nothing, so an "empty" tenant stylesheet must contain at least one surviving declaration (a
  marker custom property identifying the tenant serves this purpose and helps debugging).
- The tenant stylesheet must be loaded render-blocking after the main bundle, and its overrides
  must stay unlayered, so they always win over the defaults defined in the theme layer and no
  flash of the default styling can occur. Loading it lazily or asynchronously would reintroduce
  both the flash and the double image download.
- This mechanism only covers styling that can be expressed as CSS (custom property) overrides.
  Structural per-tenant differences (different markup, different asset pipelines) would need a
  different mechanism and should trigger revisiting this decision.
