# ADR-003: API Approach For Information Categories

## Status

Accepted

## Context

The Woo Publication Platform needs to support multiple categories of information as defined by the Open Government Act (Woo; Wet open overheid). Each category may have different requirements for metadata, document types, and processing workflows. The API
must be designed to support these multiple categories, while ensuring consistency with the UI.

## Options

Two options present themselves:

- Separate API endpoints, designed specifically for each information category.
- A single API endpoint (/dossiers) that can handle multiple categories by including a category identifier in the request payload. The server will then process the dossier according to the specified category's requirements.

The first option leads to a proliferation of endpoints, which can complicate client implementations and increase maintenance overhead. The advantage of this approach is that the API is more explicit: each endpoint is tailored to a specific category, making
it clear what data is expected. More complex workflows, like the `WOO-verzoek` category can be handled in a more straightforward manner as the endpoint can be designed keeping in mind the intricacies of that specific category.

The second option simplifies the API surface area, making it easier for clients to interact with the system. By using a single endpoint, we can centralize the logic for handling different categories, reducing duplication and potential inconsistencies.
However, this approach requires careful design to ensure that the server can correctly interpret and process dossiers based on their category.

## Decision

TLDR: Implement multiple API endpoints, tailored to a specific information category, including an alias for [TOOI](https://standaarden.overheid.nl/tooi).

During a work session on 2025-10-21 it was initially decided to implement the second option (single API endpoint (/dossiers) to handle multiple information categories). New information categories are not expected to be added frequently (or at all), because
that would depend on changes to legislation. Therefore, the flexibility offered by a single endpoint is deemed enough for the near future.

However, as part of the work session, we found that API Platform does not natively support polymorphic deserialization based on a field value, at the time of writing: [api-platform/core#6915](https://github.com/api-platform/core/issues/6915). This guided
our decision to implement multiple API endpoints after all.

## Consequences

- The API will have *multiple endpoints*, each tailored to a specific information category.
- For each information category, we will include an extra endpoint that identifies the information category using the TOOI code.
  - These aliases will have a `tooi:` prefix.
- All information categories will share a common base structure for dossiers, with category-specific fields added as needed.
- During implementation, we can separate the more complex category (WOO-verzoek) into its own structure if needed.
