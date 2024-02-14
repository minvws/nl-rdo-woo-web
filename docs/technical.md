# Woo Platform

## Technical details

### Paths & Classes

#### Path: /src/Command

Holds all commands that can be run on the commandline. Most commands are used for development purposes.

#### Path: /src/Controller

Holds all controllers that are used for the web interface.

#### Path: /src/Entity

Holds all entities that are used for the database.

#### Path: /src/Form

Holds all forms that are used for the web interface / validation.

#### Path: /src/Respository

Holds all repositories that are used for the database.

#### Path: /src/Message

Holds all messages that are used for the rabbitMQ queue. Each type of message corresponds to a ingest task.
There is for instance a PDF ingester (ie IngestPdfMessage) and an MetadataOnly ingester (ie IngestMetadataOnlyMessage).

These messages are send through the rabbitMQ queue and are consumed by the worker.

#### Path: /src/MessageHandler

Holds all message handlers that are used for the rabbitMQ queue. Each type of message handler corresponds
to a ingester. Each ingester will take a message from the queue and process it. For instance, the PDF ingester
will take a IngestPdfMessage and will extract the text from the PDF for the given page it needs to ingest, and
store it in the database and elasticsearch. The bulk of the work is done in the `App\Service\Worker` classes.

#### Path: /src/Service

Holds all services that are used for the application. These services are used by the controllers and
message handlers.

#### Class: /src/Service/Ingest/IngestService

The main class to call for ingesting a document. This class will check all handlers (found in
`App/Service/Ingest/Handler`), and see if there is a candidate that can handle the given document (based on
mimetype). If so, that handler will be used to prepare the document and send ingester messages to the queue.
For instance, a metadata only handler will generate a single ingest message, but a pdf file will generate a ingest
message per page.

#### Path: /src/Service/Ingest/Handler

These classes are used to prepare a document for ingesting.

#### Path: /src/Service/Ingest/Processor

These classes are used to process given documents for ingesting. In some cases, it is needed to fetch some
metadata like pagecount etc. These processor classes will fetch this information from the
files. Note that these are NOT the same as the processors found in `\App\Service\Worker` even though in theory
the can overlap in functionality.

#### Path: /src/Service/Worker

These classes are used to process ingester messages. These processors are called from the worker handlers
(`\App\MessageHandler\Ingest*Handler`) and provide the meat of processing of a given document.

#### Class: /src/Service/Elastic/IndexService

This class is used to manage the elasticsearch index. It consists of create/delete/alias and is used mostly
in the command line tools.

#### Class: /src/Service/Elastic/ElasticClientFactory

This class can generate a complete configured elasticsearch client.

#### Class: /src/Service/Elastic/ElasticService

The main elastic service class that communicates with elasticsearch. It is used to index documents. Retrieval of documents
is done through the `searchService`.

#### Class: /src/Service/TikaServiceFactory

This class can generate a complete configured tika client.

### Class: /src/Service/Encryption/EncryptionService

This class is used to encrypt and decrypt data at rest. It is used for database values like mfa tokens and mfa
recovery codes. It uses libsodium for encryption and decryption.

### Path: /src/Service/Storage

These classes are used to abstract the storage of files. Storage can be local, but also remote (like S3). There is
functionality to copy a file to local storage if needed, for instance when processing locally on a worker system.

### Class: /src/Service/InventoryService

This class is used to manage the inventory of documents. It processes the inventory spreadsheet of a dossier, and
adds the documents to the database.

### Class: /src/Service/UserService

This class manages users in the system. Mostly creating users, and resetting credentials (password, 2fa)

### Class: /src/Service/DocumentService

Document management. Ingests a document from a PDF or ZIP file, or removes a document from the database.

### Class: /src/Service/DossierService

Creates or mutates dossiers. This will add document entries if a inventory file is given as well.

## Adding more ingesters / file formats

To add more ingesters, you need to follow these steps:

1. Create an ingester handler in `\App\Service\Ingest\Handler` that extends the `BaseHandler` and implements `Handler`. You
need to define two methods: `canHandle` and `handle`. The `canHandle` method will check if the given document can be
handled (for instance, based on mimetype). The `handle` method will prepare the document for ingesting and will send
ingest messages to the queue for processing.

   > It's important to implement the `Handler` interface. This will automatically add the handler to the ingester.

2. Create an ingester message in `\App\Message\Ingest*Message`. This message will be sent to the queue by your new handler
and will be consumed by the worker. This message will contain all the information needed to ingest the document, which
often is nothing more than a document uuid.
3. Create a message handler in `\App\MessageHandler\Ingest*Handler`. This handler will consume the ingest message and
will often do nothing more than calling a `Processor` from `\App\Service\Worker\*Processor`.
4. Create a processor in `\App\Service\Worker\*Processor`. This will do the actual processing of the ingest message.
5. Make sure the message bus is configured to handle your new ingester message. This is done in `config/packages/messenger.yaml`
where you need to add your message to the `routing` section (currently: `async` is used).
6. When restarting the workers, it will automatically pick up the new ingester and start processing documents.

## Prometheus exporters

All workers can/will expose their stats through prometheus. There is a rudimentary Prometheus exporter for the worker stats. This can be called
from the `\prometheus` endpoint and currently consists of each worker step and average duration.
