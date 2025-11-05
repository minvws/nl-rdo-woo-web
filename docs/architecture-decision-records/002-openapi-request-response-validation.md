# ADR-002: OpenAPI Request/Response Validation

## Status

Approved, once PR is merged.

## Context

We want to develop and maintain a publicly accessible, OpenAPI-specced API for our Woo platform. To ensure that our API
adheres to the auto-generated OpenAPI specifications, we need a robust mechanism for validating both incoming
requests and outgoing responses.

## Decision

- Requests must be validated before they reach our controllers.
  - If the request is invalid, we want to return a **400 Bad Request** response to the client using "Problem Details"
    [RFC 9457](https://datatracker.ietf.org/doc/html/rfc9457). Note: This is the follow-up RFC to
    [RFC 7807](https://datatracker.ietf.org/doc/html/rfc7807), which is now deprecated.
- Responses are validated after the response has been sent to the client.
  - It is less likely to have validation issues in our responses, as the OpenAPI schemas are generated based on the
    code. However, manual changes to the OpenAPI specification or bugs in the code can still lead to discrepancies.
  - Another reason is that we do not want to impact the performance of our API by adding
    synchronous response validation.
  - Asynchronous validation can be done either by listening and hooking into the
    `Symfony\Component\HttpKernel\KernelEvents::TERMINATE` event or by dispatching a message and handling it in the
    background using Symfony Messenger.
  - When the response is invalid, violations are logged for further investigation.
  - **Nice-to-have**: Add a configuration option so that, in specific development environments, response violations
    are returned directly. This allows developers to get immediate feedback if they return an invalid response.

There are a couple of libraries that can help us achieve this:

- [openapi-httpfoundation-testing](https://github.com/osteel/openapi-httpfoundation-testing)
- [openapi-psr7-validator](https://github.com/thephpleague/openapi-psr7-validator)
- [openapi-validator-bundle](https://github.com/cydrickn/openapi-validator-bundle)

Note that some might be unmaintained, outdated, or may not support our target OpenAPI version
([OpenAPI 3.1.1](https://spec.openapis.org/oas/v3.1.1.html)).

The preference goes to a ready-made solution. If none of the existing solutions meet our needs, a careful evaluation
will be made between:

- forking one of the existing libraries, or
- building our own solution.

## Consequences

- Requests reaching our controllers are valid and as expected.
- Consumers of our API will get immediate feedback if they send an invalid request.
- Any discrepancies between the actual responses and the OpenAPI specification are logged.
- **Nice-to-have**: Developers working on the Woo platform will get immediate feedback if they return an invalid
  response.
