# ADR-004: Add temporary POST support for API

## Status

Accepted

## Context

While implementing their API Client for the Woo Publication Platform, the team at the Ministry of Finance (MinFin) discovered that the plaform they use to build the integration (WEM) currently does not support octet-stream PUT requests, which are required
for uploading documents to the platform. This is a significant obstacle for the integration, as document upload is a core functionality of the Woo Publication Platform.

While MinFin has filed a feature request with the WEM team to add support for octet-stream PUT requests, it is uncertain when this feature will be implemented. In the meantime, MinFin needs a workaround to continue their integration efforts without being
blocked by this limitation. This is why we have explored options for enabling alternative methods for document upload.

## Background

PHP has a limitation where it does not natively support the combination of multi-part upload and PUT requests. This is a fundamental issue with PHP's treatment of HTTP methods and how it handles file uploads. The choice can be defended, as a PUT request is
generally expected to replace the entire resource, which conflicts with the notion of a multi-part upload where binary data and corresponding data are sent together.

PHP does support multi-part uploads with POST requests, which is the standard method for file uploads in web applications. However, this does not align with the RESTful principles that the Woo Publication Platform aims to follow, where PUT is typically
used for storing or updating resources for which an identity (ie: a URL) is already known. Our usage of "external IDs" for documents guided the API design towards PUT-requests.

## Options

We have considered the following options:

- Add temporary POST support for document uploads in the API, allowing clients to use POST requests for uploading documents until WEM adds support for octet-stream PUT requests.
- Implement [APFD](https://pecl.php.net/package/apfd) - a PECL extension to PHP which overrides the default behavior of PHP's file upload handling, allowing for multi-part uploads with PUT requests.
This would require additional setup and maintenance, as it is not
  a standard part of PHP and may have compatibility issues with different environments or newer versions of PHP as they get released.
- Implement a middleware bridge that parses the incoming php://input as a multi-part upload, and translates it into a Request object which our framework can handle as if it is a PHP-native upload. Unless we can find a library that already solves this, this
  approach would be complex and error-prone, as it would require copying data around in in-memory streams. This would result in increased memory usage and potential performance issues, especially for larger file uploads.

## Decision

We fully expect this to be a temporary situation, as WEM has previously been amendable to feature requests and has a track record of implementing them in a reasonable timeframe. Therefore, we have decided to implement temporary POST support for document
uploads in the API. This will allow MinFin to continue their integration efforts without being blocked by the current limitations of WEM.

Changing our operational setup by rolling out a PECL extension would introduce additional complexity and coordination with the Operations team, which is not justified given the temporary nature of the issue. We will monitor the situation closely and remove
the temporary POST support once WEM has added support for octet-stream PUT requests.

Should we receive word that WEM is not going to implement the required feature in a reasonable timeframe, we will revisit this decision and reconsider implementing the PECL extension as a more permanent solution.

## Consequences

- There will be two methods for uploading documents to the API: the standard PUT method and the temporary POST method.
- Clients will need to be aware of the temporary nature of the POST routes, and change their implementation when WEM releases the required feature.
- We are at risk of this temporary solution calcifying, particularly if WEM decides not to prioritize implementing octet-stream PUT support.
