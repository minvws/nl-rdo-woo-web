# Publication API

This document provides a high-level overview of the API interface available for the Woo-platform.
It is intended as a complementary document to the OpenAPI Specification and seeks to provide additional information on the design decisions made when creating the API, and its intended usage.
Note that this document is written in English, while other parts of the documentation are in Dutch.
This is because we expect the primary audience for this document to be developers, for whom English is the lingua franca.

## OpenAPI documentation

The OpenAPI documentation of the Publication API can be found at {public}`api`.

## Platform Summary

A more extensive description of the platform can be found in the User Manual. However, for purposes of understanding the API, a brief summary is provided here.

The Woo-platform facilitates publication of government documents, in compliance with the Dutch "Wet Open Overheid" (Woo) legislation.
It allows government bodies to publish documents, manage access rights, and handle requests for information from citizens and other stakeholders.
As part of the Woo, a specific set of document types (or information categories) must be made publicly available proactively, while others can be requested by citizens.

The API is designed specifically to facilitate a publication flow originating from within the government body that owns the documents. It is not intended to be used directly by citizens or external parties.

The API aims to faithfully allow governemt bodies to replicate the publication state of a document as it exists within their internal systems.
This includes a rich set of metadata fields, as well as the ability to manage access rights and publication status, grouping documents into cases, and offering preview access to such cases before full publication.

## Tenancy

The platform is designed to be multi-tenant, allowing multiple government bodies to use a single instance of the platform while keeping their data isolated. Each government body is represented as a "tenant" within the system.
Through the authentication mechanism based on mTLS via PKIOverheid certificates, each API request is routed to a specific tenant based on the certificate presented by the client.
While the application instance is shared, each tenant has a separate underlying database schemas and file storages to ensure data isolation.

## API Design Principles

While developing the API, our team has consistently used the metaphor of a "printer" to guide design decisions.
What this means is that we primarily think of publication via the Woo-platform as a one-way street: the system of origin (or "Source System") pushes document and their associated metadata to the platform, which then takes care of making them publicly available.
This metaphor has several implications for the API design:

- The source system does not need to maintain knowledge of the state of the the Woo-platform. It simply sends documents and metadata to the platform, which then takes care of publication.
- The source system always publishes the full state of a publication (or 'dossier'), partial updates lead to inconsistent states. On an implementation level this means: no PATCH operations, and a rich endpoint for both a publication and its associated documents)
- The source system's identifiers are leading; the source system does not have to keep track of IDs assigned to publications or documents by the Platform. Internally, the Woo-platform maps these source system identifiers to its own IDs.
   The internal IDs are not exposed via the API.

Ideally, like a printer, we wanted the platform to send a signal to the source system to notify it of the state of publication (ie: "In progress", "Published", "Failed", etc).
However, our initial stakeholders indicated that their systems are firewalled off, and do not allow incoming connections.
Using Webhooks or similar mechanisms is therefor not an option. Instead, the API will provide REST-based GET-endpoints to retrieve the state of the publication process.

We believe that this design approach ensures a simple and robust integration, placing as little burden as possible on the source system.

### Recovering from errors

Because of the metaphor above, we do not allow corrections. Should a correction be necessary (for example, incorrect metadata), the source system must create a new publication or document with the correct information.
This approach simplifies the API and reduces the risk of inconsistencies.

## Authentication and Security

Digikoppeling REST mandates the use of PKIOverheid certificates for mutual TLS (mTLS) authentication. The API complies with this requirement, ensuring secure communication between the source system and the platform.
In order to access the API, clients must present a valid PKIOverheid certificate during the TLS handshake.
The platform verifies the certificate against the "Staat der Nederlanden" root-certificate, validates that the certificate has not been revoked, and extracts the tenant information from the certificate's attributes.
This means as a client, you do not have to specify which tenant you are - this is handled by the authentication. Within your tenant, multiple organisations can still exist.

## Standards Compliance

The Dutch government mandates that software products it develops comply with a number of standards. The Woo-platform seeks to fully comply with these, where relevant.
The most important standard, forming the foundation set of principles for its design, is Digikoppeling (REST).
Complying with Digikoppeling in turn implies compliance with a large number of additional standards. In addition, we have adopted a significant set of "Apply or Explain".

- Mandatory HTTPS + mTLS, and HSTS
- DNSSec
- OpenAPI Specificatie

We're happy to say that the platform achieves a 100% "Hall of Fame" score on the Internet.nl website test, checking it for compliance with the most common security standards.
Additionally, the platform uses the TOOI standards for enumerating core entities like ministries, to ensure consistent exchange between the Source System and the platform.

### Exception: Grote Bestanden

Digikoppeling also offers a standard for facilitating the transfer of large files between systems, unburderning the regular API from this responsibility. We have opted not to use this, given the following considerations:

- Increased Complexity: Grote Bestanden adds significant complexity to the API design and implementation, making it harder for developers to use.
- Performance: The Woo-platform already handles large files efficiently through its existing HTTP layer.
- Limited Added Value: Grote Bestanden was designed as an add-on for older, non-HTTP based, connections between platforms.

Specifically for REST, Logius states Grote Bestanden is optional, and should only be implemented when a clear need for it is identified. We believe our current approach strikes the right balance between functionality, simplicity, and performance.
The "Upload" endpoint for documents in the Woo-platform API handles large files efficiently using standard HTTP mechanisms. It supports chunked uploads, ensuring that even very large documents can be uploaded reliably already.
Our API does take some inspiration from Grote Bestanden in that we too separate metadata from the actual delivery of the file.
