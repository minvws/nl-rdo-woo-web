# ADR-001: API Logging

## Status

Accepted

## Context

While developing the Woo Publication Platform API, we want to implement audit logging to provide accountability to our stakeholders.

This logging is separate from the application logs that are used for debugging and monitoring the health of the application.

## Decision

- API logging will be treated as separate from the regular logs.
- - Regular logs will be used for debugging and monitoring the health of the application.
- - API logs will be used for auditing purposes
- - API logs can be requested by stakeholders to answer questions about who did what and when.
- API logs will be shipped to a separate logging service, provided by Ops.
- API logs will contain a timestamp of when the action was performed.
- API logs will always use UTC timestamps.
- API logs will contain the Rijksoverheid Identificatie Nummmer (RIN) of the user that performed the action, if available.
- API logs will contain the Bestuursorgaan code of the organisation that triggered the action.
- API logs will be structured JSON logs, with mandatory metadata fields:
  - Entity Type (ie: Subject, Document, Dossier, etc)
  - Entity Id (ie: UUID)
  - Operation ID
  - In case of partial mutations: a list of fields that were changed.
    - This so we can support PATCH operations for partial changes.
- API logs do not contain the values of the fields that were changed, just the names of the fields.
- Authentication failures will also be logged, but not as part of the API log.
- - mTLS is tied to setting up a secure connection, and is handled by the webserver, not the application.
- - Failure to authenticate using mTLS will therefor never reach the application, and will be logged by the webserver.
- API logs will be retained for as long as possible, but at least 1 year.

For now, these logs are scoped to the API. In a subsequent ADR we will address harmonizing the log trails.

## Consequences

- When logging an action, the developer must ensure that all mandatory metadata fields are included.
- The DI container should provide a logger instance that is pre-configured to log to the correct logging service.
- Developers must ensure that no sensitive information is logged in the API logs.
- Developers must ensure they use the correct logger (API vs General logs).
- Future: The logging service should provide a UI usable by non-development project members to answer auditing questions.
