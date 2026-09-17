# Introduction

<img src=Images/Woo_architecture.jpg  alt="A diagram describing the architectural components of the Woo Publication Platform, consisting a public-facing website and an administrative backend, coupled by data stores and queues"/>

## Architecture Vision

The vision of the architecture for the Woo Publication Platform consists of three main principles:

### Open source

The Woo Publication Platform is developed as an open-source project, with every aspect built on top of
open-source foundations. All components of the platform rely on open-source services and components. Essential
components such as the Document Storage are designed with flexible abstractions. This means it can be deployed on a
locally available filesystem, AWS S3 bucket, SFTP, WebDAV, or even Google Cloud Storage without requiring any
modifications to the code.

### Scalability

The platform is scalable to any quantity and number of documents, as the processing of these documents is carried out by
linearly scalable workers. Additionally, the flexible storage of documents and data in the Document Storage and
PostgreSQL database is also linearly scalable.

### Robustness

The emphasis on robustness is evident in the redundancy and clustering of Databases, RabbitMQ, Elasticsearch, Document
Storage, and even Redis cache. This redundancy entails duplication and scalability, allowing for additional machines to
be deployed if necessary, thereby preventing any single point of failure. Furthermore, through the deep integration of
Elasticsearch, we enhance the search capabilities to a higher level.

## Components

### Document storage

The document storage serves as a central hub for source documents and thumbnails uploaded to the platform.
It exclusively accommodates released documents of the `.jpg`, `.docx`, or `.pdf` formats and its derivatives. The
document derivatives are used to make the presentation of the document on the website easier. This setup is housed
within a conventional file storage cluster, connected to various processing machines and uses
[PHP library Flysystem](https://github.com/thephpleague/flysystem) as a filesystem. The functional setup can be compared to Dropbox, Amazon S3 or Google Drive.

### PostgreSQL Database

The PostgreSQL database serves as a repository for the coherence and structure between dossiers, documents and files, as
well as the coherence between documents themselves and the user information within the portal. PostgreSQL is an
industry standard, more information can be found [on the postgres website](https://www.postgresql.org/docs/current/index.html).

### RabbitMQ

RabbitMQ serves as the document processing queue for tasks assigned to the workers. Each document triggers the
generation of a single task. It only functions as a queue server from which tasks can be taken.

### Redis Cache

The Redis cache serves as the caching layer for processing page information. This accelerates processing by avoiding the
need to fetch the same information from the same documents repeatedly.

### Elasticsearch

Elasticsearch serves as a search engine, housing indexed data from every document, which includes both full-text search
and metadata. It comprises two distinct indexes: one for writing and another for reading.

### Workers

Workers are automated document processors tasked with treating PDFs as images and extracting raw text using open-source
OCR packages like Tika and Tesseract, thus improving document searchability. The document files are segmented into
pages, and from these pages, the text is extracted. During this process, we examine whether there is any text not
delivered in a recognizable format. In cases where text recognition fails, workers resort to OCR (Optical Character
Recognition). OCR operates by analyzing shapes, then letters, and finally words.
For office documents, the extraction of text is relatively straightforward, eliminating the need for OCR. In our
workflow, each worker is capable of handling any task. These workers start with a blank slate, receiving tasks from
RabbitMQ. This decentralized approach ensures efficient task allocation and completion.
